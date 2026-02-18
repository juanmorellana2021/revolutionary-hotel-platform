<?php
session_start();
require_once '../db_connection_pdo.php';

// Check if logged in
if (!isset($_SESSION['partner_id'])) {
    header('Location: login.php');
    exit();
}

$booking_id = $_GET['id'] ?? null;
$hotel_id = $_GET['hotel_id'] ?? null;

if (!$booking_id || !$hotel_id) {
    header('Location: dashboard.php');
    exit();
}

// Get partner info
$stmt = $pdo->prepare("SELECT * FROM aini_partner_businesses WHERE id = ?");
$stmt->execute([$_SESSION['partner_id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

// Get hotel info and verify ownership
$stmt = $pdo->prepare("
    SELECT hp.* 
    FROM hotel_properties hp
    WHERE hp.id = ? AND (hp.partner_business_id = ? OR hp.email = ?)
");
$stmt->execute([$hotel_id, $_SESSION['partner_id'], $partner['email']]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: dashboard.php');
    exit();
}

// Get booking details
$stmt = $pdo->prepare("
    SELECT gb.*, 
           au.name as user_name, 
           au.email as user_email, 
           au.phone as user_phone,
           hp.name as hotel_name
    FROM guest_bookings gb
    LEFT JOIN ainitravel_users au ON gb.user_id = au.id
    LEFT JOIN hotel_properties hp ON gb.hotel_id = hp.id
    WHERE gb.id = ? AND gb.hotel_id = ?
");
$stmt->execute([$booking_id, $hotel_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: view_bookings.php?id=' . $hotel_id);
    exit();
}

// Status badges
$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'confirmed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800',
    'completed' => 'bg-blue-100 text-blue-800'
];

$payment_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'paid' => 'bg-green-100 text-green-800',
    'refunded' => 'bg-gray-100 text-gray-800'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - <?php echo htmlspecialchars($booking['booking_reference']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <a href="view_bookings.php?id=<?php echo $hotel_id; ?>" class="text-blue-600 hover:text-blue-700">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900 mt-2">Booking Details</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($hotel['name']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Booking Reference</p>
                        <p class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Status</h2>
                        <div class="flex gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Booking Status</p>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $status_colors[$booking['status']]; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Payment Status</p>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $payment_colors[$booking['payment_status']]; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Guest Information -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Guest Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Name</p>
                                <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Phone</p>
                                <p class="font-medium"><?php echo htmlspecialchars($booking['guest_phone'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Number of Guests</p>
                                <p class="font-medium"><?php echo $booking['guests']; ?> guest(s)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Booking Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Check-in</p>
                                <p class="font-medium text-lg"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Check-out</p>
                                <p class="font-medium text-lg"><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Number of Nights</p>
                                <p class="font-medium"><?php echo $booking['nights']; ?> night(s)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Room Type</p>
                                <p class="font-medium"><?php echo htmlspecialchars($booking['room_type'] ?? 'Standard'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Number of Rooms</p>
                                <p class="font-medium"><?php echo $booking['room_count']; ?> room(s)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Booking Source</p>
                                <p class="font-medium"><?php echo ucfirst($booking['booking_source']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Special Requests -->
                    <?php if ($booking['special_requests']): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Special Requests</h2>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Price Summary -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Price Summary</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Price per Night</span>
                                <span class="font-medium"><?php echo $booking['currency']; ?> <?php echo number_format($booking['price_per_night'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nights</span>
                                <span class="font-medium"><?php echo $booking['nights']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rooms</span>
                                <span class="font-medium"><?php echo $booking['room_count']; ?></span>
                            </div>
                            <div class="border-t pt-3 flex justify-between">
                                <span class="text-lg font-semibold">Total</span>
                                <span class="text-lg font-bold text-blue-600"><?php echo $booking['currency']; ?> <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                            <?php if ($booking['aini_coins_earned'] > 0): ?>
                            <div class="flex justify-between text-sm text-green-600">
                                <span>Aini Coins Earned</span>
                                <span class="font-medium"><?php echo number_format($booking['aini_coins_earned']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Actions</h2>
                        <div class="space-y-2">
                            <?php if ($booking['status'] === 'pending'): ?>
                            <button onclick="updateStatus('confirmed')" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-check mr-2"></i>Confirm Booking
                            </button>
                            <button onclick="updateStatus('cancelled')" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-times mr-2"></i>Cancel Booking
                            </button>
                            <?php endif; ?>
                            <a href="mailto:<?php echo htmlspecialchars($booking['guest_email']); ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center">
                                <i class="fas fa-envelope mr-2"></i>Email Guest
                            </a>
                            <?php if ($booking['guest_phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($booking['guest_phone']); ?>" class="block w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-center">
                                <i class="fas fa-phone mr-2"></i>Call Guest
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Timeline</h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <p class="text-gray-600">Created</p>
                                <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Last Updated</p>
                                <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($booking['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateStatus(newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus} this booking?`)) {
                return;
            }
            
            fetch('update_booking_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    booking_id: <?php echo $booking_id; ?>,
                    status: newStatus
                }),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking status updated!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Could not update status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
    </script>
</body>
</html>
