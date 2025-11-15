<?php
/**
 * Modern Sidebar Component
 * Reusable sidebar navigation for all PMS pages
 */

// Get hotel info
if (!isset($hotel)) {
    $hotelInfo = new HotelInfo();
    $hotel = $hotelInfo->getHotelInfo();
}

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <h2><i class="fas fa-hotel"></i> <?php echo htmlspecialchars(substr($hotel['hotel_name'] ?? 'Hotel', 0, 15)); ?></h2>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="manager_dashboard.php" class="nav-link <?php echo ($current_page == 'manager_dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="room_management.php" class="nav-link <?php echo ($current_page == 'room_management.php') ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i>
                <span>Rooms</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="calendar_view.php" class="nav-link <?php echo ($current_page == 'calendar_view.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Calendar</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="accounting_dashboard.php" class="nav-link <?php echo ($current_page == 'accounting_dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Accounting</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="income_management.php" class="nav-link <?php echo ($current_page == 'income_management.php') ? 'active' : ''; ?>">
                <i class="fas fa-dollar-sign"></i>
                <span>Income</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="expense_management.php" class="nav-link <?php echo ($current_page == 'expense_management.php') ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i>
                <span>Expenses</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="employee_management.php" class="nav-link <?php echo ($current_page == 'employee_management.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="hotel_setup.php" class="nav-link <?php echo ($current_page == 'hotel_setup.php') ? 'active' : ''; ?>">
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
