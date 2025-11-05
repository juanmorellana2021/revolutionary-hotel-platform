<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$bookingObj = new Booking();
$roomObj = new Room();

// Get user's bookings
$userBookings = $bookingObj->getUserBookings($user['id']);
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
    <title>Hotel Booking Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section h3 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .booking-form {
            display: grid;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🏨 Hotel Booking System</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                <a href="wallet.php" class="logout-btn" style="background: #FFD700; color: #333; margin-right: 10px;">🪙 My Wallet</a>
                <?php 
                $userManager = new UserManager();
                if ($userManager->isManager($user['id'])): 
                ?>
                    <a href="manager_dashboard.php" class="logout-btn" style="background: #28a745; margin-right: 10px;">🏨 Manager</a>
                    <a href="accounting_dashboard.php" class="logout-btn" style="background: #007bff; margin-right: 10px;">💰 Accounting</a>
                <?php endif; ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome to Your Dashboard!</h2>
            <p>Manage your hotel bookings and explore available rooms.</p>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="message success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($userBookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($rooms); ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($userBookings, function($b) { return $b['status'] == 'confirmed'; })); ?></div>
                <div class="stat-label">Confirmed Bookings</div>
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

    <script>
        // Set minimum check-out date based on check-in selection
        document.getElementById('check_in').addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            checkInDate.setDate(checkInDate.getDate() + 1);
            const minCheckOut = checkInDate.toISOString().split('T')[0];
            document.getElementById('check_out').min = minCheckOut;
        });
    </script>
</body>
</html>