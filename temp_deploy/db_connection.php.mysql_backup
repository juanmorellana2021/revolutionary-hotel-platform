<?php
// db_connection.php - Simple database connection for testing

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hotel_booking_system';

// Create connection using MySQLi for compatibility with our test script
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optimize connection settings
$conn->set_charset("utf8");
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// Set charset
$conn->set_charset("utf8");
?>