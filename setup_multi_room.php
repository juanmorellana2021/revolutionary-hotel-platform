<?php
// Quick database setup script to add missing columns
require_once 'includes/classes.php';

echo "<h1>🔧 Database Setup - Adding Multi-Room Columns</h1>";

try {
    $database = new Database();
    $connection = $database->getConnection();
    
    // Add is_multi_room column
    echo "<h2>Adding is_multi_room column...</h2>";
    try {
        $connection->exec("ALTER TABLE bookings ADD COLUMN is_multi_room BOOLEAN DEFAULT FALSE");
        echo "✅ is_multi_room column added successfully<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ is_multi_room column already exists<br>";
        } else {
            echo "❌ Error adding is_multi_room column: " . $e->getMessage() . "<br>";
        }
    }
    
    // Add primary_booking_id column
    echo "<h2>Adding primary_booking_id column...</h2>";
    try {
        $connection->exec("ALTER TABLE bookings ADD COLUMN primary_booking_id INT NULL");
        echo "✅ primary_booking_id column added successfully<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ primary_booking_id column already exists<br>";
        } else {
            echo "❌ Error adding primary_booking_id column: " . $e->getMessage() . "<br>";
        }
    }
    
    // Verify the columns exist
    echo "<h2>Verifying columns...</h2>";
    $stmt = $connection->prepare("DESCRIBE bookings");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $hasMultiRoom = false;
    $hasPrimaryBooking = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'is_multi_room') {
            $hasMultiRoom = true;
        }
        if ($column['Field'] === 'primary_booking_id') {
            $hasPrimaryBooking = true;
        }
    }
    
    echo "is_multi_room column: " . ($hasMultiRoom ? "✅ EXISTS" : "❌ MISSING") . "<br>";
    echo "primary_booking_id column: " . ($hasPrimaryBooking ? "✅ EXISTS" : "❌ MISSING") . "<br>";
    
    if ($hasMultiRoom && $hasPrimaryBooking) {
        echo "<h2>🎉 Success!</h2>";
        echo "<p>All required columns have been added. You can now use the calendar system.</p>";
        echo "<p><a href='calendar_view.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Calendar</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Connection Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>