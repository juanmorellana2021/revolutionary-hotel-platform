<?php
/**
 * Zodomus Synchronization Service
 * This handles two-way sync between your hotel system and all OTAs via Zodomus
 * Supports: Airbnb, Booking.com, Expedia, VRBO, and more
 */

require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/zodomus_api.php';

class ZodomusSyncService {
    private $zodomusAPI;
    private $connection;
    private $logFile;
    
    public function __construct() {
        $this->zodomusAPI = new ZodomusAPI();
        $db = new Database();
        $this->connection = $db->getConnection();
        $this->logFile = 'logs/zodomus_sync_' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
    }
    
    /**
     * Full synchronization - all channels, all directions
     */
    public function fullSync() {
        $this->log("=== Starting Full Zodomus Synchronization ===");
        
        $results = [
            'incoming_reservations' => $this->syncIncomingReservations(),
            'outgoing_reservations' => $this->syncOutgoingReservations(),
            'availability_update' => $this->syncAvailability(),
            'rates_update' => $this->syncRates()
        ];
        
        $this->log("=== Synchronization Complete ===");
        
        return $results;
    }
    
    /**
     * Sync reservations FROM Airbnb/Booking.com (via Zodomus) TO our system
     */
    public function syncIncomingReservations() {
        $this->log("Fetching reservations from all channels via Zodomus...");
        
        $result = $this->zodomusAPI->fetchReservations();
        
        if ($result['success']) {
            $this->log("Successfully processed {$result['processed']} reservations from all channels");
            
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->log("ERROR: " . $error);
                }
            }
        } else {
            $this->log("ERROR: Failed to fetch reservations - " . $result['message']);
        }
        
        return $result;
    }
    
    /**
     * Sync reservations FROM our system TO Zodomus (which pushes to all channels)
     */
    public function syncOutgoingReservations() {
        $this->log("Syncing local reservations to Zodomus...");
        
        // Get local reservations that need to be synced
        $stmt = $this->connection->prepare("
            SELECT * FROM bookings 
            WHERE booking_source = 'direct'
            AND (sync_status IS NULL OR sync_status = 'pending')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOURS)
            AND status != 'cancelled'
        ");
        $stmt->execute();
        $localBookings = $stmt->fetchAll();
        
        $synced = 0;
        $errors = [];
        
        foreach ($localBookings as $booking) {
            try {
                $result = $this->zodomusAPI->pushBooking($booking['id']);
                
                if ($result['success']) {
                    $synced++;
                    $this->log("Synced booking ID {$booking['id']} to Zodomus");
                } else {
                    $errors[] = "Failed to sync booking {$booking['id']}: " . $result['message'];
                    $this->markBookingAsSynced($booking['id'], 'failed');
                }
                
            } catch (Exception $e) {
                $errors[] = "Failed to sync booking {$booking['id']}: " . $e->getMessage();
                $this->markBookingAsSynced($booking['id'], 'failed');
            }
        }
        
        $this->log("Synced {$synced} local bookings to Zodomus (all channels)");
        
        return [
            'success' => true,
            'synced' => $synced,
            'errors' => $errors
        ];
    }
    
    /**
     * Update room availability across all channels
     */
    public function syncAvailability() {
        $this->log("Updating room availability on all channels via Zodomus...");
        
        // Get all active rooms
        $stmt = $this->connection->prepare("SELECT * FROM rooms WHERE is_available = 1");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
        
        $updated = 0;
        $errors = [];
        
        foreach ($rooms as $room) {
            // Update availability for next 90 days
            for ($i = 0; $i < 90; $i++) {
                $date = date('Y-m-d', strtotime("+{$i} days"));
                
                // Check if room is booked on this date
                $isAvailable = $this->isRoomAvailable($room['id'], $date);
                
                try {
                    $result = $this->zodomusAPI->updateAvailability(
                        $room['id'], 
                        $date, 
                        $date, 
                        $isAvailable
                    );
                    
                    if ($result['success']) {
                        $updated++;
                    } else {
                        $errors[] = "Failed to update room {$room['id']} for {$date}: " . $result['message'];
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error updating room {$room['id']} for {$date}: " . $e->getMessage();
                }
                
                // Sleep briefly to avoid rate limiting
                usleep(100000); // 0.1 seconds
            }
        }
        
        $this->log("Updated {$updated} availability slots across all channels");
        
        return [
            'success' => true,
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Update room rates across all channels
     */
    public function syncRates() {
        $this->log("Updating room rates on all channels via Zodomus...");
        
        // Get all active rooms with current prices
        $stmt = $this->connection->prepare("SELECT * FROM rooms WHERE is_available = 1");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
        
        $updated = 0;
        $errors = [];
        
        foreach ($rooms as $room) {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d', strtotime('+90 days'));
            
            try {
                $result = $this->zodomusAPI->updateRates(
                    $room['id'],
                    $fromDate,
                    $toDate,
                    $room['price_per_night']
                );
                
                if ($result['success']) {
                    $updated++;
                    $this->log("Updated rates for room {$room['id']}: \${$room['price_per_night']}/night");
                } else {
                    $errors[] = "Failed to update rates for room {$room['id']}: " . $result['message'];
                }
                
            } catch (Exception $e) {
                $errors[] = "Error updating rates for room {$room['id']}: " . $e->getMessage();
            }
        }
        
        $this->log("Updated {$updated} room rates across all channels");
        
        return [
            'success' => true,
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Check if room is available on a specific date
     */
    private function isRoomAvailable($roomId, $date) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as bookings FROM bookings
            WHERE room_id = ?
            AND ? BETWEEN check_in_date AND check_out_date
            AND status NOT IN ('cancelled', 'no-show')
        ");
        $stmt->execute([$roomId, $date]);
        $result = $stmt->fetch();
        
        return $result['bookings'] == 0;
    }
    
    /**
     * Mark booking as synced
     */
    private function markBookingAsSynced($bookingId, $status) {
        $stmt = $this->connection->prepare("
            UPDATE bookings SET 
                sync_status = ?, 
                synced_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$status, $bookingId]);
    }
    
    /**
     * Cancel a reservation across all channels
     */
    public function cancelReservation($bookingReference, $reason = '') {
        $this->log("Cancelling reservation {$bookingReference} across all channels...");
        
        $result = $this->zodomusAPI->cancelReservation($bookingReference, $reason);
        
        if ($result['success']) {
            $this->log("Successfully cancelled reservation {$bookingReference}");
        } else {
            $this->log("ERROR: Failed to cancel reservation - " . $result['message']);
        }
        
        return $result;
    }
    
    /**
     * Get sync statistics
     */
    public function getSyncStats() {
        $stats = $this->zodomusAPI->getSyncStats();
        $stats['last_sync'] = $this->getLastSyncTime();
        
        return $stats;
    }
    
    /**
     * Get last sync time
     */
    private function getLastSyncTime() {
        $logPattern = 'logs/zodomus_sync_*.log';
        $logFiles = glob($logPattern);
        
        if (empty($logFiles)) {
            return 'Never';
        }
        
        $latestLog = max($logFiles);
        
        if (file_exists($latestLog)) {
            return date('Y-m-d H:i:s', filemtime($latestLog));
        }
        
        return 'Unknown';
    }
    
    /**
     * Log sync activities
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Also output to screen if running via browser
        if (isset($_SERVER['HTTP_HOST'])) {
            echo "<p>{$logMessage}</p>";
        }
    }
}

// If called directly, run sync
if (isset($_GET['action'])) {
    $syncService = new ZodomusSyncService();
    
    switch ($_GET['action']) {
        case 'full':
            $results = $syncService->fullSync();
            break;
        case 'incoming':
            $results = $syncService->syncIncomingReservations();
            break;
        case 'outgoing':
            $results = $syncService->syncOutgoingReservations();
            break;
        case 'availability':
            $results = $syncService->syncAvailability();
            break;
        case 'rates':
            $results = $syncService->syncRates();
            break;
        case 'stats':
            $results = $syncService->getSyncStats();
            break;
        case 'cancel':
            $bookingRef = $_GET['booking_ref'] ?? '';
            $reason = $_GET['reason'] ?? '';
            $results = $syncService->cancelReservation($bookingRef, $reason);
            break;
        default:
            $results = ['error' => 'Invalid action'];
    }
    
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode($results);
    } else {
        echo "<pre>" . print_r($results, true) . "</pre>";
    }
}
?>
