<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();
$services = $hotelInfo->getServices();
$amenities = $hotelInfo->getAmenities();

// Get email configuration
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->query("SELECT * FROM email_config ORDER BY id DESC LIMIT 1");
$emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_hotel_info'])) {
        // Direct database update with correct column names
        $currentHotelId = $_SESSION['current_hotel_id'] ?? 1;
        
        try {
            $stmt = $conn->prepare("
                UPDATE hotel_info SET 
                hotel_name = ?, property_type = ?, hotel_rating = ?, phone = ?, 
                email = ?, website = ?, hotel_description = ?, address_line1 = ?, 
                address_line2 = ?, city = ?, state = ?, zip_code = ?, country = ?, 
                timezone = ?, check_in_time = ?, check_out_time = ?, total_rooms = ?,
                updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $_POST['hotel_name'] ?? '',
                $_POST['property_type'] ?? 'hotel',
                $_POST['hotel_rating'] ?? '3.0',
                $_POST['phone'] ?? '',
                $_POST['email'] ?? '',
                $_POST['website'] ?? '',
                $_POST['hotel_description'] ?? '',
                $_POST['address_line1'] ?? '',
                $_POST['address_line2'] ?? '',
                $_POST['city'] ?? '',
                $_POST['state'] ?? '',
                $_POST['zip_code'] ?? '',
                $_POST['country'] ?? '',
                $_POST['timezone'] ?? 'America/Lima',
                $_POST['check_in_time'] ?? '15:00:00',
                $_POST['check_out_time'] ?? '11:00:00',
                $_POST['total_rooms'] ?? 0,
                $currentHotelId
            ]);
            
            if ($result) {
                $message = "Hotel information updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Failed to update hotel information";
                $messageType = 'error';
            }
            
            // Refresh hotel info
            $hotel = $hotelInfo->getHotelInfo();
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['toggle_service'])) {
        $serviceId = $_POST['service_id'];
        $status = $_POST['status'] === '1' ? 0 : 1;
        if ($hotelInfo->updateServiceStatus($serviceId, $status)) {
            $message = "Service updated successfully!";
            $messageType = 'success';
        }
        // Refresh services
        $services = $hotelInfo->getServices();
    }
    
    if (isset($_POST['toggle_amenity'])) {
        $amenityId = $_POST['amenity_id'];
        $status = $_POST['status'] === '1' ? 0 : 1;
        if ($hotelInfo->updateAmenityStatus($amenityId, $status)) {
            $message = "Amenity updated successfully!";
            $messageType = 'success';
        }
        // Refresh amenities
        $amenities = $hotelInfo->getAmenities();
    }
    
    if (isset($_POST['add_service'])) {
        if ($hotelInfo->addService($_POST['service_name'], $_POST['service_description'], $_POST['service_icon'])) {
            $message = "Service added successfully!";
            $messageType = 'success';
            $services = $hotelInfo->getServices();
        }
    }
    
    if (isset($_POST['add_amenity'])) {
        if ($hotelInfo->addAmenity($_POST['amenity_name'], $_POST['amenity_description'], $_POST['amenity_icon'])) {
            $message = "Amenity added successfully!";
            $messageType = 'success';
            $amenities = $hotelInfo->getAmenities();
        }
    }
    
    if (isset($_POST['update_email_config'])) {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if ($emailConfig) {
                // Update existing config
                $stmt = $conn->prepare("UPDATE email_config SET 
                    smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?,
                    from_email = ?, from_name = ?, reply_to = ?, is_enabled = ?,
                    use_ssl = ?, use_tls = ?, updated_by = ?, updated_at = NOW()
                    WHERE id = ?");
                
                $stmt->execute([
                    $_POST['smtp_host'],
                    $_POST['smtp_port'],
                    $_POST['smtp_username'],
                    $_POST['smtp_password'],
                    $_POST['from_email'],
                    $_POST['from_name'],
                    $_POST['reply_to'],
                    isset($_POST['is_enabled']) ? 1 : 0,
                    isset($_POST['use_ssl']) ? 1 : 0,
                    isset($_POST['use_tls']) ? 1 : 0,
                    $userId,
                    $emailConfig['id']
                ]);
            } else {
                // Insert new config
                $stmt = $conn->prepare("INSERT INTO email_config 
                    (smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name, 
                     reply_to, is_enabled, use_ssl, use_tls, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                
                $stmt->execute([
                    $_POST['smtp_host'],
                    $_POST['smtp_port'],
                    $_POST['smtp_username'],
                    $_POST['smtp_password'],
                    $_POST['from_email'],
                    $_POST['from_name'],
                    $_POST['reply_to'],
                    isset($_POST['is_enabled']) ? 1 : 0,
                    isset($_POST['use_ssl']) ? 1 : 0,
                    isset($_POST['use_tls']) ? 1 : 0,
                    $userId
                ]);
            }
            
            $message = "Email configuration updated successfully!";
            $messageType = 'success';
            
            // Refresh email config
            $stmt = $conn->query("SELECT * FROM email_config ORDER BY id DESC LIMIT 1");
            $emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $message = "Error updating email configuration: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Setup'); ?> - Configuration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            color: #e2e8f0;
        }
        
        /* Sidebar - Matching dashboard.php */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: #1e293b;
            padding: 2rem 0;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.1);
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 1.5rem;
            margin-bottom: 3rem;
        }
        
        .logo h2 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo h2 i {
            color: #6366f1;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
            padding: 0 1rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        
        .top-bar {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-box {
            position: relative;
            width: 400px;
            margin: 0 auto;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #e2e8f0;
            outline: none;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: #6366f1;
            background: rgba(15, 23, 42, 0.8);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 200px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .user-menu.active .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .user-dropdown-item:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        .user-dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .user-dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 0.5rem 0;
        }
        
        /* Currency Toggle Button */
        .currency-toggle {
            min-width: 75px;
            height: 40px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 0.5rem;
            padding: 0 1rem;
        }
        
        .currency-toggle:hover {
            background: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.5);
            transform: scale(1.05);
        }
        
        .currency-toggle span {
            color: #10b981;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        /* Theme Toggle Button */
        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 1rem;
        }
        
        .theme-toggle:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.5);
            transform: scale(1.05);
        }
        
        .theme-toggle i {
            color: #6366f1;
            font-size: 1.1rem;
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 1rem;
        }
        
        .sidebar-toggle:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.5);
            transform: scale(1.05);
        }
        
        .sidebar-toggle i {
            color: #6366f1;
            font-size: 1.1rem;
        }
        
        /* Light Theme Styles */
        body.light-theme {
            background: #f1f5f9;
            color: #0f172a;
        }
        
        body.light-theme .sidebar {
            background: #ffffff;
            border-right-color: #e2e8f0;
        }
        
        body.light-theme .logo h2,
        body.light-theme .nav-link {
            color: #0f172a;
        }
        
        body.light-theme .nav-link:hover,
        body.light-theme .nav-link.active {
            background: #f1f5f9;
            color: #6366f1;
        }
        
        body.light-theme .main-content {
            background: #f8fafc;
        }
        
        body.light-theme .top-bar {
            background: rgba(255, 255, 255, 0.9);
            border-bottom-color: #e2e8f0;
        }
        
        body.light-theme .page-title {
            color: #0f172a;
        }
        
        body.light-theme .card {
            background: #ffffff;
            border-color: #e2e8f0;
        }
        
        body.light-theme .card:hover {
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        body.light-theme .card-header h3 {
            color: #0f172a;
        }
        
        body.light-theme .user-dropdown {
            background: #ffffff;
            border-color: #e2e8f0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        body.light-theme .user-dropdown-item {
            color: #0f172a;
        }
        
        body.light-theme .user-dropdown-item:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        body.light-theme .form-group label {
            color: #475569;
        }
        
        body.light-theme .form-group input,
        body.light-theme .form-group select,
        body.light-theme .form-group textarea {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #0f172a;
        }
        
        body.light-theme .service-item,
        body.light-theme .amenity-item {
            background: #f8fafc;
            border-color: #e2e8f0;
        }
        
        /* Content Container */
        .container {
            padding: 2rem;
            max-width: 1400px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        /* Card Styles */
        .card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.1);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .card-header i {
            font-size: 1.25rem;
            color: #6366f1;
        }
        
        .card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: #cbd5e1;
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            font-size: 0.875rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Grid Layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(99, 102, 241, 0.2);
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #475569;
            transition: 0.3s;
            border-radius: 28px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        /* Service/Amenity Lists */
        .service-list, .amenity-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .service-item, .amenity-item {
            background: rgba(15, 23, 42, 0.5);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s;
        }
        
        .service-item:hover, .amenity-item:hover {
            border-color: rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }
        
        .service-info, .amenity-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            min-width: 0;
        }
        
        .service-info div, .amenity-info div {
            flex: 1;
            min-width: 0;
        }
        
        .service-info strong, .amenity-info strong {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.875rem;
        }
        
        .service-icon, .amenity-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        /* Checkbox Style */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .logo h2 span {
                display: none;
            }
            
            .nav-link span {
                display: none;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h2><i class="fas fa-hotel"></i> <span>AiNi PMS</span></h2>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="hotel_setup.php" class="nav-link active">
                    <i class="fas fa-cog"></i>
                    <span>Hotel Setup</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="room_management.php" class="nav-link">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="booking_management.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="analytics.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="employee_management.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="accounting_dashboard.php" class="nav-link">
                    <i class="fas fa-calculator"></i>
                    <span>Accounting</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="top-bar">
            <h1 class="page-title">
                <div class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars" id="sidebar-icon"></i>
                </div>
                <i class="fas fa-cog"></i> Hotel Configuration
            </h1>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search settings..." id="searchInput">
            </div>
            
            <div class="user-info">
                <div class="currency-toggle" onclick="toggleCurrency()">
                    <span id="currency-display">USD</span>
                </div>
                
                <div class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </div>
                
                <div class="user-menu" id="userMenu">
                    <div class="user-avatar" onclick="toggleUserMenu()">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="user-dropdown">
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 0.5rem;">
                            <div style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;"><?php echo ucfirst($_SESSION['user_role'] ?? 'Manager'); ?></div>
                        </div>
                        <a href="profile.php" class="user-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="hotel_setup.php" class="user-dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <div class="user-dropdown-divider"></div>
                        <a href="logout.php" class="user-dropdown-item" style="color: #ef4444;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Hotel Information Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-building"></i>
                    <h3>Hotel Information</h3>
                </div>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hotel"></i> Hotel Name</label>
                            <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($hotel['hotel_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-list"></i> Property Type</label>
                            <select name="property_type" required>
                                <option value="">Select Type</option>
                                <?php
                                $propertyTypes = ['hotel', 'hostel', 'resort', 'guest_house', 'apartment', 'villa', 'boutique_hotel', 'motel', 'lodge', 'bed_and_breakfast', 'cottage', 'campground', 'retreat_center', 'other'];
                                foreach ($propertyTypes as $type) {
                                    $selected = (($hotel['property_type'] ?? '') === $type) ? 'selected' : '';
                                    $displayName = ucwords(str_replace('_', ' ', $type));
                                    echo "<option value=\"$type\" $selected>$displayName</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-star"></i> Star Rating</label>
                            <select name="hotel_rating" required>
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    $selected = (($hotel['hotel_rating'] ?? 0) == $i) ? 'selected' : '';
                                    echo "<option value=\"$i.0\" $selected>$i Star" . ($i > 1 ? 's' : '') . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($hotel['phone'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($hotel['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> Website</label>
                            <input type="url" name="website" value="<?php echo htmlspecialchars($hotel['website'] ?? ''); ?>" placeholder="https://">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Hotel Description</label>
                        <textarea name="hotel_description" rows="3"><?php echo htmlspecialchars($hotel['hotel_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address Line 1</label>
                        <input type="text" name="address_line1" value="<?php echo htmlspecialchars($hotel['address_line1'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address Line 2</label>
                        <input type="text" name="address_line2" value="<?php echo htmlspecialchars($hotel['address_line2'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-city"></i> City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($hotel['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> State/Province</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($hotel['state'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-flag"></i> Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($hotel['country'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-mail-bulk"></i> Postal Code</label>
                            <input type="text" name="zip_code" value="<?php echo htmlspecialchars($hotel['zip_code'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Timezone</label>
                            <select name="timezone" required>
                                <?php
                                $timezones = ['America/Lima', 'America/New_York', 'America/Los_Angeles', 'America/Chicago', 'America/Denver', 'America/Mexico_City', 'America/Bogota', 'America/Caracas', 'America/Sao_Paulo', 'America/Buenos_Aires', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Australia/Sydney'];
                                foreach ($timezones as $tz) {
                                    $selected = (($hotel['timezone'] ?? 'America/Lima') === $tz) ? 'selected' : '';
                                    echo "<option value=\"$tz\" $selected>$tz</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-sign-in-alt"></i> Check-in Time</label>
                            <input type="time" name="check_in_time" value="<?php echo htmlspecialchars($hotel['check_in_time'] ?? '15:00'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-sign-out-alt"></i> Check-out Time</label>
                            <input type="time" name="check_out_time" value="<?php echo htmlspecialchars($hotel['check_out_time'] ?? '11:00'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-door-open"></i> Total Rooms</label>
                            <input type="number" name="total_rooms" value="<?php echo htmlspecialchars($hotel['total_rooms'] ?? '0'); ?>" min="0" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_hotel_info" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Hotel Information
                    </button>
                </form>
            </div>
            
            <!-- Services Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-concierge-bell"></i>
                    <h3>Hotel Services</h3>
                </div>
                
                <div class="service-list">
                    <?php if (empty($services)): ?>
                        <p style="color: #64748b; text-align: center; padding: 2rem;">No services added yet</p>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <div class="service-info">
                                    <span style="font-size: 1.5rem; margin-right: 0.75rem;"><?php echo htmlspecialchars($service['service_icon'] ?? '🏨'); ?></span>
                                    <div>
                                        <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                        <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 0.25rem;">
                                            <?php echo htmlspecialchars($service['service_description'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $service['is_active']; ?>">
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $service['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        <span class="slider"></span>
                                    </label>
                                    <input type="hidden" name="toggle_service">
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 1.5rem 0;">
                
                <h4 style="margin-bottom: 0.75rem; color: #cbd5e1; font-size: 0.95rem;"><i class="fas fa-plus-circle"></i> Add New Service</h4>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Service Name</label>
                            <input type="text" name="service_name" required>
                        </div>
                        <div class="form-group">
                            <label>Icon (emoji or FA class)</label>
                            <input type="text" name="service_icon" placeholder="🏨 or fas fa-wifi" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="service_description" required>
                        </div>
                    </div>
                    <button type="submit" name="add_service" class="btn btn-success" style="margin-top: 0.5rem;">
                        <i class="fas fa-plus"></i> Add Service
                    </button>
                </form>
            </div>
            
            <!-- Amenities Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-swimming-pool"></i>
                    <h3>Hotel Amenities</h3>
                </div>
                
                <div class="amenity-list">
                    <?php if (empty($amenities)): ?>
                        <p style="color: #64748b; text-align: center; padding: 2rem;">No amenities added yet</p>
                    <?php else: ?>
                        <?php foreach ($amenities as $amenity): ?>
                            <div class="amenity-item">
                                <div class="amenity-info">
                                    <span style="font-size: 1.5rem; margin-right: 0.75rem;"><?php echo htmlspecialchars($amenity['amenity_icon'] ?? '⭐'); ?></span>
                                    <div>
                                        <strong><?php echo htmlspecialchars($amenity['amenity_name']); ?></strong>
                                        <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 0.25rem;">
                                            <?php echo htmlspecialchars($amenity['amenity_description'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="amenity_id" value="<?php echo $amenity['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $amenity['is_active']; ?>">
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $amenity['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        <span class="slider"></span>
                                    </label>
                                    <input type="hidden" name="toggle_amenity">
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 1.5rem 0;">
                
                <h4 style="margin-bottom: 0.75rem; color: #cbd5e1; font-size: 0.95rem;"><i class="fas fa-plus-circle"></i> Add New Amenity</h4>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Amenity Name</label>
                            <input type="text" name="amenity_name" required>
                        </div>
                        <div class="form-group">
                            <label>Icon (emoji or FA class)</label>
                            <input type="text" name="amenity_icon" placeholder="🏊 or fas fa-wifi" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="amenity_description" required>
                        </div>
                    </div>
                    <button type="submit" name="add_amenity" class="btn btn-success" style="margin-top: 0.5rem;">
                        <i class="fas fa-plus"></i> Add Amenity
                    </button>
                </form>
            </div>
            
            <!-- Email Configuration Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-envelope-open-text"></i>
                    <h3>Email Configuration</h3>
                </div>
                
                <form method="POST">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label><i class="fas fa-server"></i> SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($emailConfig['smtp_host'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-network-wired"></i> SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($emailConfig['smtp_port'] ?? '587'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> SMTP Username</label>
                            <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($emailConfig['smtp_username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> SMTP Password</label>
                            <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($emailConfig['smtp_password'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> From Email</label>
                            <input type="email" name="from_email" value="<?php echo htmlspecialchars($emailConfig['from_email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> From Name</label>
                            <input type="text" name="from_name" value="<?php echo htmlspecialchars($emailConfig['from_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-reply"></i> Reply-To Email</label>
                            <input type="email" name="reply_to" value="<?php echo htmlspecialchars($emailConfig['reply_to'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="is_enabled" id="is_enabled" <?php echo ($emailConfig['is_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="is_enabled">Enable Email Notifications</label>
                    </div>
                    
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="use_ssl" id="use_ssl" <?php echo ($emailConfig['use_ssl'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="use_ssl">Use SSL</label>
                    </div>
                    
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="use_tls" id="use_tls" <?php echo ($emailConfig['use_tls'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="use_tls">Use TLS</label>
                    </div>
                    
                    <button type="submit" name="update_email_config" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Email Configuration
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarIcon = document.getElementById('sidebar-icon');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Update icon
            if (sidebar.classList.contains('collapsed')) {
                sidebarIcon.classList.remove('fa-bars');
                sidebarIcon.classList.add('fa-times');
                localStorage.setItem('sidebar', 'collapsed');
            } else {
                sidebarIcon.classList.remove('fa-times');
                sidebarIcon.classList.add('fa-bars');
                localStorage.setItem('sidebar', 'expanded');
            }
        }
        
        // User Menu Toggle
        function toggleUserMenu() {
            const userMenu = document.getElementById('userMenu');
            userMenu.classList.toggle('active');
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            if (userMenu && !userMenu.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });
        
        // Theme Toggle Functionality
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            body.classList.toggle('light-theme');
            
            // Update icon
            if (body.classList.contains('light-theme')) {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Currency Toggle Functionality
        let currentCurrency = 'USD';
        function toggleCurrency() {
            const currencyDisplay = document.getElementById('currency-display');
            
            if (currentCurrency === 'USD') {
                currentCurrency = 'PEN';
                currencyDisplay.textContent = 'PEN';
                localStorage.setItem('currency', 'PEN');
            } else {
                currentCurrency = 'USD';
                currencyDisplay.textContent = 'USD';
                localStorage.setItem('currency', 'USD');
            }
        }
        
        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Load saved preferences on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Restore theme
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
            
            // Restore sidebar state
            const savedSidebar = localStorage.getItem('sidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarIcon = document.getElementById('sidebar-icon');
            
            if (savedSidebar === 'collapsed') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                sidebarIcon.classList.remove('fa-bars');
                sidebarIcon.classList.add('fa-times');
            }
            
            // Restore currency preference
            const savedCurrency = localStorage.getItem('currency');
            if (savedCurrency === 'PEN') {
                currentCurrency = 'USD'; // Set to USD first so toggle works
                toggleCurrency();
            }
        });
    </script>
</body>
</html>
