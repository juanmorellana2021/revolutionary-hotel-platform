<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/templates/head_tailwind.php';
require_once 'includes/templates/sidebar_tailwind.php';
require_once 'includes/templates/topbar_tailwind.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: owner_login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("
    SELECT u.*, e.employee_id, e.department, e.position, e.employment_status
    FROM users u
    LEFT JOIN employees e ON u.id = e.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

$headTemplate = new HeadTemplate('My Profile', $hotel['hotel_name'] ?? 'AiNi Travel PMS');
$headTemplate->render();

$sidebarTemplate = new SidebarTemplate($hotel['hotel_name'] ?? 'AiNi Travel PMS', 'profile', $user['role'] ?? 'staff');

$topbarTemplate = new TopbarTemplate();
$topbarTemplate->setUser($user);

$name = $user['full_name'] ?? $user['username'] ?? 'User';
$words = explode(' ', trim($name));
$initials = count($words) >= 2 ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($name, 0, 2));
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <?php $sidebarTemplate->render(); ?>
    
    <div class="content-offset ml-64 transition-all duration-300">
        <?php $topbarTemplate->render(); ?>
        
        <main class="topbar-offset pt-16 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">My Profile</h1>
                <p class="text-gray-600 dark:text-gray-400">View your profile information</p>
            </div>

            <div class="max-w-4xl">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-32"></div>
                    <div class="px-6 pb-6">
                        <div class="flex items-end -mt-16 mb-4">
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-4xl border-4 border-white dark:border-gray-800 shadow-lg">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                <?php echo htmlspecialchars($name); ?>
                            </h2>
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <i class="fas fa-envelope text-sm"></i>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-user-circle text-blue-500"></i>
                            Account Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Full Name</label>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['full_name'] ?? 'Not set'); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Email Address</label>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Username</label>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username'] ?? 'Not set'); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Account Role</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-shield-alt mr-2"></i>
                                    <?php echo htmlspecialchars(ucfirst($user['role'] ?? 'Staff')); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['employee_id']): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-id-badge text-purple-500"></i>
                            Employee Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Employee ID</label>
                                <p class="text-gray-900 dark:text-white font-mono"><?php echo htmlspecialchars($user['employee_id']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Department</label>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['department'] ?? 'Not assigned'); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Position</label>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['position'] ?? 'Not assigned'); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Status</label>
                                <?php
                                $statusColors = [
                                    'active' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                    'inactive' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300',
                                    'terminated' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                                ];
                                $status = $user['employment_status'] ?? 'active';
                                $colorClass = $statusColors[$status] ?? $statusColors['active'];
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $colorClass; ?>">
                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
