<?php
// Check hotel info and other text-heavy tables
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hotel Data Check</title>
</head>
<body>
<?php
$host = 'localhost';
$db = 'hotel_booking_system';
$user = 'hotelapp';
$pass = 'hotel123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Hotel Information (check for ? marks):</h2>";
    
    $stmt = $pdo->query("SELECT * FROM hotel_info LIMIT 1");
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($hotel) {
        echo "<pre>";
        foreach ($hotel as $key => $value) {
            echo "<strong>$key:</strong> $value\n";
            if (is_string($value) && strlen($value) > 0) {
                echo "  (hex: " . substr(bin2hex($value), 0, 100) . "...)\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p>No hotel info found</p>";
    }
    
    echo "<h2>Sample Guest Names from Bookings:</h2>";
    $stmt = $pdo->query("SELECT DISTINCT guest_name, guest_email FROM booking_guests LIMIT 10");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Guest: " . ($row['guest_name'] ?? 'N/A') . " - Email: " . ($row['guest_email'] ?? 'N/A') . "\n";
    }
    echo "</pre>";
    
    echo "<h2>Sample Amenities:</h2>";
    $stmt = $pdo->query("SELECT name, description FROM hotel_amenities LIMIT 5");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Amenity: " . $row['name'] . "\n";
        echo "  Desc: " . $row['description'] . "\n\n";
    }
    echo "</pre>";
    
    echo "<h2>Sample Services:</h2>";
    $stmt = $pdo->query("SELECT name, description FROM hotel_services LIMIT 5");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Service: " . $row['name'] . "\n";
        echo "  Desc: " . $row['description'] . "\n\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
</body>
</html>
