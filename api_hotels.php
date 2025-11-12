<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

// Get hotels for Cusco region
$query = "
    SELECT 
        h.id,
        h.name,
        h.address,
        h.phone,
        h.email,
        hi.hotel_description,
        hi.city,
        hi.check_in_time,
        hi.check_out_time,
        hi.hotel_rating,
        hi.hotel_logo
    FROM hotels h
    LEFT JOIN hotel_info hi ON h.id = hi.owner_id
    WHERE h.id IS NOT NULL
    ORDER BY h.name
";

$result = $conn->query($query);

$hotels = [];
while ($row = $result->fetch_assoc()) {
    $hotels[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'address' => $row['address'],
        'phone' => $row['phone'],
        'email' => $row['email'],
        'description' => $row['hotel_description'],
        'amenities' => ['WiFi', 'Breakfast'], // TODO: Add amenities table relationship
        'price_from' => 50, // TODO: Get from rooms table
        'check_in' => $row['check_in_time'],
        'check_out' => $row['check_out_time'],
        'image' => $row['hotel_logo'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop',
        'rating' => (float)($row['hotel_rating'] ?? 4.5),
        'reviews_count' => 0,
        'location' => $row['city'] ?? 'Cusco',
        'available' => true
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($hotels),
    'hotels' => $hotels
]);
