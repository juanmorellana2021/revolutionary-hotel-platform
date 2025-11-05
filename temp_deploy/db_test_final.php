<?php
echo <h1>Final Database Connection Test & Fix</h1>;

// Test 1: Try connecting with hoteluser
echo <h2>Test 1: Direct hoteluser connection</h2>;
try {
     = new PDO(mysql:host=localhost;dbname=hotel_booking_system, hoteluser, hotelpass123);
    echo <p style="color: green;"> hoteluser connection SUCCESSFUL!</p>;
    
    // Test creating tables
    ->exec(CREATE TABLE IF NOT EXISTS connection_test (id INT PRIMARY KEY AUTO_INCREMENT, test_data VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP));
    ->exec(INSERT INTO connection_test (test_data) VALUES ("Connection test successful at  . date(Y-m-d H:i:s) . "));
    echo <p style="color: green;"> Table creation and data insertion successful!</p>;
    
} catch (PDOException ) {
    echo <p style="color: red;"> hoteluser connection failed:  . ->getMessage() . </p>;
    
    // Test 2: Try to fix using sudo mysql commands
    echo <h2>Test 2: Attempting to fix using system commands</h2>;
    
     = array(
        sudo mysql -e "DROP USER IF EXISTS hoteluser@localhost;",
        sudo mysql -e "CREATE USER hoteluser@localhost IDENTIFIED WITH mysql_native_password BY  \hotelpass123\';",
        sudo mysql -e "GRANT ALL PRIVILEGES ON hotel_booking_system.* TO hoteluser@localhost;",
        sudo mysql -e "FLUSH PRIVILEGES;"
    );
    
    foreach ( as ) {
        echo <p>Executing:  . htmlspecialchars() . </p>;
         = shell_exec( .  2>&1);
        echo <pre> . htmlspecialchars() . </pre>;
    }
    
    // Test 3: Try connection again
    echo <h2>Test 3: Retry connection after fix</h2>;
    try {
         = new PDO(mysql:host=localhost;dbname=hotel_booking_system, hoteluser, hotelpass123);
        echo <p style="color: green;"> Connection successful after fix!</p>;
        
        // Test creating tables
        ->exec(CREATE TABLE IF NOT EXISTS connection_test (id INT PRIMARY KEY AUTO_INCREMENT, test_data VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP));
        echo <p style="color: green;"> Database operations working!</p>;
        
    } catch (PDOException ) {
        echo <p style="color: red;"> Still failed after fix:  . ->getMessage() . </p>;
        
        // Test 4: Check what users actually exist
        echo <h2>Test 4: Checking existing users</h2>;
         = shell_exec(sudo mysql -e "SELECT User, Host, plugin FROM mysql.user;" 2>&1);
        echo <pre> . htmlspecialchars() . </pre>;
    }
}

echo <h2>Final Status Check</h2>;
// Try one more time to ensure everything is working
try {
     = new PDO(mysql:host=localhost;dbname=hotel_booking_system, hoteluser, hotelpass123);
    echo <p style="color: green; font-weight: bold; font-size: 18px;"> SUCCESS! Database connection is now working!</p>;
    echo <p>You can now use the hotel booking system normally.</p>;
} catch (PDOException ) {
    echo <p style="color: red; font-weight: bold; font-size: 18px;"> STILL FAILED:  . ->getMessage() . </p>;
}
?>
