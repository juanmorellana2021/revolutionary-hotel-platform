<?php
// public_booking_api.php - API for public hotel booking website

session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'search_hotels':
            searchHotels();
            break;
            
        case 'get_hotel_details':
            getHotelDetails();
            break;
            
        case 'create_booking':
            createPublicBooking();
            break;
            
        case 'get_featured_hotels':
            getFeaturedHotels();
            break;
            
        case 'get_destinations':
            getPopularDestinations();
            break;
            
        case 'check_availability':
            checkAvailability();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function searchHotels() {
    global $conn;
    
    $destination = $_GET['destination'] ?? '';
    $check_in = $_GET['check_in'] ?? '';
    $check_out = $_GET['check_out'] ?? '';
    $guests = (int)($_GET['guests'] ?? 1);
    $category = $_GET['category'] ?? '';
    $min_price = (float)($_GET['min_price'] ?? 0);
    $max_price = (float)($_GET['max_price'] ?? 9999);
    
    $query = "
        SELECT DISTINCT
            h.id as hotel_id,
            h.hotel_name,
            h.address as location,
            h.city,
            h.state,
            h.country,
            h.description,
            '' as amenities,
            'standard' as category,
            h.star_rating,
            h.phone as whatsapp_number,
            h.email,
            h.created_at,
            MIN(r.price_per_night) as min_price,
            MAX(r.price_per_night) as max_price,
            4.5 as avg_rating,
            0 as review_count,
            GROUP_CONCAT(DISTINCT r.room_type) as room_types,
            '' as photo_url
        FROM hotel_info h
        LEFT JOIN rooms r ON h.id = r.id
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    if ($destination) {
        $query .= " AND (h.hotel_name LIKE ? OR h.address LIKE ? OR h.city LIKE ? OR h.state LIKE ?)";
        $search_term = "%$destination%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        $types .= 'ssss';
    }
    
    if ($category) {
        $query .= " AND 'standard' = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    $query .= " GROUP BY h.id";
    $query .= " HAVING min_price >= ? AND min_price <= ?";
    $params = array_merge($params, [$min_price, $max_price]);
    $types .= 'dd';
    
    $query .= " ORDER BY avg_rating DESC, min_price ASC";
    $query .= " LIMIT 50";
    
    $stmt = $conn->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate AiNi coin reward (20% of base price)
        $aini_reward = round($row['min_price'] * 0.2);
        
        // Parse amenities
        $amenities = $row['amenities'] ? explode(',', $row['amenities']) : [];
        
        // Check availability if dates provided
        $available = true;
        if ($check_in && $check_out) {
            $available = checkHotelAvailability($row['hotel_id'], $check_in, $check_out, $guests);
        }
        
        $hotels[] = [
            'hotel_id' => $row['hotel_id'],
            'name' => $row['hotel_name'],
            'location' => $row['location'],
            'city' => $row['city'],
            'state' => $row['state'],
            'country' => $row['country'],
            'description' => $row['description'],
            'category' => $row['category'],
            'star_rating' => (float)$row['star_rating'],
            'min_price' => (float)$row['min_price'],
            'max_price' => (float)$row['max_price'],
            'avg_rating' => round((float)$row['avg_rating'], 1),
            'review_count' => (int)$row['review_count'],
            'aini_reward' => $aini_reward,
            'amenities' => $amenities,
            'room_types' => $row['room_types'] ? explode(',', $row['room_types']) : [],
            'whatsapp_number' => $row['whatsapp_number'],
            'photo_url' => $row['photo_url'] ?: '/hotel-booking-system/assets/images/hotel-placeholder.jpg',
            'available' => $available,
            'whatsapp_booking_url' => generateWhatsAppBookingURL($row['hotel_name'], $row['whatsapp_number'], $check_in, $check_out, $guests)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'hotels' => $hotels,
        'total_found' => count($hotels),
        'search_params' => [
            'destination' => $destination,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'guests' => $guests,
            'category' => $category
        ]
    ]);
}

