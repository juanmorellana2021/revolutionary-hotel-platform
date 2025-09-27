<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Get database connection
$database = new Database();
$connection = $database->getConnection();

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

$roomManager = new Room();
$bookingManager = new BookingManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Get current month and year, or from URL parameters
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Ensure valid month/year
if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; }
if ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }

// Get all rooms
$rooms = $roomManager->getAllRooms();

// Get primary photos for rooms
$roomPhotos = [];
$stmt = $connection->prepare("SELECT room_id, photo_path FROM room_photos WHERE is_primary = 1");
$stmt->execute();
$photos = $stmt->fetchAll();
foreach ($photos as $photo) {
    $roomPhotos[$photo['room_id']] = $photo['photo_path'];
}

// Get bookings for current month (and a bit before/after for overlap)
$startDate = date('Y-m-01', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
$endDate = date('Y-m-t', mktime(0, 0, 0, $currentMonth + 1, 1, $currentYear));

$db = new Database();
$connection = $db->getConnection();

$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type, u.first_name, u.last_name, u.email, u.phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    WHERE b.check_out_date >= ? AND b.check_in_date <= ?
    AND b.status != 'cancelled'
    ORDER BY b.check_in_date
");
$stmt->execute([$startDate, $endDate]);
$bookings = $stmt->fetchAll();

// Handle booking deletion
if (isset($_GET['delete_booking'])) {
    $bookingId = (int)$_GET['delete_booking'];
    try {
        $stmt = $connection->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $message = "Reserva eliminada exitosamente";
        $messageType = "success";
        header('Location: calendar_view.php?month=' . $currentMonth . '&year=' . $currentYear);
        exit;
    } catch (Exception $e) {
        $message = "Error al eliminar la reserva: " . $e->getMessage();
        $messageType = "error";
    }
}

// Handle booking editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_booking'])) {
    $bookingId = (int)$_POST['edit_booking_id'];
    $roomId = $_POST['edit_room_id'];
    $checkIn = $_POST['edit_check_in'];
    $checkOut = $_POST['edit_check_out'];
    $guestName = $_POST['edit_guest_name'];
    $guestEmail = $_POST['edit_guest_email'];
    $guestPhone = $_POST['edit_guest_phone'] ?? '';
    $totalPrice = (float)$_POST['edit_total_price'];
    $specialRequests = $_POST['edit_special_requests'];
    
    try {
        // Update booking
        $stmt = $connection->prepare("
            UPDATE bookings 
            SET room_id = ?, check_in_date = ?, check_out_date = ?, total_price = ?, special_requests = ?
            WHERE id = ?
        ");
        $stmt->execute([$roomId, $checkIn, $checkOut, $totalPrice, $specialRequests, $bookingId]);
        
        // Update user information
        $stmt = $connection->prepare("
            UPDATE users 
            SET first_name = ?, email = ?, phone = ?
            WHERE id = (SELECT user_id FROM bookings WHERE id = ?)
        ");
        $names = explode(' ', $guestName, 2);
        $firstName = $names[0];
        $stmt->execute([$firstName, $guestEmail, $guestPhone, $bookingId]);
        
        $message = "Reserva actualizada exitosamente";
        $messageType = "success";
        header('Location: calendar_view.php?month=' . $currentMonth . '&year=' . $currentYear);
        exit;
    } catch (Exception $e) {
        $message = "Error al actualizar la reserva: " . $e->getMessage();
        $messageType = "error";
    }
}

// Handle quick booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_booking'])) {
    $roomId = $_POST['room_id'];
    $checkIn = $_POST['check_in_date'];
    $checkOut = $_POST['check_out_date'];
    $guestName = $_POST['guest_name'];
    $guestEmail = $_POST['guest_email'];
    $guestPhone = $_POST['guest_phone'] ?? '';
    
    // Create a temporary guest user or use existing
    $userManager = new User();
    
    // Check if guest exists
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$guestEmail]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        $guestId = $existingUser['id'];
    } else {
        // Create new guest user
        $names = explode(' ', $guestName, 2);
        $firstName = $names[0];
        $lastName = isset($names[1]) ? $names[1] : '';
        
        $result = $userManager->register($firstName, $lastName, $guestEmail, 'temp123');
        if ($result['success']) {
            $guestId = $result['user_id'];
            
            // Update phone number
            if (!empty($guestPhone)) {
                $stmt = $connection->prepare("UPDATE users SET phone = ? WHERE id = ?");
                $stmt->execute([$guestPhone, $guestId]);
            }
        } else {
            $message = 'Failed to create guest user';
            $messageType = 'error';
        }
    }
    
    if (isset($guestId)) {
        // Calculate total price with custom pricing and discounts
        $room = $roomManager->getRoomById($roomId);
        $days = (strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24);
        
        // Handle currency selection and custom pricing
        $selectedCurrency = $_POST['currency_type'] ?? 'USD';
        $customPriceUSD = !empty($_POST['custom_price_usd']) ? (float)$_POST['custom_price_usd'] : 0;
        $customPricePEN = !empty($_POST['custom_price_pen']) ? (float)$_POST['custom_price_pen'] : 0;
        $useCustomPrice = isset($_POST['use_custom_price']) && $_POST['use_custom_price'] == '1';
        
        // Determine price per night in USD (for consistent database storage)
        if ($useCustomPrice) {
            if ($selectedCurrency === 'USD' && $customPriceUSD > 0) {
                $pricePerNight = $customPriceUSD;
            } elseif ($selectedCurrency === 'PEN' && $customPricePEN > 0) {
                $pricePerNight = $customPricePEN / 3.75; // Convert PEN to USD
            } else {
                // Fallback: use whichever price is available
                $pricePerNight = $customPriceUSD > 0 ? $customPriceUSD : ($customPricePEN > 0 ? $customPricePEN / 3.75 : $room['price']);
            }
        } else {
            $pricePerNight = $room['price']; // Default room price in USD
        }
        
        $subtotal = $pricePerNight * $days;
        
        // Apply discount if provided
        $discountAmount = 0;
        $discountType = $_POST['discount_type'] ?? '';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $discountReason = $_POST['discount_reason'] ?? '';
        
        if ($discountType && $discountValue > 0) {
            if ($discountType === 'percentage') {
                $discountAmount = $subtotal * ($discountValue / 100);
            } elseif ($discountType === 'fixed_usd') {
                $discountAmount = min($discountValue, $subtotal);
            } elseif ($discountType === 'fixed_pen') {
                $discountAmount = min($discountValue / 3.75, $subtotal); // Convert PEN to USD
            }
        }
        
        $totalPrice = $subtotal - $discountAmount;
        
        // Prepare special requests with pricing info
        $specialRequests = '';
        if ($useCustomPrice) {
            $displayPrice = $selectedCurrency === 'USD' ? 
                "$" . number_format($pricePerNight, 2) : 
                "S/ " . number_format($pricePerNight * 3.75, 2);
            $specialRequests .= "Precio personalizado ({$selectedCurrency}): {$displayPrice}/noche. ";
        }
        if ($discountAmount > 0) {
            $specialRequests .= "Descuento aplicado: ";
            if ($discountType === 'percentage') {
                $specialRequests .= $discountValue . "% ";
            } elseif ($discountType === 'fixed_usd') {
                $specialRequests .= "$" . number_format($discountAmount, 2) . " ";
            } elseif ($discountType === 'fixed_pen') {
                $specialRequests .= "S/ " . number_format($discountAmount * 3.75, 2) . " ";
            }
            if ($discountReason) {
                $specialRequests .= "(" . $discountReason . ") ";
            }
            $specialRequests .= "Subtotal: $" . number_format($subtotal, 2) . " / S/ " . number_format($subtotal * 3.75, 2) . 
                              ", Total final: $" . number_format($totalPrice, 2) . " / S/ " . number_format($totalPrice * 3.75, 2) . ". ";
        }
        
        // Create booking
        $booking = new Booking();
        $result = $booking->createBooking($guestId, $roomId, $checkIn, $checkOut, $totalPrice, $specialRequests, $discountAmount);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        // Refresh bookings if successful
        if ($result['success']) {
            header('Location: calendar_view.php?month=' . $currentMonth . '&year=' . $currentYear);
            exit;
        }
    }
}

