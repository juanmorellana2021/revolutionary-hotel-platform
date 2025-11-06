<?php
// Quick encoding diagnostic script
$host = 'localhost';
$db = 'hotel_booking_system';
$user = 'hotelapp';
$pass = 'hotel123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Encoding Check</h2>";
    
    // Check database charset
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set%'");
    echo "<h3>MySQL Character Sets:</h3><pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Variable_name'] . " = " . $row['Value'] . "\n";
    }
    echo "</pre>";
    
    // Check collation
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'collation%'");
    echo "<h3>MySQL Collations:</h3><pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Variable_name'] . " = " . $row['Value'] . "\n";
    }
    echo "</pre>";
    
    // Sample some room names to see encoding
    $stmt = $pdo->query("SELECT room_number, room_type FROM rooms LIMIT 5");
    echo "<h3>Sample Room Data (check for ? marks):</h3><pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Room " . $row['room_number'] . " - Type: " . $row['room_type'] . "\n";
        echo "  (raw bytes: " . bin2hex($row['room_type']) . ")\n";
    }
    echo "</pre>";
    
    // Check table collations
    $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'hotel_booking_system'");
    echo "<h3>Table Collations:</h3><pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['TABLE_NAME'] . " => " . $row['TABLE_COLLATION'] . "\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>