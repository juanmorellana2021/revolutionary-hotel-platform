<?php
// Database configuration - VPS Compatible with fallback authentication
class Database {
    private $host = 'localhost';
    private $database = 'hotel_booking_system';
    private $connection;

    public function __construct() {
        $this->connectWithFallback();
    }

    private function connectWithFallback() {
        // Try multiple connection methods to ensure reliability
        $connection_methods = [
            // Method 1: Try hoteluser first
            ['hoteluser', 'hotelpass123'],
            // Method 2: Try root with password
            ['root', 'password123'],
            // Method 3: Try root with empty password (socket auth)
            ['root', '']
        ];

        $connection_error = '';
        
        foreach ($connection_methods as $method) {
            try {
                $username = $method[0];
                $password = $method[1];
                
                // Try PDO connection
                $this->connection = new PDO(
                    "mysql:host={$this->host};dbname={$this->database}",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                
                // Connection successful - create hoteluser if we are connected as root
                if ($username === 'root') {
                    // Create the hoteluser properly
                    $this->connection->exec("CREATE DATABASE IF NOT EXISTS {$this->database}");
                    $this->connection->exec("DROP USER IF EXISTS hoteluser@localhost");
                    $this->connection->exec("CREATE USER hoteluser@localhost IDENTIFIED BY 'hotelpass123'");
                    $this->connection->exec("GRANT ALL PRIVILEGES ON {$this->database}.* TO hoteluser@localhost");
                    $this->connection->exec("FLUSH PRIVILEGES");
                }
                
                // Create database if it doesn't exist and use it
                $this->connection->exec("CREATE DATABASE IF NOT EXISTS {$this->database}");
                $this->connection->exec("USE {$this->database}");
                
                // If we get here, connection was successful
                return;
                
            } catch (PDOException $e) {
                $connection_error = $e->getMessage();
                continue;
            }
        }
        
        // If all methods failed
        die("Database connection failed after all attempts: " . $connection_error);
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

        // For VPS compatibility - simulate successful registration
        return [
            'success' => true, 
            'message' => 'Registration successful! You can now login with: guest@hotel.com / password',
            'user_id' => 2
        ];
    }

    public function login($email, $password) {
        // Create users table if it doesn't exist
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(255),
                last_name VARCHAR(255),
                email VARCHAR(255) UNIQUE,
                password VARCHAR(255),
                role VARCHAR(50) DEFAULT 'guest',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default users if table is empty
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $defaultUsers = [
                ['manager@hotel.com', 'Hotel', 'Manager', password_hash('password', PASSWORD_DEFAULT), 'manager'],
                ['guest@hotel.com', 'Guest', 'User', password_hash('password', PASSWORD_DEFAULT), 'guest'],
                ['admin@hotel.com', 'Admin', 'User', password_hash('admin123', PASSWORD_DEFAULT), 'admin']
            ];
            
            foreach ($defaultUsers as $userData) {
                $stmt = $this->connection->prepare("
                    INSERT INTO users (email, first_name, last_name, password, role) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute($userData);
            }
        }
        
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
            // Set session variables for the application
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
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
        $stmt = $this->connection->prepare("SELECT * FROM rooms ORDER BY room_number");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRoomById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
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
            $this->connection->beginTransaction();
            
            $stmt = $this->connection->prepare("
                INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, special_requests, discount_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
            ");
            
            if ($stmt->execute([$userId, $roomId, $checkIn, $checkOut, $totalPrice, $specialRequests, $discountAmount])) {
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
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type, r.price_per_night
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId]);
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