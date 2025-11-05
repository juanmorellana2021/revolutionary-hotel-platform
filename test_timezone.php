<?php
session_start();
// Set default hotel for testing
$_SESSION['current_hotel_id'] = 1;

echo "<h2>Session Test</h2>";
echo "current_hotel_id in session: " . ($_SESSION['current_hotel_id'] ?? 'NOT SET') . "<br>";

// Now include db_connection to test timezone
include 'db_connection.php';

echo "<br><h2>PHP Timezone Test</h2>";
echo "PHP date_default_timezone_get(): " . date_default_timezone_get() . "<br>";
echo "Current PHP time: " . date('Y-m-d H:i:s') . "<br>";
echo "Current PHP timestamp: " . time() . "<br>";

echo "<br><h2>MySQL Timezone Test</h2>";
$result = $conn->query("SELECT NOW() as mysql_now, @@session.time_zone as mysql_tz");
if ($row = $result->fetch_assoc()) {
    echo "MySQL NOW(): " . $row['mysql_now'] . "<br>";
    echo "MySQL timezone: " . $row['mysql_tz'] . "<br>";
}

echo "<br><h2>Hotel Info Test</h2>";
$hotel_result = $conn->query("SELECT hotel_name, timezone FROM hotel_info WHERE id = 1");
if ($hotel_row = $hotel_result->fetch_assoc()) {
    echo "Hotel Name: " . $hotel_row['hotel_name'] . "<br>";
    echo "Hotel Timezone: " . $hotel_row['timezone'] . "<br>";
}

echo "<br><h2>What time should it be in Peru?</h2>";
echo "Server UTC time: " . gmdate('Y-m-d H:i:s') . " UTC<br>";
echo "Peru time (calculated): " . gmdate('Y-m-d H:i:s', time() - (5*3600)) . " (UTC-5)<br>";
?>
