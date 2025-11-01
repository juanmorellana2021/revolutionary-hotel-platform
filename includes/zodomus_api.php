<?php
/**
 * Zodomus.com API Integration Handler
 * Zodomus is a channel manager that syncs bookings and availability between your hotel
 * and multiple OTAs (Online Travel Agencies) like Airbnb, Booking.com, Expedia, etc.
 * 
 * Documentation: https://www.zodomus.com/api-documentation
 * 
 * Features:
 * - Two-way sync with Airbnb, Booking.com, and other OTAs
 * - Automatic calendar updates
 * - Rate synchronization
 * - Reservation management
 */

class ZodomusAPI {
    private $apiUrl;
    private $apiUser;
    private $apiPassword;
    private $apiPasswordCC;
    private $connection;
    private $logFile;
    
    public function __construct($apiUser = null, $apiPassword = null, $apiPasswordCC = null) {
        // Zodomus API endpoint (Test environment)
        $this->apiUrl = 'https://app.zodomus.com/api/v1';
        
        // Load credentials from database or use provided ones
        if ($apiUser && $apiPassword) {
            $this->apiUser = $apiUser;
            $this->apiPassword = $apiPassword;
            $this->apiPasswordCC = $apiPasswordCC;
        } else {
            $this->loadCredentials();
        }
        
        $db = new Database();
        $this->connection = $db->getConnection();
        
        // Setup logging
        $this->logFile = 'logs/zodomus_sync_' . date('Y-m-d') . '.log';
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
    }
    
