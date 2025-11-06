<?php
require_once 'includes/classes.php';

$database = new Database();
$connection = $database->getConnection();

// Show current table structure
$stmt = $connection->query("DESCRIBE time_clock");
$columns = $stmt->fetchAll();

echo "Current time_clock table structure:\n\n";
foreach($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . " - " . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

// Add missing columns
echo "\n\nAdding missing columns...\n";

try {
    // Check and add location column
    $stmt = $connection->prepare("SHOW COLUMNS FROM time_clock LIKE 'location'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE time_clock ADD COLUMN location VARCHAR(100) DEFAULT 'hotel' AFTER clock_in");
        echo "✓ Added 'location' column\n";
    } else {
        echo "✓ 'location' column already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error adding location: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
