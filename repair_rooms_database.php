<?php
// Room Database Diagnosis and Repair
require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    echo "<h1>🔧 Room Database Diagnosis & Repair</h1>";
    
    // Check current rooms table structure
    echo "<h2>📋 Current Rooms Table Structure:</h2>";
    $stmt = $connection->prepare("DESCRIBE rooms");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $columnNames = [];
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Current Values</th></tr>";
    
    foreach ($columns as $column) {
        $columnNames[] = $column['Field'];
        
        // Get sample values for this column
        $stmt2 = $connection->prepare("SELECT `{$column['Field']}` FROM rooms LIMIT 3");
        $stmt2->execute();
        $sampleValues = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . implode(', ', array_map('htmlspecialchars', $sampleValues)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🔍 Missing Standard Columns Check:</h2>";
    $expectedColumns = ['price', 'max_occupancy', 'is_available', 'amenities', 'description', 'features'];
    $missingColumns = [];
    
    foreach ($expectedColumns as $expected) {
        if (!in_array($expected, $columnNames)) {
            $missingColumns[] = $expected;
            echo "<p>❌ <strong>$expected</strong> column is missing</p>";
        } else {
            echo "<p>✅ <strong>$expected</strong> column exists</p>";
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<h2>🛠️ Adding Missing Columns:</h2>";
        
        foreach ($missingColumns as $missingCol) {
            echo "<p>Adding <strong>$missingCol</strong> column...</p>";
            
            switch ($missingCol) {
                case 'price':
                    $sql = "ALTER TABLE rooms ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00";
                    break;
                case 'max_occupancy':
                    $sql = "ALTER TABLE rooms ADD COLUMN max_occupancy INT DEFAULT 1";
                    break;
                case 'is_available':
                    $sql = "ALTER TABLE rooms ADD COLUMN is_available BOOLEAN DEFAULT TRUE";
                    break;
                case 'amenities':
                    $sql = "ALTER TABLE rooms ADD COLUMN amenities TEXT";
                    break;
                case 'description':
                    $sql = "ALTER TABLE rooms ADD COLUMN description TEXT";
                    break;
                case 'features':
                    $sql = "ALTER TABLE rooms ADD COLUMN features TEXT";
                    break;
                default:
                    continue 2;
            }
            
            try {
                $stmt = $connection->prepare($sql);
                if ($stmt->execute()) {
                    echo "<p>✅ Successfully added <strong>$missingCol</strong> column</p>";
                } else {
                    echo "<p>❌ Failed to add <strong>$missingCol</strong> column</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error adding <strong>$missingCol</strong>: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Update existing rooms with default values if needed
    echo "<h2>📝 Updating Existing Rooms with Default Values:</h2>";
    
    // Check if rooms have missing data
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM rooms WHERE price IS NULL OR price = 0");
    $stmt->execute();
    $nullPriceCount = $stmt->fetch()['count'];
    
    if ($nullPriceCount > 0) {
        echo "<p>Found $nullPriceCount rooms with missing price data. Setting default prices...</p>";
        
        // Set default prices based on room type
        $defaultPrices = [
            'Standard Single' => 75.00,
            'Standard Double' => 95.00,
            'Deluxe Queen' => 125.00,
            'Deluxe Suite' => 125.00,
            'Executive Suite' => 175.00,
            'Presidential Suite' => 250.00,
            'Family Room' => 150.00
        ];
        
        foreach ($defaultPrices as $roomType => $defaultPrice) {
            $stmt = $connection->prepare("UPDATE rooms SET price = ? WHERE room_type = ? AND (price IS NULL OR price = 0)");
            $stmt->execute([$defaultPrice, $roomType]);
        }
        
        echo "<p>✅ Updated room prices with defaults</p>";
    }
    
    // Set default max occupancy
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM rooms WHERE max_occupancy IS NULL OR max_occupancy = 0");
    $stmt->execute();
    $nullOccupancyCount = $stmt->fetch()['count'];
    
    if ($nullOccupancyCount > 0) {
        echo "<p>Found $nullOccupancyCount rooms with missing occupancy data. Setting defaults...</p>";
        
        $defaultOccupancy = [
            'Standard Single' => 1,
            'Standard Double' => 2,
            'Deluxe Queen' => 2,
            'Deluxe Suite' => 2,
            'Executive Suite' => 4,
            'Presidential Suite' => 6,
            'Family Room' => 4
        ];
        
        foreach ($defaultOccupancy as $roomType => $defaultOcc) {
            $stmt = $connection->prepare("UPDATE rooms SET max_occupancy = ? WHERE room_type = ? AND (max_occupancy IS NULL OR max_occupancy = 0)");
            $stmt->execute([$defaultOcc, $roomType]);
        }
        
        echo "<p>✅ Updated room occupancy with defaults</p>";
    }
    
    // Set default amenities for rooms that don't have any
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM rooms WHERE amenities IS NULL OR amenities = ''");
    $stmt->execute();
    $nullAmenitiesCount = $stmt->fetch()['count'];
    
    if ($nullAmenitiesCount > 0) {
        echo "<p>Found $nullAmenitiesCount rooms with missing amenities. Setting defaults...</p>";
        
        $defaultAmenities = "Free WiFi, Air Conditioning, TV, Private Bathroom";
        $stmt = $connection->prepare("UPDATE rooms SET amenities = ? WHERE amenities IS NULL OR amenities = ''");
        $stmt->execute([$defaultAmenities]);
        
        echo "<p>✅ Updated room amenities with defaults</p>";
    }
    
    // Show final room data
    echo "<h2>📊 Final Room Data:</h2>";
    $stmt = $connection->prepare("SELECT * FROM rooms LIMIT 10");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    if ($rooms) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 0.9rem;'>";
        echo "<tr>";
        foreach (array_keys($rooms[0]) as $column) {
            if (!is_numeric($column)) {
                echo "<th style='padding: 5px;'>" . $column . "</th>";
            }
        }
        echo "</tr>";
        
        foreach ($rooms as $room) {
            echo "<tr>";
            foreach ($room as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td style='padding: 5px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2>🎉 Database Repair Complete!</h2>";
    echo "<p><strong>Your Room Management system should now work without errors!</strong></p>";
    echo "<p><a href='room_management.php'>🔗 Go to Room Management</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Room Database Repair</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        h1, h2 { color: #2c3e50; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
</body>
</html>