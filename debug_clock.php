<?php
require_once 'includes/classes.php';
require_once 'includes/employee_classes.php';

$tcm = new TimeClockManager();
$working = $tcm->getCurrentlyWorking();

echo "Currently working employees: " . count($working) . "\n\n";

foreach($working as $w) {
    echo "ID: " . $w['id'] . " - " . $w['first_name'] . ' ' . $w['last_name'] . "\n";
    echo "  Clocked in: " . $w['clock_in'] . "\n";
    echo "  On break: " . ($w['on_break'] ? 'Yes' : 'No') . "\n\n";
}

// Also check recent clock ins
$database = new Database();
$connection = $database->getConnection();
$stmt = $connection->query("SELECT tc.*, e.first_name, e.last_name FROM time_clock tc JOIN employees e ON tc.employee_id = e.id WHERE tc.clock_out IS NULL ORDER BY tc.clock_in DESC LIMIT 10");
$allOpen = $stmt->fetchAll();

echo "\n\nAll open time_clock records: " . count($allOpen) . "\n\n";
foreach($allOpen as $record) {
    echo "ID: " . $record['employee_id'] . " - " . $record['first_name'] . ' ' . $record['last_name'] . "\n";
    echo "  Clock in: " . $record['clock_in'] . "\n";
    echo "  Status: " . $record['status'] . "\n\n";
}
