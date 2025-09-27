<?php
/**
 * Booking.com API Integration Handler
 * This class handles communication with Booking.com API for reservation synchronization
 * 
 * IMPORTANT NOTES:
 * 1. Booking.com API access requires approval and partnership agreement
 * 2. You need to apply through Booking.com Partner Hub
 * 3. API credentials are provided after approval
 * 4. This is a framework - you'll need actual API credentials to make it work
 */

class BookingComAPI {
    private $apiUrl;
    private $apiKey;
    private $hotelId;
    private $secretKey;
    private $connection;
    
    public function __construct() {
        // These would be your actual API credentials from Booking.com
        $this->apiUrl = 'https://distribution-xml.booking.com/xml/bookings';
        $this->apiKey = 'YOUR_API_KEY_HERE'; // Replace with actual API key
        $this->hotelId = 'YOUR_HOTEL_ID_HERE'; // Replace with actual hotel ID
        $this->secretKey = 'YOUR_SECRET_KEY_HERE'; // Replace with actual secret key
        
        $db = new Database();
        $this->connection = $db->getConnection();
    }
    
    /**
     * Fetch new reservations from Booking.com
     */
    public function fetchNewReservations($fromDate = null, $toDate = null) {
        try {
            if (!$fromDate) $fromDate = date('Y-m-d');
            if (!$toDate) $toDate = date('Y-m-d', strtotime('+30 days'));
            
            $params = [
                'hotel_id' => $this->hotelId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'updated_since' => date('Y-m-d H:i:s', strtotime('-1 hour')) // Get updates from last hour
            ];
            
            $response = $this->makeAPICall('reservations', 'GET', $params);
            
            if ($response['success']) {
                return $this->processReservations($response['data']);
            }
            
            return ['success' => false, 'message' => 'Failed to fetch reservations'];
            
        } catch (Exception $e) {
            error_log("Booking.com API Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Send room availability update to Booking.com
     */
    public function updateRoomAvailability($roomId, $date, $available = true, $price = null) {
        try {
            // Map our room ID to Booking.com room type ID
            $bookingRoomType = $this->mapRoomToBookingType($roomId);
            
            $params = [
                'hotel_id' => $this->hotelId,
                'room_type_id' => $bookingRoomType,
                'date' => $date,
                'available' => $available ? 1 : 0,
                'price' => $price
            ];
            
            $response = $this->makeAPICall('availability', 'POST', $params);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Booking.com Availability Update Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Confirm reservation to Booking.com
     */
    public function confirmReservation($bookingReference) {
        try {
            $params = [
                'hotel_id' => $this->hotelId,
                'booking_reference' => $bookingReference,
                'status' => 'confirmed'
            ];
            
            $response = $this->makeAPICall('reservation_status', 'POST', $params);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Booking.com Confirmation Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cancel reservation on Booking.com
     */
    public function cancelReservation($bookingReference, $reason = 'Property request') {
        try {
            $params = [
                'hotel_id' => $this->hotelId,
                'booking_reference' => $bookingReference,
                'status' => 'cancelled',
                'cancellation_reason' => $reason
            ];
            
            $response = $this->makeAPICall('reservation_status', 'POST', $params);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Booking.com Cancellation Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Process reservations from Booking.com and save to local database
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
                $stmt->execute([$reservation['booking_reference']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing reservation
                    $this->updateLocalReservation($reservation);
                } else {
                    // Create new reservation
                    $this->createLocalReservation($reservation);
                }
                
                $processed++;
                
            } catch (Exception $e) {
                $errors[] = "Error processing reservation {$reservation['booking_reference']}: " . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Create local reservation from Booking.com data
     */
    private function createLocalReservation($reservation) {
        // Find or create guest user
        $guestId = $this->findOrCreateGuest($reservation['guest']);
        
        // Map Booking.com room type to our room
        $roomId = $this->mapBookingTypeToRoom($reservation['room_type_id']);
        
        // Calculate total price
        $totalPrice = $reservation['total_price'];
        
        // Insert booking
        $stmt = $this->connection->prepare("
            INSERT INTO bookings (
                user_id, room_id, check_in_date, check_out_date, 
                total_price, booking_reference, status, booking_source,
                guest_name, guest_email, guest_phone, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $guestId,
            $roomId,
            $reservation['check_in'],
            $reservation['check_out'],
            $totalPrice,
            $reservation['booking_reference'],
            'confirmed',
            'booking.com',
            $reservation['guest']['name'],
            $reservation['guest']['email'],
            $reservation['guest']['phone'] ?? ''
        ]);
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update existing local reservation
     */
    private function updateLocalReservation($reservation) {
        $stmt = $this->connection->prepare("
            UPDATE bookings SET 
                status = ?, total_price = ?, updated_at = NOW()
            WHERE booking_reference = ?
        ");
        
        $stmt->execute([
            $reservation['status'],
            $reservation['total_price'],
            $reservation['booking_reference']
        ]);
    }
    
    /**
     * Find or create guest user from Booking.com data
     */
    private function findOrCreateGuest($guestData) {
        // Check if guest exists
        $stmt = $this->connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$guestData['email']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Create new guest
        $userManager = new User();
        $names = explode(' ', $guestData['name'], 2);
        $firstName = $names[0];
        $lastName = isset($names[1]) ? $names[1] : '';
        
        $result = $userManager->register(
            $firstName, 
            $lastName, 
            $guestData['email'], 
            'booking_guest_' . uniqid() // Temporary password
        );
        
        if ($result['success']) {
            return $result['user_id'];
        }
        
        throw new Exception('Failed to create guest user');
    }
    
    /**
     * Map our room ID to Booking.com room type ID
     */
    private function mapRoomToBookingType($roomId) {
        // This mapping should be stored in database
        // For now, return a placeholder
        $stmt = $this->connection->prepare("
            SELECT booking_room_type_id FROM rooms WHERE id = ?
        ");
        $stmt->execute([$roomId]);
        $result = $stmt->fetch();
        
        return $result['booking_room_type_id'] ?? 'default_room_type';
    }
    
    /**
     * Map Booking.com room type ID to our room ID
     */
    private function mapBookingTypeToRoom($bookingRoomType) {
        // This mapping should be stored in database
        $stmt = $this->connection->prepare("
            SELECT id FROM rooms WHERE booking_room_type_id = ? LIMIT 1
        ");
        $stmt->execute([$bookingRoomType]);
        $result = $stmt->fetch();
        
        return $result['id'] ?? 1; // Default to first room if mapping not found
    }
    
    /**
     * Make API call to Booking.com
     */
    private function makeAPICall($endpoint, $method = 'GET', $params = []) {
        // IMPORTANT: This is a simplified example
        // Actual Booking.com API uses XML format and specific authentication
        
        $url = $this->apiUrl . '/' . $endpoint;
        
        // Add authentication
        $params['api_key'] = $this->apiKey;
        $params['timestamp'] = time();
        
        // Generate signature (required by Booking.com)
        $params['signature'] = $this->generateSignature($params);
        
        $curl = curl_init();
        
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Accept: application/xml'
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->arrayToXML($params));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            return [
                'success' => true,
                'data' => $this->parseXMLResponse($response)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'API call failed with HTTP code: ' . $httpCode,
            'response' => $response
        ];
    }
    
    /**
     * Generate signature for API authentication
     */
    private function generateSignature($params) {
        ksort($params);
        $signString = '';
        foreach ($params as $key => $value) {
            if ($key !== 'signature') {
                $signString .= $key . '=' . $value . '&';
            }
        }
        $signString = rtrim($signString, '&');
        
        return hash_hmac('sha256', $signString, $this->secretKey);
    }
    
    /**
     * Convert array to XML for API requests
     */
    private function arrayToXML($array) {
        $xml = new SimpleXMLElement('<request/>');
        
        foreach ($array as $key => $value) {
            $xml->addChild($key, htmlspecialchars($value));
        }
        
        return $xml->asXML();
    }
    
    /**
     * Parse XML response from API
     */
    private function parseXMLResponse($xmlString) {
        try {
            $xml = simplexml_load_string($xmlString);
            return json_decode(json_encode($xml), true);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>