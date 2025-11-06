<?php
echo <h1>Database Fix Tool</h1>;
echo <p>Running MySQL commands...</p>;

 = [
    sudo mysql -e "DROP USER IF EXISTS hoteluser@localhost;",
    sudo mysql -e "CREATE DATABASE IF NOT EXISTS hotel_booking_system;", 
    sudo mysql -e "CREATE USER hoteluser@localhost IDENTIFIED WITH mysql_native_password BY  \hotelpass123\';",
    sudo mysql -e "GRANT ALL PRIVILEGES ON hotel_booking_system.* TO hoteluser@localhost;",
    sudo mysql -e "FLUSH PRIVILEGES;"
];

foreach ( as ) {
    echo <p>Running:  . htmlspecialchars() . </p>;
     = shell_exec( .  2>&1);
    if () echo <pre> . htmlspecialchars() . </pre>;
}

try {
     = new PDO(mysql:host=localhost;dbname=hotel_booking_system, hoteluser, hotelpass123);
    echo <h2 style="color: green;"> Database Connected Successfully!</h2>;
    
     = <?php\n$conn = new mysqli(localhost, hoteluser, hotelpass123, hotel_booking_system);\nif($conn->connect_error) die(Connection failed:  . $conn->connect_error);\n$conn->set_charset( utf8);\n?>;
    file_put_contents(/var/www/html/manage/db_connection.php, );
    
    echo <p><strong>Database connection file updated!</strong></p>;
    echo <p><a href="/manage/" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;"> Launch Hotel System</a></p>;
    
} catch (Exception ) {
    echo <h2 style="color: red;"> Connection Failed:  . ->getMessage() . </h2>;
}
?>
