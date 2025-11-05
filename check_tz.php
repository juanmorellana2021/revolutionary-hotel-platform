<?php
session_start();
$_SESSION['current_hotel_id'] = 1;

echo "<h2>Timezone Debug</h2>";

echo "<h3>BEFORE setting timezone:</h3>";
echo "date_default_timezone_get(): " . date_default_timezone_get() . "<br>";
echo "date('Y-m-d H:i:s'): " . date('Y-m-d H:i:s') . "<br>";
echo "date('g:i A'): " . date('g:i A') . "<br>";

require_once 'includes/classes.php';
$db = new Database();
$conn = $db->getConnection();
$timezone_stmt = $conn->prepare("SELECT timezone FROM hotel_info WHERE id = ? LIMIT 1");
$timezone_stmt->execute([1]);
$timezone_result = $timezone_stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Hotel timezone from database:</h3>";
echo "Timezone: " . ($timezone_result['timezone'] ?? 'NOT FOUND') . "<br>";

if ($timezone_result) {
    $hotel_timezone = $timezone_result['timezone'] ?? 'America/Lima';
    date_default_timezone_set($hotel_timezone);
    echo "<h3>AFTER setting timezone to " . $hotel_timezone . ":</h3>";
} else {
    date_default_timezone_set('America/Lima');
    echo "<h3>AFTER setting timezone to America/Lima (default):</h3>";
}

echo "date_default_timezone_get(): " . date_default_timezone_get() . "<br>";
echo "date('Y-m-d H:i:s'): " . date('Y-m-d H:i:s') . "<br>";
echo "date('g:i A'): " . date('g:i A') . "<br>";

echo "<h3>What time SHOULD it be in Peru?</h3>";
echo "Server UTC: " . gmdate('Y-m-d H:i:s') . " UTC<br>";
echo "Peru (UTC-5): " . gmdate('Y-m-d H:i:s', time() - (5*3600)) . "<br>";
?>