function getFeaturedHotels() {
    global $conn;
    
    $query = "
        SELECT 
            h.id as hotel_id,
            h.hotel_name,
            h.address as location,
            h.city,
            h.state,
            h.country,
            h.description,
            '' as amenities,
            'standard' as category,
            h.star_rating,
            h.phone as whatsapp_number,
            '' as photo_url,
            COALESCE((SELECT MIN(price_per_night) FROM rooms WHERE rooms.id = h.id), 100) as min_price,
            h.star_rating as avg_rating,
            0 as review_count
        FROM hotel_info h
        WHERE h.hotel_name IS NOT NULL AND h.hotel_name != ''
        ORDER BY h.star_rating DESC
        LIMIT 12
    ";
    
    $result = $conn->query($query);
    $hotels = [];
    
    while ($row = $result->fetch_assoc()) {
        $aini_reward = round($row['min_price'] * 0.2);
        $amenities = $row['amenities'] ? explode(',', $row['amenities']) : [];
        
        // Generate sample image emoji based on category
        $image_emoji = '🏨';
        switch ($row['category']) {
            case 'luxury': $image_emoji = '🌟'; break;
            case 'beach': $image_emoji = '🏖️'; break;
            case 'business': $image_emoji = '🏙️'; break;
            case 'family': $image_emoji = '🎡'; break;
            case 'budget': $image_emoji = '🏠'; break;
            default: $image_emoji = '🏨';
        }
        
        $hotels[] = [
            'hotel_id' => $row['hotel_id'],
            'name' => $row['hotel_name'],
            'location' => $row['city'] . ', ' . $row['state'],
            'image_emoji' => $image_emoji,
            'star_rating' => (float)$row['star_rating'],
            'price' => (float)$row['min_price'],
            'avg_rating' => round((float)$row['avg_rating'], 1),
            'review_count' => (int)$row['review_count'],
            'aini_reward' => $aini_reward,
            'amenities' => array_slice($amenities, 0, 4), // First 4 amenities
            'category' => $row['category'],
            'whatsapp_number' => $row['whatsapp_number'],
            'photo_url' => $row['photo_url']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'hotels' => $hotels
    ]);
}

function getHotelDetails() {
    global $conn;
    
    $hotel_id = (int)($_GET['hotel_id'] ?? 0);
    
    if (!$hotel_id) {
        throw new Exception('Hotel ID is required');
    }
    
    // Get hotel details
    $query = "
        SELECT h.*, 
               AVG(COALESCE(rev.rating, 4.5)) as avg_rating,
               COUNT(DISTINCT rev.review_id) as review_count
        FROM hotels h
        LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
        WHERE h.hotel_id = ? AND h.status = 'active'
        GROUP BY h.hotel_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hotel_id);
    $stmt->execute();
    $hotel = $stmt->get_result()->fetch_assoc();
    
    if (!$hotel) {
        throw new Exception('Hotel not found');
    }
    
    // Get rooms
    $query = "SELECT * FROM rooms WHERE hotel_id = ? AND status = 'available' ORDER BY base_price ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hotel_id);
    $stmt->execute();
    $rooms_result = $stmt->get_result();
    
    $rooms = [];
    while ($room = $rooms_result->fetch_assoc()) {
        $rooms[] = [
            'room_id' => $room['room_id'],
            'room_type' => $room['room_type'],
            'description' => $room['description'],
            'base_price' => (float)$room['base_price'],
            'max_occupancy' => (int)$room['max_occupancy'],
            'amenities' => $room['amenities'] ? explode(',', $room['amenities']) : [],
            'aini_reward' => round($room['base_price'] * 0.2)
        ];
    }
    
    // Get recent reviews
    $query = "
        SELECT r.*, g.name as guest_name 
        FROM reviews r 
        LEFT JOIN guests g ON r.guest_id = g.guest_id 
        WHERE r.hotel_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 10
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hotel_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    
    $reviews = [];
    while ($review = $reviews_result->fetch_assoc()) {
        $reviews[] = [
            'guest_name' => $review['guest_name'] ?: 'Anonymous',
            'rating' => (float)$review['rating'],
            'comment' => $review['comment'],
            'created_at' => $review['created_at']
        ];
    }
    
    $hotel_details = [
        'hotel_id' => $hotel['hotel_id'],
        'name' => $hotel['hotel_name'],
        'location' => $hotel['location'],
        'city' => $hotel['city'],
        'state' => $hotel['state'],
        'country' => $hotel['country'],
        'description' => $hotel['description'],
        'amenities' => $hotel['amenities'] ? explode(',', $hotel['amenities']) : [],
        'category' => $hotel['category'],
        'star_rating' => (float)$hotel['star_rating'],
        'avg_rating' => round((float)$hotel['avg_rating'], 1),
        'review_count' => (int)$hotel['review_count'],
        'whatsapp_number' => $hotel['whatsapp_number'],
        'email' => $hotel['email'],
        'photo_url' => $hotel['photo_url'],
        'rooms' => $rooms,
        'reviews' => $reviews
    ];
    
    echo json_encode([
        'success' => true,
        'hotel' => $hotel_details
    ]);
}

