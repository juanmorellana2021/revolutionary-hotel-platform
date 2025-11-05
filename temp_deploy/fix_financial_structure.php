<?php
$connection = new mysqli("localhost", "hoteluser", "hotelpass123", "hotel_booking_system");
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

echo "<h3>Financial Tables Structure Fix</h3>";

// Drop and recreate income table with proper structure
$dropIncome = "DROP TABLE IF EXISTS income";
$createIncome = "CREATE TABLE income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PEN',
    payment_method VARCHAR(50) DEFAULT 'cash',
    payment_status VARCHAR(20) DEFAULT 'paid',
    transaction_date DATE NOT NULL,
    guest_name VARCHAR(100),
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    booking_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($connection->query($dropIncome) && $connection->query($createIncome)) {
    echo "✅ Income table recreated with proper structure<br>";
} else {
    echo "❌ Error recreating income table: " . $connection->error . "<br>";
}

// Drop and recreate expenses table with proper structure  
$dropExpenses = "DROP TABLE IF EXISTS expenses";
$createExpenses = "CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PEN',
    category VARCHAR(100),
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($connection->query($dropExpenses) && $connection->query($createExpenses)) {
    echo "✅ Expenses table recreated with proper structure<br>";
} else {
    echo "❌ Error recreating expenses table: " . $connection->error . "<br>";
}

// Insert sample data with proper columns
$insertIncome = "INSERT INTO income (description, amount, transaction_date, guest_name, guest_email) VALUES 
    ('Room Booking Payment', 150.00, CURDATE(), 'John Doe', 'john@example.com'),
    ('Restaurant Service', 45.00, CURDATE(), 'Jane Smith', 'jane@example.com'),
    ('Laundry Service', 25.00, CURDATE(), 'Bob Johnson', 'bob@example.com')";

$insertExpenses = "INSERT INTO expenses (description, amount, category, transaction_date) VALUES 
    ('Office Supplies', 30.00, 'Operations', CURDATE()),
    ('Maintenance Repair', 80.00, 'Maintenance', CURDATE()),
    ('Utility Bills', 120.00, 'Utilities', CURDATE())";

if ($connection->query($insertIncome)) {
    echo "✅ Sample income data inserted<br>";
} else {
    echo "❌ Error inserting income data: " . $connection->error . "<br>";
}

if ($connection->query($insertExpenses)) {
    echo "✅ Sample expense data inserted<br>";
} else {
    echo "❌ Error inserting expense data: " . $connection->error . "<br>";
}

echo "<br><a href='/manage/test_manager_dashboard.php'>Test Manager Dashboard Again</a>";
?>