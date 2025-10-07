<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/employee_classes.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$userManager = new UserManager();
$employeeManager = new EmployeeManager();
$timeClockManager = new TimeClockManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

$isManager = $userManager->isManager($_SESSION['user']['id']);

// Add missing columns to time_clock table if they don't exist
try {
    $database = new Database();
    $connection = $database->getConnection();
    
    // Check and add is_manual_entry column
    $stmt = $connection->prepare("SHOW COLUMNS FROM time_clock LIKE 'is_manual_entry'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE time_clock ADD COLUMN is_manual_entry BOOLEAN DEFAULT FALSE");
    }
    
    // Check and add created_by column
    $stmt = $connection->prepare("SHOW COLUMNS FROM time_clock LIKE 'created_by'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE time_clock ADD COLUMN created_by INT DEFAULT NULL");
    }
    
    // Check and add total_break_minutes column
    $stmt = $connection->prepare("SHOW COLUMNS FROM time_clock LIKE 'total_break_minutes'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE time_clock ADD COLUMN total_break_minutes INT DEFAULT NULL");
    }
    
    // Check and add break_start column
    $stmt = $connection->prepare("SHOW COLUMNS FROM time_clock LIKE 'break_start'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE time_clock ADD COLUMN break_start DATETIME DEFAULT NULL");
    }
    
    // Check and add break_end column
    $stmt = $connection->prepare("SHOW COLUMNS FROM time_clock LIKE 'break_end'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $connection->exec("ALTER TABLE time_clock ADD COLUMN break_end DATETIME DEFAULT NULL");
    }
    
} catch (Exception $e) {
    // Columns might already exist, continue silently
}

