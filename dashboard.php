<?php
session_start();
require_once 'classes.php';
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

// Get user's bookings
$userBookings = $bookingObj->getUserBookings($_SESSION['user_id']);
$rooms = $roomObj->getAllRooms();

// Handle new booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $roomId = $_POST['room_id'];
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    
    $room = $roomObj->getRoomById($roomId);
    $days = (strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24);
    $totalPrice = $days * $room['price_per_night'];
    
    $result = $bookingObj->createBooking($user['id'], $roomId, $checkIn, $checkOut, $totalPrice);
    
    if ($result['success']) {
        $successMessage = "Booking created successfully!";
        // Refresh bookings
        $userBookings = $bookingObj->getUserBookings($user['id']);
    } else {
        $errorMessage = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Dashboard'); ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            color: #667eea !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link {
            color: #4a5568 !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #f7fafc;
            color: #667eea !important;
        }
        
        .container-main {
            padding: 2rem 0;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.purple { border-left-color: #667eea; }
        .stat-card.blue { border-left-color: #4299e1; }
        .stat-card.green { border-left-color: #48bb78; }
        .stat-card.orange { border-left-color: #ed8936; }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon.blue { background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .room-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .room-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .room-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .room-type {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .room-body {
            padding: 1.5rem;
        }
        
        .room-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .room-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-badge {
            background: #f7fafc;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #4a5568;
        }
        
        .btn-book {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .bookings-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .bookings-table thead {
            background: #f7fafc;
        }
        
        .bookings-table th {
            padding: 1rem;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .bookings-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-confirmed {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-pending {
            background: #feebc8;
            color: #744210;
        }
        
        .status-cancelled {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom">
        <div class="container">
            <a href="owner_account.php" class="navbar-brand">
                <i class="fas fa-hotel"></i>
                <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel'); ?>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="owner_account.php" class="nav-link">
                    <i class="fas fa-th-large"></i> My Properties
                </a>
                <a href="hotel_setup.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem;">
                        Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋
                    </h1>
                    <p style="color: #718096; margin: 0;">
                        Here's what's happening with your hotel today
                    </p>
                </div>
                <div class="text-end">
                    <p class="text-muted mb-1"><i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?></p>
                    <p class="text-muted mb-0"><i class="fas fa-clock"></i> <?php echo date('g:i A'); ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon purple">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-value"><?php echo count($rooms); ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
            
            <div class="stat-card blue">
                <div class="stat-icon blue">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo count($userBookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">
                    <?php echo count(array_filter($userBookings, function($b) { return $b['status'] == 'confirmed'; })); ?>
                </div>
                <div class="stat-label">Confirmed</div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon orange">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">
                    $<?php echo number_format(array_sum(array_column($userBookings, 'total_price')), 0); ?>
                </div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Available Rooms -->
        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-door-open"></i>
                Available Rooms
            </h2>
            
            <?php if (count($rooms) > 0): ?>
                <div class="rooms-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <div class="room-header">
                                <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                                <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                            </div>
                            <div class="room-body">
                                <div class="room-price">
                                    $<?php echo number_format($room['price_per_night'], 2); ?>
                                    <span style="font-size: 0.9rem; color: #718096; font-weight: normal;">/night</span>
                                </div>
                                <div class="room-features">
                                    <span class="feature-badge">
                                        <i class="fas fa-users"></i> <?php echo $room['capacity']; ?> guests
                                    </span>
                                    <?php if ($room['extra_bed_available']): ?>
                                        <span class="feature-badge">
                                            <i class="fas fa-bed"></i> Extra bed
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-book" data-room-id="<?php echo $room['id']; ?>" onclick="alert('Booking feature coming soon!')">
                                    <i class="fas fa-calendar-plus"></i> Book Now
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bed"></i>
                    <h3>No rooms available</h3>
                    <p>Check back later for available rooms</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Bookings -->
        <div class="content-card">
            <h2 class="section-title">
                <i class="fas fa-list"></i>
                My Bookings
            </h2>
            
            <?php if (count($userBookings) > 0): ?>
                <div class="table-responsive">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userBookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['room_number']); ?></strong><br>
                                        <small style="color: #718096;"><?php echo htmlspecialchars($booking['room_type']); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><strong>$<?php echo number_format($booking['total_price'], 2); ?></strong></td>
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
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar"></i>
                    <h3>No bookings yet</h3>
                    <p>Start by booking a room above</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
        </div>

        <div class="dashboard-grid">
            <div class="section">
                <h3>📅 Create New Booking</h3>
                <form method="POST" class="booking-form">
                    <div class="form-group">
                        <label for="room_id">Select Room</label>
                        <select id="room_id" name="room_id" required>
                            <option value="">Choose a room...</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                    ($<?php echo $room['price_per_night']; ?>/night)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="check_in">Check-in Date</label>
                        <input type="date" id="check_in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="check_out">Check-out Date</label>
                        <input type="date" id="check_out" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <button type="submit" name="create_booking" class="btn">Create Booking</button>
                </form>
            </div>

            <div class="section">
                <h3>🏠 Available Rooms</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($rooms as $room): ?>
                        <div style="padding: 1rem; border: 1px solid #eee; border-radius: 5px; margin-bottom: 1rem;">
                            <h4>Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?></h4>
                            <p><strong>Capacity:</strong> <?php echo $room['capacity']; ?> guests</p>
                            <p><strong>Price:</strong> $<?php echo $room['price_per_night']; ?>/night</p>
                            <p><small><?php echo $room['description']; ?></small></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>📋 Your Bookings</h3>
            <?php if (empty($userBookings)): ?>
                <p>You haven't made any bookings yet. Create your first booking above!</p>
            <?php else: ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Booked On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userBookings as $booking): ?>
                            <tr>
                                <td>Room <?php echo $booking['room_number']; ?></td>
                                <td><?php echo $booking['room_type']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></td>
                                <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>