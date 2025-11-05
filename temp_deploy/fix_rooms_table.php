<?php
$connection = new mysqli("localhost", "hoteluser", "hotelpass123", "hotel_booking_system");
if ($connection->connect_error) {
    echo "Connection failed: " . $connection->connect_error;
    exit;
}

echo "<h3>Rooms table structure:</h3>";
$result = $connection->query("DESCRIBE rooms");
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No rooms table found";
}

echo "<br><h3>Adding missing price column:</h3>";
$alterResult = $connection->query("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 100.00");
if ($alterResult) {
    echo "✅ Price column added successfully";
} else {
    echo "❌ Error adding price column: " . $connection->error;
}

echo "<br><h3>Adding missing room_number column:</h3>";
$alterResult2 = $connection->query("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_number VARCHAR(10) DEFAULT '101'");
if ($alterResult2) {
    echo "✅ Room number column added successfully";
} else {
    echo "❌ Error adding room_number column: " . $connection->error;
}
?>