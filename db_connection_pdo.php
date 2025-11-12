<?php
// PDO Database connection for AiNi Travel

$host = 'localhost';
$database = 'hotel_booking_system';
$username = 'hoteluser';
$password = 'hotelpass123';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Log error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show detailed error for debugging (remove in production)
    die("Database Error: " . $e->getMessage() . "<br>Code: " . $e->getCode());
}
?>
