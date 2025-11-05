<?php
require_once 'includes/classes.php';
require_once 'includes/employee_classes.php';

$tcm = new TimeClockManager();

echo "Attempting to clock out Jane Smith (ID 2)...\n\n";

$result = $tcm->clockOut(2, 'Test clock out');

echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Message: " . $result['message'] . "\n\n";

// Check if she's still showing
$working = $tcm->getCurrentlyWorking();
echo "Currently working: " . count($working) . "\n";

// Check the database
$database = new Database();
$connection = $database->getConnection();
$stmt = $connection->query("SELECT * FROM time_clock WHERE employee_id = 2 ORDER BY id DESC LIMIT 1");
$record = $stmt->fetch();

echo "\nLatest record for Jane:\n";
echo "  Clock In: " . $record['clock_in'] . "\n";
echo "  Clock Out: " . ($record['clock_out'] ?? 'NULL') . "\n";
echo "  Status: " . $record['status'] . "\n";
