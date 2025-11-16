<?php
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set timezone FIRST - before any database operations
if (!isset($_SESSION['current_hotel_id'])) {
    $_SESSION['current_hotel_id'] = 1;
}

// Locate includes directory
$possibleIncludeDirs = [
    __DIR__ . '/includes',
    __DIR__ . '/../includes',
    dirname(__DIR__) . '/includes'
];

$includesDir = null;
foreach ($possibleIncludeDirs as $dir) {
    if ($dir && is_dir($dir)) {
        $includesDir = realpath($dir);
        break;
    }
}

if (!$includesDir) {
    die('Unable to locate includes directory.');
}

// Load required classes
require_once $includesDir . '/classes.php';

// Get hotel timezone and set it
$db = new Database();
$conn = $db->getConnection();
$timezone_stmt = $conn->prepare("SELECT timezone FROM hotel_info WHERE id = ? LIMIT 1");
$timezone_stmt->execute([$_SESSION['current_hotel_id']]);
$timezone_result = $timezone_stmt->fetch(PDO::FETCH_ASSOC);
if ($timezone_result) {
    $hotel_timezone = $timezone_result['timezone'] ?? 'America/Lima';
    date_default_timezone_set($hotel_timezone);
} else {
    date_default_timezone_set('America/Lima');
}

require_once $includesDir . '/hotel_classes.php';
require_once $includesDir . '/employee_classes.php';

// Load template files
$templateFiles = [
    $includesDir . '/templates/head_tailwind.php',
    $includesDir . '/templates/sidebar_tailwind.php',
    $includesDir . '/templates/topbar_tailwind.php'
];

foreach ($templateFiles as $templateFile) {
    if (!file_exists($templateFile)) {
        throw new RuntimeException('Missing template file: ' . $templateFile);
    }
    require_once $templateFile;
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_role'])) {
    header('Location: owner_login.php');
    exit;
}

// Initialize managers
$employeeManager = new EmployeeManager();
$timeClockManager = new TimeClockManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Check user role
$isManager = false;
if (isset($_SESSION['user_role'])) {
    $isManager = in_array($_SESSION['user_role'], ['manager', 'admin', 'owner']);
}

