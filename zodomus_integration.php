<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'zodomus_sync_service.php';

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

$syncService = new ZodomusSyncService();
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
        case 'incoming':
            $syncResults = $syncService->syncIncomingReservations();
            break;
        case 'outgoing':
            $syncResults = $syncService->syncOutgoingReservations();
            break;
        case 'availability':
            $syncResults = $syncService->syncAvailability();
            break;
        case 'rates':
            $syncResults = $syncService->syncRates();
            break;
    }
    
    $message = "Synchronization completed successfully!";
    $messageType = "success";
    
    // Refresh stats
    $stats = $syncService->getSyncStats();
}

// Handle API configuration save
if ($_POST['action'] ?? '' === 'save_config') {
    $apiKey = $_POST['api_key'] ?? '';
    $propertyId = $_POST['property_id'] ?? '';
    
    $zodomusAPI = new ZodomusAPI();
    $result = $zodomusAPI->saveCredentials($apiKey, $propertyId);
    
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

// Handle test connection
if ($_POST['action'] ?? '' === 'test_connection') {
    $zodomusAPI = new ZodomusAPI();
    $result = $zodomusAPI->testConnection();
    
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zodomus Integration - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            margin-left: 2rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #667eea;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
            border: 1px solid #ffeeba;
        }

        .channel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .channel-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .channel-card:hover {
            transform: translateY(-5px);
        }

        .channel-logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .channel-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .channel-count {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .sync-controls {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .sync-controls h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .sync-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .config-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .config-section h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .status-success {
            background: #28a745;
        }

        .status-warning {
            background: #ffc107;
        }

        .status-error {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-content">
            <div class="logo">🔗 Zodomus Channel Manager</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">Dashboard</a>
                <a href="room_management.php">Rooms</a>
                <a href="calendar_view.php">Calendar</a>
                <a href="booking_integration.php">Booking.com</a>
                <a href="zodomus_integration.php" class="active">Zodomus</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>🔗 Zodomus Channel Manager Integration</h1>
            <p>Sync with Airbnb, Booking.com, Expedia, VRBO, and more - all in one place!</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Connected Channels -->
        <div class="config-section">
            <h2>🌍 Connected Channels</h2>
            <div class="channel-grid">
                <div class="channel-card">
                    <div class="channel-logo">🏠</div>
                    <div class="channel-name">Airbnb</div>
                    <div class="channel-count"><?php echo $stats['airbnb'] ?? 0; ?></div>
                    <small>Reservations</small>
                </div>
                <div class="channel-card">
                    <div class="channel-logo">🏨</div>
                    <div class="channel-name">Booking.com</div>
                    <div class="channel-count"><?php echo $stats['booking.com'] ?? 0; ?></div>
                    <small>Reservations</small>
                </div>
                <div class="channel-card">
                    <div class="channel-logo">✈️</div>
                    <div class="channel-name">Expedia</div>
                    <div class="channel-count"><?php echo $stats['expedia'] ?? 0; ?></div>
                    <small>Reservations</small>
                </div>
                <div class="channel-card">
                    <div class="channel-logo">🏖️</div>
                    <div class="channel-name">VRBO</div>
                    <div class="channel-count"><?php echo $stats['vrbo'] ?? 0; ?></div>
                    <small>Reservations</small>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_synced'] ?? 0; ?></div>
                <div class="stat-label">Total Synced Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_sync'] ?? 0; ?></div>
                <div class="stat-label">Pending Sync</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['last_sync'] ?? 'Never'; ?></div>
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
                        🔄 Full Sync (All Channels)
                    </button>
                    <button type="submit" name="sync_type" value="incoming" class="btn btn-success">
                        ⬇️ Get New Reservations
                    </button>
                    <button type="submit" name="sync_type" value="outgoing" class="btn btn-info">
                        ⬆️ Push Local Bookings
                    </button>
                    <button type="submit" name="sync_type" value="availability" class="btn btn-warning">
                        📅 Update Availability
                    </button>
                    <button type="submit" name="sync_type" value="rates" class="btn btn-info">
                        💰 Update Rates
                    </button>
                </div>
            </form>
            
            <div class="alert warning" style="margin-top: 1rem;">
                <strong>Note:</strong> Sync will only work with valid Zodomus API credentials configured below.
            </div>
        </div>

        <!-- Configuration -->
        <div class="config-section">
            <h2>⚙️ Zodomus API Configuration</h2>
            <p>Configure your Zodomus account credentials:</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_config">
                <div class="form-group">
                    <label for="api_key">Zodomus API Key</label>
                    <input type="text" id="api_key" name="api_key" placeholder="Your Zodomus API key">
                    <small style="color: #666;">Get this from your Zodomus dashboard</small>
                </div>
                
                <div class="form-group">
                    <label for="property_id">Property ID</label>
                    <input type="text" id="property_id" name="property_id" placeholder="Your property ID">
                    <small style="color: #666;">Found in Zodomus property settings</small>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Save Configuration</button>
            </form>
            
            <!-- Test Connection -->
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="test_connection">
                <button type="submit" class="btn btn-info">🔌 Test Connection</button>
            </form>
            
            <div class="alert warning" style="margin-top: 20px;">
                <strong>🔐 Security Note:</strong> API credentials are stored securely in the database.
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="config-section" style="margin-top: 30px;">
            <h2>📋 Setup Instructions</h2>
            <ol style="margin: 20px; line-height: 1.6;">
                <li><strong>Create Zodomus Account:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Visit <a href="https://www.zodomus.com" target="_blank">www.zodomus.com</a></li>
                        <li>Sign up for a Channel Manager account</li>
                        <li>Add your property details</li>
                    </ul>
                </li>
                <li><strong>Get API Credentials:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Go to Settings → API Access</li>
                        <li>Generate API key</li>
                        <li>Note your Property ID</li>
                    </ul>
                </li>
                <li><strong>Connect Channels:</strong>
                    <ul style="margin-left: 20px;">
                        <li>In Zodomus, connect to Airbnb, Booking.com, Expedia, etc.</li>
                        <li>Each channel requires separate authorization</li>
                        <li>Follow Zodomus guides for each channel</li>
                    </ul>
                </li>
                <li><strong>Map Your Rooms:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Map your local rooms to Zodomus room types</li>
                        <li>Set base rates for each room</li>
                        <li>Configure availability rules</li>
                    </ul>
                </li>
                <li><strong>Test & Go Live:</strong>
                    <ul style="margin-left: 20px;">
                        <li>Use "Test Connection" button above</li>
                        <li>Run "Full Sync" to verify data flow</li>
                        <li>Monitor sync logs for any issues</li>
                    </ul>
                </li>
            </ol>
        </div>

        <!-- Benefits -->
        <div class="config-section">
            <h2>✨ Benefits of Zodomus Integration</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">🔄 Two-Way Sync</h3>
                    <p style="color: #666;">Bookings from Airbnb and Booking.com automatically appear in your system. Local bookings push to all channels.</p>
                </div>
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">📅 Real-Time Availability</h3>
                    <p style="color: #666;">Room availability updates across all platforms in real-time. No more double bookings!</p>
                </div>
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">💰 Rate Management</h3>
                    <p style="color: #666;">Change prices once, update everywhere. Perfect for seasonal pricing and promotions.</p>
                </div>
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">📊 Centralized Dashboard</h3>
                    <p style="color: #666;">See all bookings from all channels in one place. Manage everything from your hotel dashboard.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh stats every 30 seconds
        setInterval(function() {
            fetch('zodomus_sync_service.php?action=stats&format=json')
                .then(response => response.json())
                .then(data => {
                    console.log('Stats updated:', data);
                    // You can update the UI here with new stats
                })
                .catch(error => console.error('Error updating stats:', error));
        }, 30000);
    </script>
</body>
</html>
