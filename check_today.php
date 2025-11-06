<?php
require_once 'includes/classes.php';

$database = new Database();
$connection = $database->getConnection();

// Check all records from today
$stmt = $connection->query("SELECT tc.*, e.first_name, e.last_name FROM time_clock tc LEFT JOIN employees e ON tc.employee_id = e.id WHERE DATE(tc.clock_in) = CURDATE() ORDER BY tc.clock_in DESC");
$today = $stmt->fetchAll();

echo "Today's time clock records: " . count($today) . "\n\n";
foreach($today as $r) {
    echo "Entry ID: " . $r['id'] . "\n";
    echo "  Employee: " . ($r['first_name'] ?? 'N/A') . ' ' . ($r['last_name'] ?? 'N/A') . " (ID: " . $r['employee_id'] . ")\n";
    echo "  Clock in: " . $r['clock_in'] . "\n";
    echo "  Clock out: " . ($r['clock_out'] ?? 'NULL') . "\n";
    echo "  Status: " . $r['status'] . "\n\n";
}

// Check last 5 records overall
echo "\n\nLast 5 records overall:\n\n";
$stmt = $connection->query("SELECT tc.*, e.first_name, e.last_name FROM time_clock tc LEFT JOIN employees e ON tc.employee_id = e.id ORDER BY tc.id DESC LIMIT 5");
$recent = $stmt->fetchAll();

foreach($recent as $r) {
    echo "Entry ID: " . $r['id'] . "\n";
    echo "  Employee: " . ($r['first_name'] ?? 'N/A') . ' ' . ($r['last_name'] ?? 'N/A') . " (ID: " . $r['employee_id'] . ")\n";
    echo "  Clock in: " . $r['clock_in'] . "\n";
    echo "  Clock out: " . ($r['clock_out'] ?? 'NULL') . "\n";
    echo "  Status: " . $r['status'] . "\n\n";
}
