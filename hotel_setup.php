<?php
session_start();
require_once 'classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: owner_login.php');
    exit;
}

// Allow owners, managers, and admins
$allowed = ($_SESSION['user_type'] === 'owner') || 
           ($_SESSION['user_role'] === 'manager') || 
           ($_SESSION['user_role'] === 'admin');

if (!$allowed) {
    header('Location: dashboard.php');
    exit;
}

$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();
$services = $hotelInfo->getServices();
$amenities = $hotelInfo->getAmenities();

// Get email configuration for this hotel
$database = new Database();
$conn = $database->getConnection();
$currentHotelId = $_SESSION['current_hotel_id'] ?? 1;
$stmt = $conn->prepare("SELECT * FROM email_config WHERE hotel_id = ? LIMIT 1");
$stmt->execute([$currentHotelId]);
$emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);

// If no config exists for this hotel, create default
if (!$emailConfig) {
    $stmt = $conn->prepare("INSERT INTO email_config (hotel_id, smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name, reply_to, is_enabled) VALUES (?, 'smtp.gmail.com', 587, '', '', '', ?, '', 0)");
    $stmt->execute([$currentHotelId, $hotel['name'] ?? 'AiNi Hotel']);
    $stmt = $conn->prepare("SELECT * FROM email_config WHERE hotel_id = ? LIMIT 1");
    $stmt->execute([$currentHotelId]);
    $emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_hotel_info'])) {
        // Combine address fields
        $formData = $_POST;
        $formData['address'] = trim(($formData['address_line1'] ?? '') . ' ' . ($formData['address_line2'] ?? ''));
        
        $result = $hotelInfo->updateHotelInfo($formData);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        // Refresh hotel info
        $hotel = $hotelInfo->getHotelInfo();
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
                $stmt = $conn->prepare("INSERT INTO email_config (
                    smtp_host, smtp_port, smtp_username, smtp_password,
                    from_email, from_name, reply_to, is_enabled,
                    use_ssl, use_tls, updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
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
    <title>Hotel Setup - Manager Dashboard</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }

        .welcome-banner h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .welcome-banner p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .setup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .setup-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .btn-success:hover {
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .service-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: border-color 0.3s;
        }

        .service-card.active {
            border-color: #28a745;
            background: #d4edda;
        }

        .service-info {
            flex: 1;
        }

        .service-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .service-description {
            font-size: 0.9rem;
            color: #666;
        }

        .service-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .toggle-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .toggle-btn.active {
            background: #28a745;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
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

        .full-width-section {
            grid-column: 1 / -1;
        }

        .add-item-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            border: 2px dashed #dee2e6;
        }

        .stats-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 1024px) {
            .setup-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🏨 Hotel Management System</div>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="hotel_setup.php">Hotel Setup</a>
                <a href="room_management.php">Room Management</a>
                <a href="calendar_view.php">Calendar</a>
                <a href="accounting_dashboard.php">💰 Accounting</a>
                <a href="income_management.php">💰 Income</a>
                <a href="expense_management.php">💸 Expenses</a>
                <a href="owner_account.php">My Properties</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['new_property']) && $_GET['new_property'] == '1'): ?>
            <div class="welcome-banner" style="background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center;">
                <h1>🎉 Congratulations! Your Property is Registered!</h1>
                <p style="font-size: 1.1rem; margin: 10px 0;">Welcome to AiNi Hotel Platform! Let's complete your property setup.</p>
                <p style="opacity: 0.9;">✅ Account created | ✅ Property registered | 📋 Now complete your hotel details below</p>
            </div>
        <?php else: ?>
            <div class="welcome-banner">
                <h1>🎉 Welcome to Hotel Setup!</h1>
                <p>Configure your hotel information, services, and amenities to create the perfect guest experience</p>
            </div>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="setup-grid">
            <!-- Hotel Basic Information -->
            <div class="setup-section">
                <h2 class="section-title">🏨 Hotel Information</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="hotel_name">Hotel Name *</label>
                            <input type="text" id="hotel_name" name="hotel_name" 
                                   value="<?php echo htmlspecialchars($hotel['hotel_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hotel_rating">Star Rating</label>
                            <select id="hotel_rating" name="hotel_rating">
                                <option value="">Select Rating</option>
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                            <?php echo ($hotel['star_rating'] ?? '') == $i ? 'selected' : ''; ?>>
                                        <?php echo str_repeat('⭐', $i) . " ($i Star)"; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="hotel_description">Hotel Description</label>
                        <textarea id="hotel_description" name="hotel_description" 
                                  placeholder="Describe your hotel's unique features and atmosphere..."><?php echo htmlspecialchars($hotel['hotel_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="address_line1">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" 
                                   value="<?php echo htmlspecialchars($hotel['address_line1'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line2">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" 
                                   value="<?php echo htmlspecialchars($hotel['address_line2'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($hotel['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State/Province</label>
                            <input type="text" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($hotel['state'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="zip_code">ZIP/Postal Code</label>
                            <input type="text" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($hotel['zip_code'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($hotel['country'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">🌍 Timezone</label>
                            <select id="timezone" name="timezone" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; width: 100%;">
                                <optgroup label="Americas">
                                    <option value="America/Lima" <?php echo ($hotel['timezone'] ?? 'America/Lima') == 'America/Lima' ? 'selected' : ''; ?>>Peru (Lima) - UTC-5</option>
                                    <option value="America/New_York" <?php echo ($hotel['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>USA (New York) - UTC-5/-4</option>
                                    <option value="America/Chicago" <?php echo ($hotel['timezone'] ?? '') == 'America/Chicago' ? 'selected' : ''; ?>>USA (Chicago) - UTC-6/-5</option>
                                    <option value="America/Denver" <?php echo ($hotel['timezone'] ?? '') == 'America/Denver' ? 'selected' : ''; ?>>USA (Denver) - UTC-7/-6</option>
                                    <option value="America/Los_Angeles" <?php echo ($hotel['timezone'] ?? '') == 'America/Los_Angeles' ? 'selected' : ''; ?>>USA (Los Angeles) - UTC-8/-7</option>
                                    <option value="America/Mexico_City" <?php echo ($hotel['timezone'] ?? '') == 'America/Mexico_City' ? 'selected' : ''; ?>>Mexico (Mexico City) - UTC-6/-5</option>
                                    <option value="America/Bogota" <?php echo ($hotel['timezone'] ?? '') == 'America/Bogota' ? 'selected' : ''; ?>>Colombia (Bogotá) - UTC-5</option>
                                    <option value="America/Buenos_Aires" <?php echo ($hotel['timezone'] ?? '') == 'America/Buenos_Aires' ? 'selected' : ''; ?>>Argentina (Buenos Aires) - UTC-3</option>
                                    <option value="America/Santiago" <?php echo ($hotel['timezone'] ?? '') == 'America/Santiago' ? 'selected' : ''; ?>>Chile (Santiago) - UTC-3/-4</option>
                                    <option value="America/Caracas" <?php echo ($hotel['timezone'] ?? '') == 'America/Caracas' ? 'selected' : ''; ?>>Venezuela (Caracas) - UTC-4</option>
                                </optgroup>
                                <optgroup label="Europe">
                                    <option value="Europe/London" <?php echo ($hotel['timezone'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>UK (London) - UTC+0/+1</option>
                                    <option value="Europe/Paris" <?php echo ($hotel['timezone'] ?? '') == 'Europe/Paris' ? 'selected' : ''; ?>>France (Paris) - UTC+1/+2</option>
                                    <option value="Europe/Madrid" <?php echo ($hotel['timezone'] ?? '') == 'Europe/Madrid' ? 'selected' : ''; ?>>Spain (Madrid) - UTC+1/+2</option>
                                    <option value="Europe/Berlin" <?php echo ($hotel['timezone'] ?? '') == 'Europe/Berlin' ? 'selected' : ''; ?>>Germany (Berlin) - UTC+1/+2</option>
                                    <option value="Europe/Rome" <?php echo ($hotel['timezone'] ?? '') == 'Europe/Rome' ? 'selected' : ''; ?>>Italy (Rome) - UTC+1/+2</option>
                                    <option value="Europe/Moscow" <?php echo ($hotel['timezone'] ?? '') == 'Europe/Moscow' ? 'selected' : ''; ?>>Russia (Moscow) - UTC+3</option>
                                </optgroup>
                                <optgroup label="Asia">
                                    <option value="Asia/Dubai" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Dubai' ? 'selected' : ''; ?>>UAE (Dubai) - UTC+4</option>
                                    <option value="Asia/Bangkok" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Bangkok' ? 'selected' : ''; ?>>Thailand (Bangkok) - UTC+7</option>
                                    <option value="Asia/Singapore" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Singapore' ? 'selected' : ''; ?>>Singapore - UTC+8</option>
                                    <option value="Asia/Hong_Kong" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Hong_Kong' ? 'selected' : ''; ?>>Hong Kong - UTC+8</option>
                                    <option value="Asia/Tokyo" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Tokyo' ? 'selected' : ''; ?>>Japan (Tokyo) - UTC+9</option>
                                    <option value="Asia/Seoul" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Seoul' ? 'selected' : ''; ?>>South Korea (Seoul) - UTC+9</option>
                                    <option value="Asia/Shanghai" <?php echo ($hotel['timezone'] ?? '') == 'Asia/Shanghai' ? 'selected' : ''; ?>>China (Shanghai) - UTC+8</option>
                                </optgroup>
                                <optgroup label="Oceania">
                                    <option value="Australia/Sydney" <?php echo ($hotel['timezone'] ?? '') == 'Australia/Sydney' ? 'selected' : ''; ?>>Australia (Sydney) - UTC+10/+11</option>
                                    <option value="Pacific/Auckland" <?php echo ($hotel['timezone'] ?? '') == 'Pacific/Auckland' ? 'selected' : ''; ?>>New Zealand (Auckland) - UTC+12/+13</option>
                                </optgroup>
                            </select>
                            <small style="color: #666; display: block; margin-top: 5px;">Select your hotel's local timezone for accurate time tracking</small>
                        </div>
                    </div>

                    <button type="submit" name="update_hotel_info" class="btn btn-success">
                        💾 Save Hotel Information
                    </button>
                </form>
            </div>

            <!-- Contact & Operational Info -->
            <div class="setup-section">
                <h2 class="section-title">📞 Contact & Operations</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($hotel['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Hotel Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($hotel['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website URL</label>
                        <input type="url" id="website" name="website" 
                               value="<?php echo htmlspecialchars($hotel['website'] ?? ''); ?>">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="check_in_time">Check-in Time</label>
                            <input type="time" id="check_in_time" name="check_in_time" 
                                   value="<?php echo $hotel['check_in_time'] ?? '15:00'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="check_out_time">Check-out Time</label>
                            <input type="time" id="check_out_time" name="check_out_time" 
                                   value="<?php echo $hotel['check_out_time'] ?? '11:00'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="total_rooms">Total Number of Rooms</label>
                        <input type="number" id="total_rooms" name="total_rooms" min="1" 
                               value="<?php echo $hotel['total_rooms'] ?? ''; ?>">
                    </div>

                    <button type="submit" name="update_hotel_info" class="btn btn-success">
                        💾 Save Contact Information
                    </button>
                </form>

                <div class="stats-preview">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $hotel['total_rooms'] ?? '0'; ?></div>
                        <div>Total Rooms</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $hotel['check_in_time'] ?? '15:00'; ?></div>
                        <div>Check-in Time</div>
                    </div>
                </div>
            </div>

            <!-- Hotel Services -->
            <div class="setup-section full-width-section">
                <h2 class="section-title">🛎️ Hotel Services</h2>
                <p style="margin-bottom: 1rem; color: #666;">Enable or disable services your hotel offers to guests.</p>
                
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <?php
                        // Service icons and descriptions mapping
                        $serviceIcons = [
                            '24/7 Front Desk' => '🏨',
                            'Room Service' => '🍽️',
                            'Housekeeping' => '🧹',
                            'Concierge Service' => '🛎️',
                            'Wake-up Calls' => '⏰',
                            'Luggage Storage' => '🧳',
                            'Express Check-in/out' => '⚡',
                            'Business Center' => '💼',
                            'Valet Parking' => '🚗',
                            'Airport Shuttle' => '🚐',
                            'Laundry Service' => '👕',
                            'Car Rental' => '🚙'
                        ];
                        
                        $serviceDescriptions = [
                            '24/7 Front Desk' => 'Round-the-clock front desk assistance',
                            'Room Service' => 'In-room dining service available',
                            'Housekeeping' => 'Daily housekeeping service',
                            'Concierge Service' => 'Personal concierge assistance',
                            'Wake-up Calls' => 'Wake-up call service available',
                            'Luggage Storage' => 'Secure luggage storage facility',
                            'Express Check-in/out' => 'Quick check-in and check-out',
                            'Business Center' => 'Business facilities and services',
                            'Valet Parking' => 'Valet parking service',
                            'Airport Shuttle' => 'Complimentary airport transportation',
                            'Laundry Service' => 'Laundry and dry cleaning service',
                            'Car Rental' => 'Car rental assistance available'
                        ];
                        
                        $icon = $serviceIcons[$service['service_name']] ?? '🏨';
                        $description = $serviceDescriptions[$service['service_name']] ?? 'Hotel service available';
                        ?>
                        <div class="service-card <?php echo $service['is_active'] ? 'active' : ''; ?>">
                            <div style="display: flex; align-items: center;">
                                <span class="service-icon"><?php echo $icon; ?></span>
                                <div class="service-info">
                                    <div class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                    <div class="service-description"><?php echo htmlspecialchars($description); ?></div>
                                </div>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $service['is_active']; ?>">
                                <button type="submit" name="toggle_service" 
                                        class="toggle-btn <?php echo $service['is_active'] ? 'active' : ''; ?>">
                                    <?php echo $service['is_active'] ? 'Enabled' : 'Disabled'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="add-item-form">
                    <h4>➕ Add Custom Service</h4>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="service_name">Service Name</label>
                                <input type="text" id="service_name" name="service_name" required>
                            </div>
                            <div class="form-group">
                                <label for="service_icon">Icon (Emoji)</label>
                                <input type="text" id="service_icon" name="service_icon" value="🏨" maxlength="2">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="service_description">Service Description</label>
                            <textarea id="service_description" name="service_description" required></textarea>
                        </div>
                        <button type="submit" name="add_service" class="btn btn-small">Add Service</button>
                    </form>
                </div>
            </div>

            <!-- Hotel Amenities -->
            <div class="setup-section full-width-section">
                <h2 class="section-title">🏊 Hotel Amenities</h2>
                <p style="margin-bottom: 1rem; color: #666;">Select amenities available at your hotel property.</p>
                
                <div class="services-grid">
                    <?php foreach ($amenities as $amenity): ?>
                        <div class="service-card <?php echo $amenity['is_active'] ? 'active' : ''; ?>">
                            <?php
                            // Amenity icons and descriptions mapping
                            $amenityIcons = [
                                'Free WiFi' => '📶',
                                'Swimming Pool' => '🏊',
                                'Fitness Center' => '💪',
                                'Spa & Wellness' => '🧘',
                                'Restaurant' => '🍽️',
                                'Bar/Lounge' => '🍺',
                                'Parking' => '🅿️',
                                'Pet Friendly' => '🐕',
                                'Airport Shuttle' => '🚐',
                                'Meeting Rooms' => '🏢',
                                'Laundry Facilities' => '🧺',
                                'Safe Deposit Box' => '🔒',
                                'ATM/Banking' => '🏧',
                                'Gift Shop' => '🎁'
                            ];
                            
                            $amenityDescriptions = [
                                'Free WiFi' => 'Complimentary wireless internet access',
                                'Swimming Pool' => 'Indoor/outdoor swimming pool',
                                'Fitness Center' => '24-hour fitness facility',
                                'Spa & Wellness' => 'Full-service spa and wellness center',
                                'Restaurant' => 'On-site dining restaurant',
                                'Bar/Lounge' => 'Cocktail bar and lounge area',
                                'Parking' => 'Complimentary parking available',
                                'Pet Friendly' => 'Pet-friendly accommodations',
                                'Airport Shuttle' => 'Airport shuttle service',
                                'Meeting Rooms' => 'Conference and meeting facilities',
                                'Laundry Facilities' => 'Self-service laundry facilities',
                                'Safe Deposit Box' => 'In-room safe deposit boxes',
                                'ATM/Banking' => 'ATM and banking services',
                                'Gift Shop' => 'Hotel gift shop and souvenirs'
                            ];
                            
                            $amenityIcon = $amenityIcons[$amenity['amenity_name']] ?? '⭐';
                            $amenityDescription = $amenityDescriptions[$amenity['amenity_name']] ?? 'Hotel amenity available';
                            ?>
                            <div style="display: flex; align-items: center;">
                                <span class="service-icon"><?php echo $amenityIcon; ?></span>
                                <div class="service-info">
                                    <div class="service-name"><?php echo htmlspecialchars($amenity['amenity_name']); ?></div>
                                    <div class="service-description"><?php echo htmlspecialchars($amenityDescription); ?></div>
                                </div>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="amenity_id" value="<?php echo $amenity['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $amenity['is_active']; ?>">
                                <button type="submit" name="toggle_amenity" 
                                        class="toggle-btn <?php echo $amenity['is_active'] ? 'active' : ''; ?>">
                                    <?php echo $amenity['is_active'] ? 'Available' : 'Not Available'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="add-item-form">
                    <h4>➕ Add Custom Amenity</h4>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="amenity_name">Amenity Name</label>
                                <input type="text" id="amenity_name" name="amenity_name" required>
                            </div>
                            <div class="form-group">
                                <label for="amenity_icon">Icon (Emoji)</label>
                                <input type="text" id="amenity_icon" name="amenity_icon" value="⭐" maxlength="2">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="amenity_description">Amenity Description</label>
                            <textarea id="amenity_description" name="amenity_description" required></textarea>
                        </div>
                        <button type="submit" name="add_amenity" class="btn btn-small">Add Amenity</button>
                    </form>
                </div>
            </div>

            <!-- Email Configuration -->
            <div class="setup-section full-width-section">
                <h2 class="section-title">📧 Email Configuration</h2>
                <p style="margin-bottom: 1rem; color: #666;">Configure SMTP settings to send booking receipts and notifications to guests.</p>
                
                <?php if ($emailConfig && $emailConfig['is_enabled']): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        ✅ Email system is <strong>ENABLED</strong> and ready to send receipts
                    </div>
                <?php else: ?>
                    <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        ⚠️ Email system is <strong>DISABLED</strong>. Configure and enable to start sending receipts
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="hotel-form">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px;">🔧 SMTP Server Settings</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="smtp_host">SMTP Host *</label>
                                <input type="text" id="smtp_host" name="smtp_host" 
                                       value="<?php echo htmlspecialchars($emailConfig['smtp_host'] ?? 'smtp.gmail.com'); ?>" 
                                       placeholder="smtp.gmail.com" required>
                                <small>For Gmail: smtp.gmail.com | Outlook: smtp-mail.outlook.com</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_port">SMTP Port *</label>
                                <input type="number" id="smtp_port" name="smtp_port" 
                                       value="<?php echo $emailConfig['smtp_port'] ?? 587; ?>" 
                                       placeholder="587" required>
                                <small>Usually 587 (TLS) or 465 (SSL)</small>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="smtp_username">SMTP Username (Email) *</label>
                                <input type="email" id="smtp_username" name="smtp_username" 
                                       value="<?php echo htmlspecialchars($emailConfig['smtp_username'] ?? ''); ?>" 
                                       placeholder="your-email@gmail.com" required>
                                <small>Your full email address</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_password">SMTP Password *</label>
                                <input type="password" id="smtp_password" name="smtp_password" 
                                       value="<?php echo htmlspecialchars($emailConfig['smtp_password'] ?? ''); ?>" 
                                       placeholder="App Password" required>
                                <small>For Gmail: Use App Password, not regular password</small>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 20px; margin-top: 15px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="use_tls" value="1" 
                                       <?php echo ($emailConfig['use_tls'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Use TLS (Port 587)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="use_ssl" value="1" 
                                       <?php echo ($emailConfig['use_ssl'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Use SSL (Port 465)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px;">✉️ Email Sender Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="from_email">From Email *</label>
                                <input type="email" id="from_email" name="from_email" 
                                       value="<?php echo htmlspecialchars($emailConfig['from_email'] ?? 'reservas@ainihotel.com'); ?>" 
                                       placeholder="reservas@ainihotel.com" required>
                                <small>Email address that appears as sender</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="from_name">From Name *</label>
                                <input type="text" id="from_name" name="from_name" 
                                       value="<?php echo htmlspecialchars($emailConfig['from_name'] ?? 'AiNi Hotel'); ?>" 
                                       placeholder="AiNi Hotel" required>
                                <small>Name that appears as sender</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reply_to">Reply-To Email *</label>
                            <input type="email" id="reply_to" name="reply_to" 
                                   value="<?php echo htmlspecialchars($emailConfig['reply_to'] ?? 'reservas@ainihotel.com'); ?>" 
                                   placeholder="reservas@ainihotel.com" required>
                            <small>Email address for guest replies</small>
                        </div>
                    </div>
                    
                    <div style="background: #fff3e0; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px;">📘 Setup Instructions</h3>
                        
                        <div style="line-height: 1.8;">
                            <strong>For Gmail:</strong>
                            <ol style="margin: 10px 0 20px 20px;">
                                <li>Enable 2-Factor Authentication on your Google account</li>
                                <li>Go to: Google Account → Security → App passwords</li>
                                <li>Generate an app password for "Mail"</li>
                                <li>Use that 16-character password in "SMTP Password" field above</li>
                                <li>Set Host: smtp.gmail.com, Port: 587, Enable TLS</li>
                            </ol>
                            
                            <strong>For Outlook/Hotmail:</strong>
                            <ul style="margin: 10px 0 0 20px;">
                                <li>Host: smtp-mail.outlook.com, Port: 587</li>
                                <li>Use your regular Outlook password</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 1.1em;">
                            <input type="checkbox" name="is_enabled" value="1" 
                                   <?php echo ($emailConfig['is_enabled'] ?? 0) ? 'checked' : ''; ?>
                                   style="width: 20px; height: 20px; cursor: pointer;">
                            <strong>Enable Email System</strong>
                        </label>
                        <span style="color: #666;">(Check this to start sending receipts to guests)</span>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" name="update_email_config" class="btn" style="background: #28a745;">
                            💾 Save Email Configuration
                        </button>
                        <a href="test_email.php" class="btn" style="background: #17a2b8; text-decoration: none; display: inline-block;" target="_blank">
                            📧 Test Email System
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="dashboard.php" class="btn" style="margin-right: 1rem;">
                📊 Go to Dashboard
            </a>
            <a href="owner_account.php" class="btn">
                🏢 My Properties
            </a>
        </div>
    </div>
</body>
</html>
```