// Get user details
$userStmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!empty($user)) {
    if (empty($user['full_name']) && (isset($user['first_name']) || isset($user['last_name']))) {
        $user['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    }
    $user['user_role'] = $user['user_role'] ?? $user['role'] ?? $_SESSION['user_role'];
} else {
    $user = [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'user_role' => $_SESSION['user_role'] ?? 'staff'
    ];
}

// Add missing columns to time_clock table
try {
    $columnsToAdd = [
        'is_manual_entry' => 'BOOLEAN DEFAULT FALSE',
        'created_by' => 'INT DEFAULT NULL',
        'total_break_minutes' => 'INT DEFAULT NULL',
        'break_start' => 'DATETIME DEFAULT NULL',
        'break_end' => 'DATETIME DEFAULT NULL'
    ];
    
    foreach ($columnsToAdd as $column => $definition) {
        $stmt = $conn->prepare("SHOW COLUMNS FROM time_clock LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->rowCount() == 0) {
            $conn->exec("ALTER TABLE time_clock ADD COLUMN $column $definition");
        }
    }
} catch (Exception $e) {
    // Columns might already exist
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle flash messages
$message = '';
$messageType = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid security token. Please refresh the page.';
        $messageType = 'error';
    } else if (isset($_POST['clock_in'])) {
        $submissionKey = 'last_clock_in_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->clockIn((int)$_POST['employee_id'], 'hotel', $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF'] . (!empty($_POST['employee_id']) ? '?employee_id=' . (int)$_POST['employee_id'] : ''));
                exit;
            }
        }
    }
    
    else if (isset($_POST['clock_out'])) {
        $submissionKey = 'last_clock_out_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->clockOut((int)$_POST['employee_id'], $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF'] . (!empty($_POST['employee_id']) ? '?employee_id=' . (int)$_POST['employee_id'] : ''));
                exit;
            }
        }
    }
    
    else if (isset($_POST['start_break'])) {
        $submissionKey = 'last_break_start_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->startBreak((int)$_POST['employee_id'], $_POST['break_type'] ?? 'break', $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF'] . (!empty($_POST['employee_id']) ? '?employee_id=' . (int)$_POST['employee_id'] : ''));
                exit;
            }
        }
    }
    
    else if (isset($_POST['end_break'])) {
        $submissionKey = 'last_break_end_' . (int)$_POST['employee_id'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 5) {
            $message = 'Duplicate submission prevented. Please wait.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->endBreak((int)$_POST['employee_id'], $_POST['notes'] ?? '');
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: ' . $_SERVER['PHP_SELF'] . (!empty($_POST['employee_id']) ? '?employee_id=' . (int)$_POST['employee_id'] : ''));
                exit;
            }
        }
    }
    
    else if (isset($_POST['manual_entry']) && $isManager) {
        $submissionKey = 'last_manual_entry_' . (int)$_POST['employee_id'] . '_' . $_POST['clock_in'];
        $currentTime = time();
        
        if (isset($_SESSION[$submissionKey]) && ($currentTime - $_SESSION[$submissionKey]) < 10) {
            $message = 'Duplicate manual entry prevented. Please wait.';
            $messageType = 'warning';
        } else {
            $_SESSION[$submissionKey] = $currentTime;
            $result = $timeClockManager->addManualEntry(
                (int)$_POST['employee_id'],
                $_POST['clock_in'],
                $_POST['clock_out'] ?? null,
                $_POST['break_minutes'] ?? 0,
                $_POST['notes'] ?? '',
                $_SESSION['user_id']
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
    // Find employee record by user_id
    $empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ? OR id = ? LIMIT 1");
    $empStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $empRecord = $empStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($empRecord) {
        $filters['employee_id'] = $empRecord['id'];
    } else {
        // If no employee record found, try to use session user_id directly
        $filters['employee_id'] = $_SESSION['user_id'];
    }
    
    // Force date filters for non-managers (last 30 days)
    if (empty($_GET['start_date'])) {
        $filters['start_date'] = date('Y-m-d', strtotime('-30 days'));
    } else {
        $filters['start_date'] = $_GET['start_date'];
    }
    if (empty($_GET['end_date'])) {
        $filters['end_date'] = date('Y-m-d');
    } else {
        $filters['end_date'] = $_GET['end_date'];
    }
}

// Default to current week
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

// Initialize templates
$headTemplate = new HeadTemplate('Time Clock', $hotel['hotel_name'] ?? 'AiNi Travel PMS');
$sidebarTemplate = new SidebarTemplate($hotel['hotel_name'] ?? 'AiNi Travel PMS', 'time_clock', $user['user_role']);
$topbarTemplate = new TopbarTemplate($user, true);

$headTemplate->render();
?>
<body class="min-h-screen bg-slate-100 dark:bg-gray-950 text-slate-900 dark:text-gray-100 transition-colors duration-300">
    <?php $sidebarTemplate->render(); ?>
    <?php $topbarTemplate->render(); ?>
    
    <div class="content-offset pt-20 px-6 lg:ml-64">
        <div class="max-w-7xl mx-auto space-y-8 pb-16">
            
            <!-- Live Clock Display -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 text-white text-center">
                <div class="text-5xl font-bold mb-2" id="currentTime"></div>
                <div class="text-xl opacity-90" id="currentDate"></div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-100' : ($messageType === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 text-yellow-700 dark:text-yellow-100' : 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-100'); ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : ($messageType === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-<?php echo $isManager ? '4' : '2'; ?> gap-6">
                <?php if ($isManager): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Currently Working</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo count($currentlyWorking); ?></p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 rounded-full p-3">
                            <i class="fas fa-users text-2xl text-green-600 dark:text-green-300"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Today's Entries</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo count($todayEntries); ?></p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3">
                            <i class="fas fa-clipboard-list text-2xl text-blue-600 dark:text-blue-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Today's Hours</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo number_format($todayHours, 1); ?></p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900 rounded-full p-3">
                            <i class="fas fa-clock text-2xl text-purple-600 dark:text-purple-300"></i>
                        </div>
                    </div>
                </div>

                <?php if ($isManager): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Period Entries</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo count($timeEntries); ?></p>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-3">
                            <i class="fas fa-calendar text-2xl text-yellow-600 dark:text-yellow-300"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Currently Working Employees (Managers Only) -->
            <?php if ($isManager && !empty($currentlyWorking)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                        <i class="fas fa-user-clock mr-2"></i>Currently Working
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($currentlyWorking as $worker): ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 dark:text-gray-100">
                                            <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $worker['position']))); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            In: <?php echo date('g:i A', strtotime($worker['clock_in'])); ?>
                                        </div>
                                        <?php if (!empty($worker['on_break'])): ?>
                                            <span class="inline-block bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded text-xs font-semibold mt-1">
                                                On Break
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                        <?php echo number_format($worker['hours_today'] ?? 0, 1); ?>h
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Employee Self-Service Clock (Non-Managers) -->
            <?php if (!$isManager): ?>
                <?php
                // Check if employee is currently clocked in
                $empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ? OR id = ? LIMIT 1");
                $empStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                $myEmpRecord = $empStmt->fetch(PDO::FETCH_ASSOC);
                $myEmployeeId = $myEmpRecord['id'] ?? $_SESSION['user_id'];
                
                $isClockedIn = false;
                $isOnBreak = false;
                foreach ($currentlyWorking as $worker) {
                    if ($worker['id'] == $myEmployeeId) {
                        $isClockedIn = true;
                        $isOnBreak = !empty($worker['on_break']);
                        break;
                    }
                }
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6 text-center">
                        <i class="fas fa-user-clock mr-2"></i>My Time Clock
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto">
                        <?php if (!$isClockedIn): ?>
                            <!-- Clock In Form -->
                            <form method="POST" class="md:col-span-2">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="employee_id" value="<?php echo $myEmployeeId; ?>">
                                <button type="submit" name="clock_in" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg">
                                    <i class="fas fa-play-circle mr-2 text-2xl"></i>Clock In
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Clock Out & Break Forms -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="employee_id" value="<?php echo $myEmployeeId; ?>">
                                <button type="submit" name="clock_out" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg">
                                    <i class="fas fa-stop-circle mr-2 text-2xl"></i>Clock Out
                                </button>
                            </form>
                            
                            <?php if (!$isOnBreak): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="employee_id" value="<?php echo $myEmployeeId; ?>">
                                    <input type="hidden" name="break_type" value="break">
                                    <button type="submit" name="start_break" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg">
                                        <i class="fas fa-coffee mr-2 text-2xl"></i>Start Break
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="employee_id" value="<?php echo $myEmployeeId; ?>">
                                    <button type="submit" name="end_break" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition text-lg">
                                        <i class="fas fa-play mr-2 text-2xl"></i>End Break
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isClockedIn): ?>
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-1"></i>
                                You are currently clocked in
                                <?php if ($isOnBreak): ?>
                                    <span class="ml-2 text-yellow-600 dark:text-yellow-400">
                                        <i class="fas fa-coffee mr-1"></i>On break
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions (Managers Only) -->
            <?php if ($isManager): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Clock In -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-green-600 dark:bg-green-700 text-white px-6 py-4">
                            <h3 class="text-lg font-bold"><i class="fas fa-play-circle mr-2"></i>Clock In Employee</h3>
                        </div>
                        <form method="POST" class="p-6 space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Select Employee</label>
                                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-green-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">Choose employee...</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['id']; ?>">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                                <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-green-500 dark:bg-gray-700 dark:text-gray-100 resize-none" placeholder="Optional notes..."></textarea>
                            </div>
                            <button type="submit" name="clock_in" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                                <i class="fas fa-play-circle mr-2"></i>Clock In
                            </button>
                        </form>
                    </div>

                    <!-- Clock Out -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-red-600 dark:bg-red-700 text-white px-6 py-4">
                            <h3 class="text-lg font-bold"><i class="fas fa-stop-circle mr-2"></i>Clock Out Employee</h3>
                        </div>
                        <form method="POST" class="p-6 space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Select Employee</label>
                                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-red-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">Choose employee...</option>
                                    <?php foreach ($currentlyWorking as $worker): ?>
                                        <option value="<?php echo $worker['id']; ?>">
                                            <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                                <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-red-500 dark:bg-gray-700 dark:text-gray-100 resize-none" placeholder="Optional notes..."></textarea>
                            </div>
                            <button type="submit" name="clock_out" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                                <i class="fas fa-stop-circle mr-2"></i>Clock Out
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Break Management & Manual Entry -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Break Management -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-yellow-600 dark:bg-yellow-700 text-white px-6 py-4">
                            <h3 class="text-lg font-bold"><i class="fas fa-coffee mr-2"></i>Break Management</h3>
                        </div>
                        <form method="POST" class="p-6 space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Select Employee</label>
                                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">Choose employee...</option>
                                    <?php foreach ($currentlyWorking as $worker): ?>
                                        <option value="<?php echo $worker['id']; ?>">
                                            <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                            <?php echo !empty($worker['on_break']) ? ' (On Break)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Break Type</label>
                                <select name="break_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="break">Regular Break</option>
                                    <option value="lunch">Lunch Break</option>
                                    <option value="bathroom">Bathroom Break</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                                <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500 dark:bg-gray-700 dark:text-gray-100 resize-none" placeholder="Optional notes..."></textarea>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" name="start_break" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                                    <i class="fas fa-pause mr-2"></i>Start Break
                                </button>
                                <button type="submit" name="end_break" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                                    <i class="fas fa-play mr-2"></i>End Break
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Manual Entry -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-purple-600 dark:bg-purple-700 text-white px-6 py-4">
                            <h3 class="text-lg font-bold"><i class="fas fa-edit mr-2"></i>Manual Entry</h3>
                        </div>
                        <div class="p-6">
                            <button type="button" onclick="openManualEntryModal()" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition mb-3">
                                <i class="fas fa-plus-circle mr-2"></i>Add Manual Entry
                            </button>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                Add time entries manually for missed clock in/out
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                    <i class="fas fa-filter mr-2"></i><?php echo $isManager ? 'Filter Time Entries' : 'My Time Entries'; ?>
                </h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-<?php echo $isManager ? '4' : '3'; ?> gap-4">
                    <?php if ($isManager): ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Employee</label>
                            <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">All Employees</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>" <?php echo ($_GET['employee_id'] ?? '') == $employee['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? $filters['start_date']); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? $filters['end_date']); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                        <a href="?" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg transition text-center">
                            <i class="fas fa-undo mr-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Time Entries Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                        <i class="fas fa-table mr-2"></i>Time Entries
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Date</th>
                                <?php if ($isManager): ?>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Employee</th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Clock In</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Clock Out</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Break</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Total Hours</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php if (empty($timeEntries)): ?>
                                <tr>
                                    <td colspan="<?php echo $isManager ? '7' : '6'; ?>" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-clock text-4xl mb-4 block opacity-50"></i>
                                        No time entries found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($timeEntries as $entry): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                            <?php echo $entry['clock_in'] ? date('M d, Y', strtotime($entry['clock_in'])) : '-'; ?>
                                        </td>
                                        <?php if ($isManager): ?>
                                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                                <?php echo htmlspecialchars(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? '')); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                            <?php echo $entry['clock_in'] ? date('g:i A', strtotime($entry['clock_in'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                            <?php echo $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : '<span class="text-green-600 dark:text-green-400 font-semibold">Working</span>'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                            <?php echo ($entry['total_break_minutes'] ?? 0) . ' min'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                            <?php echo number_format($entry['total_hours'] ?? 0, 2); ?>h
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            <?php echo htmlspecialchars($entry['notes'] ?? ''); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <?php if ($isManager): ?>
        <div id="manualEntryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full">
                <div class="bg-purple-600 dark:bg-purple-700 text-white px-6 py-4 rounded-t-lg">
                    <h2 class="text-xl font-bold">Add Manual Time Entry</h2>
                </div>

                <form method="POST" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Employee *</label>
                            <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Clock In *</label>
                                <input type="datetime-local" name="clock_in" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Clock Out</label>
                                <input type="datetime-local" name="clock_out" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Break Minutes</label>
                            <input type="number" name="break_minutes" value="0" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                            <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 resize-none" placeholder="Reason for manual entry..."></textarea>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-6 mt-6 border-t">
                        <button type="submit" name="manual_entry" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>Save Entry
                        </button>
                        <button type="button" onclick="closeManualEntryModal()" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-semibold py-3 px-4 rounded-lg transition">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Live clock update
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            const dateStr = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('currentTime').textContent = timeStr;
            document.getElementById('currentDate').textContent = dateStr;
        }
        
        updateClock();
        setInterval(updateClock, 1000);

        // Manual entry modal functions
        function openManualEntryModal() {
            document.getElementById('manualEntryModal').classList.remove('hidden');
        }

        function closeManualEntryModal() {
            document.getElementById('manualEntryModal').classList.add('hidden');
        }

        // Close modal on background click
        document.getElementById('manualEntryModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeManualEntryModal();
            }
        });
    </script>
</body>
</html>
