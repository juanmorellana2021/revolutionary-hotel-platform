<?php
// Database connection - VPS MySQL connection

$host = 'localhost';
$username = 'root';
$password = 'password123';
$database = 'mysql'; // Use mysql database first

// Create connection using MySQLi for compatibility with our test script
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // If connection fails, create the database first
    $conn_temp = new mysqli($host, $username, $password);
    if (!$conn_temp->connect_error) {
        $conn_temp->query("CREATE DATABASE IF NOT EXISTS hotel_booking_system");
        $conn_temp->close();
        // Try connecting to the new database
        $conn = new mysqli($host, $username, $password, 'hotel_booking_system');
    }
}

// Optimize connection settings
$conn->set_charset("utf8");
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
?>