    /**
     * Load API credentials from database
     */
    private function loadCredentials() {
        try {
            $stmt = $this->connection->prepare("
                SELECT config_value FROM api_config 
                WHERE provider = 'zodomus' AND config_key = ?
            ");
            
            $stmt->execute(['api_user']);
            $userResult = $stmt->fetch();
            $this->apiUser = $userResult['config_value'] ?? '';
            
            $stmt->execute(['api_password']);
            $passResult = $stmt->fetch();
            $this->apiPassword = $passResult['config_value'] ?? '';
            
            $stmt->execute(['api_password_cc']);
            $ccResult = $stmt->fetch();
            $this->apiPasswordCC = $ccResult['config_value'] ?? '';
            
        } catch (Exception $e) {
            $this->log("Error loading credentials: " . $e->getMessage());
        }
    }
    
    /**
     * Save API credentials to database
     */
    public function saveCredentials($apiUser, $apiPassword, $apiPasswordCC = '') {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO api_config (provider, config_key, config_value, encrypted)
                VALUES ('zodomus', ?, ?, FALSE)
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            
            $stmt->execute(['api_user', $apiUser]);
            $stmt->execute(['api_password', $apiPassword]);
            if ($apiPasswordCC) {
                $stmt->execute(['api_password_cc', $apiPasswordCC]);
            }
            
            $this->apiUser = $apiUser;
            $this->apiPassword = $apiPassword;
            $this->apiPasswordCC = $apiPasswordCC;
            
            return ['success' => true, 'message' => 'Credentials saved successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            // Test with a simple API call - get property list
            $response = $this->makeAPICall('properties', 'GET');
            
            if (isset($response['success']) && $response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connected to Zodomus successfully!',
                    'data' => $response['data']
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to connect. Check your API credentials.'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Fetch new reservations from all channels (Airbnb, Booking.com, etc.)
     */
    public function fetchReservations($fromDate = null, $toDate = null) {
        try {
            if (!$fromDate) $fromDate = date('Y-m-d');
            if (!$toDate) $toDate = date('Y-m-d', strtotime('+90 days'));
            
            $params = [
                'property_id' => $this->propertyId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'status' => 'confirmed' // Get confirmed reservations
            ];
            
            $response = $this->makeAPICall('reservations', 'GET', $params);
            
            if (isset($response['success']) && $response['success']) {
                return $this->processReservations($response['data']);
            }
            
            return ['success' => false, 'message' => 'Failed to fetch reservations'];
            
        } catch (Exception $e) {
            $this->log("Error fetching reservations: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Process and save reservations to local database
     */
    private function processReservations($reservations) {
        $processed = 0;
        $errors = [];
        
        foreach ($reservations as $reservation) {
            try {
                // Check if reservation already exists
                $stmt = $this->connection->prepare("
                    SELECT id FROM bookings WHERE booking_reference = ?
                ");
                $stmt->execute([$reservation['confirmation_code']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing reservation
                    $this->updateLocalReservation($reservation);
                    $this->log("Updated existing reservation: " . $reservation['confirmation_code']);
                } else {
                    // Create new reservation
                    $this->createLocalReservation($reservation);
                    $this->log("Created new reservation: " . $reservation['confirmation_code']);
                }
                
                $processed++;
                
            } catch (Exception $e) {
                $errors[] = "Error processing reservation {$reservation['confirmation_code']}: " . $e->getMessage();
                $this->log("ERROR: " . $errors[count($errors) - 1]);
            }
        }
        
        return [
            'success' => true,
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Create new reservation in local database
     */
    private function createLocalReservation($reservation) {
        // Determine channel source
        $channel = $reservation['channel'] ?? 'unknown';
        
        // Map to our room (you may need to set up room mapping)
        $roomId = $this->mapChannelRoomToLocal($reservation['room_id'] ?? 1);
        
        // Insert booking
        $stmt = $this->connection->prepare("
            INSERT INTO bookings (
                hotel_id, room_id, booking_reference, booking_source,
                check_in_date, check_out_date, guests,
                guest_name, guest_email, guest_phone,
                total_cost, status, sync_status, synced_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'synced', NOW(), NOW())
        ");
        
        $stmt->execute([
            1, // hotel_id (you may have multiple hotels)
            $roomId,
            $reservation['confirmation_code'],
            $channel, // 'airbnb', 'booking.com', 'expedia', etc.
            $reservation['check_in'],
            $reservation['check_out'],
            $reservation['guests'] ?? 2,
            $reservation['guest_name'],
            $reservation['guest_email'] ?? '',
            $reservation['guest_phone'] ?? '',
            $reservation['total_price'],
        ]);
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update existing reservation in local database
     */
    private function updateLocalReservation($reservation) {
        $stmt = $this->connection->prepare("
            UPDATE bookings SET
                check_in_date = ?,
                check_out_date = ?,
                guests = ?,
                guest_name = ?,
                guest_email = ?,
                guest_phone = ?,
                total_cost = ?,
                status = ?,
                sync_status = 'synced',
                synced_at = NOW()
            WHERE booking_reference = ?
        ");
        
        $status = $reservation['status'] ?? 'confirmed';
        if ($status === 'cancelled') {
            $status = 'cancelled';
        }
        
        $stmt->execute([
            $reservation['check_in'],
            $reservation['check_out'],
            $reservation['guests'] ?? 2,
            $reservation['guest_name'],
            $reservation['guest_email'] ?? '',
            $reservation['guest_phone'] ?? '',
            $reservation['total_price'],
            $status,
            $reservation['confirmation_code']
        ]);
    }
    
    /**
     * Update room availability across all channels
     */
    public function updateAvailability($roomId, $fromDate, $toDate, $isAvailable) {
        try {
            $params = [
                'property_id' => $this->propertyId,
                'room_id' => $this->mapLocalRoomToChannel($roomId),
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'available' => $isAvailable ? 1 : 0
            ];
            
            $response = $this->makeAPICall('availability', 'POST', $params);
            
            if (isset($response['success']) && $response['success']) {
                $this->log("Updated availability for room {$roomId} from {$fromDate} to {$toDate}");
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Failed to update availability'];
            
        } catch (Exception $e) {
            $this->log("Error updating availability: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update room rates across all channels
     */
    public function updateRates($roomId, $fromDate, $toDate, $price) {
        try {
            $params = [
                'property_id' => $this->propertyId,
                'room_id' => $this->mapLocalRoomToChannel($roomId),
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'price' => $price
            ];
            
            $response = $this->makeAPICall('rates', 'POST', $params);
            
            if (isset($response['success']) && $response['success']) {
                $this->log("Updated rates for room {$roomId} from {$fromDate} to {$toDate}: \${$price}");
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Failed to update rates'];
            
        } catch (Exception $e) {
            $this->log("Error updating rates: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Send local booking to Zodomus (which will push to all channels)
     */
    public function pushBooking($bookingId) {
        try {
            // Get booking details
            $stmt = $this->connection->prepare("
                SELECT b.*, r.room_number, r.room_type, r.price_per_night
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }
            
            // Prepare booking data for Zodomus
            $params = [
                'property_id' => $this->propertyId,
                'room_id' => $this->mapLocalRoomToChannel($booking['room_id']),
                'confirmation_code' => $booking['booking_reference'],
                'check_in' => $booking['check_in_date'],
                'check_out' => $booking['check_out_date'],
                'guests' => $booking['guests'] ?? 2,
                'guest_name' => $booking['guest_name'],
                'guest_email' => $booking['guest_email'] ?? '',
                'guest_phone' => $booking['guest_phone'] ?? '',
                'total_price' => $booking['total_cost'],
                'status' => 'confirmed'
            ];
            
            $response = $this->makeAPICall('reservations', 'POST', $params);
            
            if (isset($response['success']) && $response['success']) {
                // Mark as synced
                $stmt = $this->connection->prepare("
                    UPDATE bookings SET sync_status = 'synced', synced_at = NOW() WHERE id = ?
                ");
                $stmt->execute([$bookingId]);
                
                $this->log("Pushed booking {$booking['booking_reference']} to Zodomus");
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Failed to push booking'];
            
        } catch (Exception $e) {
            $this->log("Error pushing booking: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cancel reservation across all channels
     */
    public function cancelReservation($bookingReference, $reason = '') {
        try {
            $params = [
                'property_id' => $this->propertyId,
                'confirmation_code' => $bookingReference,
                'status' => 'cancelled',
                'reason' => $reason
            ];
            
            $response = $this->makeAPICall('reservations/' . $bookingReference, 'PUT', $params);
            
            if (isset($response['success']) && $response['success']) {
                // Update local booking
                $stmt = $this->connection->prepare("
                    UPDATE bookings SET status = 'cancelled', sync_status = 'synced' 
                    WHERE booking_reference = ?
                ");
                $stmt->execute([$bookingReference]);
                
                $this->log("Cancelled reservation {$bookingReference}");
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Failed to cancel reservation'];
            
        } catch (Exception $e) {
            $this->log("Error cancelling reservation: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get sync statistics
     */
    public function getSyncStats() {
        $stats = [];
        
        // Count reservations by channel
        $stmt = $this->connection->prepare("
            SELECT booking_source, COUNT(*) as count
            FROM bookings
            WHERE booking_source IN ('airbnb', 'booking.com', 'expedia', 'vrbo')
            GROUP BY booking_source
        ");
        $stmt->execute();
        $channelStats = $stmt->fetchAll();
        
        foreach ($channelStats as $stat) {
            $stats[$stat['booking_source']] = $stat['count'];
        }
        
        // Total synced
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count FROM bookings WHERE sync_status = 'synced'
        ");
        $stmt->execute();
        $stats['total_synced'] = $stmt->fetch()['count'];
        
        // Pending sync
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count FROM bookings 
            WHERE sync_status IS NULL OR sync_status = 'pending'
        ");
        $stmt->execute();
        $stats['pending_sync'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Map local room ID to channel room ID
     */
    private function mapLocalRoomToChannel($localRoomId) {
        // This should be stored in database - for now return same ID
        // In production, you'd have a mapping table
        return $localRoomId;
    }
    
    /**
     * Map channel room ID to local room ID
     */
    private function mapChannelRoomToLocal($channelRoomId) {
        // This should be stored in database - for now return same ID
        // In production, you'd have a mapping table
        return $channelRoomId;
    }
    
    /**
     * Make API call to Zodomus
     */
    private function makeAPICall($endpoint, $method = 'GET', $params = []) {
        $url = $this->apiUrl . '/' . $endpoint;
        
        $ch = curl_init();
        
        // Zodomus uses Basic Authentication with API User and Password
        $auth = base64_encode($this->apiUser . ':' . $this->apiPassword);
        
        // Set headers
        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $data
            ];
        } else {
            return [
                'success' => false,
                'message' => $data['message'] ?? 'API request failed',
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Log messages
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Also log to database
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO sync_logs (sync_type, provider, status, message, created_at)
                VALUES ('incoming', 'zodomus', 'success', ?, NOW())
            ");
            $stmt->execute([$message]);
        } catch (Exception $e) {
            // Silent fail on logging
        }
    }
}
?>
