<?php
// Database connection for hotel_booking_system
// Using dedicated web application user

$servername = "localhost";
$username = "hotelapp";
$password = "hotel123";
$dbname = "hotel_booking_system";

// Create connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please check configuration.");
}

// For legacy compatibility
function getDatabaseConnection() {
    global $conn;
    return $conn;
}
?>