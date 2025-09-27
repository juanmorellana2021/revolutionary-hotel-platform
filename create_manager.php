<?php
// Create Manager Account Script
// Run this once to create the manager account with proper password

require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    // Check if manager already exists
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['manager@hotel.com']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing manager with new password
        $hashedPassword = password_hash('manager123', PASSWORD_DEFAULT);
        $stmt = $connection->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
        $result = $stmt->execute([$hashedPassword, 'manager', 'manager@hotel.com']);
        
        if ($result) {
            echo "<h2>✅ Manager account updated successfully!</h2>";
            echo "<p><strong>Email:</strong> manager@hotel.com</p>";
            echo "<p><strong>Password:</strong> manager123</p>";
            echo "<p><strong>Role:</strong> manager</p>";
        } else {
            echo "<h2>❌ Failed to update manager account</h2>";
        }
    } else {
        // Create new manager account
        $hashedPassword = password_hash('manager123', PASSWORD_DEFAULT);
        $stmt = $connection->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute(['Hotel', 'Manager', 'manager@hotel.com', $hashedPassword, 'manager']);
        
        if ($result) {
            echo "<h2>✅ Manager account created successfully!</h2>";
            echo "<p><strong>Email:</strong> manager@hotel.com</p>";
            echo "<p><strong>Password:</strong> manager123</p>";
            echo "<p><strong>Role:</strong> manager</p>";
        } else {
            echo "<h2>❌ Failed to create manager account</h2>";
        }
    }
    
    // Show all users for verification
    echo "<hr>";
    echo "<h3>All Users in Database:</h3>";
    $stmt = $connection->prepare("SELECT id, first_name, last_name, email, role FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['first_name'] . " " . $user['last_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Manager Account</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin-top: 10px; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Manager Account Creation</h1>
    <p><a href="index.php">← Back to Login Page</a></p>
</body>
</html>