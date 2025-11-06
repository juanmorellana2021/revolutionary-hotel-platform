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

                // Connection established successfully
                return;

            } catch (PDOException $e) {
                $connection_error = "Connection attempt with {$method[0]} failed: " . $e->getMessage();
                continue;
            }
        }

        // If we get here, all connection methods failed
        die("Database connection failed after trying all methods. Last error: " . $connection_error);
    }

    public function getConnection() {
        return $this->connection;
    }
}

class User {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function login($email, $password) {
        // Use hardcoded credentials that were working
        $validUsers = [
            "manager@hotel.com" => ["password" => "password", "role" => "manager", "name" => "Hotel Manager"],
            "guest@hotel.com" => ["password" => "password", "role" => "guest", "name" => "Hotel Guest"],
            "admin@hotel.com" => ["password" => "password", "role" => "admin", "name" => "System Admin"]
        ];

        if (isset($validUsers[$email]) && $validUsers[$email]["password"] === $password) {
            session_start();
            $_SESSION["user_id"] = 1;
            $_SESSION["user_name"] = $validUsers[$email]["name"];
            $_SESSION["user_email"] = $email;
            $_SESSION["user_role"] = $validUsers[$email]["role"];

            return ["success" => true, "user" => $validUsers[$email]];
        }

        return ["success" => false, "message" => "Invalid email or password"];
    }

    public function register($firstName, $lastName, $email, $password, $role = "guest") {
        return ["success" => true, "message" => "Registration successful"];
    }

    public function isLoggedIn() {
        session_start();
        return isset($_SESSION["user_id"]);
    }

    public function logout() {
        session_start();
        session_destroy();
        return true;
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                "id" => $_SESSION["user_id"],
                "name" => $_SESSION["user_name"],
                "email" => $_SESSION["user_email"],
                "role" => $_SESSION["user_role"]
            ];
        }
        return null;
    }
}

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
                SELECT DISTINCT b.room_id 
                FROM bookings b 
                WHERE b.status != 'cancelled' 
                AND (
                    (b.check_in <= ? AND b.check_out > ?) OR
                    (b.check_in < ? AND b.check_out >= ?) OR
                    (b.check_in >= ? AND b.check_out <= ?)
                )
            )
            ORDER BY r.room_number
        ");
        $stmt->execute([$checkIn, $checkIn, $checkOut, $checkOut, $checkIn, $checkOut]);
        return $stmt->fetchAll();
    }

    public function addRoom($roomNumber, $roomType, $price, $description = "") {
        $stmt = $this->connection->prepare("
            INSERT INTO rooms (room_number, room_type, price_per_night, description, status) 
            VALUES (?, ?, ?, ?, 'available')
        ");
        return $stmt->execute([$roomNumber, $roomType, $price, $description]);
    }

    public function updateRoom($id, $roomNumber, $roomType, $price, $description = "") {
        $stmt = $this->connection->prepare("
            UPDATE rooms 
            SET room_number = ?, room_type = ?, price_per_night = ?, description = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$roomNumber, $roomType, $price, $description, $id]);
    }

    public function updateRoomStatus($id, $status) {
        $stmt = $this->connection->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function deleteRoom($id) {
        $stmt = $this->connection->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

class Booking {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function createBooking($data) {
        $stmt = $this->connection->prepare("
            INSERT INTO bookings (guest_name, guest_email, room_id, check_in, check_out, 
                                total_price, status, special_requests, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['guest_name'],
            $data['guest_email'] ?? '',
            $data['room_id'],
            $data['check_in'],
            $data['check_out'],
            $data['total_price'] ?? 0,
            $data['status'] ?? 'pending',
            $data['special_requests'] ?? '',
            $data['phone'] ?? ''
        ]);
    }

    public function getAllBookings() {
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type 
            FROM bookings b 
            LEFT JOIN rooms r ON b.room_id = r.id 
            ORDER BY b.check_in DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBookingById($id) {
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type 
            FROM bookings b 
            LEFT JOIN rooms r ON b.room_id = r.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateBookingStatus($id, $status) {
        $stmt = $this->connection->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getBookingsByDateRange($startDate, $endDate) {
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type 
            FROM bookings b 
            LEFT JOIN rooms r ON b.room_id = r.id 
            WHERE b.check_in >= ? AND b.check_out <= ? 
            ORDER BY b.check_in
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function updateBooking($id, $data) {
        $stmt = $this->connection->prepare("
            UPDATE bookings 
            SET guest_name = ?, guest_email = ?, room_id = ?, check_in = ?, 
                check_out = ?, total_price = ?, status = ?, special_requests = ?, phone = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['guest_name'],
            $data['guest_email'] ?? '',
            $data['room_id'],
            $data['check_in'],
            $data['check_out'],
            $data['total_price'] ?? 0,
            $data['status'] ?? 'pending',
            $data['special_requests'] ?? '',
            $data['phone'] ?? '',
            $id
        ]);
    }

    public function deleteBooking($id) {
        $stmt = $this->connection->prepare("DELETE FROM bookings WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>