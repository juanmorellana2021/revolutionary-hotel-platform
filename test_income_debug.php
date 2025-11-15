<?php
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/accounting_classes.php';

$incomeManager = new IncomeManager();
$recentIncome = $incomeManager->getIncome(['limit' => 5]);

echo "Found: " . count($recentIncome) . " income entries\n\n";

foreach ($recentIncome as $income) {
    echo "ID: " . $income['id'] . "\n";
    echo "Type: " . $income['income_type'] . "\n";
    echo "Description: " . $income['description'] . "\n";
    echo "Amount: " . $income['amount'] . " " . $income['currency'] . "\n";
    echo "Date: " . $income['transaction_date'] . "\n";
    echo "Created: " . $income['created_at'] . "\n";
    echo "---\n";
}

if (empty($recentIncome)) {
    echo "ERROR: Query returned empty array!\n";
    echo "Checking raw query...\n";
}
?>
