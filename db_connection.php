<?php
// Database connection - VPS MySQL connection with fallback authentication

$host = 'localhost';
$database = 'hotel_booking_system';

// Try multiple connection methods to ensure reliability
$connection_methods = [
    // Method 1: Try hoteluser first
    ['hoteluser', 'hotelpass123'],
    // Method 2: Try root with password
    ['root', 'password123'],
    // Method 3: Try root with empty password (socket auth)
    ['root', '']
];

$conn = null;
$connection_error = '';

foreach ($connection_methods as $method) {
    try {
        $username = $method[0];
        $password = $method[1];
        
        // Try MySQLi connection
        $conn = new mysqli($host, $username, $password, $database);
        
        if (!$conn->connect_error) {
            // Connection successful - create hoteluser if we are connected as root
            if ($username === 'root') {
                // Create the hoteluser properly
                $conn->query("CREATE DATABASE IF NOT EXISTS hotel_booking_system");
                $conn->query("DROP USER IF EXISTS hoteluser@localhost");
                $conn->query("CREATE USER hoteluser@localhost IDENTIFIED BY 'hotelpass123'");
                $conn->query("GRANT ALL PRIVILEGES ON hotel_booking_system.* TO hoteluser@localhost");
                $conn->query("FLUSH PRIVILEGES");
                
                // Now switch to hoteluser connection
                $conn->close();
                $conn = new mysqli($host, 'hoteluser', 'hotelpass123', $database);
                
                if (!$conn->connect_error) {
                    break; // Success with hoteluser
                }
            } else {
                break; // Success with original user
            }
        }
    } catch (Exception $e) {
        $connection_error = $e->getMessage();
        continue;
    }
}

// Final connection check
if ($conn && !$conn->connect_error) {
    // Set charset
    $conn->set_charset("utf8");
} else {
    die("Database connection failed after all attempts: " . ($conn ? $conn->connect_error : $connection_error));
}

// Optimize connection settings
$conn->set_charset("utf8");
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
?>