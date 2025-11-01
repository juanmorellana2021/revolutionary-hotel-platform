<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: owner_login.php');
    exit;
}

// Get current hotel info
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

$database = new Database();
$conn = $database->getConnection();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$bookingObj = new Booking();
$roomObj = new Room();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Dashboard'); ?> - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            height: 100vh;
            overflow: hidden;
            color: #e2e8f0;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: #1e293b;
            padding: 2rem 0;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            padding: 0 1.5rem;
            margin-bottom: 3rem;
        }
        
        .logo h2 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo h2 i {
            color: #6366f1;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
            padding: 0 1rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            height: 100vh;
            overflow-y: auto;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        
        .top-bar {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .search-box {
            position: relative;
            width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #e2e8f0;
            outline: none;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: #6366f1;
            background: rgba(15, 23, 42, 0.8);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 1.5rem 2rem;
            height: calc(100vh - 80px);
            overflow-y: auto;
        }
        
        .welcome-section {
            margin-bottom: 1.25rem;
        }
        
        .welcome-section h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        
        .welcome-section p {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.2);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Rooms Grid */
        .rooms-section {
            margin-bottom: 1rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }
        
        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .action-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }
        
        .action-card:hover {
            transform: translateX(5px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.2);
        }
        
        .action-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .action-icon.purple {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .action-icon.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .action-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .action-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .action-content {
            flex: 1;
        }
        
        .action-content h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        
        .action-content p {
            font-size: 0.875rem;
            color: #94a3b8;
            margin: 0;
        }
        
        .action-arrow {
            color: #64748b;
            font-size: 1.25rem;
            transition: all 0.3s;
        }
        
        .action-card:hover .action-arrow {
            color: #6366f1;
            transform: translateX(5px);
        }
        
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            max-height: calc(100vh - 420px);
            overflow-y: auto;
        }
        
        .room-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .room-card:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.2);
        }
        
        .room-image {
            height: 120px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: rgba(255,255,255,0.3);
        }
        
        .room-body {
            padding: 1rem;
        }
        
        .room-header-info {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }
        
        .room-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }
        
        .room-type {
            color: #94a3b8;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .room-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #6366f1;
        }
        
        .price-label {
            font-size: 0.65rem;
            color: #64748b;
        }
        
        .room-features {
            display: flex;
            gap: 0.75rem;
            margin: 0.75rem 0;
            padding: 0.75rem 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            font-size: 0.75rem;
        }
        
        .feature i {
            color: #6366f1;
            font-size: 0.875rem;
        }
        
        .btn-book {
            width: 100%;
            padding: 0.625rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(30, 41, 59, 0.3);
            border: 2px dashed rgba(255,255,255,0.1);
            border-radius: 16px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: rgba(255,255,255,0.2);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #64748b;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.5);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.7);
        }
        
        @media (max-width: 1400px) {
            .rooms-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .rooms-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h2><i class="fas fa-hotel"></i> <?php echo htmlspecialchars(substr($hotel['hotel_name'] ?? 'Hotel', 0, 15)); ?></h2>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="owner_account.php" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>My Properties</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="hotel_setup.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search rooms, bookings...">
            </div>
            
            <div class="user-info">
                <div>
                    <div style="text-align: right; margin-bottom: 0.25rem;">
                        <strong><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></strong>
                    </div>
                    <div style="font-size: 0.875rem; color: #64748b;">
                        <?php echo ucfirst($user['user_role'] ?? 'Owner'); ?>
                    </div>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'] ?? 'User')[0]); ?>! 👋</h1>
                <p>Here's what's happening with your hotel today</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Rooms</div>
                            <div class="stat-value"><?php echo count($rooms); ?></div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="fas fa-bed"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Bookings</div>
                            <div class="stat-value"><?php echo count($userBookings); ?></div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Confirmed</div>
                            <div class="stat-value">
                                <?php 
                                $confirmed = array_filter($userBookings, function($b) { 
                                    return $b['status'] === 'confirmed'; 
                                });
                                echo count($confirmed);
                                ?>
                            </div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Revenue</div>
                            <div class="stat-value">
                                $<?php 
                                $total = array_reduce($userBookings, function($sum, $b) { 
                                    return $sum + ($b['total_price'] ?? 0); 
                                }, 0);
                                echo number_format($total, 0);
                                ?>
                            </div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Section -->
            <div class="rooms-section">
                <div class="section-header">
                    <h2 class="section-title">Quick Actions</h2>
                </div>
                
                <div class="quick-actions-grid">
                    <a href="calendar_view.php" class="action-card">
                        <div class="action-icon purple">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="action-content">
                            <h3>View Calendar</h3>
                            <p>Check availability & bookings</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="room_management.php" class="action-card">
                        <div class="action-icon blue">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="action-content">
                            <h3>Manage Rooms</h3>
                            <p>Add, edit, or view rooms</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="accounting_dashboard.php" class="action-card">
                        <div class="action-icon green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="action-content">
                            <h3>Accounting</h3>
                            <p>View income & expenses</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="hotel_setup.php" class="action-card">
                        <div class="action-icon orange">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="action-content">
                            <h3>Hotel Settings</h3>
                            <p>Configure your property</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="employee_management.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #d946ef 0%, #c026d3 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="action-content">
                            <h3>Employees</h3>
                            <p>Manage staff & schedules</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="owner_account.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="action-content">
                            <h3>My Properties</h3>
                            <p>View all your hotels</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="whatsapp_setup_wizard.php" class="action-card">
                        <div class="action-icon" style="background: #25D366; color: white;">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="action-content">
                            <h3>WhatsApp AI</h3>
                            <p>Setup AI chatbot & bookings</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="banking_setup.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="action-content">
                            <h3>Banking & Payments</h3>
                            <p>Setup payment accounts</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                    
                    <a href="social_media_setup.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div class="action-content">
                            <h3>Social Media</h3>
                            <p>Connect social profiles</p>
                        </div>
                        <i class="fas fa-arrow-right action-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
