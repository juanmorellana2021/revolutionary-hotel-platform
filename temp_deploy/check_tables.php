<?php
$connection = new mysqli("localhost", "hoteluser", "hotelpass123", "hotel_booking_system");
if ($connection->connect_error) {
    echo "Connection failed: " . $connection->connect_error;
    exit;
}
$result = $connection->query("SHOW TABLES");
echo "<h3>Tables in hotel_booking_system database:</h3>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        echo $row[0] . "<br>";
    }
} else {
    echo "No tables found - need to create database schema";
}
?>