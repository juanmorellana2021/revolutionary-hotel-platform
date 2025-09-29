<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

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

// Get hotel data and statistics
$hotel = $hotelInfo->getHotelInfo();
$stats = $bookingManager->getBookingStats();
$recentBookings = $bookingManager->getRecentBookings(10);
$rooms = $roomObj->getAllRooms();
$allUsers = $userManager->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
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
        
        .dropdown-toggle {
            cursor: pointer;
            user-select: none;
        }
        
        .dropdown-toggle:hover {
            background: rgba(255,255,255,0.3) !important;
        }udes/hotel_classes.php';

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

// Get hotel information and statistics
$hotel = $hotelInfo->getHotelInfo();
$stats = $bookingManager->getBookingStats();
$recentBookings = $bookingManager->getRecentBookings(10);
$rooms = $roomObj->getAllRooms();
$allUsers = $userManager->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .hotel-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .hotel-name {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .hotel-rating {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th,
        .bookings-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .bookings-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .quick-actions {
            display: grid;
            gap: 1rem;
        }

        .action-btn {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .action-btn.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .action-btn.info {
            background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
        }

        .recent-users {
            max-height: 400px;
            overflow-y: auto;
        }

        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .user-info h4 {
            margin-bottom: 0.25rem;
            color: #333;
        }

        .user-info small {
            color: #666;
        }

        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .role-manager {
            background: #667eea;
            color: white;
        }

        .role-guest {
            background: #e9ecef;
            color: #495057;
        }

        .welcome-message {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🏨 Manager Dashboard</div>
            <div class="nav-links">
                <a href="manager_dashboard.php" class="active">🏠 Dashboard</a>
                
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">🏨 Hotel</a>
                    <div class="dropdown-content">
                        <a href="hotel_setup.php">🏨 Hotel Setup</a>
                        <a href="room_management.php">🛏️ Room Management</a>
                        <a href="calendar_view.php">📅 Calendar View</a>
                        <a href="room_photos.php">📸 Room Photos</a>
                        <a href="public_booking.php" target="_blank">🌍 Public Booking Site</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">👥 Staff</a>
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

                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">💎 Rewards</a>
                    <div class="dropdown-content">
                        <a href="hotelcoin_admin.php">🪙 HotelCoin Admin</a>
                        <a href="wallet.php">💰 Loyalty System</a>
                        <a href="wallet.php">👑 Guest Wallets</a>
                    </div>
                </div>

                <a href="ai_admin.php">🤖 AI Configuration</a>
                <a href="whatsapp_management.php">📱 WhatsApp Management</a>
                <a href="whatsapp_setup_wizard.php">🚀 WhatsApp Setup</a>

                <a href="travel_social.php">🌍 Travel Social</a>
                <a href="dashboard.php">👁️ Guest View</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!$hotel || empty($hotel['hotel_name'])): ?>
            <div class="welcome-message">
                <h2>🎉 Welcome to Hotel Management!</h2>
                <p>Get started by setting up your hotel information and configuring your services.</p>
                <a href="hotel_setup.php" style="color: white; text-decoration: underline; font-weight: bold;">
                    Click here to complete your hotel setup →
                </a>
            </div>
        <?php endif; ?>

        <div class="hotel-header">
            <h1 class="hotel-name">
                <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Your Hotel Name'); ?>
            </h1>
            <?php if ($hotel && isset($hotel['star_rating']) && $hotel['star_rating']): ?>
                <div class="hotel-rating">
                    <?php echo str_repeat('⭐', (int)$hotel['star_rating']); ?>
                </div>
            <?php endif; ?>
            <p style="color: #666; font-size: 1.1rem;">
                <?php 
                if ($hotel && $hotel['city']) {
                    echo htmlspecialchars($hotel['city'] . ', ' . $hotel['state']);
                } else {
                    echo 'Manager Dashboard Overview';
                }
                ?>
            </p>
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-number"><?php echo $stats['current_occupancy']; ?></div>
                <div class="stat-label">Current Guests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📥</div>
                <div class="stat-number"><?php echo $stats['todays_checkins']; ?></div>
                <div class="stat-label">Today's Check-ins</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📤</div>
                <div class="stat-number"><?php echo $stats['todays_checkouts']; ?></div>
                <div class="stat-label">Today's Check-outs</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="section">
                <h2 class="section-title">📋 Recent Bookings</h2>
                <?php if (empty($recentBookings)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No bookings yet. Share your booking link with customers to get started!
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="bookings-table">
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
                                            <small><?php echo htmlspecialchars($booking['email']); ?></small>
                                        </td>
                                        <td>Room <?php echo $booking['room_number']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></td>
                                        <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
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

            <div class="section">
                <h2 class="section-title">⚡ Quick Actions</h2>
                <div class="quick-actions">
                    <a href="hotel_setup.php" class="action-btn success">
                        🏨 Setup Hotel Info
                    </a>
                    <a href="dashboard.php" class="action-btn">
                        👁️ View Guest Experience
                    </a>
                    <a href="manager_dashboard.php" class="action-btn info">
                        📊 Refresh Dashboard
                    </a>
                </div>

                <h3 style="margin: 2rem 0 1rem 0; color: #667eea;">👥 Recent Users</h3>
                <div class="recent-users">
                    <?php foreach (array_slice($allUsers, 0, 5) as $user_item): ?>
                        <div class="user-item">
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']); ?></h4>
                                <small><?php echo htmlspecialchars($user_item['email']); ?></small>
                            </div>
                            <span class="role-badge role-<?php echo $user_item['role']; ?>">
                                <?php echo ucfirst($user_item['role']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">🏠 Room Overview</h2>
            <div class="stats-grid" style="margin-top: 1rem;">
                <?php foreach ($rooms as $room): ?>
                    <div class="stat-card">
                        <div class="stat-icon">🏠</div>
                        <div class="stat-number"><?php echo $room['room_number']; ?></div>
                        <div class="stat-label">
                            <?php echo htmlspecialchars($room['room_type']); ?><br>
                            <small>$<?php echo $room['price_per_night']; ?>/night</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple navigation - no dropdown interference
        console.log('Manager Dashboard loaded - navigation should work normally');
    </script>
</body>
</html>