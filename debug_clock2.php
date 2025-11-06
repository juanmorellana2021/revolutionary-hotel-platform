<?php
require_once 'includes/classes.php';

$database = new Database();
$connection = $database->getConnection();

// Check total records
$stmt = $connection->query("SELECT COUNT(*) as total FROM time_clock");
$count = $stmt->fetch();
echo "Total time_clock records: " . $count['total'] . "\n\n";

// Check recent records (last 5)
$stmt = $connection->query("SELECT tc.*, e.first_name, e.last_name FROM time_clock tc LEFT JOIN employees e ON tc.employee_id = e.id ORDER BY tc.clock_in DESC LIMIT 5");
$recent = $stmt->fetchAll();

echo "Last 5 time clock records:\n\n";
foreach($recent as $r) {
    echo "Entry ID: " . $r['id'] . "\n";
    echo "  Employee: " . ($r['first_name'] ?? 'N/A') . ' ' . ($r['last_name'] ?? 'N/A') . " (ID: " . $r['employee_id'] . ")\n";
    echo "  Clock in: " . $r['clock_in'] . "\n";
    echo "  Clock out: " . ($r['clock_out'] ?? 'NULL') . "\n";
    echo "  Status: " . $r['status'] . "\n\n";
}

// Check database name
$stmt = $connection->query("SELECT DATABASE() as db");
$db = $stmt->fetch();
echo "\nCurrent database: " . $db['db'] . "\n";
