<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

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
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Get database connection for photo operations
$database = new Database();
$connection = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        // Handle custom room type
        $roomType = $_POST['room_type'];
        if ($roomType === 'custom' && !empty($_POST['custom_room_type'])) {
            $roomType = $_POST['custom_room_type'];
        }
        
        // Handle extra bed
        $extraBedAvailable = (int)($_POST['extra_bed_available'] ?? 0);
        $extraBedPrice = $extraBedAvailable ? (float)($_POST['extra_bed_price'] ?? 0) : 0;
        
        // Handle single discount
        $singleDiscountType = $_POST['single_discount_type'] ?? 'percentage';
        $singleDiscountValue = (float)($_POST['single_discount_value'] ?? 0);
        
        // We need to use direct database insertion since addRoom doesn't support extra bed yet
        $db = new Database();
        $connection = $db->getConnection();
        
        $stmt = $connection->prepare("
            INSERT INTO rooms (room_number, room_type, price, max_occupancy, amenities, extra_bed_available, extra_bed_price, single_discount_type, single_discount_value) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $_POST['room_number'],
            $roomType,
            (float)$_POST['price'],
            (int)$_POST['max_occupancy'],
            $_POST['amenities'] ?? '',
            $extraBedAvailable,
            $extraBedPrice,
            $singleDiscountType,
            $singleDiscountValue
        ]);
        
        $message = $success ? 'Room added successfully!' : 'Failed to add room.';
        $messageType = $success ? 'success' : 'error';
    }
    
    if (isset($_POST['update_room'])) {
        $roomId = $_POST['room_id'];
        $description = $_POST['room_description'] ?? '';
        $features = $_POST['room_features'] ?? '';
        
        // Handle custom room type
        $roomType = $_POST['room_type'];
        if ($roomType === 'custom' && !empty($_POST['custom_room_type'])) {
            $roomType = $_POST['custom_room_type'];
        }
        
        // Handle extra bed
        $extraBedAvailable = (int)($_POST['extra_bed_available'] ?? 0);
        $extraBedPrice = $extraBedAvailable ? (float)($_POST['extra_bed_price'] ?? 0) : 0;
        
        // Handle single discount
        $singleDiscountType = $_POST['single_discount_type'] ?? 'percentage';
        $singleDiscountValue = (float)($_POST['single_discount_value'] ?? 0);
        
        // Handle room update (we'll extend the Room class for this)
        $db = new Database();
        $connection = $db->getConnection();
        
        $stmt = $connection->prepare("
            UPDATE rooms SET 
            room_type = ?, price = ?, max_occupancy = ?, amenities = ?, 
            description = ?, features = ?, extra_bed_available = ?, extra_bed_price = ?, 
            single_discount_type = ?, single_discount_value = ?
            WHERE id = ?
        ");
        
        $success = $stmt->execute([
            $roomType, $_POST['price'], $_POST['max_occupancy'],
            $_POST['amenities'], $description, $features, $extraBedAvailable, $extraBedPrice, 
            $singleDiscountType, $singleDiscountValue, $roomId
        ]);
        
        $message = $success ? 'Room updated successfully!' : 'Failed to update room.';
        $messageType = $success ? 'success' : 'error';
    }
    
    if (isset($_POST['delete_room'])) {
        $roomId = $_POST['room_id'];
        $result = $roomManager->deleteRoom($roomId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    // Handle photo upload
    if (isset($_POST['upload_photo'])) {
        $roomId = (int)$_POST['room_id'];
        $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
        
        if (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/rooms/';
            $fileName = uniqid() . '_' . basename($_FILES['room_photo']['name']);
            $targetPath = $uploadDir . $fileName;
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['room_photo']['type'], $allowedTypes)) {
                // Validate file size (max 5MB)
                if ($_FILES['room_photo']['size'] <= 5 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES['room_photo']['tmp_name'], $targetPath)) {
                        // If this is set as primary, unset other primary photos for this room
                        if ($isPrimary) {
                            $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 0 WHERE room_id = ?");
                            $stmt->execute([$roomId]);
                        }
                        
                        // Insert photo record
                        $stmt = $connection->prepare("INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$roomId, $targetPath, $fileName, $isPrimary]);
                        
                        $message = "Photo uploaded successfully";
                        $messageType = "success";
                    } else {
                        $message = "Error uploading photo";
                        $messageType = "error";
                    }
                } else {
                    $message = "File too large (max 5MB)";
                    $messageType = "error";
                }
            } else {
                $message = "Invalid file type. Use JPG, PNG, GIF or WebP";
                $messageType = "error";
            }
        }
    }
    
    // Handle photo deletion
    if (isset($_GET['delete_photo'])) {
        $photoId = (int)$_GET['delete_photo'];
        
        // Get photo info
        $stmt = $connection->prepare("SELECT photo_path FROM room_photos WHERE id = ?");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch();
        
        if ($photo) {
            // Delete file
            if (file_exists($photo['photo_path'])) {
                unlink($photo['photo_path']);
            }
            
            // Delete database record
            $stmt = $connection->prepare("DELETE FROM room_photos WHERE id = ?");
            $stmt->execute([$photoId]);
            
            $message = "Photo deleted successfully";
            $messageType = "success";
        }
    }
    
    // Handle set primary photo
    if (isset($_GET['set_primary'])) {
        $photoId = (int)$_GET['set_primary'];
        
        // Get room_id for this photo
        $stmt = $connection->prepare("SELECT room_id FROM room_photos WHERE id = ?");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch();
        
        if ($photo) {
            // Unset all primary photos for this room
            $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 0 WHERE room_id = ?");
            $stmt->execute([$photo['room_id']]);
            
            // Set this photo as primary
            $stmt = $connection->prepare("UPDATE room_photos SET is_primary = 1 WHERE id = ?");
            $stmt->execute([$photoId]);
            
            $message = "Primary photo set successfully";
            $messageType = "success";
        }
    }
}

