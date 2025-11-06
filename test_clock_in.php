<?php
require_once 'includes/classes.php';
require_once 'includes/employee_classes.php';

// First, let's see if there are any employees
$database = new Database();
$connection = $database->getConnection();

$stmt = $connection->query("SELECT id, first_name, last_name FROM employees LIMIT 5");
$employees = $stmt->fetchAll();

echo "Available employees:\n";
foreach($employees as $emp) {
    echo "  ID: " . $emp['id'] . " - " . $emp['first_name'] . ' ' . $emp['last_name'] . "\n";
}

// Try to clock in employee ID 1
if (count($employees) > 0) {
    $testEmpId = $employees[0]['id'];
    echo "\n\nAttempting to clock in employee ID: $testEmpId\n";
    
    $tcm = new TimeClockManager();
    $result = $tcm->clockIn($testEmpId, 'hotel', 'Test clock in');
    
    echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Message: " . $result['message'] . "\n";
    
    if ($result['success']) {
        echo "Clock ID: " . $result['clock_id'] . "\n";
    }
}
