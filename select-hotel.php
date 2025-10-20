<?php
/**
 * Hotel Selection Page
 * Allows users to select which hotel context to work in
 * Platform admins can switch between hotels
 */

session_start();
require_once 'includes/classes.php';
require_once 'includes/tenant_context.php';
require_once 'includes/rbac_helper.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle hotel selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_hotel'])) {
    $selected_hotel_id = (int)$_POST['hotel_id'];
    
    // Verify user has access to this hotel
    if (TenantContext::hasAccessToHotel($selected_hotel_id)) {
        TenantContext::setHotel($selected_hotel_id);
        
        // Redirect to appropriate dashboard
        $redirect = RBACHelper::getDashboardForRole($user_role);
        header("Location: {$redirect}");
        exit;
    } else {
        $error = "You don't have access to this hotel.";
    }
}

// Get hotels user has access to
$hotels = [];

if ($user_role === 'owner' && !isset($_SESSION['hotel_id'])) {
    // Platform owner - can access all hotels
    $query = "SELECT id, hotel_name, city, status, featured FROM hotels WHERE status != 'closed' ORDER BY hotel_name";
    $result = $db->query($query);
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
} else if (isset($_SESSION['hotel_id'])) {
    // Regular hotel staff - only their hotel
    $hotel_id = $_SESSION['hotel_id'];
    $query = "SELECT id, hotel_name, city, status, featured FROM hotels WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}

// If user has only one hotel, auto-select it
if (count($hotels) === 1) {
    TenantContext::setHotel($hotels[0]['id']);
    $redirect = RBACHelper::getDashboardForRole($user_role);
    header("Location: {$redirect}");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Hotel - AINI.com</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-hotel text-blue-600"></i> AINI.com
                </h1>
                <h2 class="text-2xl font-semibold text-gray-700">Select Hotel</h2>
                <p class="mt-2 text-gray-600">Choose which hotel you want to manage</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($hotels)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
                    <i class="fas fa-info-circle"></i> No hotels available. Please contact administrator.
                </div>
            <?php else: ?>
                <!-- Hotel Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($hotels as $hotel): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="hotel_id" value="<?= $hotel['id'] ?>">
                            <button type="submit" name="select_hotel" class="w-full text-left">
                                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border-2 border-transparent hover:border-blue-500">
                                    <!-- Hotel Image Placeholder -->
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-32 flex items-center justify-center">
                                        <i class="fas fa-hotel text-white text-4xl"></i>
                                    </div>
                                    
                                    <!-- Hotel Info -->
                                    <div class="p-5">
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                                            <?= htmlspecialchars($hotel['hotel_name']) ?>
                                        </h3>
                                        
                                        <p class="text-gray-600 mb-3">
                                            <i class="fas fa-map-marker-alt text-blue-500"></i>
                                            <?= htmlspecialchars($hotel['city']) ?>
                                        </p>
                                        
                                        <!-- Status Badge -->
                                        <?php if ($hotel['status'] === 'active'): ?>
                                            <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php elseif ($hotel['status'] === 'pending'): ?>
                                            <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($hotel['featured']): ?>
                                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded ml-2">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Select Button -->
                                        <div class="mt-4">
                                            <div class="bg-blue-600 text-white text-center py-2 rounded hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-sign-in-alt"></i> Select Hotel
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Logout Link -->
            <div class="text-center mt-8">
                <a href="logout.php" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <!-- Admin Options -->
            <?php if ($user_role === 'owner' && !isset($_SESSION['hotel_id'])): ?>
                <div class="mt-8 text-center">
                    <a href="/hotel-register.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus-circle"></i> Register New Hotel
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