// Generate CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle flash messages from redirects
$message = '';
$messageType = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Handle form submissions with duplicate prevention
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $messageType = 'error';
    } else if (isset($_POST['clock_in'])) {
        // Check for duplicate submission using session tracking
        $submissionKey = 'last_clock_in_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait before trying again.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->clockIn((int)$_POST['employee_id'], $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            // If successful, redirect to prevent refresh resubmission
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
    
    else if (isset($_POST['clock_out'])) {
        // Check for duplicate submission
        $submissionKey = 'last_clock_out_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait before trying again.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->clockOut((int)$_POST['employee_id'], $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            // If successful, redirect to prevent refresh resubmission
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
    
    else if (isset($_POST['start_break'])) {
        // Check for duplicate submission
        $submissionKey = 'last_break_start_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait before trying again.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->startBreak((int)$_POST['employee_id'], $_POST['break_type'] ?? 'break', $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
    
    else if (isset($_POST['end_break'])) {
        // Check for duplicate submission
        $submissionKey = 'last_break_end_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait before trying again.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->endBreak((int)$_POST['employee_id'], $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
    
    else if (isset($_POST['manual_entry']) && $isManager) {
        // Check for duplicate submission
        $submissionKey = 'last_manual_entry_' . (int)$_POST['employee_id'] . '_' . $_POST['clock_in'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 10) {
            $message = 'Duplicate manual entry prevented. Please wait before trying again.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->addManualEntry(
                (int)$_POST['employee_id'],
                $_POST['clock_in'],
                $_POST['clock_out'] ?? null,
                $_POST['break_minutes'] ?? 0,
                $_POST['notes'] ?? '',
                $_SESSION['user']['id']
            );
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_entry' && $isManager) {
        $result = $timeClockManager->updateTimeEntry(
            (int)$_POST['entry_id'],
            (int)$_POST['employee_id'],
            $_POST['date'],
            $_POST['clock_in'],
            $_POST['clock_out'] ?? null,
            $_POST['notes'] ?? ''
        );
        
        echo json_encode($result);
        exit;
    }
    
    if ($_POST['action'] === 'delete_entry' && $isManager) {
        $result = $timeClockManager->deleteTimeEntry((int)$_POST['entry_id']);
        
        echo json_encode($result);
        exit;
    }
    
    // Invalid action or insufficient permissions
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action or insufficient permissions'
    ]);
    exit;
}

// Get filters for time entries
$filters = [];
if ($isManager) {
    if (!empty($_GET['employee_id'])) {
        $filters['employee_id'] = (int)$_GET['employee_id'];
    }
    if (!empty($_GET['start_date'])) {
        $filters['start_date'] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $filters['end_date'] = $_GET['end_date'];
    }
} else {
    // Regular employees can only see their own records
    $filters['employee_id'] = $_SESSION['user']['id'];
}

// Default to current week if no dates specified
if (empty($filters['start_date'])) {
    $filters['start_date'] = date('Y-m-d', strtotime('monday this week'));
}
if (empty($filters['end_date'])) {
    $filters['end_date'] = date('Y-m-d', strtotime('sunday this week'));
}

// Get employees for dropdown (managers only)
$employees = [];
if ($isManager) {
    $employees = $employeeManager->getEmployees(['employment_status' => 'active']);
}

// Get time entries
$timeEntries = $timeClockManager->getTimeEntries($filters);

// Get currently clocked in employees
$currentlyWorking = $timeClockManager->getCurrentlyWorking();

// Get today's summary
$todayFilters = array_merge($filters, [
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d')
]);
$todayEntries = $timeClockManager->getTimeEntries($todayFilters);
$todayHours = array_sum(array_map(function($entry) {
    return $entry['total_hours'] ?? 0;
}, $todayEntries));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Clock - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
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

        .clock-display {
            font-size: 3rem;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }

        .date-display {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 20px;
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

        .clock-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .clock-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .clock-card.active {
            border-color: #28a745;
            background: #d4edda;
        }

        .clock-card.break {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 15px;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
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

        .currently-working {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .currently-working h3 {
            color: #155724;
            margin-bottom: 10px;
        }

        .worker-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .worker-info {
            flex: 1;
        }

        .worker-name {
            font-weight: bold;
            color: #2c3e50;
        }

        .worker-details {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .worker-hours {
            font-weight: bold;
            color: #28a745;
        }

        .time-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .time-table th,
        .time-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .time-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .time-table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-working {
            background: #d4edda;
            color: #155724;
        }

        .badge-completed {
            background: #cce5ff;
            color: #004085;
        }

        .badge-break {
            background: #fff3cd;
            color: #856404;
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
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
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
            
            .clock-actions {
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
            <div class="logo">⏰ Time Clock</div>
            <div class="nav-links">
                <a href="<?php echo $isManager ? 'manager_dashboard.php' : 'dashboard.php'; ?>">🏠 Dashboard</a>
                
                <?php if ($isManager): ?>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">🏨 Hotel</a>
                    <div class="dropdown-content">
                        <a href="hotel_setup.php">🏨 Hotel Setup</a>
                        <a href="room_management.php">�️ Room Management</a>
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
                <?php else: ?>
                <a href="time_clock.php" class="active">⏰ Time Clock</a>
                <?php endif; ?>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>⏰ Time Clock System</h1>
            <p>Track employee hours and manage time entries</p>
            
            <div class="clock-display" id="currentTime"></div>
            <div class="date-display" id="currentDate"></div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($currentlyWorking); ?></div>
                    <div class="stat-label">Currently Working</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($todayEntries); ?></div>
                    <div class="stat-label">Today's Entries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($todayHours, 1); ?></div>
                    <div class="stat-label">Today's Hours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($timeEntries); ?></div>
                    <div class="stat-label">Period Entries</div>
                </div>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Currently Working Section -->
        <?php if (!empty($currentlyWorking)): ?>
            <div class="currently-working">
                <h3>👷 Currently Working</h3>
                <?php foreach ($currentlyWorking as $worker): ?>
                    <div class="worker-card">
                        <div class="worker-info">
                            <div class="worker-name"><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></div>
                            <div class="worker-details">
                                <?php echo ucfirst(str_replace('_', ' ', $worker['position'])); ?> • 
                                Clocked in at <?php echo date('g:i A', strtotime($worker['clock_in'])); ?>
                                <?php if ($worker['on_break']): ?>
                                    • <span style="color: #856404;">On Break</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="worker-hours"><?php echo number_format($worker['hours_today'] ?? 0, 1); ?>h</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Clock Actions -->
        <?php if ($isManager): ?>
            <div class="section">
                <h2><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-play-circle me-2"></i>Clock In Employee
                            </div>
                            <div class="card-body">
                                <form method="POST" onsubmit="return preventDoubleSubmit(this)">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Select Employee</label>
                                        <select name="employee_id" class="form-select" required>
                                            <option value="">Select Employee</option>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?php echo $employee['id']; ?>">
                                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes (Optional)</label>
                                        <textarea name="notes" class="form-control" placeholder="Optional notes..." rows="2"></textarea>
                                    </div>
                                    <button type="submit" name="clock_in" class="btn btn-success w-100">
                                        <i class="bi bi-play-circle me-2"></i>Clock In
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <i class="bi bi-stop-circle me-2"></i>Clock Out Employee
                            </div>
                            <div class="card-body">
                                <form method="POST" onsubmit="return preventDoubleSubmit(this)">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Select Employee</label>
                                        <select name="employee_id" class="form-select" required>
                                            <option value="">Select Employee</option>
                                            <?php foreach ($currentlyWorking as $worker): ?>
                                                <option value="<?php echo $worker['id']; ?>">
                                                    <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes (Optional)</label>
                                        <textarea name="notes" class="form-control" placeholder="Optional notes..." rows="2"></textarea>
                                    </div>
                                    <button type="submit" name="clock_out" class="btn btn-danger w-100">
                                        <i class="bi bi-stop-circle me-2"></i>Clock Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                    
                    <div class="clock-card">
                        <h3>☕ Break Management</h3>
                        <form method="POST" onsubmit="return preventDoubleSubmit(this)">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="form-group">
                                <select name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($currentlyWorking as $worker): ?>
                                        <option value="<?php echo $worker['id']; ?>">
                                            <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                            <?php echo $worker['on_break'] ? ' (On Break)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="break_type">
                                    <option value="break">Regular Break</option>
                                    <option value="lunch">Lunch Break</option>
                                    <option value="bathroom">Bathroom Break</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <textarea name="notes" placeholder="Optional notes..." rows="2"></textarea>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="start_break" class="btn btn-warning" style="flex: 1;">☕ Start Break</button>
                                <button type="submit" name="end_break" class="btn btn-success" style="flex: 1;">🏁 End Break</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="clock-card">
                        <h3>✏️ Manual Entry</h3>
                        <button type="button" onclick="openManualEntryModal()" class="btn btn-primary">✏️ Add Manual Entry</button>
                        <?php if ($isManager): ?>
                            <a href="time_clock_cleanup.php" class="btn btn-warning mt-2" title="Remove duplicate ghost entries">
                                <i class="bi bi-tools me-1"></i>🧹 Cleanup Duplicates
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Time Entries Filter -->
        <div class="section">
            <h2>📋 Time Entries</h2>
            <form method="GET">
                <div class="form-row">
                    <?php if ($isManager): ?>
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
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $_GET['start_date'] ?? $filters['start_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $_GET['end_date'] ?? $filters['end_date']; ?>">
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">🔍 Filter Entries</button>
                    <a href="time_clock.php" class="btn btn-warning">🔄 Reset</a>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <?php if ($isManager): ?>
                                <th><i class="bi bi-person-fill me-2"></i>Employee</th>
                            <?php endif; ?>
                            <th><i class="bi bi-calendar3 me-2"></i>Date</th>
                            <th><i class="bi bi-clock me-2"></i>Clock In</th>
                            <th><i class="bi bi-clock-fill me-2"></i>Clock Out</th>
                            <th><i class="bi bi-cup-hot me-2"></i>Break Time</th>
                            <th><i class="bi bi-stopwatch me-2"></i>Total Hours</th>
                            <th><i class="bi bi-check-circle me-2"></i>Status</th>
                            <th><i class="bi bi-sticky me-2"></i>Notes</th>
                            <?php if ($isManager): ?>
                                <th><i class="bi bi-gear me-2"></i>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($timeEntries)): ?>
                            <tr>
                                <td colspan="<?php echo $isManager ? 9 : 8; ?>" style="text-align: center; padding: 30px; color: #6c757d;">
                                    No time entries found for the selected period
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($timeEntries as $entry): ?>
                                <tr>
                                    <?php if ($isManager): ?>
                                        <td>
                                            <strong><?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($entry['employee_id']); ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td><?php echo date('M j, Y', strtotime($entry['clock_in'])); ?></td>
                                    <td><?php echo $entry['clock_in'] ? date('g:i A', strtotime($entry['clock_in'])) : '-'; ?></td>
                                    <td><?php echo $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : '-'; ?></td>
                                    <td><?php echo ($entry['total_break_minutes'] ?? 0) > 0 ? $entry['total_break_minutes'] . ' min' : '-'; ?></td>
                                    <td>
                                        <?php if ($entry['total_hours'] !== null && $entry['clock_out']): ?>
                                            <strong><?php echo number_format($entry['total_hours'], 2); ?>h</strong>
                                            <?php if ($entry['overtime_hours'] > 0): ?>
                                                <br><small style="color: #ffc107;">+<?php echo number_format($entry['overtime_hours'], 2); ?>h OT</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($entry['clock_out'])): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-play-circle me-1"></i>Working
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-check-circle me-1"></i>Completed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($entry['notes'] ?? ''); ?></td>
                                    <?php if ($isManager): ?>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-outline-primary btn-sm edit-btn" data-entry-id="<?php echo $entry['id']; ?>" title="Edit Entry">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm delete-btn" data-entry-id="<?php echo $entry['id']; ?>" data-employee-name="<?php echo htmlspecialchars(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? '')); ?>" data-entry-date="<?php echo $entry['date'] ?? ''; ?>" title="Delete Entry">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <?php if ($isManager): ?>
        <div id="manualEntryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Add Manual Time Entry</h2>
                    <span class="close text-white" onclick="closeManualEntryModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" onsubmit="return preventDoubleSubmit(this)">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="manual_employee_id" class="form-label">Employee *</label>
                            <select id="manual_employee_id" name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="manual_clock_in" class="form-label">Clock In Date & Time *</label>
                                    <input type="datetime-local" id="manual_clock_in" name="clock_in" class="form-control" required>
                                </div>
                            </div>
                        <div class="form-group">
                            <label for="manual_clock_out">Clock Out Date & Time</label>
                            <input type="datetime-local" id="manual_clock_out" name="clock_out">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="manual_break_minutes">Break Minutes</label>
                        <input type="number" id="manual_break_minutes" name="break_minutes" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="manual_notes">Notes</label>
                        <textarea id="manual_notes" name="notes" rows="3" placeholder="Reason for manual entry..."></textarea>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" name="manual_entry" class="btn btn-success">✏️ Add Entry</button>
                        <button type="button" onclick="closeManualEntryModal()" class="btn btn-warning">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Time Entry Modal -->
        <div id="editEntryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditEntryModal()">&times;</span>
                <h2>📝 Edit Time Entry</h2>
                <form id="editEntryForm">
                    <input type="hidden" id="editEntryId" name="entry_id">
                    <div class="form-group">
                        <label for="editEmployeeId">Employee *</label>
                        <select id="editEmployeeId" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDate">Date *</label>
                        <input type="date" id="editDate" name="date" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editClockIn">Clock In Time *</label>
                            <input type="time" id="editClockIn" name="clock_in" required>
                        </div>
                        <div class="form-group">
                            <label for="editClockOut">Clock Out Time</label>
                            <input type="time" id="editClockOut" name="clock_out">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea id="editNotes" name="notes" rows="3" placeholder="Edit notes..."></textarea>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" class="btn btn-success" id="updateEntryBtn">💾 Update Entry</button>
                        <button type="button" onclick="closeEditEntryModal()" class="btn btn-warning">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Update clock every second
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('currentDate').textContent = dateString;
        }

        // Start the clock
        updateClock();
        setInterval(updateClock, 1000);

        function openManualEntryModal() {
            document.getElementById('manualEntryModal').style.display = 'block';
            // Set default date to today
            const now = new Date();
            const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('manual_clock_in').value = localDateTime;
        }

        function closeManualEntryModal() {
            document.getElementById('manualEntryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('manualEntryModal');
            if (event.target == modal) {
                closeManualEntryModal();
            }
        }

        // Auto-refresh page every 5 minutes to keep data current
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Edit time entry functions
        function editTimeEntry(entry) {
            document.getElementById('editEntryId').value = entry.id;
            document.getElementById('editEmployeeId').value = entry.employee_id;
            document.getElementById('editDate').value = entry.date;
            document.getElementById('editClockIn').value = entry.clock_in;
            document.getElementById('editClockOut').value = entry.clock_out || '';
            document.getElementById('editNotes').value = entry.notes || '';
            
            document.getElementById('editEntryModal').style.display = 'block';
        }

        // Delete time entry function
        function deleteTimeEntry(entryId, employeeName, date) {
            if (confirm('Are you sure you want to delete the time entry for ' + employeeName + ' on ' + date + '?\n\nThis action cannot be undone.')) {
                var formData = new FormData();
                formData.append('action', 'delete_entry');
                formData.append('entry_id', entryId);
                
                fetch('time_clock.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        showAlert('Time entry deleted successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert(data.message || 'Failed to delete entry', 'error');
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    showAlert('Network error occurred', 'error');
                });
            }
        }

        // Add event listeners for edit and delete buttons after page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Edit buttons - full functionality
            var editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var entryId = this.getAttribute('data-entry-id');
                    
                    // Find the entry data from the page
                    var row = this.closest('tr');
                    var cells = row.cells;
                    
                    // Extract data from the table row
                    var employeeName = cells[0].textContent.split('\n')[0].trim();
                    var employeeId = cells[0].textContent.split('\n')[1].trim(); // Employee ID
                    var date = cells[1].textContent.trim();
                    var clockIn = cells[2].textContent.trim();
                    var clockOut = cells[3].textContent.trim();
                    var notes = cells[7].textContent.trim();
                    
                    // Populate the edit modal
                    document.getElementById('editEntryId').value = entryId;
                    document.getElementById('editDate').value = convertDateToInput(date);
                    document.getElementById('editClockIn').value = convertTimeToInput(clockIn);
                    document.getElementById('editClockOut').value = clockOut !== '-' ? convertTimeToInput(clockOut) : '';
                    document.getElementById('editNotes').value = notes;
                    
                    // Set the employee dropdown by finding the option that contains the employee name
                    var employeeSelect = document.getElementById('editEmployeeId');
                    for (var i = 0; i < employeeSelect.options.length; i++) {
                        if (employeeSelect.options[i].text.toLowerCase().includes(employeeName.toLowerCase())) {
                            employeeSelect.selectedIndex = i;
                            break;
                        }
                    }
                    
                    // Show the modal
                    document.getElementById('editEntryModal').style.display = 'block';
                });
            });
            
            // Delete buttons
            var deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var entryId = this.getAttribute('data-entry-id');
                    var employeeName = this.getAttribute('data-employee-name');
                    var entryDate = this.getAttribute('data-entry-date');
                    
                    deleteTimeEntry(entryId, employeeName, entryDate);
                });
            });
        });

        function closeEditEntryModal() {
            document.getElementById('editEntryModal').style.display = 'none';
        }

        // Close edit modal when clicking outside
        window.onclick = function(event) {
            const manualModal = document.getElementById('manualEntryModal');
            const editModal = document.getElementById('editEntryModal');
            if (event.target == manualModal) {
                closeManualEntryModal();
            } else if (event.target == editModal) {
                closeEditEntryModal();
            }
        }

        // Simple edit entry form handling
        document.getElementById('editEntryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            var submitBtn = document.getElementById('updateEntryBtn');
            submitBtn.innerHTML = 'Updating...';
            submitBtn.disabled = true;
            
            // Get form data manually
            var entryId = document.getElementById('editEntryId').value;
            var employeeId = document.getElementById('editEmployeeId').value;
            var date = document.getElementById('editDate').value;
            var clockIn = document.getElementById('editClockIn').value;
            var clockOut = document.getElementById('editClockOut').value;
            var notes = document.getElementById('editNotes').value;
            
            console.log('Form data:', {entryId, employeeId, date, clockIn, clockOut, notes});
            
            // Create simple POST request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'time_clock.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                console.log('XHR Response:', xhr.responseText);
                
                // Reset button immediately
                submitBtn.innerHTML = '💾 Update Entry';
                submitBtn.disabled = false;
                
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        alert('Entry updated successfully!');
                        closeEditEntryModal();
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update'));
                    }
                } catch(e) {
                    alert('Server error occurred');
                    console.error('Parse error:', e);
                }
            };
            
            xhr.onerror = function() {
                console.log('XHR Error');
                submitBtn.innerHTML = '💾 Update Entry';
                submitBtn.disabled = false;
                alert('Network error occurred');
            };
            
            // Send data
            var postData = 'action=update_entry&entry_id=' + entryId + 
                          '&employee_id=' + employeeId + 
                          '&date=' + encodeURIComponent(date) + 
                          '&clock_in=' + encodeURIComponent(clockIn) + 
                          '&clock_out=' + encodeURIComponent(clockOut) + 
                          '&notes=' + encodeURIComponent(notes);
            
            console.log('Sending:', postData);
            xhr.send(postData);
        });

        function showAlert(message, type = 'info') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alert, container.firstChild);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Helper functions for date/time conversion
        function convertDateToInput(dateString) {
            // Convert "Oct 1, 2025" to "2025-10-01"
            var date = new Date(dateString);
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0');
        }

        function convertTimeToInput(timeString) {
            // Convert "7:30 AM" to "07:30"
            if (timeString === '-' || !timeString) return '';
            
            var time = timeString.trim();
            var isPM = time.includes('PM');
            var isAM = time.includes('AM');
            
            time = time.replace(/[AP]M/, '').trim();
            var parts = time.split(':');
            var hours = parseInt(parts[0]);
            var minutes = parts[1] || '00';
            
            if (isPM && hours !== 12) hours += 12;
            if (isAM && hours === 12) hours = 0;
            
            return String(hours).padStart(2, '0') + ':' + minutes;
        }

        // Emergency button reset function - can be called from console
        function forceResetUpdateButton() {
            var btn = document.getElementById('updateEntryBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '💾 Update Entry';
                console.log('Button force reset complete');
            }
            editFormSubmitting = false;
        }

        // Prevent double form submissions
        function preventDoubleSubmit(form) {
            if (form.submitted) {
                alert('⚠️ Please wait - your request is being processed...');
                return false;
            }
            
            form.submitted = true;
            
            // Find the submit button and disable it
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '⏳ Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after 5 seconds as failsafe
                setTimeout(() => {
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        form.submitted = false;
                    }
                }, 5000);
            }
            
            return true;
        }
    </script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>