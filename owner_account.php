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
    $stmt = $conn->prepare("SELECT name FROM hotel_info WHERE id = ?");
    $stmt->execute([$_SESSION['current_hotel_id']]);
    $currentHotel = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentHotelName = $currentHotel['name'] ?? '';
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
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .property-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid #667eea;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .property-card.current {
            border-left-color: #28a745;
            background: #f8fff9;
        }
        .property-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .property-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .property-type {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 1rem;
        }
        .current-badge {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .property-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-box {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-manage {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-manage:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-switch {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-switch:hover {
            background: #667eea;
            color: white;
        }
        .add-property-btn {
            background: white;
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            color: #667eea;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .add-property-btn:hover {
            background: #f8f9ff;
            border-color: #764ba2;
            color: #764ba2;
        }
        .user-info-box {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-hotel"></i> My Properties</h1>
                    <p class="mb-0">Manage all your hospitality properties in one place</p>
                </div>
                <div>
                    <a href="logout.php" class="btn btn-light">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- User Info -->
        <div class="user-info-box">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="mb-0 text-muted">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                        <?php if ($currentHotelName): ?>
                            <span class="ms-3">
                                <i class="fas fa-building"></i> Currently managing: <strong><?php echo htmlspecialchars($currentHotelName); ?></strong>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-muted">
                        <div><strong><?php echo count($properties); ?></strong> Properties</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties List -->
        <?php foreach ($properties as $property): ?>
            <div class="property-card <?php echo ($property['id'] == ($_SESSION['current_hotel_id'] ?? 0)) ? 'current' : ''; ?>">
                <div class="property-header">
                    <div>
                        <h3 class="property-name">
                            <?php echo htmlspecialchars($property['name'] ?: 'Unnamed Property'); ?>
                            <span class="property-type"><?php echo htmlspecialchars($property['property_type'] ?: 'Hotel'); ?></span>
                        </h3>
                        <?php if ($property['address']): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['address']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($property['id'] == ($_SESSION['current_hotel_id'] ?? 0)): ?>
                        <span class="current-badge">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                    <?php endif; ?>
                </div>

                <div class="property-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $property['total_rooms']; ?></div>
                        <div class="stat-label">Rooms</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $property['active_bookings']; ?></div>
                        <div class="stat-label">Active Bookings</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $property['total_employees']; ?></div>
                        <div class="stat-label">Employees</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-label"><?php echo htmlspecialchars($property['timezone'] ?: 'UTC'); ?></div>
                    </div>
                </div>

                <div class="action-buttons">
                    <?php if ($property['id'] == ($_SESSION['current_hotel_id'] ?? 0)): ?>
                        <a href="hotel_setup.php" class="btn btn-manage">
                            <i class="fas fa-cog"></i> Manage Property
                        </a>
                        <a href="dashboard.php" class="btn btn-switch">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="?switch=<?php echo $property['id']; ?>" class="btn btn-switch">
                            <i class="fas fa-exchange-alt"></i> Switch to This Property
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Add New Property -->
        <a href="owner_registration.php" class="add-property-btn d-block text-decoration-none">
            <i class="fas fa-plus-circle fa-3x mb-3"></i>
            <div>Add New Property</div>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
