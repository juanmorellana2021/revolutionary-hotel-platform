<?php
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Locate includes directory - flexible path handling
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
    die('Unable to locate includes directory. Checked: ' . implode(', ', $possibleIncludeDirs));
}

// Load required classes
require_once $includesDir . '/classes.php';
require_once $includesDir . '/hotel_classes.php';
require_once $includesDir . '/employee_classes.php';

// Load template files (sidebar, topbar, etc.)
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
if (!isset($_SESSION['user_id'])) {
    header('Location: owner_login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Get user role if not set
if (!isset($_SESSION['user_role'])) {
    $roleStmt = $conn->prepare('SELECT user_role, role FROM users WHERE id = ? LIMIT 1');
    $roleStmt->execute([$_SESSION['user_id']]);
    $roleRow = $roleStmt->fetch(PDO::FETCH_ASSOC);
    if ($roleRow) {
        $_SESSION['user_role'] = $roleRow['user_role'] ?? $roleRow['role'] ?? null;
    }
}

// Check permissions - allow manager, admin, and owner
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'admin', 'owner'])) {
    header('Location: index.php');
    exit;
}

// Initialize managers
try {
    $employeeManager = new EmployeeManager();
    $timeClockManager = new TimeClockManager();
    $hotelInfo = new HotelInfo();
    $hotel = $hotelInfo->getHotelInfo();
} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
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
        'user_role' => $_SESSION['user_role'] ?? 'manager'
    ];
}

// Add currency columns to employees table if they don't exist
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM employees LIKE 'hourly_rate_currency'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE employees ADD COLUMN hourly_rate_currency VARCHAR(3) DEFAULT 'USD' AFTER hourly_rate");
    }
    
    $stmt = $conn->prepare("SHOW COLUMNS FROM employees LIKE 'overtime_rate_currency'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE employees ADD COLUMN overtime_rate_currency VARCHAR(3) DEFAULT 'USD' AFTER overtime_rate");
    }
} catch (Exception $e) {
    // Columns might already exist, continue silently
}

