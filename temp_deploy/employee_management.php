<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/employee_classes.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_role'])) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$employeeManager = new EmployeeManager();
$timeClockManager = new TimeClockManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Add currency columns to employees table if they don't exist
try {
    $database = new Database();
    $connection = $database->getConnection();
    
    // Check if columns exist
    $stmt = $connection->prepare("SHOW COLUMNS FROM employees LIKE 'hourly_rate_currency'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE employees ADD COLUMN hourly_rate_currency VARCHAR(3) DEFAULT 'USD' AFTER hourly_rate");
    }
    
    $stmt = $connection->prepare("SHOW COLUMNS FROM employees LIKE 'overtime_rate_currency'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE employees ADD COLUMN overtime_rate_currency VARCHAR(3) DEFAULT 'USD' AFTER overtime_rate");
    }
} catch (Exception $e) {
    // Columns might already exist, continue silently
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        // Currency conversion for consistent USD storage
        $USD_TO_PEN_RATE = 3.50;
        
        // Convert hourly rate to USD if needed
        $hourlyRate = (float)$_POST['hourly_rate'];
        $hourlyRateCurrency = $_POST['hourly_rate_currency'] ?? 'USD';
        if ($hourlyRateCurrency === 'PEN') {
            $hourlyRate = $hourlyRate / $USD_TO_PEN_RATE;
        }
        
        // Convert overtime rate to USD if needed
        $overtimeRate = !empty($_POST['overtime_rate']) ? (float)$_POST['overtime_rate'] : $hourlyRate * 1.5;
        $overtimeRateCurrency = $_POST['overtime_rate_currency'] ?? 'USD';
        if ($overtimeRateCurrency === 'PEN' && !empty($_POST['overtime_rate'])) {
            $overtimeRate = $overtimeRate / $USD_TO_PEN_RATE;
        } elseif (empty($_POST['overtime_rate'])) {
            // Auto-calculate overtime in same currency as hourly
            $overtimeRate = $hourlyRate * 1.5;
        }
        
        $result = $employeeManager->addEmployee([
            'employee_id' => $_POST['employee_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'position' => $_POST['position'],
            'department' => $_POST['department'],
            'hire_date' => $_POST['hire_date'],
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'weekly_hours' => (int)$_POST['weekly_hours'],
            'salary_type' => $_POST['salary_type'],
            'monthly_salary' => !empty($_POST['monthly_salary']) ? (float)$_POST['monthly_salary'] : null,
            'emergency_contact_name' => $_POST['emergency_contact_name'] ?? '',
            'emergency_contact_phone' => $_POST['emergency_contact_phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'tax_id' => $_POST['tax_id'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'hourly_rate_currency' => $hourlyRateCurrency,
            'overtime_rate_currency' => $overtimeRateCurrency
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['update_employee'])) {
        // Currency conversion for consistent USD storage
        $USD_TO_PEN_RATE = 3.50;
        
        // Convert hourly rate to USD if needed
        $hourlyRate = (float)$_POST['hourly_rate'];
        $hourlyRateCurrency = $_POST['hourly_rate_currency'] ?? 'USD';
        if ($hourlyRateCurrency === 'PEN') {
            $hourlyRate = $hourlyRate / $USD_TO_PEN_RATE;
        }
        
        // Convert overtime rate to USD if needed
        $overtimeRate = (float)$_POST['overtime_rate'];
        $overtimeRateCurrency = $_POST['overtime_rate_currency'] ?? 'USD';
        if ($overtimeRateCurrency === 'PEN') {
            $overtimeRate = $overtimeRate / $USD_TO_PEN_RATE;
        }
        
        $result = $employeeManager->updateEmployee((int)$_POST['employee_id'], [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'position' => $_POST['position'],
            'department' => $_POST['department'],
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'weekly_hours' => (int)$_POST['weekly_hours'],
            'salary_type' => $_POST['salary_type'],
            'monthly_salary' => !empty($_POST['monthly_salary']) ? (float)$_POST['monthly_salary'] : null,
            'employment_status' => $_POST['employment_status'],
            'emergency_contact_name' => $_POST['emergency_contact_name'],
            'emergency_contact_phone' => $_POST['emergency_contact_phone'],
            'address' => $_POST['address'],
            'tax_id' => $_POST['tax_id'],
            'notes' => $_POST['notes'],
            'hourly_rate_currency' => $hourlyRateCurrency,
            'overtime_rate_currency' => $overtimeRateCurrency
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['delete_employee'])) {
        $result = $employeeManager->deleteEmployee((int)$_POST['employee_id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Get filters
$filters = [];
if (!empty($_GET['department'])) {
    $filters['department'] = $_GET['department'];
}
if (!empty($_GET['position'])) {
    $filters['position'] = $_GET['position'];
}
if (!empty($_GET['employment_status'])) {
    $filters['employment_status'] = $_GET['employment_status'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$filters['limit'] = $perPage;
$filters['offset'] = ($page - 1) * $perPage;

// Get employees
$employees = $employeeManager->getEmployees($filters);
$totalFilters = $filters;
unset($totalFilters['limit'], $totalFilters['offset']);
$totalEmployees = $employeeManager->getEmployees($totalFilters);
$totalPages = ceil(count($totalEmployees) / $perPage);

// Get today's time entries for summary
$todayEntries = $timeClockManager->getTimeEntries(['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]);
$activeEmployees = count(array_filter($todayEntries, function($entry) { return empty($entry['clock_out']); }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
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
        .form-group select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group textarea {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .table-container {
            overflow-x: auto;
        }

        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .employee-table th,
        .employee-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .employee-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .employee-table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-manager {
            background: #d1ecf1;
            color: #0c5460;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">👥 Employee Management</div>
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
            <h1>👥 Employee Management</h1>
            <p>Manage your hotel staff and track employee information</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($totalEmployees); ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $activeEmployees; ?></div>
                    <div class="stat-label">Currently Working</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($totalEmployees, function($e) { return $e['employment_status'] === 'active'; })); ?></div>
                    <div class="stat-label">Active Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($todayEntries); ?></div>
                    <div class="stat-label">Today's Clock Ins</div>
                </div>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="section">
            <h2>🔍 Filter Employees</h2>
            <form method="GET">
                <div class="filters">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">All Departments</option>
                            <option value="front_desk" <?php echo ($_GET['department'] ?? '') === 'front_desk' ? 'selected' : ''; ?>>Front Desk</option>
                            <option value="housekeeping" <?php echo ($_GET['department'] ?? '') === 'housekeeping' ? 'selected' : ''; ?>>Housekeeping</option>
                            <option value="maintenance" <?php echo ($_GET['department'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="food_beverage" <?php echo ($_GET['department'] ?? '') === 'food_beverage' ? 'selected' : ''; ?>>Food & Beverage</option>
                            <option value="security" <?php echo ($_GET['department'] ?? '') === 'security' ? 'selected' : ''; ?>>Security</option>
                            <option value="management" <?php echo ($_GET['department'] ?? '') === 'management' ? 'selected' : ''; ?>>Management</option>
                            <option value="accounting" <?php echo ($_GET['department'] ?? '') === 'accounting' ? 'selected' : ''; ?>>Accounting</option>
                            <option value="other" <?php echo ($_GET['department'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <select id="position" name="position">
                            <option value="">All Positions</option>
                            <option value="manager" <?php echo ($_GET['position'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="receptionist" <?php echo ($_GET['position'] ?? '') === 'receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                            <option value="housekeeper" <?php echo ($_GET['position'] ?? '') === 'housekeeper' ? 'selected' : ''; ?>>Housekeeper</option>
                            <option value="maintenance" <?php echo ($_GET['position'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="security" <?php echo ($_GET['position'] ?? '') === 'security' ? 'selected' : ''; ?>>Security</option>
                            <option value="kitchen" <?php echo ($_GET['position'] ?? '') === 'kitchen' ? 'selected' : ''; ?>>Kitchen Staff</option>
                            <option value="waiter" <?php echo ($_GET['position'] ?? '') === 'waiter' ? 'selected' : ''; ?>>Waiter</option>
                            <option value="bartender" <?php echo ($_GET['position'] ?? '') === 'bartender' ? 'selected' : ''; ?>>Bartender</option>
                            <option value="concierge" <?php echo ($_GET['position'] ?? '') === 'concierge' ? 'selected' : ''; ?>>Concierge</option>
                            <option value="accountant" <?php echo ($_GET['position'] ?? '') === 'accountant' ? 'selected' : ''; ?>>Accountant</option>
                            <option value="other" <?php echo ($_GET['position'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="employment_status">Status</label>
                        <select id="employment_status" name="employment_status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($_GET['employment_status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($_GET['employment_status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="on_leave" <?php echo ($_GET['employment_status'] ?? '') === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                            <option value="terminated" <?php echo ($_GET['employment_status'] ?? '') === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Name, Employee ID..." value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                    <a href="employee_management.php" class="btn btn-warning">🔄 Clear Filters</a>
                    <button type="button" class="btn btn-success" onclick="openAddModal()">➕ Add Employee</button>
                </div>
            </form>
        </div>

        <!-- Employees Table -->
        <div class="section">
            <div class="table-container">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Contact</th>
                            <th>Hourly Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                    No employees found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong><br>
                                        <small>Hired: <?php echo date('M j, Y', strtotime($employee['hire_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo ucfirst(str_replace('_', ' ', $employee['position'])); ?>
                                        <?php if ($employee['position'] === 'manager'): ?>
                                            <span class="badge badge-manager">Manager</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $employee['department'])); ?></td>
                                    <td>
                                        <?php if ($employee['email']): ?>
                                            <small><?php echo htmlspecialchars($employee['email']); ?></small><br>
                                        <?php endif; ?>
                                        <?php if ($employee['phone']): ?>
                                            <small><?php echo htmlspecialchars($employee['phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $currency = $employee['hourly_rate_currency'] ?? 'USD';
                                        $hourlyRateUSD = $employee['hourly_rate']; // Stored in USD
                                        
                                        if ($currency === 'PEN') {
                                            // Display in PEN, show USD equivalent
                                            $hourlyRatePEN = $hourlyRateUSD * 3.50;
                                            echo 'S/ ' . number_format($hourlyRatePEN, 2) . '/hr';
                                            echo '<br><small style="color: #666;">($' . number_format($hourlyRateUSD, 2) . ' USD)</small>';
                                        } else {
                                            // Display in USD, show PEN equivalent
                                            echo '$' . number_format($hourlyRateUSD, 2) . '/hr';
                                            $hourlyRatePEN = $hourlyRateUSD * 3.50;
                                            echo '<br><small style="color: #666;">(S/ ' . number_format($hourlyRatePEN, 2) . ' PEN)</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $employee['employment_status'] === 'active' ? 'active' : 'inactive'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $employee['employment_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" class="btn btn-primary btn-sm">✏️ Edit</button>
                                        <a href="time_clock.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-warning btn-sm">⏰ Time</a>
                                        <button onclick="deleteEmployee(<?php echo $employee['id']; ?>)" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Employee</h2>
            <form id="employeeForm" method="POST">
                <input type="hidden" id="employee_id" name="employee_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_employee_id">Employee ID *</label>
                        <input type="text" id="modal_employee_id" name="employee_id" required placeholder="EMP001">
                    </div>
                    <div class="form-group">
                        <label for="modal_employment_status">Employment Status</label>
                        <select id="modal_employment_status" name="employment_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on_leave">On Leave</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_first_name">First Name *</label>
                        <input type="text" id="modal_first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_last_name">Last Name *</label>
                        <input type="text" id="modal_last_name" name="last_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_email">Email</label>
                        <input type="email" id="modal_email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="modal_phone">Phone</label>
                        <input type="tel" id="modal_phone" name="phone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_position">Position *</label>
                        <select id="modal_position" name="position" required>
                            <option value="manager">Manager</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="housekeeper">Housekeeper</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="security">Security</option>
                            <option value="kitchen">Kitchen Staff</option>
                            <option value="waiter">Waiter</option>
                            <option value="bartender">Bartender</option>
                            <option value="concierge">Concierge</option>
                            <option value="accountant">Accountant</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_department">Department *</label>
                        <select id="modal_department" name="department" required>
                            <option value="front_desk">Front Desk</option>
                            <option value="housekeeping">Housekeeping</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="food_beverage">Food & Beverage</option>
                            <option value="security">Security</option>
                            <option value="management">Management</option>
                            <option value="accounting">Accounting</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_hire_date">Hire Date *</label>
                        <input type="date" id="modal_hire_date" name="hire_date" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_salary_type">Salary Type *</label>
                        <select id="modal_salary_type" name="salary_type" required>
                            <option value="hourly">Hourly</option>
                            <option value="salary">Monthly Salary</option>
                            <option value="commission">Commission</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_hourly_rate">💰 Hourly Rate *</label>
                        <div style="display: flex; gap: 5px;">
                            <select id="hourly_rate_currency" name="hourly_rate_currency" onchange="updateHourlyRateDisplay()" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="USD">USD</option>
                                <option value="PEN" selected>PEN</option>
                            </select>
                            <input type="number" id="modal_hourly_rate" name="hourly_rate" step="0.01" min="0" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <small id="hourly_rate_conversion" style="color: #666; font-size: 12px;">Equivale a $0.00 USD</small>
                    </div>
                    <div class="form-group">
                        <label for="modal_overtime_rate">⏰ Overtime Rate</label>
                        <div style="display: flex; gap: 5px;">
                            <select id="overtime_rate_currency" name="overtime_rate_currency" onchange="updateOvertimeRateDisplay()" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="USD">USD</option>
                                <option value="PEN" selected>PEN</option>
                            </select>
                            <input type="number" id="modal_overtime_rate" name="overtime_rate" step="0.01" min="0" placeholder="Auto-calculated if empty" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <small id="overtime_rate_conversion" style="color: #666; font-size: 12px;">Equivale a $0.00 USD</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_weekly_hours">Weekly Hours</label>
                        <input type="number" id="modal_weekly_hours" name="weekly_hours" min="1" max="168" value="40">
                    </div>
                    <div class="form-group">
                        <label for="modal_monthly_salary">Monthly Salary ($)</label>
                        <input type="number" id="modal_monthly_salary" name="monthly_salary" step="0.01" min="0" placeholder="For salary employees">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="modal_emergency_contact_name" name="emergency_contact_name">
                    </div>
                    <div class="form-group">
                        <label for="modal_emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="tel" id="modal_emergency_contact_phone" name="emergency_contact_phone">
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_address">Address</label>
                    <textarea id="modal_address" name="address" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_tax_id">Tax ID / SSN</label>
                        <input type="text" id="modal_tax_id" name="tax_id">
                    </div>
                    <div class="form-group">
                        <label for="modal_notes">Notes</label>
                        <textarea id="modal_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" id="submitBtn" name="add_employee" class="btn btn-success">👥 Add Employee</button>
                    <button type="button" onclick="closeModal()" class="btn btn-warning">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Employee';
            document.getElementById('employeeForm').reset();
            document.getElementById('employee_id').value = '';
            document.getElementById('modal_hire_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('submitBtn').textContent = '👥 Add Employee';
            document.getElementById('submitBtn').name = 'add_employee';
            document.getElementById('modal_employee_id').disabled = false;
            document.getElementById('employeeModal').style.display = 'block';
        }

        function editEmployee(employee) {
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            document.getElementById('employee_id').value = employee.id;
            document.getElementById('modal_employee_id').value = employee.employee_id;
            document.getElementById('modal_employee_id').disabled = true;
            document.getElementById('modal_first_name').value = employee.first_name;
            document.getElementById('modal_last_name').value = employee.last_name;
            document.getElementById('modal_email').value = employee.email || '';
            document.getElementById('modal_phone').value = employee.phone || '';
            document.getElementById('modal_position').value = employee.position;
            document.getElementById('modal_department').value = employee.department;
            document.getElementById('modal_hire_date').value = employee.hire_date;
            document.getElementById('modal_salary_type').value = employee.salary_type;
            document.getElementById('modal_hourly_rate').value = employee.hourly_rate;
            document.getElementById('hourly_rate_currency').value = employee.hourly_rate_currency || 'USD';
            document.getElementById('modal_overtime_rate').value = employee.overtime_rate || '';
            document.getElementById('overtime_rate_currency').value = employee.overtime_rate_currency || 'USD';
            document.getElementById('modal_weekly_hours').value = employee.weekly_hours;
            document.getElementById('modal_monthly_salary').value = employee.monthly_salary || '';
            document.getElementById('modal_employment_status').value = employee.employment_status;
            document.getElementById('modal_emergency_contact_name').value = employee.emergency_contact_name || '';
            document.getElementById('modal_emergency_contact_phone').value = employee.emergency_contact_phone || '';
            document.getElementById('modal_address').value = employee.address || '';
            document.getElementById('modal_tax_id').value = employee.tax_id || '';
            document.getElementById('modal_notes').value = employee.notes || '';
            document.getElementById('submitBtn').textContent = '✏️ Update Employee';
            document.getElementById('submitBtn').name = 'update_employee';
            document.getElementById('employeeModal').style.display = 'block';
            
            // Update currency displays
            updateHourlyRateDisplay();
            updateOvertimeRateDisplay();
        }

        function deleteEmployee(employeeId) {
            if (confirm('Are you sure you want to delete this employee? This will also delete all their time records and payroll data.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="employee_id" value="${employeeId}">
                    <input type="hidden" name="delete_employee" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('employeeModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('employeeModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Currency conversion functions
        const USD_TO_PEN_RATE = 3.50; // Employee rates use 3.50 rate
        
        function convertCurrency(amount, fromCurrency, toCurrency) {
            if (fromCurrency === toCurrency) return amount;
            if (fromCurrency === 'USD' && toCurrency === 'PEN') {
                return amount * USD_TO_PEN_RATE;
            } else if (fromCurrency === 'PEN' && toCurrency === 'USD') {
                return amount / USD_TO_PEN_RATE;
            }
            return amount;
        }
        
        function updateHourlyRateDisplay() {
            const currency = document.getElementById('hourly_rate_currency').value;
            const rate = parseFloat(document.getElementById('modal_hourly_rate').value) || 0;
            const conversionText = document.getElementById('hourly_rate_conversion');
            
            if (rate > 0) {
                if (currency === 'PEN') {
                    const usdEquivalent = convertCurrency(rate, 'PEN', 'USD');
                    conversionText.textContent = `Equivale a $${usdEquivalent.toFixed(2)} USD`;
                } else {
                    const penEquivalent = convertCurrency(rate, 'USD', 'PEN');
                    conversionText.textContent = `Equivale a S/ ${penEquivalent.toFixed(2)} PEN`;
                }
            } else {
                conversionText.textContent = currency === 'PEN' ? 'Equivale a $0.00 USD' : 'Equivale a S/ 0.00 PEN';
            }
            
            // Update overtime rate calculation
            updateOvertimeCalculation();
        }
        
        function updateOvertimeRateDisplay() {
            const currency = document.getElementById('overtime_rate_currency').value;
            const rate = parseFloat(document.getElementById('modal_overtime_rate').value) || 0;
            const conversionText = document.getElementById('overtime_rate_conversion');
            
            if (rate > 0) {
                if (currency === 'PEN') {
                    const usdEquivalent = convertCurrency(rate, 'PEN', 'USD');
                    conversionText.textContent = `Equivale a $${usdEquivalent.toFixed(2)} USD`;
                } else {
                    const penEquivalent = convertCurrency(rate, 'USD', 'PEN');
                    conversionText.textContent = `Equivale a S/ ${penEquivalent.toFixed(2)} PEN`;
                }
            } else {
                conversionText.textContent = currency === 'PEN' ? 'Equivale a $0.00 USD' : 'Equivale a S/ 0.00 PEN';
            }
        }
        
        function updateOvertimeCalculation() {
            const hourlyRate = parseFloat(document.getElementById('modal_hourly_rate').value) || 0;
            const hourlyCurrency = document.getElementById('hourly_rate_currency').value;
            const overtimeField = document.getElementById('modal_overtime_rate');
            const overtimeCurrency = document.getElementById('overtime_rate_currency').value;
            
            if (!overtimeField.value && hourlyRate > 0) {
                const overtimeRate = hourlyRate * 1.5;
                const symbol = hourlyCurrency === 'PEN' ? 'S/ ' : '$ ';
                overtimeField.placeholder = `${symbol}${overtimeRate.toFixed(2)} (1.5x)`;
                
                // Sync currency selectors
                document.getElementById('overtime_rate_currency').value = hourlyCurrency;
                updateOvertimeRateDisplay();
            }
        }

        // Auto-calculate overtime rate when hourly rate changes
        document.getElementById('modal_hourly_rate').addEventListener('input', function() {
            updateHourlyRateDisplay();
        });
        
        // Update displays when currency changes
        document.getElementById('hourly_rate_currency').addEventListener('change', updateHourlyRateDisplay);
        document.getElementById('overtime_rate_currency').addEventListener('change', updateOvertimeRateDisplay);
        document.getElementById('modal_overtime_rate').addEventListener('input', updateOvertimeRateDisplay);
    </script>
</body>
</html>