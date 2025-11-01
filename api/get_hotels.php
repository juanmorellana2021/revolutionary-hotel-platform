<?php
/**
 * API Endpoint: Get Hotels
 * Returns all active hotels from database
 * Used by public_booking.php to populate hotel list dynamically
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include database connection
require_once __DIR__ . '/../db_connection.php';

try {
    // Prepare query to get all active hotel properties
    $query = "SELECT 
                id,
                name,
                location,
                emoji,
                rating,
                price,
                features,
                aini_coins,
                category,
                latitude,
                longitude,
                images,
                description,
                rooms_available
              FROM hotel_properties 
              WHERE is_active = 1
              ORDER BY rating DESC, price ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $hotels = array();
    
    while ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        $hotel = array(
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'location' => $row['location'],
            'emoji' => $row['emoji'],
            'rating' => (float)$row['rating'],
            'price' => (float)$row['price'],
            'features' => json_decode($row['features'], true),
            'aini_coins' => (int)$row['aini_coins'],
            'category' => $row['category'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'images' => json_decode($row['images'], true),
            'description' => $row['description'],
            'rooms_available' => (int)$row['rooms_available']
        );
        
        $hotels[] = $hotel;
    }
    
    // Return success response
    echo json_encode(array(
        'success' => true,
        'count' => count($hotels),
        'hotels' => $hotels
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
