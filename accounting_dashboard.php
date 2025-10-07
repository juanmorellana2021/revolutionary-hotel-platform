<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/accounting_classes.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userManager = new UserManager();
if (!$userManager->isManager($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

$incomeManager = new IncomeManager();
$expenseManager = new ExpenseManager();
$reportManager = new FinancialReportManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Currency conversion settings
$USD_TO_PEN_RATE = 3.50; // Fixed exchange rate

function formatCurrencyDual($amount, $usdToPenRate = 3.50) {
    $penAmount = $amount * $usdToPenRate;
    return [
        'pen' => $penAmount,
        'usd' => $amount,
        'pen_formatted' => 'S/ ' . number_format($penAmount, 2),
        'usd_formatted' => '$' . number_format($amount, 2)
    ];
}

// Get date range for reports (default to current month)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Generate financial report
$financialReport = $reportManager->generateReport($startDate, $endDate);

// Get recent income and expenses
$recentIncome = $incomeManager->getIncome(['limit' => 5]);
$recentExpenses = $expenseManager->getExpenses(['limit' => 5]);

// Handle quick add forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_income'])) {
        $result = $incomeManager->addIncome([
            'income_type' => $_POST['income_type'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'currency' => $_POST['currency'] ?? 'PEN',
            'payment_method' => $_POST['payment_method'],
            'transaction_date' => $_POST['transaction_date'],
            'created_by' => $_SESSION['user']['id'],
            'notes' => $_POST['notes'] ?? ''
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        // Refresh data
        if ($result['success']) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?start_date=' . $startDate . '&end_date=' . $endDate);
            exit;
        }
    }
    
    if (isset($_POST['add_expense'])) {
        $result = $expenseManager->addExpense([
            'expense_category' => $_POST['expense_category'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'currency' => $_POST['currency'] ?? 'PEN',
            'payment_method' => $_POST['payment_method'],
            'vendor_name' => $_POST['vendor_name'] ?? '',
            'expense_date' => $_POST['expense_date'],
            'paid_by' => $_SESSION['user']['id'],
            'notes' => $_POST['notes'] ?? ''
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        // Refresh data
        if ($result['success']) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?start_date=' . $startDate . '&end_date=' . $endDate);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Dashboard - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
    
    <!-- AINI Innovations Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="favicon.png">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-item {
            position: relative;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 8px;
            top: 100%;
            left: 0;
            margin-top: 5px;
        }

        .dropdown-content a {
            color: #333 !important;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border-radius: 0;
            transition: background-color 0.3s;
        }

        .dropdown-content a:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .dropdown-content a:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-toggle::after {
            content: '▼';
            font-size: 0.8em;
            margin-left: 5px;
        }

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .date-filter {
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        .date-filter input {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-number.positive {
            color: #28a745;
        }

        .stat-number.negative {
            color: #dc3545;
        }

        .stat-number.warning {
            color: #fd7e14;
        }

        .stat-number.neutral {
            color: #007bff;
        }

        /* Dual Currency Display Styles */
        .primary-currency {
            font-size: 2rem;
            font-weight: bold;
            line-height: 1.2;
        }

        .secondary-currency {
            font-size: 1.2rem;
            font-weight: normal;
            opacity: 0.7;
            margin-top: 5px;
        }

        /* Currency flag indicators */
        .primary-currency::before {
            content: "🇵🇪 ";
            font-size: 1rem;
            margin-right: 5px;
        }

        .secondary-currency::before {
            content: "🇺🇸 ";
            font-size: 0.8rem;
            margin-right: 3px;
        }

        /* Exchange Rate Badge */
        .currency-info {
            text-align: center;
            margin: 15px 0;
        }

        .exchange-rate-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .exchange-rate-badge small {
            opacity: 0.8;
            margin-left: 5px;
        }

        .currency-flag {
            font-size: 1.2rem;
            margin-right: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }

        .quick-add-form {
            display: grid;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .transactions-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f8f9fa;
            transition: background 0.3s;
        }

        .transaction-item:hover {
            background: #f8f9fa;
        }

        .transaction-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .transaction-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .transaction-amount {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .transaction-amount.income {
            color: #28a745;
        }

        .transaction-amount.expense {
            color: #dc3545;
        }

        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .date-filter {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">💰 Accounting Dashboard</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">🏠 Dashboard</a>
                
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">🏨 Hotel</a>
                    <div class="dropdown-content">
                        <a href="hotel_setup.php">🏨 Hotel Setup</a>
                        <a href="room_management.php">🛏️ Room Management</a>
                        <a href="calendar_view.php">📅 Calendar View</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">👥 Staff</a>
                    <div class="dropdown-content">
                        <a href="employee_management.php">👥 Employee Management</a>
                        <a href="time_clock.php">⏰ Time Clock</a>
                        <a href="payroll_management.php">💰 Payroll</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="dropdown-toggle active">💰 Finance</a>
                    <div class="dropdown-content">
                        <a href="accounting_dashboard.php">📊 Accounting Dashboard</a>
                        <a href="income_management.php">💰 Income Management</a>
                        <a href="expense_management.php">💸 Expense Management</a>
                    </div>
                </div>

                <a href="dashboard.php">👁️ Guest View</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>💰 Financial Overview</h1>
            <p>Manage your hotel's income, expenses, and financial reports</p>
            
            <div class="currency-info">
                <div class="exchange-rate-badge">
                    <span class="currency-flag">🇵🇪</span> 1 USD = S/ <?php echo number_format($USD_TO_PEN_RATE, 2); ?> PEN
                    <small>(Fixed Rate)</small>
                </div>
            </div>
            
            <form method="GET" class="date-filter">
                <label>
                    From: <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                </label>
                <label>
                    To: <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                </label>
                <button type="submit" class="btn btn-primary">📊 Update Report</button>
            </form>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Financial Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <?php $totalIncome = formatCurrencyDual($financialReport['total_income'], $USD_TO_PEN_RATE); ?>
                <div class="stat-number positive">
                    <div class="primary-currency"><?php echo $totalIncome['pen_formatted']; ?></div>
                    <div class="secondary-currency"><?php echo $totalIncome['usd_formatted']; ?></div>
                </div>
                <div class="stat-label">Total Income</div>
            </div>
            <div class="stat-card">
                <?php $totalExpenses = formatCurrencyDual($financialReport['total_expenses'], $USD_TO_PEN_RATE); ?>
                <div class="stat-number negative">
                    <div class="primary-currency"><?php echo $totalExpenses['pen_formatted']; ?></div>
                    <div class="secondary-currency"><?php echo $totalExpenses['usd_formatted']; ?></div>
                </div>
                <div class="stat-label">Current Expenses</div>
            </div>
            <?php if (isset($financialReport['future_expenses']) && $financialReport['future_expenses'] > 0): ?>
            <div class="stat-card">
                <?php $futureExpenses = formatCurrencyDual($financialReport['future_expenses'], $USD_TO_PEN_RATE); ?>
                <div class="stat-number warning">
                    <div class="primary-currency"><?php echo $futureExpenses['pen_formatted']; ?></div>
                    <div class="secondary-currency"><?php echo $futureExpenses['usd_formatted']; ?></div>
                </div>
                <div class="stat-label">Future Expenses</div>
            </div>
            <?php endif; ?>
            <div class="stat-card">
                <?php $netProfit = formatCurrencyDual($financialReport['net_profit'], $USD_TO_PEN_RATE); ?>
                <div class="stat-number <?php echo $financialReport['net_profit'] >= 0 ? 'positive' : 'negative'; ?>">
                    <div class="primary-currency"><?php echo $netProfit['pen_formatted']; ?></div>
                    <div class="secondary-currency"><?php echo $netProfit['usd_formatted']; ?></div>
                </div>
                <div class="stat-label">Net Profit</div>
            </div>
            <div class="stat-card">
                <div class="stat-number neutral"><?php echo $financialReport['occupancy_rate']; ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
            <div class="stat-card">
                <?php $roomRevenue = formatCurrencyDual($financialReport['room_revenue'], $USD_TO_PEN_RATE); ?>
                <div class="stat-number neutral">
                    <div class="primary-currency"><?php echo $roomRevenue['pen_formatted']; ?></div>
                    <div class="secondary-currency"><?php echo $roomRevenue['usd_formatted']; ?></div>
                </div>
                <div class="stat-label">Room Revenue</div>
            </div>
            <div class="stat-card">
                <?php $avgDailyRate = formatCurrencyDual($financialReport['average_daily_rate'], $USD_TO_PEN_RATE); ?>
                <div class="stat-number neutral">
                    <div class="primary-currency"><?php echo $avgDailyRate['pen_formatted']; ?></div>
                    <div class="secondary-currency"><?php echo $avgDailyRate['usd_formatted']; ?></div>
                </div>
                <div class="stat-label">Average Daily Rate</div>
            </div>
        </div>

        <!-- Quick Add Forms -->
        <div class="content-grid">
            <!-- Quick Add Income -->
            <div class="section">
                <h2>💰 Quick Add Income</h2>
                <form method="POST" class="quick-add-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_type">Income Type</label>
                            <select id="income_type" name="income_type" required>
                                <option value="room_booking">Room Booking</option>
                                <option value="extra_bed">Extra Bed</option>
                                <option value="food_beverage">Food & Beverage</option>
                                <option value="laundry">Laundry</option>
                                <option value="spa">Spa Services</option>
                                <option value="parking">Parking</option>
                                <option value="wifi">WiFi</option>
                                <option value="minibar">Minibar</option>
                                <option value="conference">Conference Room</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="online">Online Payment</option>
                                <option value="booking_com">Booking.com</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" required placeholder="Brief description of income">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" id="amount" name="amount" step="0.01" min="0" required style="flex: 1;">
                                <select id="currency" name="currency" style="width: 80px;">
                                    <option value="PEN">PEN</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div id="currency_conversion_income" style="margin-top: 5px; font-size: 0.85rem; color: #666;"></div>
                        </div>
                        <div class="form-group">
                            <label for="transaction_date">Transaction Date</label>
                            <input type="date" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Additional notes"></textarea>
                    </div>
                    <button type="submit" name="add_income" class="btn btn-success">💰 Add Income</button>
                </form>
            </div>

            <!-- Quick Add Expense -->
            <div class="section">
                <h2>💸 Quick Add Expense</h2>
                <form method="POST" class="quick-add-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expense_category">Expense Category</label>
                            <select id="expense_category" name="expense_category" required>
                                <option value="utilities">Utilities</option>
                                <option value="rent">Rent</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="supplies">Supplies</option>
                                <option value="food_beverage">Food & Beverage</option>
                                <option value="staff_salary">Staff Salary</option>
                                <option value="marketing">Marketing</option>
                                <option value="insurance">Insurance</option>
                                <option value="taxes">Taxes</option>
                                <option value="cleaning">Cleaning</option>
                                <option value="laundry">Laundry</option>
                                <option value="internet">Internet</option>
                                <option value="telephone">Telephone</option>
                                <option value="repairs">Repairs</option>
                                <option value="equipment">Equipment</option>
                                <option value="office_supplies">Office Supplies</option>
                                <option value="transportation">Transportation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_method_exp">Payment Method</label>
                            <select id="payment_method_exp" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description_exp">Description</label>
                        <input type="text" id="description_exp" name="description" required placeholder="Brief description of expense">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount_exp">Amount</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" id="amount_exp" name="amount" step="0.01" min="0" required style="flex: 1;">
                                <select id="currency_exp" name="currency" style="width: 80px;">
                                    <option value="PEN">PEN</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div id="currency_conversion_expense" style="margin-top: 5px; font-size: 0.85rem; color: #666;"></div>
                        </div>
                        <div class="form-group">
                            <label for="expense_date">Expense Date</label>
                            <input type="date" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="vendor_name">Vendor Name (Optional)</label>
                        <input type="text" id="vendor_name" name="vendor_name" placeholder="Name of vendor/supplier">
                    </div>
                    <div class="form-group">
                        <label for="notes_exp">Notes (Optional)</label>
                        <textarea id="notes_exp" name="notes" rows="2" placeholder="Additional notes"></textarea>
                    </div>
                    <button type="submit" name="add_expense" class="btn btn-danger">💸 Add Expense</button>
                </form>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="content-grid">
            <!-- Recent Income -->
            <div class="section">
                <h2>💰 Recent Income</h2>
                <div class="transactions-list">
                    <?php if (empty($recentIncome)): ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">No recent income entries</p>
                    <?php else: ?>
                        <?php foreach ($recentIncome as $income): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <h4><?php echo htmlspecialchars($income['description']); ?></h4>
                                    <div class="transaction-meta">
                                        <?php echo ucfirst(str_replace('_', ' ', $income['income_type'])); ?> • 
                                        <?php echo date('M j, Y', strtotime($income['transaction_date'])); ?> • 
                                        <?php echo ucfirst(str_replace('_', ' ', $income['payment_method'])); ?>
                                    </div>
                                </div>
                                <div class="transaction-amount income">
                                    <?php 
                                    $currency = $income['currency'] ?? 'PEN';
                                    if ($currency === 'PEN') {
                                        $penAmount = $income['amount'];
                                        $usdAmount = $income['amount'] / 3.50;
                                        echo '+S/. ' . number_format($penAmount, 2);
                                        echo '<br><small style="color: #28a745; opacity: 0.8;">≈ $' . number_format($usdAmount, 2) . '</small>';
                                    } else {
                                        $usdAmount = $income['amount'];
                                        $penAmount = $income['amount'] * 3.50;
                                        echo '+S/. ' . number_format($penAmount, 2);
                                        echo '<br><small style="color: #28a745; opacity: 0.8;">($' . number_format($usdAmount, 2) . ' USD)</small>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="income_management.php" class="btn btn-primary">View All Income</a>
                </div>
            </div>

            <!-- Recent Expenses -->
            <div class="section">
                <h2>💸 Recent Expenses</h2>
                <div class="transactions-list">
                    <?php if (empty($recentExpenses)): ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">No recent expense entries</p>
                    <?php else: ?>
                        <?php foreach ($recentExpenses as $expense): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <h4><?php echo htmlspecialchars($expense['description']); ?></h4>
                                    <div class="transaction-meta">
                                        <?php echo ucfirst(str_replace('_', ' ', $expense['expense_category'])); ?> • 
                                        <?php echo date('M j, Y', strtotime($expense['expense_date'])); ?>
                                        <?php if ($expense['vendor_name']): ?>
                                            • <?php echo htmlspecialchars($expense['vendor_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="transaction-amount expense">
                                    <?php 
                                    $currency = $expense['currency'] ?? 'PEN';
                                    if ($currency === 'PEN') {
                                        $penAmount = $expense['amount'];
                                        $usdAmount = $expense['amount'] / 3.50;
                                        echo '-S/. ' . number_format($penAmount, 2);
                                        echo '<br><small style="color: #dc3545; opacity: 0.8;">≈ $' . number_format($usdAmount, 2) . '</small>';
                                    } else {
                                        $usdAmount = $expense['amount'];
                                        $penAmount = $expense['amount'] * 3.50;
                                        echo '-S/. ' . number_format($penAmount, 2);
                                        echo '<br><small style="color: #dc3545; opacity: 0.8;">($' . number_format($usdAmount, 2) . ' USD)</small>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="expense_management.php" class="btn btn-danger">View All Expenses</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Currency conversion functions for quick add forms
        function updateIncomeConversion() {
            const amountInput = document.getElementById('amount');
            const currencySelect = document.getElementById('currency');
            const conversionDisplay = document.getElementById('currency_conversion_income');
            
            const amount = parseFloat(amountInput.value) || 0;
            const currency = currencySelect.value;
            const exchangeRate = 3.50; // PEN to USD rate for income
            
            if (amount > 0) {
                if (currency === 'PEN') {
                    const usdAmount = (amount / exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ $${usdAmount} USD</small>`;
                } else {
                    const penAmount = (amount * exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ S/. ${penAmount} PEN</small>`;
                }
                conversionDisplay.style.display = 'block';
            } else {
                conversionDisplay.style.display = 'none';
            }
        }

        function updateExpenseConversion() {
            const amountInput = document.getElementById('amount_exp');
            const currencySelect = document.getElementById('currency_exp');
            const conversionDisplay = document.getElementById('currency_conversion_expense');
            
            const amount = parseFloat(amountInput.value) || 0;
            const currency = currencySelect.value;
            const exchangeRate = 3.50; // PEN to USD rate for expenses
            
            if (amount > 0) {
                if (currency === 'PEN') {
                    const usdAmount = (amount / exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ $${usdAmount} USD</small>`;
                } else {
                    const penAmount = (amount * exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ S/. ${penAmount} PEN</small>`;
                }
                conversionDisplay.style.display = 'block';
            } else {
                conversionDisplay.style.display = 'none';
            }
        }

        // Add event listeners when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Income form listeners
            const incomeAmountInput = document.getElementById('amount');
            const incomeCurrencySelect = document.getElementById('currency');
            
            if (incomeAmountInput && incomeCurrencySelect) {
                incomeAmountInput.addEventListener('input', updateIncomeConversion);
                incomeCurrencySelect.addEventListener('change', updateIncomeConversion);
            }

            // Expense form listeners
            const expenseAmountInput = document.getElementById('amount_exp');
            const expenseCurrencySelect = document.getElementById('currency_exp');
            
            if (expenseAmountInput && expenseCurrencySelect) {
                expenseAmountInput.addEventListener('input', updateExpenseConversion);
                expenseCurrencySelect.addEventListener('change', updateExpenseConversion);
            }
        });
    </script>
</body>
</html>