<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/rbac_helper.php';

$rbac = new RBACHelper();

// Only employees can access this page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

$db = new Database();
$conn = $db->getConnection();

// Handle clock in/out
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clock_in') {
        $stmt = $conn->prepare("INSERT INTO time_clock (employee_id, clock_in, status) VALUES (?, NOW(), 'clocked_in')");
        $stmt->execute([$userId]);
        $message = 'Clocked in successfully at ' . date('h:i A');
        $messageType = 'success';
    } elseif ($action === 'clock_out') {
        $stmt = $conn->prepare("
            UPDATE time_clock 
            SET clock_out = NOW(), 
                total_hours = TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60,
                status = 'clocked_out'
            WHERE employee_id = ? AND clock_out IS NULL
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $message = 'Clocked out successfully at ' . date('h:i A');
        $messageType = 'success';
    }
}

// Get current clock status
$stmt = $conn->prepare("
    SELECT * FROM time_clock 
    WHERE employee_id = ? AND clock_out IS NULL 
    ORDER BY clock_in DESC LIMIT 1
");
$stmt->execute([$userId]);
$currentClock = $stmt->fetch();
$isClockedIn = !empty($currentClock);

// Get today's hours
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(total_hours), 0) as today_hours 
    FROM time_clock 
    WHERE employee_id = ? AND DATE(clock_in) = CURDATE()
");
$stmt->execute([$userId]);
$todayStats = $stmt->fetch();

// Get this week's hours
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(total_hours), 0) as week_hours 
    FROM time_clock 
    WHERE employee_id = ? AND YEARWEEK(clock_in) = YEARWEEK(CURDATE())
");
$stmt->execute([$userId]);
$weekStats = $stmt->fetch();

// Get recent time entries
$stmt = $conn->prepare("
    SELECT * FROM time_clock 
    WHERE employee_id = ? 
    ORDER BY clock_in DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$recentEntries = $stmt->fetchAll();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Time Clock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .clock-status {
            font-size: 4rem;
            text-align: center;
            padding: 2rem;
        }
        .clock-button {
            font-size: 1.5rem;
            padding: 1.5rem 3rem;
            border-radius: 15px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">⏰ Time Clock - Welcome <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>!</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="clock-status">
                            <div id="currentTime" class="fw-bold text-primary"></div>
                            <div class="fs-4 text-muted"><?php echo date('l, F j, Y'); ?></div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <?php if ($isClockedIn): ?>
                                <div class="alert alert-success">
                                    <h5>✅ You are clocked IN</h5>
                                    <p class="mb-0">Since: <?php echo date('h:i A', strtotime($currentClock['clock_in'])); ?></p>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clock_out">
                                    <button type="submit" class="btn btn-danger clock-button">
                                        🚪 Clock Out
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h5>⏸️ You are clocked OUT</h5>
                                    <p class="mb-0">Ready to start your shift?</p>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clock_in">
                                    <button type="submit" class="btn btn-success clock-button">
                                        ✅ Clock In
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-card">
                                    <h5>Today's Hours</h5>
                                    <div class="fs-2 fw-bold">
                                        <?php echo number_format($todayStats['today_hours'], 2); ?> hrs
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-card">
                                    <h5>This Week's Hours</h5>
                                    <div class="fs-2 fw-bold">
                                        <?php echo number_format($weekStats['week_hours'], 2); ?> hrs
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($userInfo['hourly_rate'] > 0): ?>
                            <div class="alert alert-info">
                                <strong>💰 This Week's Estimated Earnings:</strong> 
                                S/. <?php echo number_format($weekStats['week_hours'] * $userInfo['hourly_rate'], 2); ?>
                                (Rate: S/. <?php echo number_format($userInfo['hourly_rate'], 2); ?>/hr)
                            </div>
                        <?php endif; ?>
                        
                        <h5 class="mt-4">Recent Time Entries</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentEntries as $entry): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($entry['clock_in'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($entry['clock_in'])); ?></td>
                                            <td>
                                                <?php 
                                                echo $entry['clock_out'] 
                                                    ? date('h:i A', strtotime($entry['clock_out'])) 
                                                    : '<span class="badge bg-success">Active</span>'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                echo $entry['total_hours'] 
                                                    ? number_format($entry['total_hours'], 2) . ' hrs' 
                                                    : '-'; 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentEntries)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No time entries yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="my_payroll.php" class="btn btn-outline-primary">
                                💰 View My Payroll
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>
