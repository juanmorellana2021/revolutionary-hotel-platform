<?php
// Database update script for Booking.com integration
require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    echo "<h2>Setting up Booking.com Integration Database</h2>";
    
    // Add columns to bookings table
    $bookingColumns = [
        'booking_reference' => "VARCHAR(100) NULL COMMENT 'Booking.com reference number'",
        'booking_source' => "VARCHAR(50) DEFAULT 'direct' COMMENT 'Source of booking (direct, booking.com, etc.)'",
        'sync_status' => "ENUM('pending', 'synced', 'failed') NULL COMMENT 'Sync status with Booking.com'",
        'synced_at' => "TIMESTAMP NULL COMMENT 'When booking was synced'",
        'guest_name' => "VARCHAR(255) NULL COMMENT 'Guest name from external booking'",
        'guest_email' => "VARCHAR(255) NULL COMMENT 'Guest email from external booking'",
        'guest_phone' => "VARCHAR(50) NULL COMMENT 'Guest phone from external booking'"
    ];
    
    foreach ($bookingColumns as $column => $definition) {
        $stmt = $connection->prepare("SHOW COLUMNS FROM bookings LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE bookings ADD COLUMN {$column} {$definition}";
            $connection->exec($sql);
            echo "<p>✅ Added '{$column}' column to bookings table</p>";
        } else {
            echo "<p>ℹ️ Column '{$column}' already exists in bookings table</p>";
        }
    }
    
    // Add columns to rooms table for Booking.com mapping
    $roomColumns = [
        'booking_room_type_id' => "VARCHAR(100) NULL COMMENT 'Booking.com room type ID'",
        'booking_room_name' => "VARCHAR(255) NULL COMMENT 'Room name as shown on Booking.com'"
    ];
    
    foreach ($roomColumns as $column => $definition) {
        $stmt = $connection->prepare("SHOW COLUMNS FROM rooms LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE rooms ADD COLUMN {$column} {$definition}";
            $connection->exec($sql);
            echo "<p>✅ Added '{$column}' column to rooms table</p>";
        } else {
            echo "<p>ℹ️ Column '{$column}' already exists in rooms table</p>";
        }
    }
    
    // Create API configuration table
    $configTableSql = "
        CREATE TABLE IF NOT EXISTS api_config (
            id INT PRIMARY KEY AUTO_INCREMENT,
            provider VARCHAR(50) NOT NULL,
            config_key VARCHAR(100) NOT NULL,
            config_value TEXT,
            encrypted BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_provider_key (provider, config_key)
        )
    ";
    
    $connection->exec($configTableSql);
    echo "<p>✅ Created 'api_config' table for storing API credentials</p>";
    
    // Create sync log table
    $syncLogTableSql = "
        CREATE TABLE IF NOT EXISTS sync_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sync_type ENUM('incoming', 'outgoing', 'availability') NOT NULL,
            provider VARCHAR(50) NOT NULL,
            status ENUM('success', 'error', 'warning') NOT NULL,
            message TEXT,
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $connection->exec($syncLogTableSql);
    echo "<p>✅ Created 'sync_logs' table for tracking synchronization</p>";
    
    // Add indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_bookings_reference ON bookings(booking_reference)",
        "CREATE INDEX IF NOT EXISTS idx_bookings_source ON bookings(booking_source)",
        "CREATE INDEX IF NOT EXISTS idx_bookings_sync_status ON bookings(sync_status)",
        "CREATE INDEX IF NOT EXISTS idx_rooms_booking_type ON rooms(booking_room_type_id)",
        "CREATE INDEX IF NOT EXISTS idx_sync_logs_type ON sync_logs(sync_type, provider)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $connection->exec($indexSql);
            echo "<p>✅ Added database index</p>";
        } catch (Exception $e) {
            echo "<p>ℹ️ Index may already exist</p>";
        }
    }
    
    // Show updated table structures
    echo "<h3>Updated Bookings Table Structure:</h3>";
    $stmt = $connection->prepare("DESCRIBE bookings");
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
    
    echo "<p><strong>✅ Booking.com integration database setup completed!</strong></p>";
    echo "<p><a href='booking_integration.php'>→ Go to Booking.com Integration Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>