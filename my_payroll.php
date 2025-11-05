<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/rbac_helper.php';

$rbac = new RBACHelper();

// Only employees can access this page (or managers viewing employee payroll)
if (!isset($_SESSION['user_role'])) {
    header('Location: index.php');
    exit;
}

// Employees can only view their own payroll
$userId = $_SESSION['user_id'];
$canViewAll = $rbac->hasPermission('view_payroll');

// If viewing specific employee and not authorized
if (isset($_GET['employee_id']) && !$canViewAll) {
    if ($_GET['employee_id'] != $userId) {
        header('Location: my_payroll.php?error=unauthorized');
        exit;
    }
}

$viewingUserId = $_GET['employee_id'] ?? $userId;

$db = new Database();
$conn = $db->getConnection();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$viewingUserId]);
$userInfo = $stmt->fetch();

if (!$userInfo) {
    die("User not found");
}

// Get payroll period filter
$period = $_GET['period'] ?? 'current_month';
$startDate = '';
$endDate = '';

switch ($period) {
    case 'current_month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'last_month':
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'current_year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
    case 'custom':
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        break;
}

// Get time clock entries for period
$stmt = $conn->prepare("
    SELECT 
        DATE(clock_in) as work_date,
        COUNT(*) as shifts,
        SUM(total_hours) as total_hours,
        SUM(overtime_hours) as overtime_hours
    FROM time_clock 
    WHERE employee_id = ? 
    AND DATE(clock_in) BETWEEN ? AND ?
    AND clock_out IS NOT NULL
    GROUP BY DATE(clock_in)
    ORDER BY work_date DESC
");
$stmt->execute([$viewingUserId, $startDate, $endDate]);
$timeEntries = $stmt->fetchAll();

// Calculate totals
$totalHours = 0;
$totalOvertimeHours = 0;
$totalRegularPay = 0;
$totalOvertimePay = 0;
$totalGrossPay = 0;

$hourlyRate = $userInfo['hourly_rate'] ?? 0;
$overtimeRate = $hourlyRate * 1.5; // Overtime is 1.5x regular rate

foreach ($timeEntries as $entry) {
    $totalHours += $entry['total_hours'];
    $totalOvertimeHours += $entry['overtime_hours'];
}

$regularHours = $totalHours - $totalOvertimeHours;
$totalRegularPay = $regularHours * $hourlyRate;
$totalOvertimePay = $totalOvertimeHours * $overtimeRate;
$totalGrossPay = $totalRegularPay + $totalOvertimePay;

// Get any payroll records if they exist
$stmt = $conn->prepare("
    SELECT * FROM payroll 
    WHERE employee_id = ? 
    AND pay_period_start >= ? 
    AND pay_period_end <= ?
    ORDER BY pay_period_start DESC
");
$stmt->execute([$viewingUserId, $startDate, $endDate]);
$payrollRecords = $stmt->fetchAll();

// Get year-to-date summary
$stmt = $conn->prepare("
    SELECT 
        SUM(total_hours) as ytd_hours,
        SUM(overtime_hours) as ytd_overtime
    FROM time_clock 
    WHERE employee_id = ? 
    AND YEAR(clock_in) = YEAR(CURDATE())
    AND clock_out IS NOT NULL
");
$stmt->execute([$viewingUserId]);
$ytdStats = $stmt->fetch();

$ytdGrossPay = (($ytdStats['ytd_hours'] - $ytdStats['ytd_overtime']) * $hourlyRate) + 
               ($ytdStats['ytd_overtime'] * $overtimeRate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payroll - Hotel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .payroll-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .stat-box {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            💰 Payroll Information
                            <?php if ($canViewAll): ?>
                                - <?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Period Selector -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <select name="period" id="periodSelect" class="form-select" onchange="toggleCustomDates()">
                                        <option value="current_month" <?php echo $period === 'current_month' ? 'selected' : ''; ?>>Current Month</option>
                                        <option value="last_month" <?php echo $period === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                        <option value="current_year" <?php echo $period === 'current_year' ? 'selected' : ''; ?>>Year to Date</option>
                                        <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                    </select>
                                </div>
                                <div class="col-md-3" id="customDatesStart" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="col-md-3" id="customDatesEnd" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> View
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Summary Cards -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-box text-center">
                                    <h6 class="text-muted">Total Hours</h6>
                                    <h3 class="text-primary"><?php echo number_format($totalHours, 2); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-box text-center">
                                    <h6 class="text-muted">Overtime Hours</h6>
                                    <h3 class="text-warning"><?php echo number_format($totalOvertimeHours, 2); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-box text-center">
                                    <h6 class="text-muted">Hourly Rate</h6>
                                    <h3 class="text-info">S/. <?php echo number_format($hourlyRate, 2); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-box text-center">
                                    <h6 class="text-muted">Gross Pay</h6>
                                    <h3 class="text-success">S/. <?php echo number_format($totalGrossPay, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pay Breakdown -->
                        <div class="payroll-card">
                            <h5>💵 Pay Breakdown (<?php echo date('M j', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>)</h5>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Regular Hours:</strong> <?php echo number_format($regularHours, 2); ?> hrs × S/. <?php echo number_format($hourlyRate, 2); ?></p>
                                    <p class="mb-1"><strong>Regular Pay:</strong> S/. <?php echo number_format($totalRegularPay, 2); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($totalOvertimeHours > 0): ?>
                                        <p class="mb-1"><strong>Overtime Hours:</strong> <?php echo number_format($totalOvertimeHours, 2); ?> hrs × S/. <?php echo number_format($overtimeRate, 2); ?></p>
                                        <p class="mb-1"><strong>Overtime Pay:</strong> S/. <?php echo number_format($totalOvertimePay, 2); ?></p>
                                    <?php else: ?>
                                        <p class="mb-1"><strong>Overtime:</strong> None</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr style="border-color: rgba(255,255,255,0.3);">
                            <h4 class="text-end mb-0">Total: S/. <?php echo number_format($totalGrossPay, 2); ?></h4>
                        </div>
                        
                        <!-- Year to Date Summary -->
                        <div class="alert alert-info">
                            <h6><strong>📊 Year to Date Summary (<?php echo date('Y'); ?>)</strong></h6>
                            <div class="row">
                                <div class="col-md-4">
                                    Total Hours: <strong><?php echo number_format($ytdStats['ytd_hours'] ?? 0, 2); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    Overtime: <strong><?php echo number_format($ytdStats['ytd_overtime'] ?? 0, 2); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    Gross Pay: <strong>S/. <?php echo number_format($ytdGrossPay, 2); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daily Breakdown -->
                        <h5 class="mt-4">📅 Daily Work Record</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Shifts</th>
                                        <th>Hours</th>
                                        <th>Overtime</th>
                                        <th>Regular Pay</th>
                                        <th>Overtime Pay</th>
                                        <th>Total Pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timeEntries as $entry): ?>
                                        <?php
                                        $dayRegularHours = $entry['total_hours'] - $entry['overtime_hours'];
                                        $dayRegularPay = $dayRegularHours * $hourlyRate;
                                        $dayOvertimePay = $entry['overtime_hours'] * $overtimeRate;
                                        $dayTotalPay = $dayRegularPay + $dayOvertimePay;
                                        ?>
                                        <tr>
                                            <td><?php echo date('D, M j, Y', strtotime($entry['work_date'])); ?></td>
                                            <td><?php echo $entry['shifts']; ?></td>
                                            <td><?php echo number_format($entry['total_hours'], 2); ?> hrs</td>
                                            <td><?php echo number_format($entry['overtime_hours'], 2); ?> hrs</td>
                                            <td>S/. <?php echo number_format($dayRegularPay, 2); ?></td>
                                            <td>S/. <?php echo number_format($dayOvertimePay, 2); ?></td>
                                            <td><strong>S/. <?php echo number_format($dayTotalPay, 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($timeEntries)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No work hours recorded for this period</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="employee_dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                            <?php if ($canViewAll): ?>
                                <a href="payroll_management.php" class="btn btn-primary">
                                    <i class="bi bi-people"></i> View All Payroll
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCustomDates() {
            const period = document.getElementById('periodSelect').value;
            const startDiv = document.getElementById('customDatesStart');
            const endDiv = document.getElementById('customDatesEnd');
            
            if (period === 'custom') {
                startDiv.style.display = 'block';
                endDiv.style.display = 'block';
            } else {
                startDiv.style.display = 'none';
                endDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
