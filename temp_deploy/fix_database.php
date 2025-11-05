<?php
// Database Structure Checker and Fixer
require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    echo "<h1>Database Structure Check</h1>";
    
    // Check current users table structure
    echo "<h2>Current Users Table Structure:</h2>";
    $stmt = $connection->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasRoleColumn = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'role') {
            $hasRoleColumn = true;
        }
    }
    echo "</table>";
    
    // Add role column if it doesn't exist
    if (!$hasRoleColumn) {
        echo "<h2>🔧 Adding Missing Role Column...</h2>";
        
        $alterStmt = $connection->prepare("
            ALTER TABLE users 
            ADD COLUMN role ENUM('guest', 'manager', 'admin') DEFAULT 'guest' 
            AFTER password
        ");
        
        if ($alterStmt->execute()) {
            echo "<p>✅ Role column added successfully!</p>";
            
            // Update existing users to have 'guest' role by default
            $updateStmt = $connection->prepare("UPDATE users SET role = 'guest' WHERE role IS NULL");
            $updateStmt->execute();
            
            echo "<p>✅ Existing users updated with 'guest' role</p>";
        } else {
            echo "<p>❌ Failed to add role column</p>";
        }
    } else {
        echo "<h2>✅ Role column already exists</h2>";
    }
    
    // Now create/update manager account
    echo "<hr><h2>Creating Manager Account...</h2>";
    
    // Check if manager exists
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['manager@hotel.com']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing manager
        $hashedPassword = password_hash('manager123', PASSWORD_DEFAULT);
        $stmt = $connection->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
        $result = $stmt->execute([$hashedPassword, 'manager', 'manager@hotel.com']);
        
        if ($result) {
            echo "<p>✅ Manager account updated successfully!</p>";
        } else {
            echo "<p>❌ Failed to update manager account</p>";
        }
    } else {
        // Create new manager
        $hashedPassword = password_hash('manager123', PASSWORD_DEFAULT);
        $stmt = $connection->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute(['Hotel', 'Manager', 'manager@hotel.com', $hashedPassword, 'manager']);
        
        if ($result) {
            echo "<p>✅ Manager account created successfully!</p>";
        } else {
            echo "<p>❌ Failed to create manager account</p>";
        }
    }
    
    // Show final users table
    echo "<hr><h2>All Users:</h2>";
    $stmt = $connection->prepare("SELECT id, first_name, last_name, email, role, created_at FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['first_name'] . " " . $user['last_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td><strong>" . $user['role'] . "</strong></td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Database is now ready!</h2>";
    echo "<p><strong>Manager Login:</strong></p>";
    echo "<p><strong>Email:</strong> manager@hotel.com</p>";
    echo "<p><strong>Password:</strong> manager123</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Fixer</title>
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
    <p><a href="index.php">← Back to Login Page</a></p>
</body>
</html>