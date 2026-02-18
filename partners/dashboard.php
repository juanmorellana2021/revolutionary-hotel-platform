<?php
session_start();
require_once '../db_connection_pdo.php';

// Check if logged in
if (!isset($_SESSION['partner_id'])) {
    header('Location: login.php');
    exit();
}

// Get partner info
$stmt = $pdo->prepare("SELECT * FROM aini_partner_businesses WHERE id = ?");
$stmt->execute([$_SESSION['partner_id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all hotels owned by this partner
$stmt = $pdo->prepare("
    SELECT hp.* 
    FROM hotel_properties hp
    WHERE hp.partner_business_id = ? OR hp.email = ?
    ORDER BY hp.created_at DESC
");
$stmt->execute([$_SESSION['partner_id'], $partner['email']]);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update partner_business_id for hotels that don't have it set
if (!empty($hotels)) {
    foreach ($hotels as $hotel) {
        if (empty($hotel['partner_business_id'])) {
            $update = $pdo->prepare("UPDATE hotel_properties SET partner_business_id = ? WHERE id = ?");
            $update->execute([$_SESSION['partner_id'], $hotel['id']]);
        }
    }
}

// Refresh hotels after update
$stmt->execute([$_SESSION['partner_id'], $partner['email']]);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_hotels = count($hotels);
$pending_hotels = count(array_filter($hotels, fn($h) => $h['status'] === 'pending'));
$active_hotels = count(array_filter($hotels, fn($h) => $h['status'] === 'approved' && $h['is_active']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Dashboard - AiNi Travel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Top Navigation -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-white">
                        <i class="fas fa-hotel mr-2"></i>AiNi Travel Partner
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-white">
                        <i class="fas fa-user-circle mr-2"></i><?= htmlspecialchars($partner['business_name'] ?? $partner['email']) ?>
                    </span>
                    <a href="logout.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-purple-50 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-hotel text-4xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Hotels</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_hotels ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-4xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $active_hotels ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-4xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending Review</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $pending_hotels ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-check text-4xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Bookings</p>
                        <p class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hotels Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-building mr-2 text-purple-600"></i>My Hotels
                </h2>
                <a href="../hotel_register.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add New Hotel
                </a>
            </div>

            <div class="p-6">
                <?php if (empty($hotels)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-hotel text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Hotels Yet</h3>
                        <p class="text-gray-500 mb-6">Get started by registering your first property</p>
                        <a href="../hotel_register.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition">
                            <i class="fas fa-plus mr-2"></i>Register Your Hotel
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($hotels as $hotel): 
                            $images = json_decode($hotel['images'] ?? '[]', true);
                            // Handle both old format (strings) and new format (objects with path/caption)
                            if (!empty($images)) {
                                $first_image = is_string($images[0]) ? $images[0] : $images[0]['path'];
                            } else {
                                $first_image = null;
                            }
                            
                            $status_colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'suspended' => 'bg-gray-100 text-gray-800'
                            ];
                            $status_color = $status_colors[$hotel['status']] ?? 'bg-gray-100 text-gray-800';
                            
                            $status_icons = [
                                'pending' => 'fa-clock',
                                'approved' => 'fa-check-circle',
                                'rejected' => 'fa-times-circle',
                                'suspended' => 'fa-pause-circle'
                            ];
                            $status_icon = $status_icons[$hotel['status']] ?? 'fa-circle';
                        ?>
                            <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                                <!-- Hotel Image -->
                                <div class="h-48 bg-gray-200 relative">
                                    <?php if ($first_image): ?>
                                        <img src="<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($hotel['name']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-hotel text-6xl text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Status Badge -->
                                    <div class="absolute top-2 right-2">
                                        <span class="<?= $status_color ?> px-3 py-1 rounded-full text-xs font-semibold">
                                            <i class="fas <?= $status_icon ?> mr-1"></i><?= ucfirst($hotel['status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Hotel Info -->
                                <div class="p-4">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($hotel['name']) ?></h3>
                                    
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-map-marker-alt mr-2 text-purple-600"></i><?= htmlspecialchars($hotel['location']) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-star mr-2 text-yellow-500"></i><?= $hotel['star_category'] ?> Stars • <?= ucfirst($hotel['property_type']) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-door-open mr-2 text-blue-600"></i><?= $hotel['rooms_available'] ?? 10 ?> Rooms
                                        </p>
                                    </div>

                                    <a href="hotel_profile.php?id=<?= $hotel['id'] ?>" class="block w-full text-center bg-purple-600 text-white py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                                        <i class="fas fa-eye mr-2"></i>View Profile
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</body>
</html>
