<?php
echo "PHP is working!<br>";

// Test database connection
try {
    require_once 'includes/classes.php';
    echo "Classes loaded successfully!<br>";
    
    $database = new Database();
    $connection = $database->getConnection();
    echo "Database connection successful!<br>";
    
    // Test session
    session_start();
    echo "Session started successfully!<br>";
    
    if (isset($_SESSION['user'])) {
        echo "User is logged in: " . $_SESSION['user']['first_name'] . "<br>";
    } else {
        echo "User is not logged in<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='room_management.php'>Try Room Management</a>";
?>