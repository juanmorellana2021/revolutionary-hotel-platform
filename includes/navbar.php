<?php
/**
 * Shared Navigation Bar for Hotel Management System
 * Adapts based on user role (Manager vs Guest)
 */

// Determine current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$isManager = isset($_SESSION['user']) && $userManager->isManager($_SESSION['user']['id']);
?>

<nav class="navbar navbar-expand-lg navbar-hotel sticky-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo $isManager ? 'manager_dashboard.php' : 'dashboard.php'; ?>">
            <i class="bi bi-building me-2"></i>
            <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'AiNi Hotel'); ?>
        </a>
        
        <!-- Mobile toggle button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($isManager): ?>
                    <!-- Manager Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'manager_dashboard.php' ? 'active' : ''; ?>" href="manager_dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-building me-1"></i>Hotel Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="hotel_setup.php"><i class="bi bi-gear me-2"></i>Hotel Setup</a></li>
                            <li><a class="dropdown-item" href="room_management.php"><i class="bi bi-door-open me-2"></i>Room Management</a></li>
                            <li><a class="dropdown-item" href="calendar_view.php"><i class="bi bi-calendar3 me-2"></i>Booking Calendar</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-people me-1"></i>Staff
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="employee_management.php"><i class="bi bi-person-badge me-2"></i>Employee Management</a></li>
                            <li><a class="dropdown-item" href="time_clock.php"><i class="bi bi-clock me-2"></i>Time Clock</a></li>
                            <li><a class="dropdown-item" href="payroll_management.php"><i class="bi bi-currency-dollar me-2"></i>Payroll</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-graph-up me-1"></i>Financial
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="accounting_dashboard.php"><i class="bi bi-pie-chart me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="income_management.php"><i class="bi bi-arrow-up-circle me-2"></i>Income</a></li>
                            <li><a class="dropdown-item" href="expense_management.php"><i class="bi bi-arrow-down-circle me-2"></i>Expenses</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-coin me-1"></i>Systems
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="hotelcoin_admin.php"><i class="bi bi-currency-bitcoin me-2"></i>HotelCoin Admin</a></li>
                            <li><a class="dropdown-item" href="wallet.php"><i class="bi bi-wallet2 me-2"></i>Loyalty System</a></li>
                            <li><a class="dropdown-item" href="whatsapp_management.php"><i class="bi bi-whatsapp me-2"></i>WhatsApp</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php#bookings">
                            <i class="bi bi-calendar-check me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="wallet.php">
                            <i class="bi bi-wallet2 me-1"></i>My Wallet
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- User info and logout -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['first_name'] ?? 'User'); ?>
                        <?php if ($isManager): ?>
                            <span class="badge bg-warning text-dark ms-1">Manager</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>