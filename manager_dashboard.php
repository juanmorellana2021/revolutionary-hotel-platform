<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/accounting_classes.php';

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

$user = $_SESSION['user'];
$hotelInfo = new HotelInfo();
$bookingManager = new BookingManager();
$roomObj = new Room();
$financialReportManager = new FinancialReportManager();

// Get hotel data and statistics
$hotel = $hotelInfo->getHotelInfo();
$stats = $bookingManager->getBookingStats();
$recentBookings = $bookingManager->getRecentBookings(10);
$rooms = $roomObj->getAllRooms();
$allUsers = $userManager->getAllUsers();

// Get financial data (current month) - all amounts converted to PEN
$startDate = date('Y-m-01'); // First day of current month
$endDate = date('Y-m-t'); // Last day of current month
$financialData = $financialReportManager->generateReport($startDate, $endDate);

// Set page title for shared header
$pageTitle = 'Manager Dashboard';
?>

<?php include 'includes/header.php'; ?>
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    .main-content {
        padding-top: 20px;
    }
    
    /* Dashboard-specific Bootstrap overrides */
    .card {
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card {
        text-align: center;
        padding: 2rem;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--hotel-primary);
    }
    
    .welcome-message {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        text-align: center;
    }
</style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Dashboard Content Starts Here -->
        <?php if (!$hotel || empty($hotel['hotel_name'])): ?>
            <div class="welcome-message">
                <h2>🎉 Welcome to Hotel Management!</h2>
                <p>Get started by setting up your hotel information and configuring your services.</p>
                <a href="hotel_setup.php" style="color: white; text-decoration: underline; font-weight: bold;">
                    Click here to complete your hotel setup →
                </a>
            </div>
        <?php endif; ?>

        <div class="card card-hotel mb-4">
            <div class="card-body text-center">
                <h1 class="display-4 text-primary mb-2">
                    <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Your Hotel Name'); ?>
                </h1>
                <?php if ($hotel && isset($hotel['star_rating']) && $hotel['star_rating']): ?>
                    <div class="h3 mb-3">
                        <?php echo str_repeat('⭐', (int)$hotel['star_rating']); ?>
                    </div>
                <?php endif; ?>
                <p class="text-muted h5">
                    <?php 
                    if ($hotel && $hotel['city']) {
                        echo htmlspecialchars($hotel['city'] . ', ' . $hotel['state']);
                    } else {
                        echo 'Manager Dashboard Overview';
                    }
                    ?>
                </p>
            </div>
        </div>

        <?php 
        // Check WhatsApp setup status (simplified)
        $whatsappSetupComplete = false; // Set to true once WhatsApp is configured
        
        if (!$whatsappSetupComplete): ?>
            <div class="revolutionary-setup-banner">
                <div class="banner-content">
                    <div class="banner-icon">🚀</div>
                    <div class="banner-text">
                        <h3>🌟 Revolutionary Feature Available!</h3>
                        <p>Set up the world's first AI-powered WhatsApp booking system. Let guests book rooms through WhatsApp chat and earn HotelCoins!</p>
                    </div>
                    <div class="banner-actions">
                        <a href="whatsapp_quick_start.php" class="setup-btn">⚡ Quick Start</a>
                        <a href="whatsapp_setup_wizard.php" class="setup-btn" style="margin-left: 1rem; background: rgba(255,255,255,0.1);">🚀 Full Setup</a>
                    </div>
                </div>
            </div>
            
            <style>
                .revolutionary-setup-banner {
                    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
                    color: white;
                    padding: 2rem;
                    border-radius: 15px;
                    margin-bottom: 2rem;
                    box-shadow: 0 10px 30px rgba(37, 211, 102, 0.3);
                    animation: pulse-glow 3s infinite;
                }
                
                .banner-content {
                    display: flex;
                    align-items: center;
                    gap: 2rem;
                }
                
                .banner-icon {
                    font-size: 3rem;
                    animation: bounce 2s infinite;
                }
                
                .banner-text h3 {
                    margin-bottom: 0.5rem;
                    font-size: 1.5rem;
                }
                
                .banner-text p {
                    opacity: 0.9;
                    line-height: 1.4;
                }
                
                .setup-btn {
                    background: rgba(255,255,255,0.2);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 10px;
                    text-decoration: none;
                    font-weight: 600;
                    border: 2px solid rgba(255,255,255,0.3);
                    transition: all 0.3s;
                    backdrop-filter: blur(10px);
                }
                
                .setup-btn:hover {
                    background: rgba(255,255,255,0.3);
                    border-color: rgba(255,255,255,0.5);
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
                
                @keyframes pulse-glow {
                    0%, 100% { box-shadow: 0 10px 30px rgba(37, 211, 102, 0.3); }
                    50% { box-shadow: 0 10px 40px rgba(37, 211, 102, 0.5); }
                }
                
                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-10px); }
                    60% { transform: translateY(-5px); }
                }
                
                @media (max-width: 768px) {
                    .banner-content {
                        flex-direction: column;
                        text-align: center;
                        gap: 1rem;
                    }
                }
            </style>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="card card-hotel stat-card">
                    <div class="card-body">
                        <div class="h1 mb-3">📊</div>
                        <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                        <div class="text-muted">Total Bookings</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hotel stat-card">
                    <div class="card-body">
                        <div class="h1 mb-3">✅</div>
                        <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
                        <div class="text-muted">Confirmed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hotel stat-card">
                    <div class="card-body">
                        <div class="h1 mb-3">🏠</div>
                        <div class="stat-number"><?php echo $stats['current_occupancy']; ?></div>
                        <div class="text-muted">Current Guests</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hotel stat-card">
                    <div class="card-body">
                        <div class="h1 mb-3">💰</div>
                        <div class="stat-number">S/. <?php echo number_format($financialData['total_income'], 0); ?></div>
                        <div class="text-muted">Total Revenue (This Month)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hotel stat-card">
                    <div class="card-body">
                        <div class="h1 mb-3">📥</div>
                        <div class="stat-number"><?php echo $stats['todays_checkins']; ?></div>
                        <div class="text-muted">Today's Check-ins</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hotel stat-card">
                    <div class="card-body">
                        <div class="h1 mb-3">📤</div>
                        <div class="stat-number"><?php echo $stats['todays_checkouts']; ?></div>
                        <div class="text-muted">Today's Check-outs</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card card-hotel">
                    <div class="card-header">
                        <h2 class="h4 mb-0">📋 Recent Bookings</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentBookings)): ?>
                            <p class="text-center text-muted py-4">
                                No bookings yet. Share your booking link with customers to get started!
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hotel table-hover">
                                    <thead>
                                        <tr>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentBookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                </td>
                                                <td>Room <?php echo $booking['room_number']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></td>
                                                <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-hotel mb-4">
                    <div class="card-header">
                        <h2 class="h4 mb-0">⚡ Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="hotel_setup.php" class="btn btn-success">
                                🏨 Setup Hotel Info
                            </a>
                            <a href="dashboard.php" class="btn btn-hotel-primary">
                                👁️ View Guest Experience
                            </a>
                            <a href="manager_dashboard.php" class="btn btn-info">
                                📊 Refresh Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card card-hotel">
                    <div class="card-header">
                        <h3 class="h5 mb-0">👥 Recent Users</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($allUsers, 0, 5) as $user_item): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($user_item['email']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $user_item['role'] === 'manager' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($user_item['role']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-hotel">
            <div class="card-header">
                <h2 class="h4 mb-0">🏠 Room Overview</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <div class="h1 mb-3">🏠</div>
                                    <h5 class="card-title text-primary">Room <?php echo $room['room_number']; ?></h5>
                                    <p class="card-text">
                                        <?php echo htmlspecialchars($room['room_type']); ?><br>
                                        <small class="text-muted">$<?php echo $room['price_per_night']; ?>/night</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    </div>
    
    <?php 
    $customScripts = "
        // Manager Dashboard specific scripts
        console.log('Manager Dashboard loaded with shared header system');
        
        // Auto-refresh dashboard every 5 minutes
        enableAutoRefresh(5);
    ";
    include 'includes/footer.php'; 
    ?>