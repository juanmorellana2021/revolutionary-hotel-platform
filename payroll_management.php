<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/employee_classes.php';

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

$employeeManager = new EmployeeManager();
$timeClockManager = new TimeClockManager();
$payrollManager = new PayrollManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_payroll'])) {
        $result = $payrollManager->generatePayroll(
            (int)$_POST['employee_id'],
            $_POST['pay_period_start'],
            $_POST['pay_period_end'],
            $_POST['pay_date']
        );
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['process_payment'])) {
        $result = $payrollManager->processPayment((int)$_POST['payroll_id'], $_SESSION['user']['id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['update_tax_settings'])) {
        $result = $payrollManager->updateTaxSettings((int)$_POST['employee_id'], [
            'federal_tax_rate' => (float)$_POST['federal_tax_rate'],
            'state_tax_rate' => (float)$_POST['state_tax_rate'],
            'social_security_rate' => (float)$_POST['social_security_rate'],
            'medicare_rate' => (float)$_POST['medicare_rate'],
            'other_deductions' => (float)$_POST['other_deductions']
        ]);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['delete_payroll'])) {
        $result = $payrollManager->deletePayroll((int)$_POST['payroll_id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Get filters
$filters = [];
if (!empty($_GET['employee_id'])) {
    $filters['employee_id'] = (int)$_GET['employee_id'];
}
if (!empty($_GET['pay_period_start'])) {
    $filters['pay_period_start'] = $_GET['pay_period_start'];
}
if (!empty($_GET['pay_period_end'])) {
    $filters['pay_period_end'] = $_GET['pay_period_end'];
}
if (!empty($_GET['payment_status'])) {
    $filters['payment_status'] = $_GET['payment_status'];
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$filters['limit'] = $perPage;
$filters['offset'] = ($page - 1) * $perPage;

// Get payroll records
$payrollRecords = $payrollManager->getPayrollRecords($filters);
$totalFilters = $filters;
unset($totalFilters['limit'], $totalFilters['offset']);
$totalRecords = $payrollManager->getPayrollRecords($totalFilters);
$totalPages = ceil(count($totalRecords) / $perPage);

// Get employees for dropdown
$employees = $employeeManager->getEmployees(['employment_status' => 'active']);

// Get payroll summary for current month
$currentMonth = date('Y-m');
$monthlyPayroll = $payrollManager->getPayrollRecords([
    'pay_period_start' => $currentMonth . '-01',
    'pay_period_end' => date('Y-m-t', strtotime($currentMonth . '-01'))
]);

$monthlyTotals = [
    'gross_pay' => array_sum(array_column($monthlyPayroll, 'gross_pay')),
    'net_pay' => array_sum(array_column($monthlyPayroll, 'net_pay')),
    'total_deductions' => array_sum(array_column($monthlyPayroll, 'total_deductions')),
    'processed_count' => count(array_filter($monthlyPayroll, function($p) { return $p['payment_status'] === 'paid'; })),
    'pending_count' => count(array_filter($monthlyPayroll, function($p) { return $p['payment_status'] === 'pending'; }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
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

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .payroll-table th,
        .payroll-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .payroll-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .payroll-table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-paid {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
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

        .payroll-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .payroll-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .breakdown-section h4 {
            color: #495057;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .breakdown-total {
            font-weight: bold;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .payroll-breakdown {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">💰 Payroll Management</div>
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
                    <a href="#" class="dropdown-toggle active">👥 Staff</a>
                    <div class="dropdown-content">
                        <a href="employee_management.php">👥 Employee Management</a>
                        <a href="time_clock.php">⏰ Time Clock</a>
                        <a href="payroll_management.php">💰 Payroll</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">💰 Finance</a>
                    <div class="dropdown-content">
                        <a href="accounting_dashboard.php">� Accounting Dashboard</a>
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
            <h1>💰 Payroll Management</h1>
            <p>Manage employee wages, taxes, and payroll processing</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($monthlyTotals['gross_pay'], 2); ?></div>
                    <div class="stat-label">Monthly Gross Pay</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($monthlyTotals['net_pay'], 2); ?></div>
                    <div class="stat-label">Monthly Net Pay</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $monthlyTotals['processed_count']; ?></div>
                    <div class="stat-label">Payments Processed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $monthlyTotals['pending_count']; ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="section">
            <h2>⚡ Quick Actions</h2>
            <div class="quick-actions">
                <div class="action-card">
                    <h3>📊 Generate Payroll</h3>
                    <p>Create payroll for employees based on time entries</p>
                    <button onclick="openPayrollModal()" class="btn btn-success">📊 Generate Payroll</button>
                </div>
                
                <div class="action-card">
                    <h3>⚙️ Tax Settings</h3>
                    <p>Configure tax rates and deductions for employees</p>
                    <button onclick="openTaxModal()" class="btn btn-primary">⚙️ Tax Settings</button>
                </div>
                
                <div class="action-card">
                    <h3>📈 Payroll Reports</h3>
                    <p>View detailed payroll reports and summaries</p>
                    <a href="payroll_reports.php" class="btn btn-warning">📈 View Reports</a>
                </div>
            </div>
        </div>

        <!-- Payroll Records Filter -->
        <div class="section">
            <h2>📋 Payroll Records</h2>
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="employee_id">Employee</label>
                        <select id="employee_id" name="employee_id">
                            <option value="">All Employees</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" <?php echo ($_GET['employee_id'] ?? '') == $employee['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pay_period_start">Pay Period Start</label>
                        <input type="date" id="pay_period_start" name="pay_period_start" value="<?php echo $_GET['pay_period_start'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="pay_period_end">Pay Period End</label>
                        <input type="date" id="pay_period_end" name="pay_period_end" value="<?php echo $_GET['pay_period_end'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="payment_status">Payment Status</label>
                        <select id="payment_status" name="payment_status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo ($_GET['payment_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo ($_GET['payment_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="cancelled" <?php echo ($_GET['payment_status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">🔍 Filter Records</button>
                    <a href="payroll_management.php" class="btn btn-warning">🔄 Clear Filters</a>
                </div>
            </form>
            
            <div style="overflow-x: auto;">
                <table class="payroll-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Pay Period</th>
                            <th>Hours</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payrollRecords)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                    No payroll records found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payrollRecords as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($record['employee_id']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('M j', strtotime($record['pay_period_start'])); ?> - 
                                        <?php echo date('M j, Y', strtotime($record['pay_period_end'])); ?>
                                        <br><small>Pay Date: <?php echo date('M j, Y', strtotime($record['pay_date'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($record['regular_hours'], 1); ?>h</strong>
                                        <?php if ($record['overtime_hours'] > 0): ?>
                                            <br><small style="color: #ffc107;">+<?php echo number_format($record['overtime_hours'], 1); ?>h OT</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($record['gross_pay'], 2); ?></strong>
                                        <br><small>Base: $<?php echo number_format($record['regular_pay'], 2); ?></small>
                                        <?php if ($record['overtime_pay'] > 0): ?>
                                            <br><small>OT: $<?php echo number_format($record['overtime_pay'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($record['total_deductions'], 2); ?></strong>
                                        <br><small>Fed: $<?php echo number_format($record['federal_tax'], 2); ?></small>
                                        <br><small>State: $<?php echo number_format($record['state_tax'], 2); ?></small>
                                        <br><small>SS: $<?php echo number_format($record['social_security'], 2); ?></small>
                                        <br><small>Med: $<?php echo number_format($record['medicare'], 2); ?></small>
                                    </td>
                                    <td><strong>$<?php echo number_format($record['net_pay'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $record['payment_status']; ?>">
                                            <?php echo ucfirst($record['payment_status']); ?>
                                        </span>
                                        <?php if ($record['payment_date']): ?>
                                            <br><small>Paid: <?php echo date('M j, Y', strtotime($record['payment_date'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['payment_status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="payroll_id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" name="process_payment" class="btn btn-success btn-sm" onclick="return confirm('Process payment for this payroll?')">💳 Pay</button>
                                            </form>
                                        <?php endif; ?>
                                        <button onclick="viewPayrollDetails(<?php echo htmlspecialchars(json_encode($record)); ?>)" class="btn btn-primary btn-sm">👁️ View</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="payroll_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" name="delete_payroll" class="btn btn-danger btn-sm" onclick="return confirm('Delete this payroll record?')">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Generate Payroll Modal -->
    <div id="payrollModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePayrollModal()">&times;</span>
            <h2>📊 Generate Payroll</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="payroll_employee_id">Employee *</label>
                    <select id="payroll_employee_id" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pay_period_start">Pay Period Start *</label>
                        <input type="date" id="payroll_pay_period_start" name="pay_period_start" required>
                    </div>
                    <div class="form-group">
                        <label for="pay_period_end">Pay Period End *</label>
                        <input type="date" id="payroll_pay_period_end" name="pay_period_end" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="pay_date">Pay Date *</label>
                    <input type="date" id="payroll_pay_date" name="pay_date" required>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="generate_payroll" class="btn btn-success">📊 Generate Payroll</button>
                    <button type="button" onclick="closePayrollModal()" class="btn btn-warning">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tax Settings Modal -->
    <div id="taxModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTaxModal()">&times;</span>
            <h2>⚙️ Tax Settings</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="tax_employee_id">Employee *</label>
                    <select id="tax_employee_id" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="federal_tax_rate">Federal Tax Rate (%)</label>
                        <input type="number" id="federal_tax_rate" name="federal_tax_rate" step="0.01" min="0" max="50" value="12">
                    </div>
                    <div class="form-group">
                        <label for="state_tax_rate">State Tax Rate (%)</label>
                        <input type="number" id="state_tax_rate" name="state_tax_rate" step="0.01" min="0" max="20" value="5">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_security_rate">Social Security Rate (%)</label>
                        <input type="number" id="social_security_rate" name="social_security_rate" step="0.01" min="0" max="10" value="6.2" readonly>
                    </div>
                    <div class="form-group">
                        <label for="medicare_rate">Medicare Rate (%)</label>
                        <input type="number" id="medicare_rate" name="medicare_rate" step="0.01" min="0" max="5" value="1.45" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label for="other_deductions">Other Deductions ($)</label>
                    <input type="number" id="other_deductions" name="other_deductions" step="0.01" min="0" value="0">
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="update_tax_settings" class="btn btn-success">⚙️ Update Settings</button>
                    <button type="button" onclick="closeTaxModal()" class="btn btn-warning">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payroll Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <h2>📋 Payroll Details</h2>
            <div id="payrollDetailsContent"></div>
        </div>
    </div>

    <script>
        function openPayrollModal() {
            document.getElementById('payrollModal').style.display = 'block';
            // Set default dates to current week
            const today = new Date();
            const monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
            const sunday = new Date(today.setDate(today.getDate() - today.getDay() + 7));
            
            document.getElementById('payroll_pay_period_start').value = monday.toISOString().split('T')[0];
            document.getElementById('payroll_pay_period_end').value = sunday.toISOString().split('T')[0];
            document.getElementById('payroll_pay_date').value = new Date().toISOString().split('T')[0];
        }

        function closePayrollModal() {
            document.getElementById('payrollModal').style.display = 'none';
        }

        function openTaxModal() {
            document.getElementById('taxModal').style.display = 'block';
        }

        function closeTaxModal() {
            document.getElementById('taxModal').style.display = 'none';
        }

        function viewPayrollDetails(record) {
            const content = `
                <div class="payroll-details">
                    <h3>${record.first_name} ${record.last_name} (${record.employee_id})</h3>
                    <p>Pay Period: ${new Date(record.pay_period_start).toLocaleDateString()} - ${new Date(record.pay_period_end).toLocaleDateString()}</p>
                    <p>Pay Date: ${new Date(record.pay_date).toLocaleDateString()}</p>
                    
                    <div class="payroll-breakdown">
                        <div class="breakdown-section">
                            <h4>💰 Earnings</h4>
                            <div class="breakdown-item">
                                <span>Regular Hours (${record.regular_hours}h)</span>
                                <span>$${parseFloat(record.regular_pay).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Overtime Hours (${record.overtime_hours}h)</span>
                                <span>$${parseFloat(record.overtime_pay).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item breakdown-total">
                                <span>Gross Pay</span>
                                <span>$${parseFloat(record.gross_pay).toFixed(2)}</span>
                            </div>
                        </div>
                        
                        <div class="breakdown-section">
                            <h4>📊 Deductions</h4>
                            <div class="breakdown-item">
                                <span>Federal Tax</span>
                                <span>$${parseFloat(record.federal_tax).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>State Tax</span>
                                <span>$${parseFloat(record.state_tax).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Social Security</span>
                                <span>$${parseFloat(record.social_security).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Medicare</span>
                                <span>$${parseFloat(record.medicare).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Other Deductions</span>
                                <span>$${parseFloat(record.other_deductions || 0).toFixed(2)}</span>
                            </div>
                            <div class="breakdown-item breakdown-total">
                                <span>Total Deductions</span>
                                <span>$${parseFloat(record.total_deductions).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 8px;">
                        <h3 style="color: #28a745;">Net Pay: $${parseFloat(record.net_pay).toFixed(2)}</h3>
                    </div>
                </div>
            `;
            
            document.getElementById('payrollDetailsContent').innerHTML = content;
            document.getElementById('detailsModal').style.display = 'block';
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const payrollModal = document.getElementById('payrollModal');
            const taxModal = document.getElementById('taxModal');
            const detailsModal = document.getElementById('detailsModal');
            
            if (event.target == payrollModal) {
                closePayrollModal();
            }
            if (event.target == taxModal) {
                closeTaxModal();
            }
            if (event.target == detailsModal) {
                closeDetailsModal();
            }
        }
    </script>
</body>
</html>