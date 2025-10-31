<?php
// Add hotel management classes to existing classes.php

// Hotel Information Management Class
class HotelInfo {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function getHotelInfo($hotelId = null) {
        // If no hotel ID provided, use session's current hotel
        if ($hotelId === null) {
            $hotelId = $_SESSION['current_hotel_id'] ?? 1;
        }
        
        $stmt = $this->connection->prepare("SELECT * FROM hotel_info WHERE id = ? LIMIT 1");
        $stmt->execute([$hotelId]);
        return $stmt->fetch();
    }

    public function updateHotelInfo($data) {
        // Check if hotel info exists
        $existing = $this->getHotelInfo();
        
        if ($existing) {
            // Update existing record
            $stmt = $this->connection->prepare("
                UPDATE hotel_info SET 
                hotel_name = ?, description = ?, address = ?, city = ?, state = ?, zip_code = ?, 
                country = ?, timezone = ?, phone = ?, email = ?, website = ?, check_in_time = ?, check_out_time = ?, 
                total_rooms = ?, star_rating = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['hotel_name'] ?? '', $data['hotel_description'] ?? '', $data['address'] ?? '', 
                $data['city'] ?? '', $data['state'] ?? '', $data['zip_code'] ?? '', $data['country'] ?? '', 
                $data['timezone'] ?? 'America/Lima',
                $data['phone'] ?? '', $data['email'] ?? '', $data['website'] ?? '', 
                $data['check_in_time'] ?? '15:00:00', $data['check_out_time'] ?? '11:00:00',
                $data['total_rooms'] ?? 0, $data['hotel_rating'] ?? 3, $existing['id']
            ]);
        } else {
            // Insert new record
            $stmt = $this->connection->prepare("
                INSERT INTO hotel_info 
                (hotel_name, description, address, city, state, zip_code, 
                country, timezone, phone, email, website, check_in_time, check_out_time, total_rooms, star_rating)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $data['hotel_name'] ?? '', $data['hotel_description'] ?? '', $data['address'] ?? '',
                $data['city'] ?? '', $data['state'] ?? '', $data['zip_code'] ?? '', $data['country'] ?? '', 
                $data['timezone'] ?? 'America/Lima',
                $data['phone'] ?? '', $data['email'] ?? '', $data['website'] ?? '', 
                $data['check_in_time'] ?? '15:00:00', $data['check_out_time'] ?? '11:00:00',
                $data['total_rooms'] ?? 0, $data['hotel_rating'] ?? 3
            ]);
        }

        return [
            'success' => $result,
            'message' => $result ? 'Hotel information updated successfully!' : 'Failed to update hotel information'
        ];
    }

    public function getServices() {
        $stmt = $this->connection->prepare("SELECT * FROM hotel_services ORDER BY service_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAmenities() {
        $stmt = $this->connection->prepare("SELECT * FROM hotel_amenities ORDER BY amenity_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateServiceStatus($serviceId, $status) {
        $stmt = $this->connection->prepare("UPDATE hotel_services SET is_active = ? WHERE id = ?");
        return $stmt->execute([$status, $serviceId]);
    }

    public function updateAmenityStatus($amenityId, $status) {
        $stmt = $this->connection->prepare("UPDATE hotel_amenities SET is_active = ? WHERE id = ?");
        return $stmt->execute([$status, $amenityId]);
    }

    public function addService($name, $description, $icon = '🏨') {
        $stmt = $this->connection->prepare("
            INSERT INTO hotel_services (service_name, service_description, service_icon) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$name, $description, $icon]);
    }

    public function addAmenity($name, $description, $icon = '⭐') {
        $stmt = $this->connection->prepare("
            INSERT INTO hotel_amenities (amenity_name, amenity_description, amenity_icon) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$name, $description, $icon]);
    }
}

// Enhanced User class with role management
class UserManager extends User {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function isManager($userId) {
        $stmt = $this->connection->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user && ($user['role'] === 'manager' || $user['role'] === 'admin');
    }

    public function getAllUsers() {
        $stmt = $this->connection->prepare("
            SELECT id, first_name, last_name, email, role, created_at 
            FROM users ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateUserRole($userId, $role) {
        $stmt = $this->connection->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }
    
    public function getUserByUsername($username) {
        // Search by email (acting as username) or by first name + last name combination
        $stmt = $this->connection->prepare("
            SELECT id, first_name, last_name, email, role, created_at,
                   CONCAT(first_name, ' ', last_name) as full_name
            FROM users 
            WHERE email = ? OR CONCAT(first_name, ' ', last_name) = ? OR first_name = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username, $username]);
        return $stmt->fetch();
    }
}

// Enhanced Booking class with statistics
class BookingManager extends Booking {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }

    public function getBookingStats() {
        $stats = [];
        
        // Total bookings
        $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM bookings");
        $stmt->execute();
        $stats['total_bookings'] = $stmt->fetch()['total'];
        
        // Confirmed bookings
        $stmt = $this->connection->prepare("SELECT COUNT(*) as confirmed FROM bookings WHERE status = 'confirmed'");
        $stmt->execute();
        $stats['confirmed_bookings'] = $stmt->fetch()['confirmed'];
        
        // Today's check-ins
        $stmt = $this->connection->prepare("SELECT COUNT(*) as checkins FROM bookings WHERE check_in_date = CURDATE() AND status = 'confirmed'");
        $stmt->execute();
        $stats['todays_checkins'] = $stmt->fetch()['checkins'];
        
        // Today's check-outs
        $stmt = $this->connection->prepare("SELECT COUNT(*) as checkouts FROM bookings WHERE check_out_date = CURDATE() AND status = 'confirmed'");
        $stmt->execute();
        $stats['todays_checkouts'] = $stmt->fetch()['checkouts'];
        
        // Total revenue
        $stmt = $this->connection->prepare("SELECT SUM(total_price) as revenue FROM bookings WHERE status = 'confirmed'");
        $stmt->execute();
        $stats['total_revenue'] = $stmt->fetch()['revenue'] ?: 0;
        
        // Current occupancy
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as occupied FROM bookings 
            WHERE status = 'confirmed' 
            AND check_in_date <= CURDATE() 
            AND check_out_date > CURDATE()
        ");
        $stmt->execute();
        $stats['current_occupancy'] = $stmt->fetch()['occupied'];
        
        return $stats;
    }

    public function getRecentBookings($limit = 10) {
        // Ensure limit is an integer to prevent SQL injection
        $limit = (int)$limit;
        
        $stmt = $this->connection->prepare("
            SELECT b.*, r.room_number, r.room_type, u.first_name, u.last_name, u.email
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
            LIMIT " . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>