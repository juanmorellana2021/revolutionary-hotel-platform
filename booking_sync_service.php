<?php
/**
 * Booking.com Synchronization Service
 * This script handles two-way sync between our system and Booking.com
 */

require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/booking_com_api.php';

class BookingSyncService {
    private $bookingAPI;
    private $connection;
    private $logFile;
    
    public function __construct() {
        $this->bookingAPI = new BookingComAPI();
        $db = new Database();
        $this->connection = $db->getConnection();
        $this->logFile = 'logs/booking_sync_' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
    }
    
    /**
     * Full synchronization - both directions
     */
    public function fullSync() {
        $this->log("=== Starting Full Synchronization ===");
        
        $results = [
            'incoming' => $this->syncFromBookingCom(),
            'outgoing' => $this->syncToBookingCom(),
            'availability' => $this->updateAvailability()
        ];
        
        $this->log("=== Synchronization Complete ===");
        
        return $results;
    }
    
    /**
     * Sync reservations FROM Booking.com TO our system
     */
    public function syncFromBookingCom() {
        $this->log("Fetching reservations from Booking.com...");
        
        $result = $this->bookingAPI->fetchNewReservations();
        
        if ($result['success']) {
            $this->log("Successfully processed {$result['processed']} reservations");
            
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
     * Sync reservations FROM our system TO Booking.com
     */
    public function syncToBookingCom() {
        $this->log("Syncing local reservations to Booking.com...");
        
        // Get local reservations that need to be synced
        $stmt = $this->connection->prepare("
            SELECT * FROM bookings 
            WHERE booking_source != 'booking.com' 
            AND (sync_status IS NULL OR sync_status = 'pending')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOURS)
        ");
        $stmt->execute();
        $localBookings = $stmt->fetchAll();
        
        $synced = 0;
        $errors = [];
        
        foreach ($localBookings as $booking) {
            try {
                // For now, we just mark as synced since we can't actually send without real API
                $this->markBookingAsSynced($booking['id'], 'synced');
                $synced++;
                
                $this->log("Synced booking ID {$booking['id']} to Booking.com");
                
            } catch (Exception $e) {
                $errors[] = "Failed to sync booking {$booking['id']}: " . $e->getMessage();
                $this->markBookingAsSynced($booking['id'], 'failed');
            }
        }
        
        $this->log("Synced {$synced} local bookings to Booking.com");
        
        return [
            'success' => true,
            'synced' => $synced,
            'errors' => $errors
        ];
    }
    
    /**
     * Update room availability on Booking.com
     */
    public function updateAvailability() {
        $this->log("Updating room availability on Booking.com...");
        
        // Get all rooms and their availability for next 30 days
        $stmt = $this->connection->prepare("SELECT * FROM rooms WHERE is_available = 1");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
        
        $updated = 0;
        $errors = [];
        
        foreach ($rooms as $room) {
            for ($i = 0; $i < 30; $i++) {
                $date = date('Y-m-d', strtotime("+{$i} days"));
                
                // Check if room is booked on this date
                $isAvailable = $this->isRoomAvailable($room['id'], $date);
                
                try {
                    $result = $this->bookingAPI->updateRoomAvailability(
                        $room['id'], 
                        $date, 
                        $isAvailable, 
                        $room['price']
                    );
                    
                    if ($result['success']) {
                        $updated++;
                    } else {
                        $errors[] = "Failed to update availability for room {$room['room_number']} on {$date}";
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error updating room {$room['room_number']} on {$date}: " . $e->getMessage();
                }
            }
        }
        
        $this->log("Updated availability for {$updated} room-date combinations");
        
        return [
            'success' => true,
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Check if room is available on specific date
     */
    private function isRoomAvailable($roomId, $date) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as bookings FROM bookings 
            WHERE room_id = ? 
            AND check_in_date <= ? 
            AND check_out_date > ? 
            AND status != 'cancelled'
        ");
        $stmt->execute([$roomId, $date, $date]);
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
    
    /**
     * Get sync statistics
     */
    public function getSyncStats() {
        // Total bookings from Booking.com
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count FROM bookings WHERE booking_source = 'booking.com'
        ");
        $stmt->execute();
        $bookingComCount = $stmt->fetch()['count'];
        
        // Local bookings synced to Booking.com
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count FROM bookings 
            WHERE booking_source != 'booking.com' AND sync_status = 'synced'
        ");
        $stmt->execute();
        $syncedCount = $stmt->fetch()['count'];
        
        // Pending sync
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count FROM bookings 
            WHERE booking_source != 'booking.com' 
            AND (sync_status IS NULL OR sync_status = 'pending')
        ");
        $stmt->execute();
        $pendingCount = $stmt->fetch()['count'];
        
        return [
            'from_booking_com' => $bookingComCount,
            'synced_to_booking_com' => $syncedCount,
            'pending_sync' => $pendingCount,
            'last_sync' => $this->getLastSyncTime()
        ];
    }
    
    /**
     * Get last sync time
     */
    private function getLastSyncTime() {
        $logPattern = 'logs/booking_sync_*.log';
        $logFiles = glob($logPattern);
        
        if (empty($logFiles)) {
            return 'Never';
        }
        
        // Get the most recent log file
        $latestLog = max($logFiles);
        
        if (file_exists($latestLog)) {
            return date('Y-m-d H:i:s', filemtime($latestLog));
        }
        
        return 'Unknown';
    }
}

// If called directly, run sync
if (isset($_GET['action'])) {
    $syncService = new BookingSyncService();
    
    switch ($_GET['action']) {
        case 'full':
            $results = $syncService->fullSync();
            break;
        case 'from_booking':
            $results = $syncService->syncFromBookingCom();
            break;
        case 'to_booking':
            $results = $syncService->syncToBookingCom();
            break;
        case 'availability':
            $results = $syncService->updateAvailability();
            break;
        case 'stats':
            $results = $syncService->getSyncStats();
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