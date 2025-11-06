<?php
session_start();
$_SESSION['csrf_token'] = 'test123'; // Simulate a session token

require_once 'includes/classes.php';
require_once 'includes/employee_classes.php';

// Simulate form POST for John Doe (ID 1)
$_POST['clock_in'] = true;
$_POST['employee_id'] = '2'; // Jane Smith
$_POST['notes'] = 'Test via script';
$_POST['csrf_token'] = 'test123';

$timeClockManager = new TimeClockManager();

echo "Simulating clock in for employee ID 2 (Jane Smith)...\n\n";

$result = $timeClockManager->clockIn(2, 'hotel', 'Test via script');

echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Message: " . $result['message'] . "\n";

if ($result['success']) {
    echo "Clock ID: " . $result['clock_id'] . "\n";
    
    // Verify it was created
    $working = $timeClockManager->getCurrentlyWorking();
    echo "\nCurrently working: " . count($working) . "\n";
    foreach($working as $w) {
        echo "  - " . $w['first_name'] . ' ' . $w['last_name'] . "\n";
    }
}
