<?php
/**
 * STANDARD MENU COMPONENT
 * Universal sidebar menu for all PMS pages
 * Include this file in all pages to maintain consistency
 * 
 * Usage: 
 * $currentPage = 'dashboard'; // or 'rooms', 'calendar', 'accounting', etc.
 * include 'includes/standard_menu.php';
 */

// Get hotel info if not already loaded
if (!isset($hotel)) {
    require_once __DIR__ . '/hotel_classes.php';
    $hotelInfo = new HotelInfo();
    $hotel = $hotelInfo->getHotelInfo();
}

// Get user info if not already loaded
if (!isset($user)) {
    require_once __DIR__ . '/classes.php';
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Determine active page
$currentPage = $currentPage ?? 'dashboard';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h2><i class="fas fa-hotel"></i> <?php echo htmlspecialchars(substr($hotel['hotel_name'] ?? 'Hotel', 0, 15)); ?></h2>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="room_management_modern.php" class="nav-link <?php echo $currentPage === 'rooms' ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i>
                <span>Rooms</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="calendar_view.php" class="nav-link <?php echo $currentPage === 'calendar' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i>
                <span>Calendar</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="accounting_dashboard.php" class="nav-link <?php echo $currentPage === 'accounting' ? 'active' : ''; ?>">
                <i class="fas fa-dollar-sign"></i>
                <span>Accounting</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="employee_management.php" class="nav-link <?php echo $currentPage === 'employees' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="owner_account.php" class="nav-link <?php echo $currentPage === 'properties' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>My Properties</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="hotel_setup.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
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
