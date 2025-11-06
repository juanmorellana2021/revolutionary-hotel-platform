<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'booking_sync_service.php';

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

$syncService = new BookingSyncService();
$stats = $syncService->getSyncStats();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Handle manual sync trigger
if ($_POST['action'] ?? '' === 'manual_sync') {
    $syncType = $_POST['sync_type'] ?? 'full';
    $syncResults = null;
    
    switch ($syncType) {
        case 'full':
            $syncResults = $syncService->fullSync();
            break;
        case 'from_booking':
            $syncResults = $syncService->syncFromBookingCom();
            break;
        case 'to_booking':
            $syncResults = $syncService->syncToBookingCom();
            break;
        case 'availability':
            $syncResults = $syncService->updateAvailability();
            break;
    }
    
    $message = "Synchronization completed successfully!";
    $messageType = "success";
    
    // Refresh stats
    $stats = $syncService->getSyncStats();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking.com Integration - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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
        }

        .navbar {
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }

        .sync-controls {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .sync-controls h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .sync-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .api-status {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-connected {
            background: #28a745;
        }

        .status-disconnected {
            background: #dc3545;
        }

        .status-warning {
            background: #ffc107;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
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

        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .config-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .sync-log {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">🔗 Booking.com Integration</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">Dashboard</a>
                <a href="room_management.php">Rooms</a>
                <a href="calendar_view.php">Calendar</a>
                <a href="booking_integration.php" class="active">Booking.com</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>🔗 Booking.com Integration</h1>
            <p>Manage synchronization between your hotel system and Booking.com</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- API Status -->
        <div class="api-status">
            <h2>🔌 API Connection Status</h2>
            <div class="status-indicator">
                <div class="status-dot status-warning"></div>
                <strong>Configuration Required</strong>
            </div>
            <div class="alert warning">
                <strong>⚠️ Important:</strong> Booking.com API integration requires:
                <ul style="margin: 10px 0 0 20px;">
                    <li>Partner Hub approval from Booking.com</li>
                    <li>Valid API credentials (API key, Hotel ID, Secret key)</li>
                    <li>Room type mapping configuration</li>
                    <li>SSL certificate for production use</li>
                </ul>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['from_booking_com']; ?></div>
                <div class="stat-label">Reservations from Booking.com</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['synced_to_booking_com']; ?></div>
                <div class="stat-label">Local Bookings Synced</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_sync']; ?></div>
                <div class="stat-label">Pending Sync</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['last_sync']; ?></div>
                <div class="stat-label">Last Synchronization</div>
            </div>
        </div>

        <!-- Sync Controls -->
        <div class="sync-controls">
            <h2>🔄 Synchronization Controls</h2>
            <form method="POST">
                <input type="hidden" name="action" value="manual_sync">
                <div class="sync-buttons">
                    <button type="submit" name="sync_type" value="full" class="btn btn-primary">
                        🔄 Full Sync
                    </button>
                    <button type="submit" name="sync_type" value="from_booking" class="btn btn-success">
                        ⬇️ Get from Booking.com
                    </button>
                    <button type="submit" name="sync_type" value="to_booking" class="btn btn-info">
                        ⬆️ Send to Booking.com
                    </button>
                    <button type="submit" name="sync_type" value="availability" class="btn btn-warning">
                        📅 Update Availability
                    </button>
                </div>
            </form>
            
            <div class="alert warning">
                <strong>Note:</strong> Manual sync will only work with valid API credentials configured below.
            </div>
        </div>

        <!-- Configuration -->
        <div class="config-section">
            <h2>⚙️ API Configuration</h2>
            <p>Configure your Booking.com API credentials (obtained from Partner Hub):</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="api_key">API Key</label>
                    <input type="text" id="api_key" name="api_key" placeholder="Your Booking.com API key">
                </div>
                
                <div class="form-group">
                    <label for="hotel_id">Hotel ID</label>
                    <input type="text" id="hotel_id" name="hotel_id" placeholder="Your hotel ID from Booking.com">
                </div>
                
                <div class="form-group">
                    <label for="secret_key">Secret Key</label>
                    <input type="password" id="secret_key" name="secret_key" placeholder="Your API secret key">
                </div>
                
                <button type="submit" class="btn btn-success">💾 Save Configuration</button>
            </form>
            
            <div class="alert warning" style="margin-top: 20px;">
                <strong>🔐 Security Note:</strong> API credentials should be stored securely. In production, use environment variables or encrypted configuration files.
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="config-section" style="margin-top: 30px;">
            <h2>📋 Setup Instructions</h2>
            <ol style="margin: 20px; line-height: 1.6;">
                <li><strong>Apply for Booking.com Partnership:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Visit <a href="https://partner.booking.com" target="_blank">partner.booking.com</a></li>
                        <li>Register your property</li>
                        <li>Apply for API access through Partner Hub</li>
                    </ul>
                </li>
                <li><strong>Get API Credentials:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Once approved, you'll receive API credentials</li>
                        <li>Note your Hotel ID, API Key, and Secret Key</li>
                    </ul>
                </li>
                <li><strong>Configure Room Mapping:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Map your rooms to Booking.com room types</li>
                        <li>This ensures correct availability updates</li>
                    </ul>
                </li>
                <li><strong>Test Integration:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Start with sandbox/test environment</li>
                        <li>Verify two-way synchronization works</li>
                        <li>Go live after thorough testing</li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>

    <script>
        // Auto-refresh stats every 30 seconds
        setInterval(function() {
            fetch('booking_sync_service.php?action=stats&format=json')
                .then(response => response.json())
                .then(data => {
                    // Update stat displays
                    console.log('Stats updated:', data);
                })
                .catch(error => console.error('Error updating stats:', error));
        }, 30000);
    </script>
</body>
</html>