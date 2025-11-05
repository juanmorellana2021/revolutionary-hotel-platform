<?php
echo "=== BEFORE db_connection.php ===\n";
echo "PHP Timezone: " . date_default_timezone_get() . "\n";
echo "Current time: " . date('Y-m-d g:i A') . "\n\n";

session_start();
$_SESSION['current_hotel_id'] = 1;

echo "Session current_hotel_id: " . $_SESSION['current_hotel_id'] . "\n\n";

include 'db_connection.php';

echo "=== AFTER db_connection.php ===\n";
echo "PHP Timezone: " . date_default_timezone_get() . "\n";
echo "Current time: " . date('Y-m-d g:i A') . "\n";
?>
