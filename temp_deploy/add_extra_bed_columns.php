<?php
// Database update script to add extra bed columns to rooms table
require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    echo "<h2>Adding Extra Bed Columns to Rooms Table</h2>";
    
    // Check if columns already exist
    $stmt = $connection->prepare("SHOW COLUMNS FROM rooms LIKE 'extra_bed_available'");
    $stmt->execute();
    $extraBedAvailableExists = $stmt->fetch();
    
    $stmt = $connection->prepare("SHOW COLUMNS FROM rooms LIKE 'extra_bed_price'");
    $stmt->execute();
    $extraBedPriceExists = $stmt->fetch();
    
    if (!$extraBedAvailableExists) {
        $sql = "ALTER TABLE rooms ADD COLUMN extra_bed_available TINYINT(1) DEFAULT 0 AFTER amenities";
        $connection->exec($sql);
        echo "<p>✅ Added 'extra_bed_available' column</p>";
    } else {
        echo "<p>ℹ️ Column 'extra_bed_available' already exists</p>";
    }
    
    if (!$extraBedPriceExists) {
        $sql = "ALTER TABLE rooms ADD COLUMN extra_bed_price DECIMAL(10,2) DEFAULT 0.00 AFTER extra_bed_available";
        $connection->exec($sql);
        echo "<p>✅ Added 'extra_bed_price' column</p>";
    } else {
        echo "<p>ℹ️ Column 'extra_bed_price' already exists</p>";
    }
    
    // Show current table structure
    echo "<h3>Current Rooms Table Structure:</h3>";
    $stmt = $connection->prepare("DESCRIBE rooms");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>✅ Database update completed successfully!</strong></p>";
    echo "<p><a href='room_management.php'>← Back to Room Management</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>