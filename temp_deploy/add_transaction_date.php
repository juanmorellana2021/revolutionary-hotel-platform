<?php
$connection = new mysqli("localhost", "hoteluser", "hotelpass123", "hotel_booking_system");

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

echo "<h3>Adding missing transaction_date columns...</h3>";

// Add transaction_date to income table
$addIncomeCol = "ALTER TABLE income ADD COLUMN IF NOT EXISTS transaction_date DATE";
if ($connection->query($addIncomeCol)) {
    echo "✅ Added transaction_date to income table<br>";
} else {
    echo "❌ Error adding transaction_date to income: " . $connection->error . "<br>";
}

// Add transaction_date to expenses table  
$addExpenseCol = "ALTER TABLE expenses ADD COLUMN IF NOT EXISTS transaction_date DATE";
if ($connection->query($addExpenseCol)) {
    echo "✅ Added transaction_date to expenses table<br>";
} else {
    echo "❌ Error adding transaction_date to expenses: " . $connection->error . "<br>";
}

// Update existing records to have transaction_date = date
$updateIncome = "UPDATE income SET transaction_date = date WHERE transaction_date IS NULL";
if ($connection->query($updateIncome)) {
    echo "✅ Updated income records with transaction_date<br>";
} else {
    echo "❌ Error updating income records: " . $connection->error . "<br>";
}

$updateExpenses = "UPDATE expenses SET transaction_date = date WHERE transaction_date IS NULL";
if ($connection->query($updateExpenses)) {
    echo "✅ Updated expense records with transaction_date<br>";
} else {
    echo "❌ Error updating expense records: " . $connection->error . "<br>";
}

echo "<br><a href='/manage/test_manager_dashboard.php'>Test Manager Dashboard Again</a>";
?>