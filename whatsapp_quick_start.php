<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in (support both session formats)
$isLoggedIn = isset($_SESSION['user']) || isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// Get user role (support both session formats)
$userRole = $_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? 'guest';

// Check if user is manager/admin
if ($userRole !== 'manager' && $userRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$connection = $conn;

// WhatsApp configuration status (simplified for current database structure)
$config = [
    'setup_completed' => '0',
    'whatsapp_enabled' => '0', 
    'whatsapp_access_token' => '',
    'hotel_name' => 'Ocean View Resort Miami',
    'whatsapp_business_phone' => '+1305555OCEAN'
];

$isSetupComplete = ($config['setup_completed'] ?? '0') === '1';
$isWhatsAppEnabled = ($config['whatsapp_enabled'] ?? '0') === '1';
$hasCredentials = !empty($config['whatsapp_access_token'] ?? '');

// Sample WhatsApp activity stats (replace with real data once WhatsApp tables are set up)
$whatsappStats = [];
if ($isSetupComplete) {
    $whatsappStats = [
        'total_users' => 25,
        'total_messages' => 148, 
        'messages_24h' => 12
    ];
} else {
    $whatsappStats = [
        'total_users' => 0,
        'total_messages' => 0, 
        'messages_24h' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Quick Start - Revolutionary Hotel Platform</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: #25D366;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card.success {
            border-left: 5px solid #25D366;
        }

        .card.warning {
            border-left: 5px solid #ffa500;
        }

        .card.error {
            border-left: 5px solid #dc3545;
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-warning {
            background: #ffa500;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #25D366;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .whatsapp-demo {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .chat-preview {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .message {
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
        }

        .guest-message {
            background: #e3f2fd;
            margin-right: 2rem;
        }

        .ai-message {
            background: #e8f5e8;
            margin-left: 2rem;
        }

        @media (max-width: 768px) {
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WhatsApp Booking Quick Start</h1>
            <p>Your Revolutionary Hotel Platform with AI-powered WhatsApp booking</p>
        </div>

        <div class="status-cards">
            <!-- Setup Status Card -->
            <div class="card <?php echo $isSetupComplete ? 'success' : 'warning'; ?>">
                <div class="card-icon"><?php echo $isSetupComplete ? '✅' : '⚙️'; ?></div>
                <div class="card-title">
                    <?php echo $isSetupComplete ? 'Setup Complete!' : 'Setup Required'; ?>
                </div>
                <div class="card-description">
                    <?php if ($isSetupComplete): ?>
                        Your WhatsApp booking system is configured and ready to accept guest reservations through chat.
                    <?php else: ?>
                        Complete the setup wizard to enable revolutionary WhatsApp booking for your hotel.
                    <?php endif; ?>
                </div>
                <?php if (!$isSetupComplete): ?>
                    <a href="whatsapp_setup_wizard.php" class="btn btn-primary">Complete Setup</a>
                <?php endif; ?>
            </div>

            <!-- WhatsApp Status Card -->
            <div class="card <?php echo $isWhatsAppEnabled ? 'success' : ($hasCredentials ? 'warning' : 'error'); ?>">
                <div class="card-icon">📱</div>
                <div class="card-title">WhatsApp Integration</div>
                <div class="card-description">
                    <?php if ($isWhatsAppEnabled): ?>
                        WhatsApp is active and ready to receive booking requests from guests.
                    <?php elseif ($hasCredentials): ?>
                        Credentials configured but WhatsApp integration is disabled.
                    <?php else: ?>
                        WhatsApp Business API credentials needed to enable booking.
                    <?php endif; ?>
                </div>
                <?php if (!$isWhatsAppEnabled): ?>
                    <a href="whatsapp_management.php" class="btn <?php echo $hasCredentials ? 'btn-warning' : 'btn-primary'; ?>">
                        <?php echo $hasCredentials ? 'Enable WhatsApp' : 'Configure API'; ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- AI Status Card -->
            <div class="card success">
                <div class="card-icon">🤖</div>
                <div class="card-title">AI Assistant</div>
                <div class="card-description">
                    Intelligent AI assistant ready to handle guest inquiries, booking requests, and HotelCoin questions.
                </div>
                <a href="ai_admin.php" class="btn btn-secondary">Configure AI</a>
            </div>
        </div>

        <?php if ($isSetupComplete && $isWhatsAppEnabled): ?>
            <div class="card">
                <div class="card-title">📊 WhatsApp Activity</div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($whatsappStats['total_users'] ?? 0); ?></div>
                        <div class="stat-label">WhatsApp Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($whatsappStats['total_messages'] ?? 0); ?></div>
                        <div class="stat-label">Total Messages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($whatsappStats['messages_24h'] ?? 0); ?></div>
                        <div class="stat-label">Last 24 Hours</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="quick-actions">
            <div class="card-title">🎯 Quick Actions</div>
            <div class="action-buttons">
                <a href="whatsapp_management.php" class="action-btn btn-primary">
                    📱 Manage WhatsApp<br>
                    <small>View conversations & send broadcasts</small>
                </a>
                
                <a href="hotelcoin_admin.php" class="action-btn btn-primary">
                    🪙 HotelCoin Admin<br>
                    <small>Manage digital currency rewards</small>
                </a>
                
                <a href="api/whatsapp_test.php" class="action-btn btn-secondary" target="_blank">
                    🧪 Test API<br>
                    <small>Check system status & connectivity</small>
                </a>
                
                <a href="manager_dashboard.php" class="action-btn btn-secondary">
                    🏠 Back to Dashboard<br>
                    <small>Return to main management panel</small>
                </a>
            </div>
        </div>

        <?php if ($isSetupComplete): ?>
            <div class="whatsapp-demo">
                <div class="card-title" style="text-align: center; margin-bottom: 2rem;">
                    💬 How Guests Experience Your WhatsApp Booking
                </div>
                
                <div class="chat-preview">
                    <div class="message guest-message">
                        Hi! I want to book a room for 2 guests next weekend
                    </div>
                    
                    <div class="message ai-message">
                        🏨 Welcome to <?php echo htmlspecialchars($config['hotel_name'] ?? 'Your Hotel'); ?>! I found these options for next weekend:
                        
                        🛏️ Deluxe Room - $120/night
                        • Earn 24 HotelCoins per night!
                        
                        Which interests you? 😊
                    </div>
                    
                    <div class="message guest-message">
                        The deluxe room sounds perfect!
                    </div>
                    
                    <div class="message ai-message">
                        Excellent choice! 🎉
                        ✅ Deluxe Room: Sat-Sun (2 nights)
                        💰 Total: $240 + taxes  
                        🪙 HotelCoins: 48 coins earned
                        
                        Ready to confirm? Reply 'YES' 📞
                    </div>
                </div>
                
                <p style="text-align: center; margin-top: 2rem; color: #666;">
                    <strong>Revolutionary Experience:</strong> Guests book directly in WhatsApp while earning HotelCoins!
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>