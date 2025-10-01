<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/accounting_classes.php';

// Get database connection
$database = new Database();
$connection = $database->getConnection();

echo "<h2>Backfilling Income Records for Existing Bookings</h2>";

// Get bookings that don't have corresponding income records
$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type 
    FROM bookings b 
    LEFT JOIN income i ON b.id = i.booking_id 
    LEFT JOIN rooms r ON b.room_id = r.id
    WHERE i.booking_id IS NULL AND b.status = 'confirmed'
");
$stmt->execute();
$bookingsToBackfill = $stmt->fetchAll();

echo "<p>Found " . count($bookingsToBackfill) . " bookings without income records.</p>";

$backfillCount = 0;
foreach ($bookingsToBackfill as $booking) {
    // Insert income record directly
    $incomeStmt = $connection->prepare("
        INSERT INTO income (booking_id, income_type, description, amount, payment_method, payment_status, transaction_date, created_by, notes, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $description = 'Room ' . ($booking['room_number'] ?? $booking['room_id']) . ' - ' . ($booking['room_type'] ?? 'Booking');
    $paymentMethod = $booking['payment_method'] ?? 'cash';
    $paymentStatus = $booking['payment_status'] ?? 'paid';
    $notes = 'Backfilled from booking #' . $booking['id'];
    $createdBy = 1; // Assuming admin user ID is 1
    
    $success = $incomeStmt->execute([
        $booking['id'],
        'room_booking',
        $description,
        $booking['total_price'],
        $paymentMethod,
        $paymentStatus,
        $booking['check_in_date'],
        $createdBy,
        $notes
    ]);
    
    if ($success) {
        $backfillCount++;
        echo "<p>✅ Created income record for booking #{$booking['id']} - {$booking['guest_name']} - $" . number_format($booking['total_price'], 2) . "</p>";
    } else {
        echo "<p>❌ Failed to create income record for booking #{$booking['id']}</p>";
    }
}

echo "<h3>Backfill completed: {$backfillCount} income records created.</h3>";
echo '<p><a href="income_management.php">Go to Income Management</a></p>';
?>