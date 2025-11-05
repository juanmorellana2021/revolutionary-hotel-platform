<?php
// Database Structure Checker for Rooms Table
require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    echo "<h1>Rooms Table Structure Check</h1>";
    
    // Check current rooms table structure
    echo "<h2>Current Rooms Table Structure:</h2>";
    $stmt = $connection->prepare("DESCRIBE rooms");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasDescription = false;
    $hasFeatures = false;
    $hasImagePath = false;
    $hasAmenities = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'description') $hasDescription = true;
        if ($column['Field'] === 'features') $hasFeatures = true;
        if ($column['Field'] === 'image_path') $hasImagePath = true;
        if ($column['Field'] === 'amenities') $hasAmenities = true;
    }
    echo "</table>";
    
    echo "<hr><h2>🔧 Adding Missing Columns...</h2>";
    
    // Add missing columns
    if (!$hasDescription) {
        echo "<p>Adding 'description' column...</p>";
        $stmt = $connection->prepare("ALTER TABLE rooms ADD COLUMN description TEXT");
        if ($stmt->execute()) {
            echo "<p>✅ Description column added successfully!</p>";
        } else {
            echo "<p>❌ Failed to add description column</p>";
        }
    } else {
        echo "<p>✅ Description column already exists</p>";
    }
    
    if (!$hasFeatures) {
        echo "<p>Adding 'features' column...</p>";
        $stmt = $connection->prepare("ALTER TABLE rooms ADD COLUMN features TEXT");
        if ($stmt->execute()) {
            echo "<p>✅ Features column added successfully!</p>";
        } else {
            echo "<p>❌ Failed to add features column</p>";
        }
    } else {
        echo "<p>✅ Features column already exists</p>";
    }
    
    if (!$hasImagePath) {
        echo "<p>Adding 'image_path' column...</p>";
        $stmt = $connection->prepare("ALTER TABLE rooms ADD COLUMN image_path VARCHAR(255)");
        if ($stmt->execute()) {
            echo "<p>✅ Image path column added successfully!</p>";
        } else {
            echo "<p>❌ Failed to add image path column</p>";
        }
    } else {
        echo "<p>✅ Image path column already exists</p>";
    }
    
    // Show final table structure
    echo "<hr><h2>Final Rooms Table Structure:</h2>";
    $stmt = $connection->prepare("DESCRIBE rooms");
    $stmt->execute();
    $finalColumns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($finalColumns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show some sample data
    echo "<hr><h2>Current Rooms Data:</h2>";
    $stmt = $connection->prepare("SELECT * FROM rooms LIMIT 5");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    if ($rooms) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($rooms[0]) as $column) {
            if (!is_numeric($column)) {
                echo "<th>" . $column . "</th>";
            }
        }
        echo "</tr>";
        
        foreach ($rooms as $room) {
            echo "<tr>";
            foreach ($room as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No rooms found in database.</p>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Database update complete!</h2>";
    echo "<p><strong>Room Management is now ready to use!</strong></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rooms Database Fixer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <p><a href="room_management.php">← Go to Room Management</a></p>
</body>
</html>