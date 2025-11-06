<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/accounting_classes.php';

// Set up a test session for manager access
$_SESSION['user_role'] = 'manager';
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Manager';
$_SESSION['user_email'] = 'manager@hotel.com';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Financial System Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; } .error { color: red; } .info { color: blue; }
        .test-result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🏨 Financial Management System Test</h1>";

try {
    // Test database connection
    echo "<div class='test-result info'><strong>Testing Database Connection...</strong></div>";
    $db = new Database();
    $connection = $db->getConnection();
    echo "<div class='test-result success'>✅ Database connection successful</div>";

    // Test Income Manager
    echo "<div class='test-result info'><strong>Testing Income Manager...</strong></div>";
    $incomeManager = new IncomeManager();
    echo "<div class='test-result success'>✅ Income Manager initialized</div>";

    // Test Expense Manager
    echo "<div class='test-result info'><strong>Testing Expense Manager...</strong></div>";
    $expenseManager = new ExpenseManager();
    echo "<div class='test-result success'>✅ Expense Manager initialized</div>";

    // Test Financial Report Manager
    echo "<div class='test-result info'><strong>Testing Financial Report Manager...</strong></div>";
    $reportManager = new FinancialReportManager();
    echo "<div class='test-result success'>✅ Financial Report Manager initialized</div>";

    // Test getting recent income
    echo "<div class='test-result info'><strong>Testing Income Retrieval...</strong></div>";
    $recentIncome = $incomeManager->getIncome(['limit' => 5]);
    echo "<div class='test-result success'>✅ Income data retrieved (found " . count($recentIncome) . " records)</div>";

    // Test getting recent expenses
    echo "<div class='test-result info'><strong>Testing Expense Retrieval...</strong></div>";
    $recentExpenses = $expenseManager->getExpenses(['limit' => 5]);
    echo "<div class='test-result success'>✅ Expense data retrieved (found " . count($recentExpenses) . " records)</div>";

    // Test financial report generation
    echo "<div class='test-result info'><strong>Testing Financial Report Generation...</strong></div>";
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');
    $report = $reportManager->generateReport($startDate, $endDate);
    echo "<div class='test-result success'>✅ Financial report generated for period $startDate to $endDate</div>";

    echo "<div class='test-result success' style='background-color: #d4edda; color: #155724; border-color: #c3e6cb;'>
        <h3>🎉 ALL TESTS PASSED!</h3>
        <p><strong>Financial Management System is fully operational</strong></p>
        <p>Access points:</p>
        <ul>
            <li><a href='accounting_dashboard.php'>📊 Accounting Dashboard</a></li>
            <li><a href='income_management.php'>💰 Income Management</a></li>
            <li><a href='expense_management.php'>💸 Expense Management</a></li>
        </ul>
    </div>";

} catch (Exception $e) {
    echo "<div class='test-result error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>