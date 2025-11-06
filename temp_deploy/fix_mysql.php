<?php
echo <h1>MySQL Authentication Fix</h1>;

// First check current MySQL setup
try {
    // Try connecting as root with socket authentication (as system root user)
     = [
        DROP USER IF EXISTS hoteluser@localhost,
        CREATE USER hoteluser@localhost IDENTIFIED WITH mysql_native_password BY "hotelpass123",
        GRANT ALL PRIVILEGES ON hotel_booking_system.* TO hoteluser@localhost,
        FLUSH PRIVILEGES
    ];
    
    foreach ( as ) {
         = shell_exec(sudo mysql -e "" 2>&1);
        echo <p>Command: </p>;
        echo <pre></pre>;
    }
    
    echo <p style="color: green;">MySQL user setup attempted!</p>;
    
    // Now test the connection
    try {
         = new PDO(mysql:host=localhost;dbname=hotel_booking_system, hoteluser, hotelpass123);
        echo <p style="color: green;"> Hotel user connection successful!</p>;
    } catch (Exception ) {
        echo <p style="color: red;"> Hotel user connection failed:  . ->getMessage() . </p>;
    }
    
} catch (Exception ) {
    echo <p style="color: red;">Error:  . ->getMessage() . </p>;
}
?>
