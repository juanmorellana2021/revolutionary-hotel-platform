<?php
/**
 * DASHBOARD - CLEAN TEMPLATE VERSION
 * Property Management System Dashboard
 * Uses template system for consistency
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/currency_manager.php';
require_once 'includes/templates/head_tailwind.php';
require_once 'includes/templates/sidebar_tailwind.php';
require_once 'includes/templates/topbar_tailwind.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: owner_login.php');
    exit;
}

// STAFF REDIRECT - Regular employees should only access time clock
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare("SELECT user_role, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userInfo && $userInfo['user_role'] === 'guest' && $userInfo['role'] === 'staff') {
    header('Location: time_clock.php');
    exit;
}

// Get current hotel info
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$bookingObj = new Booking();
$roomObj = new Room();

// Currency exchange rate (USD to PEN) - Now fetched from live API
$currencyManager = new CurrencyManager();
$exchangeRate = $currencyManager->getExchangeRate();

// Get hotel bookings (all bookings for this hotel, not just user's bookings)
$currentHotelId = $_SESSION['current_hotel_id'] ?? 1;
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.room_type
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.hotel_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$currentHotelId]);
$userBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rooms = $roomObj->getAllRooms();

// Initialize templates
$headTemplate = new HeadTemplate('Dashboard', $hotel['hotel_name'] ?? 'AiNi Travel PMS');
$headTemplate->render();

$sidebarTemplate = new SidebarTemplate($hotel['hotel_name'] ?? 'AiNi Travel PMS', 'dashboard', $userInfo['role'] ?? 'manager');

$topbarTemplate = new TopbarTemplate();
$topbarTemplate->setUser($user);
?>

<!-- Main Container -->
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <?php $sidebarTemplate->render(); ?>

    <!-- Main Content Area -->
    <div class="content-offset ml-64 transition-all duration-300">
        <?php $topbarTemplate->render(); ?>

        <!-- Dashboard Content -->
        <main class="topbar-offset pt-16 p-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Rooms Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Rooms</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo count($rooms); ?></div>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-bed text-white text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Bookings Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Bookings</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo count($userBookings); ?></div>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-calendar-check text-white text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Confirmed Bookings Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Confirmed</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                                <?php
                                $confirmed = array_filter($userBookings, function($b) {
                                    return $b['status'] === 'confirmed';
                                });
                                echo count($confirmed);
                                ?>
                            </div>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Revenue Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Revenue</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white currency-value" data-usd="<?php
                                $total = array_reduce($userBookings, function($sum, $b) {
                                    return $sum + ($b['total_price'] ?? 0);
                                }, 0);
                                echo $total;
                            ?>">
                                $<?php echo number_format($total, 0); ?>
                            </div>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-white text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue & Bookings Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Revenue & Bookings Overview</h2>
                    <a href="analytics.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-chart-bar"></i>
                        <span>View Full Analytics</span>
                    </a>
                </div>
                <div class="relative" style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Quick Actions Grid -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Calendar -->
                    <a href="calendar_view_modern.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-calendar-alt text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">View Calendar</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Check availability & bookings</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- Manage Rooms -->
                    <a href="room_management_modern.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-bed text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Manage Rooms</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Add, edit, or view rooms</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- Accounting -->
                    <a href="accounting_dashboard.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-chart-line text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Accounting</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">View income & expenses</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- Hotel Settings -->
                    <a href="hotel_setup.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-cog text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Hotel Settings</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Configure your property</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- Employees -->
                    <a href="employee_management.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-users text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Employees</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Manage staff & schedules</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- My Properties -->
                    <a href="owner_account.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-building text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">My Properties</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">View all your hotels</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- WhatsApp AI -->
                    <a href="whatsapp_setup_wizard.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200" style="background: #25D366;">
                                <i class="fab fa-whatsapp text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">WhatsApp AI</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Setup AI chatbot & bookings</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- Banking & Payments -->
                    <a href="banking_setup.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-university text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Banking & Payments</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Setup payment accounts</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>

                    <!-- Social Media -->
                    <a href="social_media_setup.php" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-share-alt text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Social Media</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Connect social profiles</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:translate-x-2 transition-transform duration-200"></i>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Exchange rate (USD to PEN)
const EXCHANGE_RATE = <?php echo $exchangeRate; ?>;

// Revenue & Bookings Chart
const ctx = document.getElementById('revenueChart');

// Generate last 30 days data
const labels = [];
const revenueDataUSD = []; // Store original USD values
const revenueData = [];
const bookingsData = [];

<?php
// PHP: Generate last 30 days of data
$chartData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('M j', strtotime("-$i days"));
    $dateKey = date('Y-m-d', strtotime("-$i days"));

    // Count bookings for this date
    $dayBookings = array_filter($userBookings, function($b) use ($dateKey) {
        return date('Y-m-d', strtotime($b['check_in_date'])) === $dateKey;
    });

    // Sum revenue for this date
    $dayRevenue = array_reduce($dayBookings, function($sum, $b) {
        return $sum + ($b['total_price'] ?? 0);
    }, 0);

    $chartData[] = [
        'date' => $date,
        'revenue' => $dayRevenue,
        'bookings' => count($dayBookings)
    ];
}

foreach ($chartData as $data) {
    echo "labels.push('" . $data['date'] . "');\n";
    echo "revenueDataUSD.push(" . $data['revenue'] . ");\n";
    echo "revenueData.push(" . $data['revenue'] . ");\n";
    echo "bookingsData.push(" . $data['bookings'] . ");\n";
}
?>

const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue (USD)',
            data: revenueData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            fill: true,
            yAxisID: 'y',
            tension: 0.4
        }, {
            label: 'Bookings',
            data: bookingsData,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 2,
            fill: true,
            yAxisID: 'y1',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                labels: {
                    color: '#94a3b8',
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                titleColor: '#f1f5f9',
                bodyColor: '#cbd5e1',
                borderColor: '#334155',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                },
                ticks: {
                    color: '#94a3b8',
                    maxRotation: 45,
                    minRotation: 45
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                },
                ticks: {
                    color: '#10b981',
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    color: '#6366f1',
                    callback: function(value) {
                        return value + ' bookings';
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
