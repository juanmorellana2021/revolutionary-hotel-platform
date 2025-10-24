<?php
session_start();
require_once __DIR__ . '/includes/classes.php';
require_once __DIR__ . '/includes/hotel_classes.php';

// Database connection
$database = new Database();
$connection = $database->getConnection();

// Get the most recent booking
$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type, r.price as room_price,
           u.first_name, u.last_name, u.email, u.phone,
           GROUP_CONCAT(DISTINCT CONCAT(bg.guest_name, '|', COALESCE(bg.guest_email, ''), '|', COALESCE(bg.guest_phone, ''), '|', bg.is_primary) SEPARATOR ';;;') as all_guests
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    LEFT JOIN booking_guests bg ON b.id = bg.booking_id
    WHERE b.id IS NOT NULL
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 1
");
$stmt->execute();
$booking = $stmt->fetch();

if (!$booking) {
    die('No bookings found in database');
}

echo "<h2>Testing PDF Generation</h2>";
echo "<h3>Found Booking:</h3>";
echo "<pre>";
print_r($booking);
echo "</pre>";

echo '<p><a href="receipt_handler.php?booking_id=' . $booking['id'] . '&action=pdf" target="_blank">🔗 Test PDF Generation</a></p>';
echo '<p><a href="receipt_handler.php?booking_id=' . $booking['id'] . '&action=view" target="_blank">🔗 View Receipt (HTML)</a></p>';
?>