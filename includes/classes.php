<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'hotel_booking_system';
    private $connection;

    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}

// User class for authentication
class User {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function register($firstName, $lastName, $email, $password, $termsAccepted = false, $termsVersion = '1.0') {
        // Check if terms are accepted
        if (!$termsAccepted) {
            return ['success' => false, 'message' => 'You must accept the terms and conditions to register'];
        }

        // Check if user already exists
        $stmt = $this->connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'User already exists with this email'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user with terms acceptance
        $stmt = $this->connection->prepare("
            INSERT INTO users (first_name, last_name, email, password, terms_accepted, terms_accepted_at, terms_version) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword, 1, $termsVersion])) {
            $userId = $this->connection->lastInsertId();
            return [
                'success' => true, 
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    public function login($email, $password) {
        $stmt = $this->connection->prepare("
            SELECT id, first_name, last_name, email, password, role 
            FROM users WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'No user found with this email'];
        }

        $user = $stmt->fetch();
        
        if (password_verify($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return [
                'success' => true, 
                'message' => 'Login successful',
                'user' => $user
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid password'];
        }
    }

    public function getUserById($id) {
        $stmt = $this->connection->prepare("
            SELECT id, first_name, last_name, email, created_at 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function isManager($userId) {
        $stmt = $this->connection->prepare("
            SELECT role FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user && ($user['role'] === 'manager' || $user['role'] === 'admin');
    }
}

// Room class for room management
class Room {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function getAllRooms() {
        $hotelId = $_SESSION['current_hotel_id'] ?? 1;
        $stmt = $this->connection->prepare("SELECT * FROM rooms WHERE hotel_id = ? ORDER BY room_number");
        $stmt->execute([$hotelId]);
        return $stmt->fetchAll();
    }

    public function getRoomById($id) {
        $hotelId = $_SESSION['current_hotel_id'] ?? 1;
        $stmt = $this->connection->prepare("SELECT * FROM rooms WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$id, $hotelId]);
        return $stmt->fetch();
    }

    public function getAvailableRooms($checkIn, $checkOut) {
        $stmt = $this->connection->prepare("
            SELECT r.* FROM rooms r
            WHERE r.id NOT IN (
                SELECT DISTINCT b.room_id FROM bookings b
                WHERE b.status != 'cancelled'
                AND ((b.check_in_date <= ? AND b.check_out_date > ?)
                OR (b.check_in_date < ? AND b.check_out_date >= ?)
                OR (b.check_in_date >= ? AND b.check_out_date <= ?))
            )
            ORDER BY r.room_number
        ");
        $stmt->execute([$checkIn, $checkIn, $checkOut, $checkOut, $checkIn, $checkOut]);
        return $stmt->fetchAll();
    }

    public function addRoom($roomNumber, $roomType, $price, $maxOccupancy, $amenities = '') {
        // Check if room number already exists
        $stmt = $this->connection->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $stmt->execute([$roomNumber]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Room number already exists'];
        }

        $stmt = $this->connection->prepare("
            INSERT INTO rooms (room_number, room_type, price, max_occupancy, amenities, is_available) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        if ($stmt->execute([$roomNumber, $roomType, $price, $maxOccupancy, $amenities])) {
            return [
                'success' => true,
                'message' => 'Room added successfully',
                'room_id' => $this->connection->lastInsertId()
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add room'];
        }
    }

    public function updateRoom($roomId, $roomType, $price, $maxOccupancy, $amenities, $description = '', $features = '') {
        $stmt = $this->connection->prepare("
            UPDATE rooms SET 
            room_type = ?, price = ?, max_occupancy = ?, amenities = ?,
            description = ?, features = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$roomType, $price, $maxOccupancy, $amenities, $description, $features, $roomId])) {
            return ['success' => true, 'message' => 'Room updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update room'];
        }
    }

    public function deleteRoom($roomId) {
        // Check if room has any bookings
        $stmt = $this->connection->prepare("SELECT id FROM bookings WHERE room_id = ? AND status != 'cancelled'");
        $stmt->execute([$roomId]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Cannot delete room with active bookings'];
        }

        $stmt = $this->connection->prepare("DELETE FROM rooms WHERE id = ?");
        
        if ($stmt->execute([$roomId])) {
            return ['success' => true, 'message' => 'Room deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete room'];
        }
    }
}

// Booking class for reservation management
class Booking {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function createBooking($userId, $roomId, $checkIn, $checkOut, $totalPrice, $specialRequests = '', $discountAmount = 0) {
        try {
            $hotelId = $_SESSION['current_hotel_id'] ?? 1;
            $this->connection->beginTransaction();
            
            $stmt = $this->connection->prepare("
                INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, special_requests, discount_amount, status, hotel_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
            ");
            
            if ($stmt->execute([$userId, $roomId, $checkIn, $checkOut, $totalPrice, $specialRequests, $discountAmount, $hotelId])) {
                $bookingId = $this->connection->lastInsertId();
                
                // Award HotelCoins and Loyalty Points
                require_once 'hotelcoin_manager.php';
                require_once 'loyalty_manager.php';
                
                $hotelCoinManager = new HotelCoinManager();
                $loyaltyManager = new LoyaltyManager();
                
                // Calculate actual amount paid (after discount)
                $paidAmount = $totalPrice - $discountAmount;
                
                // Award HotelCoins (1% of booking value in coins)
                $hotelCoinManager->awardCoinsForBooking($userId, $paidAmount, 'USD', $bookingId);
                
                // Award Loyalty Points (10 points per dollar with tier multiplier)
                $loyaltyManager->awardPointsForBooking($userId, $paidAmount, 'USD', $bookingId);
                
                $this->connection->commit();
                
                return [
                    'success' => true,
                    'message' => 'Booking created successfully! HotelCoins and Loyalty Points awarded.',
                    'booking_id' => $bookingId
                ];
            } else {
                $this->connection->rollBack();
                return ['success' => false, 'message' => 'Failed to create booking'];
            }
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Error creating booking with rewards: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create booking: ' . $e->getMessage()];
        }
    }

    public function getUserBookings($userId) {
        $hotelId = $_SESSION['current_hotel_id'] ?? 1;
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type, r.price_per_night
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            WHERE b.user_id = ? AND b.hotel_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId, $hotelId]);
        return $stmt->fetchAll();
    }

    public function getAllBookings() {
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type, u.first_name, u.last_name, u.email
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>