function createPublicBooking() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['hotel_id', 'room_id', 'guest_name', 'guest_email', 'guest_phone', 'check_in', 'check_out', 'guests'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $hotel_id = (int)$data['hotel_id'];
    $room_id = (int)$data['room_id'];
    $guest_name = $conn->real_escape_string($data['guest_name']);
    $guest_email = $conn->real_escape_string($data['guest_email']);
    $guest_phone = $conn->real_escape_string($data['guest_phone']);
    $check_in = $data['check_in'];
    $check_out = $data['check_out'];
    $guests = (int)$data['guests'];
    $special_requests = $conn->real_escape_string($data['special_requests'] ?? '');
    
    // Validate dates
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    
    if ($check_in_date >= $check_out_date) {
        throw new Exception('Check-out date must be after check-in date');
    }
    
    if ($check_in_date < new DateTime('today')) {
        throw new Exception('Check-in date cannot be in the past');
    }
    
    // Check availability
    if (!checkHotelAvailability($hotel_id, $check_in, $check_out, $guests)) {
        throw new Exception('Hotel is not available for the selected dates');
    }
    
    // Get room details for pricing
    $query = "SELECT * FROM rooms WHERE room_id = ? AND hotel_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $room_id, $hotel_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        throw new Exception('Room not found');
    }
    
    // Calculate total cost
    $nights = $check_in_date->diff($check_out_date)->days;
    $total_cost = $room['base_price'] * $nights;
    $aini_coins_earned = round($total_cost * 0.2);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create or get guest
        $query = "SELECT guest_id FROM guests WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $guest_email);
        $stmt->execute();
        $guest_result = $stmt->get_result();
        
        if ($guest_result->num_rows > 0) {
            $guest_id = $guest_result->fetch_assoc()['guest_id'];
            
            // Update guest info
            $query = "UPDATE guests SET name = ?, phone = ?, updated_at = NOW() WHERE guest_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssi', $guest_name, $guest_phone, $guest_id);
            $stmt->execute();
        } else {
            // Create new guest
            $query = "INSERT INTO guests (name, email, phone, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sss', $guest_name, $guest_email, $guest_phone);
            $stmt->execute();
            $guest_id = $conn->insert_id;
            
            // Create wallet for new guest
            $query = "INSERT INTO guest_wallets (guest_id, aini_coins, loyalty_points, created_at) VALUES (?, 0, 0, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $guest_id);
            $stmt->execute();
        }
        
        // Create booking
        $booking_reference = 'AINI' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $query = "
            INSERT INTO bookings (
                hotel_id, room_id, guest_id, booking_reference, 
                check_in_date, check_out_date, guests, 
                total_cost, special_requests, status, 
                booking_source, aini_coins_earned, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'website', ?, NOW())
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'iiisssissi', 
            $hotel_id, $room_id, $guest_id, $booking_reference,
            $check_in, $check_out, $guests, 
            $total_cost, $special_requests, $aini_coins_earned
        );
        $stmt->execute();
        $booking_id = $conn->insert_id;
        
        // Award AiNi coins
        $query = "UPDATE guest_wallets SET aini_coins = aini_coins + ?, updated_at = NOW() WHERE guest_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $aini_coins_earned, $guest_id);
        $stmt->execute();
        
        // Log AiNi transaction
        $query = "
            INSERT INTO aini_transactions (
                guest_id, transaction_type, amount, description, 
                booking_id, created_at
            ) VALUES (?, 'earned', ?, 'Booking reward', ?, NOW())
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iii', $guest_id, $aini_coins_earned, $booking_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Get hotel info for confirmation
        $query = "SELECT hotel_name, whatsapp_number FROM hotels WHERE hotel_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $hotel_id);
        $stmt->execute();
        $hotel_info = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'total_cost' => $total_cost,
            'aini_coins_earned' => $aini_coins_earned,
            'hotel_name' => $hotel_info['hotel_name'],
            'whatsapp_number' => $hotel_info['whatsapp_number'],
            'guest_id' => $guest_id,
            'message' => 'Booking confirmed! You earned ' . $aini_coins_earned . ' AiNi coins!'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function checkAvailability() {
    global $conn;
    
    $hotel_id = (int)($_GET['hotel_id'] ?? 0);
    $check_in = $_GET['check_in'] ?? '';
    $check_out = $_GET['check_out'] ?? '';
    $guests = (int)($_GET['guests'] ?? 1);
    
    if (!$hotel_id || !$check_in || !$check_out) {
        throw new Exception('Hotel ID, check-in and check-out dates are required');
    }
    
    $available = checkHotelAvailability($hotel_id, $check_in, $check_out, $guests);
    
    echo json_encode([
        'success' => true,
        'available' => $available,
        'hotel_id' => $hotel_id,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'guests' => $guests
    ]);
}

