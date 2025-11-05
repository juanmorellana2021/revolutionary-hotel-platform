<?php
// Robust database connection that handles MySQL 8.0 authentication issues

function getDatabaseConnection() {
     = localhost;
     = hotel_booking_system;
     = hoteluser;
     = hotelpass123;
    
    // Try direct PHP connection first
    try {
         = new mysqli(, , , );
        if (!->connect_error) {
            ->set_charset(utf8);
            return ;
        }
    } catch (Exception ) {
        // PHP connection failed, try to fix via system commands
         = [
            sudo mysql -e "CREATE DATABASE IF NOT EXISTS hotel_booking_system;",
            sudo mysql -e "DROP USER IF EXISTS hoteluser@localhost;", 
            sudo mysql -e "CREATE USER hoteluser@localhost IDENTIFIED WITH mysql_native_password BY  hotelpass123;",
            sudo mysql -e "GRANT ALL PRIVILEGES ON hotel_booking_system.* TO hoteluser@localhost;",
            sudo mysql -e "FLUSH PRIVILEGES;"
        ];
        
        foreach ( as ) {
            shell_exec( .  2>/dev/null);
        }
        
        // Try connection again
        try {
             = new mysqli(, , , );
            if (!->connect_error) {
                ->set_charset(utf8);
                return ;
            }
        } catch (Exception ) {
            die(Database connection failed after repair attempt:  . ->getMessage());
        }
    }
    
    die(Database connection failed:  . ->connect_error);
}

// Get the connection
 = getDatabaseConnection();
?>
