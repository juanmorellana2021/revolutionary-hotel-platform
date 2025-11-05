<?php
$connection = new mysqli("localhost", "hoteluser", "hotelpass123", "hotel_booking_system");

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

echo "<h3>Creating missing financial tables...</h3>";

// Create income table
$createIncome = "CREATE TABLE IF NOT EXISTS income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PEN',
    date DATE NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($connection->query($createIncome)) {
    echo "✅ Income table created successfully<br>";
} else {
    echo "❌ Error creating income table: " . $connection->error . "<br>";
}

// Create expenses table
$createExpenses = "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PEN',
    date DATE NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($connection->query($createExpenses)) {
    echo "✅ Expenses table created successfully<br>";
} else {
    echo "❌ Error creating expenses table: " . $connection->error . "<br>";
}

// Insert sample data
$insertSampleIncome = "INSERT INTO income (description, amount, date, category) VALUES 
    ('Room Booking Payment', 150.00, CURDATE(), 'Booking'),
    ('Restaurant Service', 45.00, CURDATE(), 'Food'),
    ('Laundry Service', 25.00, CURDATE(), 'Service')";

if ($connection->query($insertSampleIncome)) {
    echo "✅ Sample income data inserted<br>";
} else {
    echo "❌ Error inserting income data: " . $connection->error . "<br>";
}

$insertSampleExpenses = "INSERT INTO expenses (description, amount, date, category) VALUES 
    ('Office Supplies', 30.00, CURDATE(), 'Operations'),
    ('Maintenance Repair', 80.00, CURDATE(), 'Maintenance'),
    ('Utility Bills', 120.00, CURDATE(), 'Utilities')";

if ($connection->query($insertSampleExpenses)) {
    echo "✅ Sample expense data inserted<br>";
} else {
    echo "❌ Error inserting expense data: " . $connection->error . "<br>";
}

echo "<br><a href='/manage/test_manager_dashboard.php'>Test Manager Dashboard Again</a>";
?>