function getPopularDestinations() {
    global $conn;
    
    $query = "
        SELECT 
            city,
            state,
            country,
            COUNT(*) as hotel_count,
            AVG(star_rating) as avg_rating,
            MIN((SELECT MIN(base_price) FROM rooms WHERE hotel_id = hotels.hotel_id)) as min_price
        FROM hotels 
        WHERE status = 'active'
        GROUP BY city, state, country
        ORDER BY hotel_count DESC, avg_rating DESC
        LIMIT 20
    ";
    
    $result = $conn->query($query);
    $destinations = [];
    
    while ($row = $result->fetch_assoc()) {
        $destinations[] = [
            'city' => $row['city'],
            'state' => $row['state'],
            'country' => $row['country'],
            'full_name' => $row['city'] . ', ' . $row['state'] . ', ' . $row['country'],
            'hotel_count' => (int)$row['hotel_count'],
            'avg_rating' => round((float)$row['avg_rating'], 1),
            'min_price' => (float)$row['min_price']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'destinations' => $destinations
    ]);
}

// Helper Functions

function checkHotelAvailability($hotel_id, $check_in, $check_out, $guests) {
    global $conn;
    
    // Check if there are available rooms for the dates
    $query = "
        SELECT COUNT(*) as available_rooms
        FROM rooms r
        WHERE r.hotel_id = ? 
        AND r.status = 'available'
        AND r.max_occupancy >= ?
        AND r.room_id NOT IN (
            SELECT DISTINCT b.room_id 
            FROM bookings b 
            WHERE b.hotel_id = ?
            AND b.status IN ('confirmed', 'checked_in')
            AND (
                (b.check_in_date <= ? AND b.check_out_date > ?) OR
                (b.check_in_date < ? AND b.check_out_date >= ?) OR
                (b.check_in_date >= ? AND b.check_out_date <= ?)
            )
        )
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiissssss', 
        $hotel_id, $guests, $hotel_id,
        $check_in, $check_in,
        $check_out, $check_out,
        $check_in, $check_out
    );
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['available_rooms'] > 0;
}

function generateWhatsAppBookingURL($hotel_name, $whatsapp_number, $check_in, $check_out, $guests) {
    if (!$whatsapp_number) return null;
    
    $message = "Hi! 🏨 I'd like to book $hotel_name";
    if ($check_in && $check_out) {
        $message .= " from $check_in to $check_out for $guests guest(s)";
    }
    $message .= ". Can you help me with the booking and AiNi coin rewards? 🪙";
    
    return "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp_number) . "?text=" . urlencode($message);
}

?>