// Calendar helper functions
function getDaysInMonth($month, $year) {
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

function getFirstDayOfWeek($month, $year) {
    return date('w', mktime(0, 0, 0, $month, 1, $year));
}

function isDateBooked($roomId, $date, $bookings) {
    foreach ($bookings as $booking) {
        if ($booking['room_id'] == $roomId) {
            if ($date >= $booking['check_in_date'] && $date < $booking['check_out_date']) {
                return $booking;
            }
        }
    }
    return false;
}

function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$month];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Availability Calendar - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            width: 100%;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            width: 100%;
            margin: 20px 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .month-nav a {
            padding: 12px 18px;
            text-decoration: none;
            background: #007bff;
            color: white;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
        }

        .month-nav a:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.4);
        }

        .current-month {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .calendar-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40,167,69,0.3);
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23,162,184,0.3);
        }

        .calendar-wrapper {
            padding: 30px;
            overflow-x: auto;
            width: 100%;
        }

        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            min-width: 100%;
            table-layout: fixed;
        }

        .calendar-table th {
            background: #343a40;
            color: white;
            padding: 20px 15px;
            text-align: center;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 1rem;
            min-width: 50px;
        }

        .calendar-table th.room-header {
            background: #495057;
            width: 200px;
            text-align: left;
            padding-left: 20px;
            min-width: 200px;
        }

        .calendar-table td {
            height: 60px;
            border: 1px solid #dee2e6;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            padding: 10px;
            min-width: 50px;
        }

        .room-info {
            background: #f8f9fa;
            padding: 15px 20px;
            border-right: 2px solid #dee2e6;
            position: sticky;
            left: 0;
            z-index: 5;
            width: 200px;
            min-width: 200px;
        }

        .room-number {
            font-weight: bold;
            color: #007bff;
            font-size: 1.1rem;
        }

        .room-type {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 2px;
        }

        .room-price {
            font-size: 0.8rem;
            color: #28a745;
            font-weight: 600;
        }

        .day-cell {
            background: #ffffff;
            text-align: center;
            vertical-align: middle;
            font-size: 1.1rem;
            min-width: 50px;
            font-weight: 500;
        }

        .day-cell:hover {
            background: #e3f2fd;
        }

        .day-cell.weekend {
            background: #f8f9fa;
        }

        .day-cell.today {
            background: #fff3cd;
            font-weight: bold;
        }

        .booked {
            background: #dc3545 !important;
            color: white;
            position: relative;
        }

        .booked:hover {
            background: #c82333 !important;
        }

        .checkout {
            background: #fd7e14 !important;
            color: white;
        }

        .available {
            background: #28a745;
            color: white;
        }

        .available:hover {
            background: #218838;
        }

        .booking-info {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.7rem;
            font-weight: bold;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 40px;
            padding: 25px;
            background: #f8f9fa;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 500;
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 20px;
            border-radius: 12px;
            width: 95%;
            max-width: 1000px;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        @media (min-width: 1200px) {
            .modal-content {
                max-width: 1100px;
                padding: 25px;
            }
        }
        
        /* Responsive adjustments for smaller screens */
        @media (max-width: 1024px) {
            /* For tablets and smaller laptops - make pricing section 3 columns */
            .modal-content div[style*="grid-template-columns: 1fr 1fr 1fr 1fr 1fr"] {
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 10px !important;
            }
        }
        
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-width: 95%;
                margin: 2% auto;
                padding: 15px;
            }
            
            /* Stack columns on mobile */
            .modal-content div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }
        }

        .room-preview {
            display: none;
            margin-top: 10px;
            text-align: center;
        }

        .room-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .room-preview.active {
            display: block;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-bar {
            display: flex;
            justify-content: space-around;
            padding: 20px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-item {
            text-align: center;
            padding: 0 15px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">🗓️ Room Calendar</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">Dashboard</a>
                <a href="hotel_setup.php">Hotel Setup</a>
                <a href="room_management.php">Room Management</a>
                <a href="calendar_view.php" class="active">Calendar</a>
                <a href="wallet.php">🪙 Wallet</a>
                <a href="accounting_dashboard.php">💰 Accounting</a>
                <a href="income_management.php">💰 Income</a>
                <a href="expense_management.php">💸 Expenses</a>
                <a href="dashboard.php">Guest View</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>📅 Room Availability Calendar</h1>
            <p>Visual room booking calendar for <?php echo getMonthName($currentMonth) . ' ' . $currentYear; ?></p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php
        // Calculate statistics for current month
        $totalRooms = count($rooms);
        $totalDays = getDaysInMonth($currentMonth, $currentYear);
        $totalPossibleBookings = $totalRooms * $totalDays;
        $actualBookings = 0;
        
        foreach ($bookings as $booking) {
            $checkIn = max($booking['check_in_date'], date('Y-m-01', mktime(0, 0, 0, $currentMonth, 1, $currentYear)));
            $checkOut = min($booking['check_out_date'], date('Y-m-t', mktime(0, 0, 0, $currentMonth, 1, $currentYear)));
            $days = (strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24);
            $actualBookings += max(0, $days);
        }
        
        $occupancyRate = $totalPossibleBookings > 0 ? ($actualBookings / $totalPossibleBookings) * 100 : 0;
        ?>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo $totalRooms; ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($bookings); ?></div>
                <div class="stat-label">Active Bookings</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($occupancyRate, 1); ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $totalDays; ?></div>
                <div class="stat-label">Days in Month</div>
            </div>
        </div>

        <div class="calendar-controls">
            <div class="month-nav">
                <a href="?month=<?php echo $currentMonth == 1 ? 12 : $currentMonth - 1; ?>&year=<?php echo $currentMonth == 1 ? $currentYear - 1 : $currentYear; ?>">
                    ← Previous
                </a>
                <div class="current-month">
                    <?php echo getMonthName($currentMonth) . ' ' . $currentYear; ?>
                </div>
                <a href="?month=<?php echo $currentMonth == 12 ? 1 : $currentMonth + 1; ?>&year=<?php echo $currentMonth == 12 ? $currentYear + 1 : $currentYear; ?>">
                    Next →
                </a>
            </div>
            <div class="calendar-actions">
                <button onclick="openBookingModal()" class="btn btn-success">+ Quick Booking</button>
                <a href="room_management.php" class="btn btn-info">Manage Rooms</a>
                <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
            </div>
        </div>

        <div class="calendar-wrapper">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th class="room-header">Room</th>
                        <?php
                        $daysInMonth = getDaysInMonth($currentMonth, $currentYear);
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dayOfWeek = date('D', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                            echo "<th>{$day}<br><small>{$dayOfWeek}</small></th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="room-info">
                                <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                                <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                <div class="room-price">$<?php echo number_format($room['price'] ?? 0, 0); ?>/night</div>
                            </td>
                            <?php
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $currentDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                                $booking = isDateBooked($room['id'], $currentDate, $bookings);
                                $isWeekend = date('w', mktime(0, 0, 0, $currentMonth, $day, $currentYear)) == 0 || 
                                           date('w', mktime(0, 0, 0, $currentMonth, $day, $currentYear)) == 6;
                                $isToday = $currentDate == date('Y-m-d');
                                
                                $cellClass = 'day-cell';
                                if ($isWeekend) $cellClass .= ' weekend';
                                if ($isToday) $cellClass .= ' today';
                                
                                if ($booking) {
                                    if ($currentDate == $booking['check_out_date']) {
                                        $cellClass .= ' checkout';
                                        $cellContent = '<div class="booking-info">OUT</div>';
                                    } else {
                                        $cellClass .= ' booked';
                                        $cellContent = '<div class="booking-info">' . 
                                                     substr($booking['first_name'], 0, 1) . 
                                                     substr($booking['last_name'], 0, 1) . '</div>';
                                    }
                                    
                                    // Prepare booking data for JavaScript
                                    $bookingData = json_encode([
                                        'id' => $booking['id'],
                                        'room_id' => $booking['room_id'],
                                        'guest_name' => $booking['first_name'] . ' ' . $booking['last_name'],
                                        'guest_email' => $booking['email'],
                                        'guest_phone' => $booking['phone'] ?? '',
                                        'room_number' => $booking['room_number'],
                                        'room_type' => $booking['room_type'],
                                        'check_in_date' => $booking['check_in_date'],
                                        'check_out_date' => $booking['check_out_date'],
                                        'total_amount' => $booking['total_price'],
                                        'status' => $booking['status'],
                                        'special_requests' => $booking['special_requests'] ?? '',
                                        'booking_date' => $booking['created_at']
                                    ]);
                                } else {
                                    $cellClass .= ' available';
                                    $cellContent = $day;
                                    $bookingData = 'null';
                                }
                                
                                echo "<td class=\"{$cellClass}\" onclick=\"cellClick('{$room['id']}', '{$currentDate}', '" . 
                                     ($booking ? 'booked' : 'available') . "', " . htmlspecialchars($bookingData) . ")\" title=\"" . 
                                     ($booking ? 'Booked by ' . $booking['first_name'] . ' ' . $booking['last_name'] : 'Available') . 
                                     "\">{$cellContent}</td>";
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #28a745;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #dc3545;"></div>
                <span>Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fd7e14;"></div>
                <span>Check-out</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fff3cd; border: 1px solid #ffeaa7;"></div>
                <span>Today</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #f8f9fa; border: 1px solid #dee2e6;"></div>
                <span>Weekend</span>
            </div>
        </div>
    </div>

    <!-- Quick Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📝 Quick Booking</h3>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <form method="POST">
                <!-- Main booking information in a 2-column grid for wide screens -->
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="room_id">🏨 Room</label>
                        <select id="room_id" name="room_id" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                    ($<?php echo number_format($room['price'] ?? 0, 2); ?> USD / S/ <?php echo number_format(($room['price'] ?? 0) * 3.75, 2); ?> PEN per night)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Room photo previews -->
                        <?php foreach ($rooms as $room): ?>
                            <?php if (isset($roomPhotos[$room['id']])): ?>
                                <div class="room-preview" id="room-preview-<?php echo $room['id']; ?>">
                                    <img src="<?php echo $roomPhotos[$room['id']]; ?>" alt="Habitación <?php echo $room['room_number']; ?>">
                                    <p style="font-size: 0.9em; color: #666; margin-top: 5px;">📸 Vista previa de la habitación</p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- All main fields in one compact 4-column row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="check_in_date" style="font-size: 0.9em;">📅 Check-in</label>
                        <input type="date" id="check_in_date" name="check_in_date" required 
                               min="<?php echo date('Y-m-d'); ?>" style="font-size: 0.9em;">
                    </div>
                    <div class="form-group">
                        <label for="check_out_date" style="font-size: 0.9em;">📅 Check-out</label>
                        <input type="date" id="check_out_date" name="check_out_date" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="font-size: 0.9em;">
                    </div>
                    <div class="form-group">
                        <label for="guest_name" style="font-size: 0.9em;">👤 Guest Name</label>
                        <input type="text" id="guest_name" name="guest_name" required 
                               placeholder="Full name" style="font-size: 0.9em;">
                    </div>
                    <div class="form-group">
                        <label for="booking_currency" style="font-size: 0.9em;">� Currency</label>
                        <select id="booking_currency" name="booking_currency" onchange="changeCurrency()" style="font-size: 0.9em;">
                            <option value="USD">🇺🇸 USD</option>
                            <option value="PEN">🇵🇪 PEN</option>
                        </select>
                    </div>
                </div>
                
                <!-- Guest contact information with exchange rate info -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="guest_email" style="font-size: 0.9em;">� Guest Email</label>
                        <input type="email" id="guest_email" name="guest_email" required 
                               placeholder="guest@email.com" style="font-size: 0.9em;">
                    </div>
                    <div class="form-group">
                        <label for="guest_phone" style="font-size: 0.9em;">📱 WhatsApp/Phone</label>
                        <input type="tel" id="guest_phone" name="guest_phone" 
                               placeholder="+51 999 999 999" style="font-size: 0.9em;"
                               pattern="[\+]?[0-9\s\-\(\)]+" title="Enter a valid phone number">
                    </div>
                    <div style="display: flex; align-items: end; color: #6c757d; font-size: 0.85em; padding-bottom: 8px;">
                        💱 Exchange Rate: 1 USD = 3.75 PEN
                    </div>
                </div>
                
                <!-- Compact Pricing Section -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h4 style="color: #495057; margin-bottom: 12px; font-size: 1.1em;">💰 Pricing & Discounts</h4>
                    
                    <!-- Everything in one horizontal layout -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 12px; align-items: start;">
                        <!-- Price Summary (Compact) -->
                        <div id="priceDisplay" style="background: white; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6; font-size: 0.85em;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>Base/night:</span>
                                <span id="basePrice">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>Nights:</span>
                                <span id="nightCount">0</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>Subtotal:</span>
                                <span id="subtotal">$0.00</span>
                            </div>
                            <div id="discountDisplay" style="display: none; color: #28a745; margin-bottom: 3px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Discount:</span>
                                    <span id="discountAmount">-$0.00</span>
                                </div>
                            </div>
                            <hr style="margin: 5px 0;">
                            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 0.95em;">
                                <span>Total:</span>
                                <span id="totalPrice">$0.00</span>
                            </div>
                        </div>
                        
                        <!-- Custom Pricing -->
                        <div class="form-group">
                            <label style="font-size: 0.9em; margin-bottom: 5px; display: block;">
                                <input type="checkbox" id="use_custom_price" onchange="toggleCustomPrice()"> 
                                💲 Custom Price
                            </label>
                            <input type="number" id="custom_price_usd" name="custom_price_usd" 
                                   step="0.01" min="0" placeholder="USD" disabled 
                                   onchange="updateCustomPrice('USD')" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;">
                            <input type="number" id="custom_price_pen" name="custom_price_pen" 
                                   step="0.01" min="0" placeholder="PEN" disabled 
                                   onchange="updateCustomPrice('PEN')" style="font-size: 0.8em; padding: 4px; width: 100%;">
                        </div>
                        
                        <!-- Discount Type -->
                        <div class="form-group">
                            <label for="discount_type" style="font-size: 0.9em;">🏷️ Discount Type</label>
                            <select id="discount_type" name="discount_type" onchange="toggleDiscountInput()" style="font-size: 0.85em;">
                                <option value="">No discount</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed_usd">Fixed (USD)</option>
                                <option value="fixed_pen">Fixed (PEN)</option>
                            </select>
                        </div>
                        
                        <!-- Discount Value -->
                        <div class="form-group">
                            <label for="discount_value" style="font-size: 0.9em;">💰 Value</label>
                            <input type="number" id="discount_value" name="discount_value" 
                                   step="0.01" min="0" placeholder="0" disabled onchange="calculateTotal()"
                                   style="font-size: 0.85em;">
                        </div>
                        
                        <!-- Discount Reason -->
                        <div class="form-group">
                            <label for="discount_reason" style="font-size: 0.9em;">📝 Reason</label>
                            <input type="text" id="discount_reason" name="discount_reason" 
                                   placeholder="e.g., Frequent guest" disabled
                                   style="font-size: 0.85em;">
                        </div>
                    </div>
                </div>
                
                <!-- Hidden currency field for form submission -->
                <input type="hidden" id="currency_type_hidden" name="currency_type" value="USD">
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="quick_booking" class="btn btn-success">💾 Create Booking</button>
                    <button type="button" onclick="closeBookingModal()" class="btn" style="background: #6c757d; color: white;">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Guest Information Modal -->
    <div id="guestInfoModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeGuestInfoModal()">&times;</span>
            <h2 id="guestModalTitle">📋 Información de Reserva</h2>
            <div id="guestInfoContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div style="margin-top: 20px; text-align: center;">
                <button type="button" onclick="editBooking()" class="btn btn-primary">✏️ Editar Reserva</button>
                <button type="button" onclick="closeGuestInfoModal()" class="btn" style="background: #6c757d; color: white;">🚪 Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Edit Booking Modal -->
    <div id="editBookingModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeEditBookingModal()">&times;</span>
            <h2>✏️ Editar Reserva</h2>
            <form method="POST" id="editBookingForm">
                <input type="hidden" id="edit_booking_id" name="edit_booking_id">
                <input type="hidden" name="edit_booking" value="1">
                
                <!-- Booking Information -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="edit_room_id">🏨 Room</label>
                        <select id="edit_room_id" name="edit_room_id" required>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                    ($<?php echo number_format($room['price'] ?? 0, 2); ?> USD)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_guest_name">👤 Guest Name</label>
                        <input type="text" id="edit_guest_name" name="edit_guest_name" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="edit_check_in">📅 Check-in</label>
                        <input type="date" id="edit_check_in" name="edit_check_in" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_check_out">📅 Check-out</label>
                        <input type="date" id="edit_check_out" name="edit_check_out" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_total_price">💰 Total Price</label>
                        <input type="number" id="edit_total_price" name="edit_total_price" step="0.01" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="edit_guest_email">📧 Guest Email</label>
                        <input type="email" id="edit_guest_email" name="edit_guest_email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_guest_phone">📱 WhatsApp/Phone</label>
                        <input type="tel" id="edit_guest_phone" name="edit_guest_phone" 
                               placeholder="+51 999 999 999" pattern="[\+]?[0-9\s\-\(\)]+" 
                               title="Enter a valid phone number">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_special_requests">📝 Special Requests</label>
                    <textarea id="edit_special_requests" name="edit_special_requests" rows="3" 
                              placeholder="Any special requests or notes..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">💾 Update Booking</button>
                    <button type="button" onclick="deleteBooking()" class="btn" style="background: #dc3545; color: white;">🗑️ Delete Booking</button>
                    <button type="button" onclick="closeEditBookingModal()" class="btn" style="background: #6c757d; color: white;">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentBookingData = null;
        
        // Exchange rate USD to PEN (Soles)
        const USD_TO_PEN_RATE = 3.75; // This should be updated regularly or fetched from API
        let currentCurrency = 'USD'; // Track selected currency
        
        // Currency conversion functions
        function formatDualCurrency(usdAmount) {
            const penAmount = usdAmount * USD_TO_PEN_RATE;
            return `$${parseFloat(usdAmount).toFixed(2)} USD <span style="color: #666; font-size: 0.9em;">(S/ ${penAmount.toFixed(2)} PEN)</span>`;
        }
        
        function formatCurrencyInput(usdAmount) {
            const penAmount = usdAmount * USD_TO_PEN_RATE;
            return `$${parseFloat(usdAmount).toFixed(2)} / S/ ${penAmount.toFixed(2)}`;
        }
        
        function formatSingleCurrency(amount, currency) {
            if (currency === 'USD') {
                return `$${parseFloat(amount).toFixed(2)} USD`;
            } else {
                return `S/ ${parseFloat(amount).toFixed(2)} PEN`;
            }
        }
        
        function convertCurrency(amount, fromCurrency, toCurrency) {
            if (fromCurrency === toCurrency) return amount;
            if (fromCurrency === 'USD' && toCurrency === 'PEN') {
                return amount * USD_TO_PEN_RATE;
            } else if (fromCurrency === 'PEN' && toCurrency === 'USD') {
                return amount / USD_TO_PEN_RATE;
            }
            return amount;
        }
        
        function changeCurrency() {
            currentCurrency = document.getElementById('booking_currency').value;
            
            // Update hidden field for form submission
            document.getElementById('currency_type_hidden').value = currentCurrency;
            
            // Update discount type options
            const discountType = document.getElementById('discount_type');
            const currentValue = discountType.value;
            
            discountType.innerHTML = `
                <option value="">Sin descuento</option>
                <option value="percentage">Porcentaje (%)</option>
                <option value="fixed_usd">Cantidad fija (USD)</option>
                <option value="fixed_pen">Cantidad fija (PEN)</option>
            `;
            
            // Restore selection if compatible
            if (currentValue === 'percentage' || currentValue === '') {
                discountType.value = currentValue;
            }
            
            // Update required fields based on currency selection
            const usdInput = document.getElementById('custom_price_usd');
            const penInput = document.getElementById('custom_price_pen');
            const isCustomPriceEnabled = document.getElementById('use_custom_price').checked;
            
            if (isCustomPriceEnabled) {
                if (currentCurrency === 'USD') {
                    usdInput.required = true;
                    penInput.required = false;
                } else {
                    penInput.required = true;
                    usdInput.required = false;
                }
            }
            
            calculateTotal();
        }
        
        function updateCustomPrice(sourceCurrency) {
            const usdInput = document.getElementById('custom_price_usd');
            const penInput = document.getElementById('custom_price_pen');
            
            if (sourceCurrency === 'USD' && usdInput.value) {
                const usdValue = parseFloat(usdInput.value);
                penInput.value = (usdValue * USD_TO_PEN_RATE).toFixed(2);
            } else if (sourceCurrency === 'PEN' && penInput.value) {
                const penValue = parseFloat(penInput.value);
                usdInput.value = (penValue / USD_TO_PEN_RATE).toFixed(2);
            }
            
            calculateTotal();
        }

        function cellClick(roomId, date, status, bookingData) {
            if (status === 'available') {
                // Pre-fill the booking form
                document.getElementById('room_id').value = roomId;
                document.getElementById('check_in_date').value = date;
                
                // Set checkout to next day
                const checkIn = new Date(date);
                const checkOut = new Date(checkIn);
                checkOut.setDate(checkOut.getDate() + 1);
                document.getElementById('check_out_date').value = checkOut.toISOString().split('T')[0];
                
                openBookingModal();
            } else if (bookingData) {
                // Show booking details in popup
                currentBookingData = bookingData;
                showGuestInfo(bookingData);
            }
        }

        function showGuestInfo(booking) {
            console.log('Showing guest info for booking:', booking);
            
            // Store booking data for editing
            currentBookingData = {
                id: booking.id,
                room_id: booking.room_id,
                guest_name: booking.guest_name,
                guest_email: booking.guest_email || '',
                guest_phone: booking.guest_phone || '',
                check_in_date: booking.check_in_date,
                check_out_date: booking.check_out_date,
                total_price: booking.total_amount,
                special_requests: booking.special_requests || ''
            };
            
            console.log('Stored currentBookingData:', currentBookingData);
            
            const content = `
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">👤 Información del Cliente</h3>
                            <p><strong>Nombre:</strong> ${booking.guest_name}</p>
                            <p><strong>Email:</strong> <a href="mailto:${booking.guest_email}" style="color: #007bff; text-decoration: none;">📧 ${booking.guest_email}</a></p>
                            ${booking.guest_phone ? `<p><strong>WhatsApp:</strong> <a href="https://wa.me/${booking.guest_phone.replace(/[\s\-\(\)]/g, '')}" target="_blank" style="color: #25D366; text-decoration: none;">📱 ${booking.guest_phone}</a></p>` : ''}
                            <p><strong>ID Reserva:</strong> #${booking.id}</p>
                            <p><strong>Estado:</strong> <span style="background: ${booking.status === 'confirmed' ? '#d4edda' : '#fff3cd'}; padding: 2px 8px; border-radius: 4px; color: ${booking.status === 'confirmed' ? '#155724' : '#856404'};">${booking.status === 'confirmed' ? 'Confirmada' : booking.status}</span></p>
                        </div>
                        <div>
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">🏨 Detalles de la Habitación</h3>
                            <p><strong>Habitación:</strong> ${booking.room_number}</p>
                            <p><strong>Tipo:</strong> ${booking.room_type}</p>
                            <p><strong>Precio Total:</strong> ${formatDualCurrency(booking.total_amount)}</p>
                        </div>
                    </div>
                </div>
                
                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <h3 style="color: #1976d2; margin-bottom: 15px;">📅 Fechas de Estancia</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; text-align: center;">
                        <div>
                            <p><strong>Check-in</strong></p>
                            <p style="font-size: 1.1em; color: #1976d2;">${new Date(booking.check_in_date).toLocaleDateString('es-ES', {weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'})}</p>
                        </div>
                        <div>
                            <p><strong>Check-out</strong></p>
                            <p style="font-size: 1.1em; color: #1976d2;">${new Date(booking.check_out_date).toLocaleDateString('es-ES', {weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'})}</p>
                        </div>
                        <div>
                            <p><strong>Noches</strong></p>
                            <p style="font-size: 1.1em; color: #1976d2;">${Math.ceil((new Date(booking.check_out_date) - new Date(booking.check_in_date)) / (1000 * 60 * 60 * 24))}</p>
                        </div>
                    </div>
                </div>
                
                ${booking.special_requests ? `
                <div style="background: #fff3e0; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                    <h3 style="color: #f57c00; margin-bottom: 10px;">📝 Solicitudes Especiales</h3>
                    <p>${booking.special_requests}</p>
                </div>
                ` : ''}
                
                <div style="background: #f3e5f5; padding: 15px; border-radius: 10px;">
                    <h3 style="color: #7b1fa2; margin-bottom: 10px;">📊 Información de Reserva</h3>
                    <p><strong>Fecha de Reserva:</strong> ${new Date(booking.booking_date).toLocaleDateString('es-ES', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'})}</p>
                </div>
            `;
            
            document.getElementById('guestInfoContent').innerHTML = content;
            document.getElementById('guestInfoModal').style.display = 'block';
        }

        function editBooking() {
            console.log('Edit booking clicked');
            console.log('Current booking data:', currentBookingData);
            
            if (currentBookingData) {
                // Close guest info modal first
                closeGuestInfoModal();
                // Open edit booking modal with current data
                openEditBookingModal(currentBookingData);
            } else {
                alert('No hay datos de reserva disponibles para editar.');
            }
        }
        
        function openBookingModal() {
            document.getElementById('bookingModal').style.display = 'block';
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
        
        function openEditBookingModal(bookingData) {
            console.log('Opening edit modal with data:', bookingData);
            
            try {
                // Populate form with current booking data
                document.getElementById('edit_booking_id').value = bookingData.id;
                document.getElementById('edit_room_id').value = bookingData.room_id;
                document.getElementById('edit_guest_name').value = bookingData.guest_name || '';
                document.getElementById('edit_guest_email').value = bookingData.guest_email || '';
                document.getElementById('edit_guest_phone').value = bookingData.guest_phone || '';
                document.getElementById('edit_check_in').value = bookingData.check_in_date;
                document.getElementById('edit_check_out').value = bookingData.check_out_date;
                document.getElementById('edit_total_price').value = bookingData.total_price || '';
                document.getElementById('edit_special_requests').value = bookingData.special_requests || '';
                
                console.log('Form populated, showing modal...');
                
                // Show the modal
                const modal = document.getElementById('editBookingModal');
                if (modal) {
                    modal.style.display = 'block';
                    console.log('Modal should be visible now');
                } else {
                    console.error('Edit booking modal not found!');
                }
            } catch (error) {
                console.error('Error opening edit modal:', error);
            }
        }
        
        function closeEditBookingModal() {
            document.getElementById('editBookingModal').style.display = 'none';
            currentBookingData = null; // Clear data when closing edit modal
        }
        
        function deleteBooking() {
            if (confirm('¿Estás seguro de que quieres eliminar esta reserva? Esta acción no se puede deshacer.')) {
                const bookingId = document.getElementById('edit_booking_id').value;
                if (bookingId) {
                    window.location.href = `calendar_view.php?delete_booking=${bookingId}&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>`;
                }
            }
        }
        
        function showRoomPreview() {
            // Hide all previews
            const allPreviews = document.querySelectorAll('.room-preview');
            allPreviews.forEach(preview => preview.classList.remove('active'));
            
            // Show selected room preview
            const selectedRoomId = document.getElementById('room_id').value;
            if (selectedRoomId) {
                const preview = document.getElementById('room-preview-' + selectedRoomId);
                if (preview) {
                    preview.classList.add('active');
                }
            }
        }

        function closeGuestInfoModal() {
            document.getElementById('guestInfoModal').style.display = 'none';
            // Don't clear currentBookingData here, as it might be needed for editing
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const bookingModal = document.getElementById('bookingModal');
            const guestModal = document.getElementById('guestInfoModal');
            
            if (event.target == bookingModal) {
                bookingModal.style.display = 'none';
            }
            if (event.target == guestModal) {
                guestModal.style.display = 'none';
                currentBookingData = null;
            }
        }
        
        // Update checkout date when checkin changes
        document.getElementById('check_in_date').addEventListener('change', function() {
            const checkIn = new Date(this.value);
            const checkOut = new Date(checkIn);
            checkOut.setDate(checkOut.getDate() + 1);
            document.getElementById('check_out_date').min = checkOut.toISOString().split('T')[0];
            
            if (document.getElementById('check_out_date').value <= this.value) {
                document.getElementById('check_out_date').value = checkOut.toISOString().split('T')[0];
            }
        });

        // Pricing and discount functions
        let roomPrices = {};
        
        // Store room prices for calculation
        <?php foreach ($rooms as $room): ?>
            roomPrices['<?php echo $room['id']; ?>'] = <?php echo $room['price'] ?? 0; ?>;
        <?php endforeach; ?>

        function toggleCustomPrice() {
            const checkbox = document.getElementById('use_custom_price');
            const usdInput = document.getElementById('custom_price_usd');
            const penInput = document.getElementById('custom_price_pen');
            const selectedCurrency = document.getElementById('currency_type').value;
            
            if (checkbox.checked) {
                usdInput.disabled = false;
                penInput.disabled = false;
                
                // Focus on the appropriate currency input based on selection
                if (selectedCurrency === 'USD') {
                    usdInput.required = true;
                    penInput.required = false;
                    usdInput.focus();
                } else {
                    penInput.required = true;
                    usdInput.required = false;
                    penInput.focus();
                }
            } else {
                usdInput.disabled = true;
                penInput.disabled = true;
                usdInput.required = false;
                penInput.required = false;
                usdInput.value = '';
                penInput.value = '';
            }
            calculateTotal();
        }

        function toggleDiscountInput() {
            const discountType = document.getElementById('discount_type').value;
            const discountValue = document.getElementById('discount_value');
            const discountReason = document.getElementById('discount_reason');
            
            if (discountType) {
                discountValue.disabled = false;
                discountReason.disabled = false;
                discountValue.placeholder = discountType === 'percentage' ? '0-100' : '0.00';
                if (discountType === 'percentage') {
                    discountValue.max = '100';
                } else {
                    discountValue.removeAttribute('max');
                }
            } else {
                discountValue.disabled = true;
                discountReason.disabled = true;
                discountValue.value = '';
                discountReason.value = '';
            }
            calculateTotal();
        }

        function calculateTotal() {
            const roomId = document.getElementById('room_id').value;
            const checkIn = document.getElementById('check_in_date').value;
            const checkOut = document.getElementById('check_out_date').value;
            const useCustomPrice = document.getElementById('use_custom_price').checked;
            const selectedCurrency = document.getElementById('booking_currency').value;
            const discountType = document.getElementById('discount_type').value;
            const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;

            if (!roomId || !checkIn || !checkOut) {
                resetPriceDisplay();
                return;
            }

            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);
            const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

            if (nights <= 0) {
                resetPriceDisplay();
                return;
            }

            let pricePerNightUSD = 0;
            
            if (useCustomPrice) {
                // Use custom pricing based on selected currency
                const customPriceUSD = parseFloat(document.getElementById('custom_price_usd').value) || 0;
                const customPricePEN = parseFloat(document.getElementById('custom_price_pen').value) || 0;
                
                if (selectedCurrency === 'USD' && customPriceUSD > 0) {
                    pricePerNightUSD = customPriceUSD;
                } else if (selectedCurrency === 'PEN' && customPricePEN > 0) {
                    pricePerNightUSD = customPricePEN / 3.75; // Convert PEN to USD for calculation
                } else {
                    pricePerNightUSD = customPriceUSD || (customPricePEN / 3.75) || 0;
                }
            } else {
                // Use room default price (assumed to be in USD)
                pricePerNightUSD = roomPrices[roomId] || 0;
            }

            let subtotalUSD = pricePerNightUSD * nights;
            let discountAmountUSD = 0;

            if (discountType && discountValue > 0) {
                if (discountType === 'percentage') {
                    discountAmountUSD = subtotalUSD * (discountValue / 100);
                } else if (discountType === 'fixed_usd') {
                    discountAmountUSD = Math.min(discountValue, subtotalUSD);
                } else if (discountType === 'fixed_pen') {
                    discountAmountUSD = Math.min(discountValue / 3.75, subtotalUSD);
                }
            }

            const totalUSD = subtotalUSD - discountAmountUSD;

            // Update display with dual currency
            document.getElementById('basePrice').innerHTML = formatCurrencyInput(pricePerNightUSD);
            document.getElementById('nightCount').textContent = nights;
            document.getElementById('subtotal').innerHTML = formatCurrencyInput(subtotalUSD);
            
            const discountDisplay = document.getElementById('discountDisplay');
            if (discountAmountUSD > 0) {
                document.getElementById('discountAmount').innerHTML = '-' + formatCurrencyInput(discountAmountUSD);
                discountDisplay.style.display = 'block';
            } else {
                discountDisplay.style.display = 'none';
            }
            
            document.getElementById('totalPrice').innerHTML = '<strong>' + formatCurrencyInput(totalUSD) + '</strong>';
        }

        function resetPriceDisplay() {
            document.getElementById('basePrice').innerHTML = '$0.00 / S/ 0.00';
            document.getElementById('nightCount').textContent = '0';
            document.getElementById('subtotal').innerHTML = '$0.00 / S/ 0.00';
            document.getElementById('discountDisplay').style.display = 'none';
            document.getElementById('totalPrice').innerHTML = '<strong>$0.00 / S/ 0.00</strong>';
        }

        // Add event listeners for automatic calculation
        document.getElementById('room_id').addEventListener('change', function() {
            calculateTotal();
            showRoomPreview();
        });
        document.getElementById('check_in_date').addEventListener('change', function() {
            const checkIn = new Date(this.value);
            const checkOut = new Date(checkIn);
            checkOut.setDate(checkOut.getDate() + 1);
            const checkOutInput = document.getElementById('check_out_date');
            checkOutInput.min = checkOut.toISOString().split('T')[0];
            
            if (new Date(checkOutInput.value) <= checkIn) {
                checkOutInput.value = checkOut.toISOString().split('T')[0];
            }
            calculateTotal();
        });
        document.getElementById('check_out_date').addEventListener('change', calculateTotal);

    </script>
</body>
</html>