$message = null;
$messageType = null;

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
        
        // Make duplicate entry errors more user-friendly
        if (!$result['success'] && strpos($result['message'], 'Duplicate entry') !== false) {
            if (strpos($result['message'], 'employee_id') !== false) {
                $message = "Employee ID '{$_POST['employee_id']}' already exists. Please use a different Employee ID.";
            } elseif (strpos($result['message'], 'email') !== false) {
                $message = "Email '{$_POST['email']}' is already in use. Please use a different email address.";
            } else {
                $message = "This employee already exists in the system. Please check Employee ID and Email.";
            }
            $messageType = 'error';
        } else {
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    }
    
    if (isset($_POST['update_employee'])) {
        $USD_TO_PEN_RATE = 3.50;
        
        $hourlyRate = (float)$_POST['hourly_rate'];
        $hourlyRateCurrency = $_POST['hourly_rate_currency'] ?? 'USD';
        if ($hourlyRateCurrency === 'PEN') {
            $hourlyRate = $hourlyRate / $USD_TO_PEN_RATE;
        }
        
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

// Initialize template instances
$headTemplate = new HeadTemplate('Employee Management', $hotel['hotel_name'] ?? 'AiNi Travel PMS');
$sidebarTemplate = new SidebarTemplate($hotel['hotel_name'] ?? 'AiNi Travel PMS', 'employee_management', $user['user_role']);
$topbarTemplate = new TopbarTemplate($user, true);

$headTemplate->render();
?>
<body class="min-h-screen bg-slate-100 dark:bg-gray-950 text-slate-900 dark:text-gray-100 transition-colors duration-300">
    <?php $sidebarTemplate->render(); ?>
    <?php $topbarTemplate->render(); ?>
    
    <div class="content-offset pt-20 px-6 lg:ml-64">
        <div class="max-w-7xl mx-auto space-y-8 pb-16">
            
            <?php if (isset($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-100' : 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-100'; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Total Employees</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo count($totalEmployees); ?></p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3">
                            <i class="fas fa-users text-2xl text-blue-600 dark:text-blue-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Currently Working</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo $activeEmployees; ?></p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 rounded-full p-3">
                            <i class="fas fa-clock text-2xl text-green-600 dark:text-green-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Active Status</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo count(array_filter($totalEmployees, function($e) { return $e['employment_status'] === 'active'; })); ?></p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900 rounded-full p-3">
                            <i class="fas fa-user-check text-2xl text-purple-600 dark:text-purple-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-semibold uppercase">Today's Clock Ins</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo count($todayEntries); ?></p>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-3">
                            <i class="fas fa-sign-in-alt text-2xl text-yellow-600 dark:text-yellow-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                        <i class="fas fa-filter mr-2"></i>Filters
                    </h2>
                    <button type="button" onclick="openAddModal()" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Add Employee
                    </button>
                </div>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Department</label>
                        <select name="department" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">All Departments</option>
                            <option value="front_desk" <?php echo ($_GET['department'] ?? '') === 'front_desk' ? 'selected' : ''; ?>>Front Desk</option>
                            <option value="housekeeping" <?php echo ($_GET['department'] ?? '') === 'housekeeping' ? 'selected' : ''; ?>>Housekeeping</option>
                            <option value="maintenance" <?php echo ($_GET['department'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="food_beverage" <?php echo ($_GET['department'] ?? '') === 'food_beverage' ? 'selected' : ''; ?>>Food & Beverage</option>
                            <option value="management" <?php echo ($_GET['department'] ?? '') === 'management' ? 'selected' : ''; ?>>Management</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Position</label>
                        <input type="text" name="position" value="<?php echo htmlspecialchars($_GET['position'] ?? ''); ?>" placeholder="Position..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select name="employment_status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo ($_GET['employment_status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="on_leave" <?php echo ($_GET['employment_status'] ?? '') === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                            <option value="terminated" <?php echo ($_GET['employment_status'] ?? '') === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Name, email..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                        <a href="?" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg transition text-center">
                            <i class="fas fa-undo mr-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Employees Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                        <i class="fas fa-users mr-2"></i>Employee Records
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Employee ID</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Department</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Position</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Status</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Contact</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-users text-4xl mb-4 block opacity-50"></i>
                                        No employees found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                            <div class="font-semibold"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($employee['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                            <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-semibold">
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $employee['department']))); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($employee['position']); ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php 
                                            $statusColors = [
                                                'active' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                                'on_leave' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                                'terminated' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
                                            ];
                                            $statusColor = $statusColors[$employee['employment_status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-block <?php echo $statusColor; ?> px-3 py-1 rounded text-xs font-semibold">
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $employee['employment_status']))); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($employee['phone']); ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-semibold transition">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </button>
                                                <button onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-semibold transition">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800 dark:text-gray-100">Add Employee</h2>
            </div>

            <form method="POST" class="p-6">
                <input type="hidden" name="employee_id" id="employee_id" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 border-b pb-2">Personal Information</h3>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Employee ID *</label>
                        <input type="text" name="employee_id" id="emp_employee_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">First Name *</label>
                        <input type="text" name="first_name" id="first_name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email *</label>
                        <input type="email" name="email" id="email" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Phone *</label>
                        <input type="tel" name="phone" id="phone" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Address</label>
                        <input type="text" name="address" id="address" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <!-- Employment Information -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 border-b pb-2">Employment Information</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Department *</label>
                        <select name="department" id="department" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="front_desk">Front Desk</option>
                            <option value="housekeeping">Housekeeping</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="food_beverage">Food & Beverage</option>
                            <option value="management">Management</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Position *</label>
                        <input type="text" name="position" id="position" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Hire Date *</label>
                        <input type="date" name="hire_date" id="hire_date" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Employment Status</label>
                        <select name="employment_status" id="employment_status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>

                    <!-- Compensation -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 border-b pb-2">Compensation</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Salary Type *</label>
                        <select name="salary_type" id="salary_type" required onchange="toggleSalaryFields()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="hourly">Hourly</option>
                            <option value="monthly">Monthly Salary</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Weekly Hours *</label>
                        <input type="number" name="weekly_hours" id="weekly_hours" value="40" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div id="hourly_fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Hourly Rate *</label>
                            <div class="flex gap-2">
                                <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" required class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                <select name="hourly_rate_currency" id="hourly_rate_currency" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="USD">USD</option>
                                    <option value="PEN">PEN</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Overtime Rate</label>
                            <div class="flex gap-2">
                                <input type="number" name="overtime_rate" id="overtime_rate" step="0.01" placeholder="Auto: 1.5x hourly" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                <select name="overtime_rate_currency" id="overtime_rate_currency" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="USD">USD</option>
                                    <option value="PEN">PEN</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="monthly_fields" class="hidden">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Monthly Salary *</label>
                        <input type="number" name="monthly_salary" id="monthly_salary" step="0.01" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tax ID</label>
                        <input type="text" name="tax_id" id="tax_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <!-- Emergency Contact -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 border-b pb-2">Emergency Contact</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contact Name</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contact Phone</label>
                        <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100 resize-none"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-6 mt-6 border-t">
                    <button type="submit" id="submitBtn" name="add_employee" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Save Employee
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-semibold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSalaryFields() {
            const salaryType = document.getElementById('salary_type').value;
            const hourlyFields = document.getElementById('hourly_fields');
            const monthlyFields = document.getElementById('monthly_fields');
            const hourlyRate = document.getElementById('hourly_rate');
            const monthlySalary = document.getElementById('monthly_salary');
            
            if (salaryType === 'hourly') {
                hourlyFields.classList.remove('hidden');
                monthlyFields.classList.add('hidden');
                hourlyRate.required = true;
                monthlySalary.required = false;
            } else {
                hourlyFields.classList.add('hidden');
                monthlyFields.classList.remove('hidden');
                hourlyRate.required = false;
                monthlySalary.required = true;
            }
        }

        function openAddModal() {
            document.getElementById('employeeModal').classList.remove('hidden');
            document.getElementById('employee_id').value = '';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Save Employee';
            document.getElementById('submitBtn').name = 'add_employee';
            document.getElementById('modalTitle').textContent = 'Add Employee';
            document.querySelector('form').reset();
            document.getElementById('hire_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('employment_status').value = 'active';
            toggleSalaryFields();
        }

        function editEmployee(employee) {
            document.getElementById('employeeModal').classList.remove('hidden');
            document.getElementById('employee_id').value = employee.id;
            document.getElementById('emp_employee_id').value = employee.employee_id;
            document.getElementById('first_name').value = employee.first_name;
            document.getElementById('last_name').value = employee.last_name;
            document.getElementById('email').value = employee.email;
            document.getElementById('phone').value = employee.phone;
            document.getElementById('address').value = employee.address || '';
            document.getElementById('department').value = employee.department;
            document.getElementById('position').value = employee.position;
            document.getElementById('hire_date').value = employee.hire_date;
            document.getElementById('employment_status').value = employee.employment_status;
            document.getElementById('salary_type').value = employee.salary_type;
            document.getElementById('weekly_hours').value = employee.weekly_hours;
            document.getElementById('hourly_rate').value = employee.hourly_rate || '';
            document.getElementById('hourly_rate_currency').value = employee.hourly_rate_currency || 'USD';
            document.getElementById('overtime_rate').value = employee.overtime_rate || '';
            document.getElementById('overtime_rate_currency').value = employee.overtime_rate_currency || 'USD';
            document.getElementById('monthly_salary').value = employee.monthly_salary || '';
            document.getElementById('tax_id').value = employee.tax_id || '';
            document.getElementById('emergency_contact_name').value = employee.emergency_contact_name || '';
            document.getElementById('emergency_contact_phone').value = employee.emergency_contact_phone || '';
            document.getElementById('notes').value = employee.notes || '';
            
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update Employee';
            document.getElementById('submitBtn').name = 'update_employee';
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            toggleSalaryFields();
        }

        function deleteEmployee(employeeId, employeeName) {
            if (confirm(`Are you sure you want to delete ${employeeName}? This action cannot be undone.`)) {
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
            document.getElementById('employeeModal').classList.add('hidden');
        }

        document.getElementById('employeeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleSalaryFields();
        });
    </script>
</body>
</html>
