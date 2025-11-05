<?php
require_once 'includes/classes.php';
require_once 'includes/employee_classes.php';

$tcm = new TimeClockManager();

// Try to clock out John (ID 1)
echo "Attempting to clock out employee ID 1 (John Doe)...\n\n";

$result = $tcm->clockOut(1, 'Test clock out');

echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Message: " . $result['message'] . "\n";

// Check if he's still showing as working
echo "\n\nChecking currently working employees:\n";
$working = $tcm->getCurrentlyWorking();
echo "Count: " . count($working) . "\n";
foreach($working as $w) {
    echo "  - " . $w['first_name'] . ' ' . $w['last_name'] . "\n";
}

// Check the database record
$database = new Database();
$connection = $database->getConnection();
$stmt = $connection->prepare("SELECT * FROM time_clock WHERE employee_id = 1 ORDER BY clock_in DESC LIMIT 1");
$stmt->execute();
$record = $stmt->fetch();

echo "\n\nLatest record for John Doe:\n";
echo "  Clock In: " . $record['clock_in'] . "\n";
echo "  Clock Out: " . ($record['clock_out'] ?? 'NULL') . "\n";
echo "  Status: " . $record['status'] . "\n";
