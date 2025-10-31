<?php
session_start();
require_once 'classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header('Location: owner_login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Get all properties owned by this user
$stmt = $conn->prepare("
    SELECT h.*, 
           (SELECT COUNT(*) FROM bookings WHERE hotel_id = h.id AND status = 'confirmed') as active_bookings,
           (SELECT COUNT(*) FROM rooms WHERE hotel_id = h.id) as total_rooms,
           (SELECT COUNT(*) FROM employees WHERE hotel_id = h.id) as total_employees
    FROM hotel_info h
    JOIN user_hotel_access uha ON h.id = uha.hotel_id
    WHERE uha.user_id = ?
    ORDER BY h.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle hotel switching
if (isset($_GET['switch']) && is_numeric($_GET['switch'])) {
    $hotelId = $_GET['switch'];
    
    // Verify user has access to this hotel
    $stmt = $conn->prepare("SELECT id FROM user_hotel_access WHERE user_id = ? AND hotel_id = ?");
    $stmt->execute([$_SESSION['user_id'], $hotelId]);
    
    if ($stmt->fetch()) {
        $_SESSION['current_hotel_id'] = $hotelId;
        header('Location: hotel_setup.php');
        exit;
    }
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current hotel name
$currentHotelName = '';
if (isset($_SESSION['current_hotel_id'])) {
    $stmt = $conn->prepare("SELECT hotel_name FROM hotel_info WHERE id = ?");
    $stmt->execute([$_SESSION['current_hotel_id']]);
    $currentHotel = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentHotelName = $currentHotel['hotel_name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - AiNi Travel</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-bottom: 3rem;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.5rem 0;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .property-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
        }
        
        .property-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .property-card.active::before {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .property-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .property-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            padding: 2rem;
            position: relative;
        }
        
        .property-card.active .property-header {
            background: linear-gradient(135deg, rgba(67, 233, 123, 0.05) 0%, rgba(56, 249, 215, 0.05) 100%);
        }
        
        .active-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(67, 233, 123, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .property-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .property-type-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .property-address {
            color: #718096;
            font-size: 0.95rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .property-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding: 2rem;
        }
        
        .mini-stat {
            text-align: center;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 12px;
        }
        
        .mini-stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .mini-stat-label {
            color: #718096;
            font-size: 0.85rem;
            margin-top: 0.3rem;
            font-weight: 500;
        }
        
        .property-actions {
            padding: 0 2rem 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .btn-primary-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .btn-outline-gradient {
            background: white;
            border: 2px solid;
            border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-image-slice: 1;
            color: #667eea;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s;
        }
        
        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        .add-property-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            border: 3px dashed rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
            cursor: pointer;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .add-property-card:hover {
            border-color: #667eea;
            background: rgba(255, 255, 255, 1);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .add-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            transition: transform 0.3s;
        }
        
        .add-property-card:hover .add-icon {
            transform: rotate(90deg);
        }
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
            border-color: white;
        }
        
        .section-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 3rem 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, rgba(255, 255, 255, 0.3), transparent);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-hotel"></i>
                AiNi Travel
            </span>
            <a href="logout.php" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- User Info Card -->
        <div class="user-info-card">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-1" style="color: #2d3748; font-weight: 700;">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <?php if ($currentHotelName): ?>
                        <p class="mb-0 mt-2" style="color: #667eea; font-weight: 600;">
                            <i class="fas fa-building me-2"></i>Currently managing: <?php echo htmlspecialchars($currentHotelName); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-building"></i>
                </div>
                <h3 style="font-size: 2rem; font-weight: 700; color: #2d3748;"><?php echo count($properties); ?></h3>
                <p style="color: #718096; margin: 0;">Total Properties</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-door-open"></i>
                </div>
                <h3 style="font-size: 2rem; font-weight: 700; color: #2d3748;">
                    <?php echo array_sum(array_column($properties, 'total_rooms')); ?>
                </h3>
                <p style="color: #718096; margin: 0;">Total Rooms</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 style="font-size: 2rem; font-weight: 700; color: #2d3748;">
                    <?php echo array_sum(array_column($properties, 'active_bookings')); ?>
                </h3>
                <p style="color: #718096; margin: 0;">Active Bookings</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-users"></i>
                </div>
                <h3 style="font-size: 2rem; font-weight: 700; color: #2d3748;">
                    <?php echo array_sum(array_column($properties, 'total_employees')); ?>
                </h3>
                <p style="color: #718096; margin: 0;">Total Employees</p>
            </div>
        </div>

        <!-- Section Title -->
        <h2 class="section-title">
            <i class="fas fa-th-large"></i>
            Your Properties
        </h2>

        <!-- Properties Grid -->
        <div class="property-grid">
            <?php foreach ($properties as $property): ?>
                <div class="property-card <?php echo ($property['id'] == ($_SESSION['current_hotel_id'] ?? 0)) ? 'active' : ''; ?>">
                    <?php if ($property['id'] == ($_SESSION['current_hotel_id'] ?? 0)): ?>
                        <div class="active-badge">
                            <i class="fas fa-check-circle"></i> Active
                        </div>
                    <?php endif; ?>
                    
                    <div class="property-header">
                        <h3 class="property-title">
                            <?php echo htmlspecialchars($property['hotel_name'] ?: 'Unnamed Property'); ?>
                        </h3>
                        <span class="property-type-badge">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($property['property_type'] ?: 'Hotel'); ?>
                        </span>
                        <?php 
                        $address = trim(($property['address_line1'] ?? '') . ' ' . ($property['city'] ?? '') . ', ' . ($property['state'] ?? ''));
                        if ($address && $address !== ', '): 
                        ?>
                            <p class="property-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($address); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="property-stats">
                        <div class="mini-stat">
                            <div class="mini-stat-number"><?php echo $property['total_rooms']; ?></div>
                            <div class="mini-stat-label"><i class="fas fa-bed"></i> Rooms</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-number"><?php echo $property['active_bookings']; ?></div>
                            <div class="mini-stat-label"><i class="fas fa-calendar"></i> Bookings</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-number"><?php echo $property['total_employees']; ?></div>
                            <div class="mini-stat-label"><i class="fas fa-user-tie"></i> Staff</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-number"><i class="fas fa-clock"></i></div>
                            <div class="mini-stat-label"><?php echo htmlspecialchars($property['timezone'] ?: 'UTC'); ?></div>
                        </div>
                    </div>

                    <div class="property-actions">
                        <?php if ($property['id'] == ($_SESSION['current_hotel_id'] ?? 0)): ?>
                            <a href="hotel_setup.php" class="btn btn-primary-gradient">
                                <i class="fas fa-cog"></i> Manage
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-gradient">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a href="?switch=<?php echo $property['id']; ?>" class="btn btn-primary-gradient" style="flex: 2;">
                                <i class="fas fa-exchange-alt"></i> Switch to This Property
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Add New Property Card -->
            <a href="owner_registration.php" class="add-property-card text-decoration-none">
                <div class="add-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3 style="color: #667eea; font-weight: 700; margin-bottom: 0.5rem;">Add New Property</h3>
                <p style="color: #718096; margin: 0;">Expand your hospitality portfolio</p>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