// Get all rooms
$rooms = $roomManager->getAllRooms();

// Get photos for all rooms
$roomPhotos = [];
$stmt = $connection->prepare("
    SELECT rp.*, r.room_number 
    FROM room_photos rp 
    JOIN rooms r ON rp.room_id = r.id 
    ORDER BY rp.room_id, rp.is_primary DESC, rp.upload_date DESC
");
$stmt->execute();
$allPhotos = $stmt->fetchAll();

foreach ($allPhotos as $photo) {
    $roomPhotos[$photo['room_id']][] = $photo;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
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

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tab {
            flex: 1;
            padding: 15px 20px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #6c757d;
            text-decoration: none;
            display: block;
        }

        .nav-tab.active, .nav-tab:hover {
            background: white;
            color: #007bff;
            border-bottom: 3px solid #007bff;
        }

        .content {
            padding: 30px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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

        .section {
            margin-bottom: 40px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            font-style: italic;
        }

        #custom_room_type_group,
        #edit_custom_room_type_group {
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        #custom_room_type_group label,
        #edit_custom_room_type_group label {
            color: #007bff;
            font-weight: 600;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #545b62);
            color: white;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .room-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .room-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }

        .room-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }

        .room-type {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .room-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
            margin-left: auto;
        }

        .room-details {
            margin: 15px 0;
        }

        .room-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .room-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .room-actions .btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 0.9rem;
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
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #2c3e50;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .image-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.3s;
        }

        .image-upload:hover {
            border-color: #007bff;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 Room Management</h1>
            <p>Manage your hotel rooms, descriptions, and amenities</p>
        </div>

        <div class="nav-tabs">
            <a href="hotel_setup.php" class="nav-tab">🏨 Hotel Info</a>
            <a href="room_management.php" class="nav-tab <?php echo ($currentView === 'rooms') ? 'active' : ''; ?>">🏠 Rooms</a>
            <a href="room_management.php?view=photos" class="nav-tab <?php echo ($currentView === 'photos') ? 'active' : ''; ?>">📷 Photos</a>
            <a href="calendar_view.php" class="nav-tab">📅 Calendar</a>
            <a href="wallet.php" class="nav-tab">🪙 Wallet</a>
            <a href="accounting_dashboard.php" class="nav-tab">💰 Accounting</a>
            <a href="manager_dashboard.php" class="nav-tab">📊 Dashboard</a>
        </div>

        <div class="content">
            <?php if (isset($message)): ?>
                <div class="alert <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php $currentView = $_GET['view'] ?? 'rooms'; ?>
            
            <?php if ($currentView === 'photos'): ?>
                <!-- Photo Management View -->
                <div class="section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>📷 Photo Management</h2>
                        <a href="room_management.php" class="btn btn-secondary">← Back to Rooms</a>
                    </div>
                    
                    <div class="rooms-grid">
                        <?php foreach ($rooms as $room): ?>
                            <div class="room-card">
                                <div class="room-header">
                                    <div>
                                        <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                                        <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                    </div>
                                    <div class="room-price">$<?php echo number_format($room['price'] ?? 0, 2); ?></div>
                                </div>
                                
                                <!-- Room Photos -->
                                <?php if (isset($roomPhotos[$room['id']]) && !empty($roomPhotos[$room['id']])): ?>
                                    <?php 
                                    // Count only photos that actually exist
                                    $validPhotos = [];
                                    foreach ($roomPhotos[$room['id']] as $photo) {
                                        if (!empty($photo['photo_path']) && file_exists($photo['photo_path'])) {
                                            $validPhotos[] = $photo;
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($validPhotos)): ?>
                                    <div class="room-photos">
                                        <h4 style="margin: 10px 0 5px 0; font-size: 14px; color: #666;">📸 Photos (<?php echo count($validPhotos); ?>)</h4>
                                        <div class="photo-thumbnails" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin: 10px 0;">
                                            <?php foreach ($validPhotos as $photo): ?>
                                                <div style="position: relative;">
                                                    <img src="<?php echo $photo['photo_path']; ?>" alt="Room <?php echo htmlspecialchars($room['room_number']); ?>" 
                                                         style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: <?php echo $photo['is_primary'] ? '2px solid #28a745' : '1px solid #ddd'; ?>">
                                                    <?php if ($photo['is_primary']): ?>
                                                        <span style="position: absolute; top: 2px; right: 2px; background: #28a745; color: white; padding: 1px 4px; border-radius: 2px; font-size: 10px;">PRIMARY</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="room-photos">
                                        <p style="font-size: 12px; color: #999; margin: 10px 0;">📷 No photos uploaded</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="room-actions">
                                    <button onclick="managePhotos(<?php echo $room['id']; ?>)" class="btn btn-primary">📷 Manage Photos</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Regular Room Management View -->

            <!-- Room Statistics -->
            <div class="section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($rooms); ?></div>
                        <div class="stat-label">Total Rooms</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php 
                            $availableCount = 0;
                            foreach($rooms as $r) {
                                if (isset($r['is_available']) && $r['is_available']) $availableCount++;
                            }
                            echo $availableCount;
                        ?></div>
                        <div class="stat-label">Available Rooms</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">$<?php 
                            $totalPrice = 0;
                            $roomCount = 0;
                            foreach($rooms as $r) {
                                if (isset($r['price']) && $r['price'] > 0) {
                                    $totalPrice += $r['price'];
                                    $roomCount++;
                                }
                            }
                            echo number_format($roomCount > 0 ? $totalPrice / $roomCount : 0, 2);
                        ?></div>
                        <div class="stat-label">Average Price</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php 
                            $totalCapacity = 0;
                            foreach($rooms as $r) {
                                if (isset($r['max_occupancy'])) {
                                    $totalCapacity += $r['max_occupancy'];
                                }
                            }
                            echo $totalCapacity;
                        ?></div>
                        <div class="stat-label">Total Capacity</div>
                    </div>
                </div>
            </div>

            <!-- Add New Room -->
            <div class="section">
                <h2>➕ Add New Room</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="room_number">Room Number</label>
                            <input type="text" id="room_number" name="room_number" required>
                        </div>
                        <div class="form-group">
                            <label for="room_type">Room Type</label>
                            <select id="room_type" name="room_type" required onchange="toggleCustomRoomType(this)">
                                <option value="">Select Room Type</option>
                                <option value="Standard Single">Standard Single</option>
                                <option value="Standard Double">Standard Double</option>
                                <option value="Twin Beds">Twin Beds (2 Single Beds)</option>
                                <option value="Triple Single">Triple Single (3 Single Beds)</option>
                                <option value="Triple Twin">Triple Twin (3 Twin Beds)</option>
                                <option value="Medium Twin">Medium Twin (2 Single Beds)</option>
                                <option value="Deluxe Twin">Deluxe Twin (2 Single Beds)</option>
                                <option value="Deluxe Queen">Deluxe Queen</option>
                                <option value="Executive Suite">Executive Suite</option>
                                <option value="Presidential Suite">Presidential Suite</option>
                                <option value="Family Room">Family Room</option>
                                <option value="Connecting Rooms">Connecting Rooms</option>
                                <option value="custom">🛠️ Custom Room Type</option>
                            </select>
                        </div>
                        <div class="form-group" id="custom_room_type_group" style="display: none;">
                            <label for="custom_room_type">Custom Room Type</label>
                            <input type="text" id="custom_room_type" name="custom_room_type" placeholder="Enter custom room type (e.g., Junior Suite, Studio Apartment)">
                            <small style="color: #6c757d; font-size: 0.9rem;">Enter a custom room type name that will be saved for this room.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price per Night ($)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="max_occupancy">Base Max Occupancy</label>
                            <input type="number" id="max_occupancy" name="max_occupancy" min="1" max="10" required>
                            <small style="color: #6c757d; font-size: 0.9rem;">Maximum guests without extra bed</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="single_discount_type">Single Occupancy Discount</label>
                            <select id="single_discount_type" name="single_discount_type" onchange="toggleSingleDiscount(this)">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="single_discount_value">Discount Value</label>
                            <input type="number" id="single_discount_value" name="single_discount_value" step="0.01" min="0" max="100" placeholder="e.g., 15 for 15% or $15">
                            <small style="color: #ff9800; font-size: 0.9rem;" id="single_discount_help">Enter percentage (0-100) for single guest discount</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="extra_bed_available">Extra Bed Available</label>
                            <select id="extra_bed_available" name="extra_bed_available" onchange="toggleExtraBedPrice(this)">
                                <option value="0">No Extra Bed</option>
                                <option value="1">Extra Bed Available</option>
                            </select>
                        </div>
                        <div class="form-group" id="extra_bed_price_group" style="display: none;">
                            <label for="extra_bed_price">Extra Bed Price per Night ($)</label>
                            <input type="number" id="extra_bed_price" name="extra_bed_price" step="0.01" min="0" placeholder="Additional cost for extra bed">
                            <small style="color: #28a745; font-size: 0.9rem;">Extra bed adds +1 person to room capacity</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <div id="capacity_display" style="padding: 12px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3; margin-bottom: 15px;">
                            <strong>🏠 Total Room Capacity: <span id="total_capacity_text">Enter base occupancy</span></strong>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="amenities">Room Amenities</label>
                        <textarea id="amenities" name="amenities" 
                                  placeholder="List room amenities (e.g., Free WiFi, Air Conditioning, TV, Mini Fridge)"></textarea>
                    </div>
                    <button type="submit" name="add_room" class="btn btn-success">➕ Add Room</button>
                </form>
            </div>

            <!-- Existing Rooms -->
            <div class="section">
                <h2>🏠 Current Rooms (<?php echo count($rooms); ?>)</h2>
                <div class="room-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <div class="room-header">
                                <div>
                                    <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                                    <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                </div>
                                <div class="room-price">$<?php echo number_format($room['price'] ?? 0, 2); ?></div>
                            </div>
                            
                            <div class="room-details">
                                <div class="room-detail-item">
                                    <span>👥 Capacity:</span>
                                    <span>
                                        <?php 
                                        $baseCapacity = $room['max_occupancy'] ?? 0;
                                        $hasExtraBed = isset($room['extra_bed_available']) && $room['extra_bed_available'];
                                        
                                        if ($hasExtraBed) {
                                            $totalCapacity = $baseCapacity + 1;
                                            echo $baseCapacity . " people (+" . $totalCapacity . " with extra bed)";
                                        } else {
                                            echo $baseCapacity . " people";
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="room-detail-item">
                                    <span>🟢 Status:</span>
                                    <span><?php echo ($room['is_available'] ?? true) ? 'Available' : 'Occupied'; ?></span>
                                </div>
                                <?php if (isset($room['extra_bed_available']) && $room['extra_bed_available']): ?>
                                    <div class="room-detail-item">
                                        <span>🛏️ Extra Bed:</span>
                                        <span>+1 person (+$<?php echo number_format($room['extra_bed_price'] ?? 0, 2); ?>/night)</span>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($room['amenities']) && $room['amenities']): ?>
                                    <div class="room-detail-item">
                                        <span>🛎️ Amenities:</span>
                                        <span><?php echo htmlspecialchars(substr($room['amenities'], 0, 30)) . '...'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Room Photos -->
                            <?php if (isset($roomPhotos[$room['id']]) && !empty($roomPhotos[$room['id']])): ?>
                                <?php 
                                // Count only photos that actually exist
                                $validPhotos = [];
                                foreach ($roomPhotos[$room['id']] as $photo) {
                                    if (!empty($photo['photo_path']) && file_exists($photo['photo_path'])) {
                                        $validPhotos[] = $photo;
                                    }
                                }
                                ?>
                                <?php if (!empty($validPhotos)): ?>
                                <div class="room-photos">
                                    <h4 style="margin: 10px 0 5px 0; font-size: 14px; color: #666;">📸 Photos (<?php echo count($validPhotos); ?>)</h4>
                                    <div class="photo-thumbnails">
                                        <?php foreach (array_slice($validPhotos, 0, 3) as $photo): ?>
                                            <img src="<?php echo $photo['photo_path']; ?>" alt="Room <?php echo htmlspecialchars($room['room_number']); ?>" 
                                                 style="width: 50px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 5px; border: <?php echo $photo['is_primary'] ? '2px solid #28a745' : '1px solid #ddd'; ?>">
                                        <?php endforeach; ?>
                                        <?php if (count($validPhotos) > 3): ?>
                                            <span style="font-size: 12px; color: #666;">+<?php echo count($validPhotos) - 3; ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="room-actions">
                                <button onclick="editRoom(<?php echo $room['id']; ?>)" class="btn btn-primary">✏️ Edit</button>
                                <button onclick="managePhotos(<?php echo $room['id']; ?>)" class="btn btn-secondary">📷 Photos</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this room?')">
                                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" name="delete_room" class="btn btn-danger">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($rooms)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <h3>No rooms added yet</h3>
                        <p>Add your first room using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; // End of view condition ?>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Room Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="editRoomForm">
                <input type="hidden" id="edit_room_id" name="room_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_room_type">Room Type</label>
                        <select id="edit_room_type" name="room_type" required onchange="toggleEditCustomRoomType(this)">
                            <option value="Standard Single">Standard Single</option>
                            <option value="Standard Double">Standard Double</option>
                            <option value="Twin Beds">Twin Beds (2 Single Beds)</option>
                            <option value="Triple Single">Triple Single (3 Single Beds)</option>
                            <option value="Triple Twin">Triple Twin (3 Twin Beds)</option>
                            <option value="Medium Twin">Medium Twin (2 Single Beds)</option>
                            <option value="Deluxe Twin">Deluxe Twin (2 Single Beds)</option>
                            <option value="Deluxe Queen">Deluxe Queen</option>
                            <option value="Executive Suite">Executive Suite</option>
                            <option value="Presidential Suite">Presidential Suite</option>
                            <option value="Family Room">Family Room</option>
                            <option value="Connecting Rooms">Connecting Rooms</option>
                            <option value="custom">🛠️ Custom Room Type</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_custom_room_type_group" style="display: none;">
                        <label for="edit_custom_room_type">Custom Room Type</label>
                        <input type="text" id="edit_custom_room_type" name="custom_room_type" placeholder="Enter custom room type">
                        <small style="color: #6c757d; font-size: 0.9rem;">Enter a custom room type name that will be saved for this room.</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">Price per Night ($)</label>
                        <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_max_occupancy">Base Max Occupancy</label>
                        <input type="number" id="edit_max_occupancy" name="max_occupancy" min="1" max="10" required>
                        <small style="color: #6c757d; font-size: 0.9rem;">Maximum guests without extra bed</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_single_discount_type">Single Occupancy Discount</label>
                        <select id="edit_single_discount_type" name="single_discount_type" onchange="toggleEditSingleDiscount(this)">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount ($)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_single_discount_value">Discount Value</label>
                        <input type="number" id="edit_single_discount_value" name="single_discount_value" step="0.01" min="0" max="100" placeholder="e.g., 15 for 15% or $15">
                        <small style="color: #ff9800; font-size: 0.9rem;" id="edit_single_discount_help">Enter percentage (0-100) for single guest discount</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_extra_bed_available">Extra Bed Available</label>
                        <select id="edit_extra_bed_available" name="extra_bed_available" onchange="toggleEditExtraBedPrice(this)">
                            <option value="0">No Extra Bed</option>
                            <option value="1">Extra Bed Available</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_extra_bed_price_group" style="display: none;">
                        <label for="edit_extra_bed_price">Extra Bed Price per Night ($)</label>
                        <input type="number" id="edit_extra_bed_price" name="extra_bed_price" step="0.01" min="0" placeholder="Additional cost for extra bed">
                        <small style="color: #28a745; font-size: 0.9rem;">Extra bed adds +1 person to room capacity</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <div id="edit_capacity_display" style="padding: 12px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3; margin-bottom: 15px;">
                        <strong>🏠 Total Room Capacity: <span id="edit_total_capacity_text">Loading...</span></strong>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_amenities">Room Amenities</label>
                    <textarea id="edit_amenities" name="amenities" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_room_description">Room Description</label>
                    <textarea id="edit_room_description" name="room_description" rows="4" 
                              placeholder="Describe this room in detail..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_room_features">Special Features</label>
                    <textarea id="edit_room_features" name="room_features" rows="3" 
                              placeholder="List special features (e.g., Ocean view, Balcony, Jacuzzi)"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_room" class="btn btn-success">💾 Save Changes</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Photo Management Modal -->
    <div id="photoModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>📷 Manage Room Photos</h3>
                <span class="close" onclick="closePhotoModal()">&times;</span>
            </div>
            
            <!-- Upload Form -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h4>📤 Upload New Photo</h4>
                <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                    <input type="hidden" id="photo_room_id" name="room_id" value="">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">📸 Select Photo</label>
                            <input type="file" name="room_photo" accept="image/*" required
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; gap: 5px; font-size: 14px;">
                                <input type="checkbox" name="is_primary"> ⭐ Primary Photo
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="upload_photo" style="margin-top: 10px; padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        📤 Upload Photo
                    </button>
                </form>
            </div>
            
            <!-- Photos Grid -->
            <div id="photoContent">
                <h4>🖼️ Current Photos</h4>
                <div id="roomPhotosGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                    <!-- Photos will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Room editing functionality
        const rooms = <?php echo json_encode($rooms); ?>;
        
        function editRoom(roomId) {
            const room = rooms.find(r => r.id == roomId);
            if (!room) return;
            
            document.getElementById('edit_room_id').value = room.id;
            
            // Check if room type exists in predefined options
            const roomTypeSelect = document.getElementById('edit_room_type');
            const roomTypeExists = Array.from(roomTypeSelect.options).some(option => option.value === room.room_type);
            
            if (roomTypeExists && room.room_type !== 'custom') {
                roomTypeSelect.value = room.room_type;
                document.getElementById('edit_custom_room_type_group').style.display = 'none';
            } else {
                // Use custom option for non-predefined room types
                roomTypeSelect.value = 'custom';
                document.getElementById('edit_custom_room_type_group').style.display = 'block';
                document.getElementById('edit_custom_room_type').value = room.room_type;
            }
            
            document.getElementById('edit_price').value = room.price;
            document.getElementById('edit_max_occupancy').value = room.max_occupancy;
            document.getElementById('edit_amenities').value = room.amenities || '';
            document.getElementById('edit_room_description').value = room.description || '';
            document.getElementById('edit_room_features').value = room.features || '';
            
            // Handle extra bed fields
            const extraBedAvailable = room.extra_bed_available || '0';
            document.getElementById('edit_extra_bed_available').value = extraBedAvailable;
            
            if (extraBedAvailable === '1' || extraBedAvailable === 1) {
                document.getElementById('edit_extra_bed_price_group').style.display = 'block';
                document.getElementById('edit_extra_bed_price').value = room.extra_bed_price || '';
                document.getElementById('edit_extra_bed_price').required = true;
            } else {
                document.getElementById('edit_extra_bed_price_group').style.display = 'none';
                document.getElementById('edit_extra_bed_price').required = false;
            }
            
            // Handle single discount fields
            const discountType = room.single_discount_type || 'percentage';
            const discountValue = room.single_discount_value || 0;
            document.getElementById('edit_single_discount_type').value = discountType;
            document.getElementById('edit_single_discount_value').value = discountValue;
            
            // Update help text based on discount type
            toggleEditSingleDiscount(document.getElementById('edit_single_discount_type'));
            
            // Update capacity display
            updateEditCapacityDisplay();
            
            document.getElementById('editRoomModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editRoomModal').style.display = 'none';
        }
        
        function managePhotos(roomId) {
            // Set the room ID for photo upload
            document.getElementById('photo_room_id').value = roomId;
            
            // Load photos for this room
            loadRoomPhotos(roomId);
            
            // Show the modal
            document.getElementById('photoModal').style.display = 'block';
        }
        
        function loadRoomPhotos(roomId) {
            const roomPhotos = <?php echo json_encode($roomPhotos); ?>;
            const photos = roomPhotos[roomId] || [];
            const grid = document.getElementById('roomPhotosGrid');
            
            if (photos.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #666; padding: 20px;">No photos uploaded yet.</p>';
                return;
            }
            
            grid.innerHTML = photos.map(photo => `
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <img src="${photo.photo_path}" alt="Room photo" 
                         style="width: 100%; height: 100px; object-fit: cover;">
                    <div style="padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 12px; color: #666;">
                                ${new Date(photo.upload_date).toLocaleDateString()}
                            </span>
                            ${photo.is_primary ? '<span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;">⭐ PRIMARY</span>' : ''}
                        </div>
                        <div style="display: flex; gap: 5px;">
                            ${!photo.is_primary ? `<button onclick="setPrimary(${photo.id})" style="padding: 4px 8px; font-size: 11px; background: #ffc107; color: black; border: none; border-radius: 3px; cursor: pointer;">⭐ Make Primary</button>` : ''}
                            <button onclick="deletePhoto(${photo.id})" style="padding: 4px 8px; font-size: 11px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">🗑️ Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function setPrimary(photoId) {
            if (confirm('Set this as the primary photo?')) {
                window.location.href = `?set_primary=${photoId}`;
            }
        }
        
        function deletePhoto(photoId) {
            if (confirm('Delete this photo? This action cannot be undone.')) {
                window.location.href = `?delete_photo=${photoId}`;
            }
        }
        
        function closePhotoModal() {
            document.getElementById('photoModal').style.display = 'none';
        }
        
        // Custom room type functionality
        function toggleCustomRoomType(selectElement) {
            const customGroup = document.getElementById('custom_room_type_group');
            const customInput = document.getElementById('custom_room_type');
            
            if (selectElement.value === 'custom') {
                customGroup.style.display = 'block';
                customInput.required = true;
            } else {
                customGroup.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
        
        function toggleEditCustomRoomType(selectElement) {
            const customGroup = document.getElementById('edit_custom_room_type_group');
            const customInput = document.getElementById('edit_custom_room_type');
            
            if (selectElement.value === 'custom') {
                customGroup.style.display = 'block';
                customInput.required = true;
            } else {
                customGroup.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }

        // Extra bed functionality
        function toggleExtraBedPrice(selectElement) {
            const priceGroup = document.getElementById('extra_bed_price_group');
            const priceInput = document.getElementById('extra_bed_price');
            
            if (selectElement.value === '1') {
                priceGroup.style.display = 'block';
                priceInput.required = true;
            } else {
                priceGroup.style.display = 'none';
                priceInput.required = false;
                priceInput.value = '';
            }
            updateCapacityDisplay();
        }
        
        // Single discount functionality
        function toggleSingleDiscount(selectElement) {
            const helpText = document.getElementById('single_discount_help');
            const valueInput = document.getElementById('single_discount_value');
            
            if (selectElement.value === 'percentage') {
                helpText.textContent = 'Enter percentage (0-100) for single guest discount';
                valueInput.placeholder = 'e.g., 15 for 15% off';
                valueInput.max = '100';
            } else {
                helpText.textContent = 'Enter fixed dollar amount for single guest discount';
                valueInput.placeholder = 'e.g., 15 for $15 off';
                valueInput.max = '9999';
            }
        }
        
        function toggleEditSingleDiscount(selectElement) {
            const helpText = document.getElementById('edit_single_discount_help');
            const valueInput = document.getElementById('edit_single_discount_value');
            
            if (selectElement.value === 'percentage') {
                helpText.textContent = 'Enter percentage (0-100) for single guest discount';
                valueInput.placeholder = 'e.g., 15 for 15% off';
                valueInput.max = '100';
            } else {
                helpText.textContent = 'Enter fixed dollar amount for single guest discount';
                valueInput.placeholder = 'e.g., 15 for $15 off';
                valueInput.max = '9999';
            }
        }

        function toggleEditExtraBedPrice(selectElement) {
            const priceGroup = document.getElementById('edit_extra_bed_price_group');
            const priceInput = document.getElementById('edit_extra_bed_price');
            
            if (selectElement.value === '1') {
                priceGroup.style.display = 'block';
                priceInput.required = true;
            } else {
                priceGroup.style.display = 'none';
                priceInput.required = false;
                priceInput.value = '';
            }
            updateEditCapacityDisplay();
        }

        // Capacity display functions
        function updateCapacityDisplay() {
            const baseOccupancy = parseInt(document.getElementById('max_occupancy').value) || 0;
            const hasExtraBed = document.getElementById('extra_bed_available').value === '1';
            const capacityText = document.getElementById('total_capacity_text');
            
            if (baseOccupancy === 0) {
                capacityText.textContent = 'Enter base occupancy';
                return;
            }
            
            if (hasExtraBed) {
                const totalCapacity = baseOccupancy + 1;
                capacityText.textContent = `${baseOccupancy} people (${totalCapacity} with extra bed)`;
            } else {
                capacityText.textContent = `${baseOccupancy} people`;
            }
        }
        
        function updateEditCapacityDisplay() {
            const baseOccupancy = parseInt(document.getElementById('edit_max_occupancy').value) || 0;
            const hasExtraBed = document.getElementById('edit_extra_bed_available').value === '1';
            const capacityText = document.getElementById('edit_total_capacity_text');
            
            if (baseOccupancy === 0) {
                capacityText.textContent = 'Enter base occupancy';
                return;
            }
            
            if (hasExtraBed) {
                const totalCapacity = baseOccupancy + 1;
                capacityText.textContent = `${baseOccupancy} people (${totalCapacity} with extra bed)`;
            } else {
                capacityText.textContent = `${baseOccupancy} people`;
            }
        }

        // Add event listeners for real-time updates
        document.addEventListener('DOMContentLoaded', function() {
            const maxOccupancyInput = document.getElementById('max_occupancy');
            const editMaxOccupancyInput = document.getElementById('edit_max_occupancy');
            
            if (maxOccupancyInput) {
                maxOccupancyInput.addEventListener('input', updateCapacityDisplay);
            }
            
            if (editMaxOccupancyInput) {
                editMaxOccupancyInput.addEventListener('input', updateEditCapacityDisplay);
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editRoomModal');
            const photoModal = document.getElementById('photoModal');
            
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == photoModal) {
                photoModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>