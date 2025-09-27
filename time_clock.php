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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clock_in'])) {
        $result = $timeClockManager->clockIn((int)$_POST['employee_id'], $_POST['notes'] ?? '');
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['clock_out'])) {
        $result = $timeClockManager->clockOut((int)$_POST['employee_id'], $_POST['notes'] ?? '');
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['start_break'])) {
        $result = $timeClockManager->startBreak((int)$_POST['employee_id'], $_POST['break_type'] ?? 'break', $_POST['notes'] ?? '');
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['end_break'])) {
        $result = $timeClockManager->endBreak((int)$_POST['employee_id'], $_POST['notes'] ?? '');
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['manual_entry']) && $isManager) {
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
    }
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
                <h2>⚡ Quick Actions</h2>
                <div class="clock-actions">
                    <div class="clock-card">
                        <h3>🟢 Clock In Employee</h3>
                        <form method="POST">
                            <div class="form-group">
                                <select name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['id']; ?>">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <textarea name="notes" placeholder="Optional notes..." rows="2"></textarea>
                            </div>
                            <button type="submit" name="clock_in" class="btn btn-success">🟢 Clock In</button>
                        </form>
                    </div>
                    
                    <div class="clock-card">
                        <h3>🔴 Clock Out Employee</h3>
                        <form method="POST">
                            <div class="form-group">
                                <select name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($currentlyWorking as $worker): ?>
                                        <option value="<?php echo $worker['employee_id']; ?>">
                                            <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <textarea name="notes" placeholder="Optional notes..." rows="2"></textarea>
                            </div>
                            <button type="submit" name="clock_out" class="btn btn-danger">🔴 Clock Out</button>
                        </form>
                    </div>
                    
                    <div class="clock-card">
                        <h3>☕ Break Management</h3>
                        <form method="POST">
                            <div class="form-group">
                                <select name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($currentlyWorking as $worker): ?>
                                        <option value="<?php echo $worker['employee_id']; ?>">
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
            
            <div style="overflow-x: auto;">
                <table class="time-table">
                    <thead>
                        <tr>
                            <?php if ($isManager): ?>
                                <th>Employee</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Break Time</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($timeEntries)): ?>
                            <tr>
                                <td colspan="<?php echo $isManager ? 8 : 7; ?>" style="text-align: center; padding: 30px; color: #6c757d;">
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
                                    <td><?php echo date('M j, Y', strtotime($entry['date'])); ?></td>
                                    <td><?php echo $entry['clock_in'] ? date('g:i A', strtotime($entry['clock_in'])) : '-'; ?></td>
                                    <td><?php echo $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : '-'; ?></td>
                                    <td><?php echo $entry['break_minutes'] > 0 ? $entry['break_minutes'] . ' min' : '-'; ?></td>
                                    <td>
                                        <?php if ($entry['total_hours']): ?>
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
                                            <span class="badge badge-working">Working</span>
                                        <?php else: ?>
                                            <span class="badge badge-completed">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($entry['notes'] ?? ''); ?></td>
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
                <span class="close" onclick="closeManualEntryModal()">&times;</span>
                <h2>✏️ Add Manual Time Entry</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="manual_employee_id">Employee *</label>
                        <select id="manual_employee_id" name="employee_id" required>
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
                            <label for="manual_clock_in">Clock In Date & Time *</label>
                            <input type="datetime-local" id="manual_clock_in" name="clock_in" required>
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
    </script>
</body>
</html>