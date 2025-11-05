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

// For now, default to hotel_id = 1 (Samay Wasi)
// Later this will come from tenant context or hotel selection
$hotel_id = 1;

// Get hotel information
$stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    die("Hotel not found. Please contact administrator.");
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $accessToken = trim($_POST['access_token'] ?? '');
    $phoneNumberId = trim($_POST['phone_number_id'] ?? '');
    $businessPhone = trim($_POST['business_phone'] ?? '');
    $verifyToken = trim($_POST['verify_token'] ?? 'hotel_booking_webhook_2024');
    
    if (empty($accessToken) || empty($phoneNumberId)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } else {
        try {
            // Insert or update hotel WhatsApp configuration
            $stmt = $conn->prepare("
                INSERT INTO hotel_whatsapp_config 
                (hotel_id, whatsapp_access_token, whatsapp_phone_number_id, whatsapp_business_phone, whatsapp_webhook_token, whatsapp_enabled) 
                VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    whatsapp_access_token = VALUES(whatsapp_access_token),
                    whatsapp_phone_number_id = VALUES(whatsapp_phone_number_id),
                    whatsapp_business_phone = VALUES(whatsapp_business_phone),
                    whatsapp_webhook_token = VALUES(whatsapp_webhook_token),
                    whatsapp_enabled = 1
            ");
            $stmt->execute([$hotel_id, $accessToken, $phoneNumberId, $businessPhone, $verifyToken]);
            
            $message = 'WhatsApp configuration saved successfully for ' . htmlspecialchars($hotel['name']) . '! Now configure the webhook in Meta.';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = 'Error saving configuration: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Load existing configuration for this hotel
$config = [];
try {
    $stmt = $conn->prepare("SELECT * FROM hotel_whatsapp_config WHERE hotel_id = ?");
    $stmt->execute([$hotel_id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    // Table doesn't exist yet or error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp API Configuration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .subtitle {
            color: #718096;
            margin-bottom: 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .label-required::after {
            content: " *";
            color: #e53e3e;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .help-text {
            font-size: 0.875rem;
            color: #718096;
            margin-top: 0.5rem;
        }
        
        .webhook-info {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .webhook-info h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .code-box {
            background: #2d3748;
            color: #48bb78;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            margin: 0.5rem 0;
            overflow-x: auto;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
            margin-left: 1rem;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .steps {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e2e8f0;
        }
        
        .step {
            margin-bottom: 1.5rem;
        }
        
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 WhatsApp API Configuration</h1>
        <p class="subtitle">Configure WhatsApp for <strong><?php echo htmlspecialchars($hotel['name']); ?></strong></p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="access_token" class="label-required">WhatsApp Access Token</label>
                <input 
                    type="text" 
                    id="access_token" 
                    name="access_token" 
                    value="<?php echo htmlspecialchars($config['whatsapp_access_token'] ?? ''); ?>"
                    placeholder="EAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                    required
                >
                <p class="help-text">Get this from Meta Developers → Your App → WhatsApp → API Setup</p>
            </div>
            
            <div class="form-group">
                <label for="phone_number_id" class="label-required">Phone Number ID</label>
                <input 
                    type="text" 
                    id="phone_number_id" 
                    name="phone_number_id" 
                    value="<?php echo htmlspecialchars($config['whatsapp_phone_number_id'] ?? ''); ?>"
                    placeholder="123456789012345"
                    required
                >
                <p class="help-text">Found next to your WhatsApp business phone number in Meta</p>
            </div>
            
            <div class="form-group">
                <label for="verify_token">Webhook Verify Token</label>
                <input 
                    type="text" 
                    id="verify_token" 
                    name="verify_token" 
                    value="<?php echo htmlspecialchars($config['whatsapp_webhook_token'] ?? 'hotel_booking_webhook_2024'); ?>"
                >
                <p class="help-text">You can keep the default or change it (must match Meta configuration)</p>
            </div>
            
            <button type="submit" name="save_config" class="btn btn-primary">💾 Save Configuration</button>
            <a href="whatsapp_quick_start.php" class="btn btn-secondary">← Back</a>
        </form>
        
        <div class="webhook-info">
            <h3>🔗 Webhook Configuration</h3>
            <p>After saving your credentials, configure the webhook in Meta:</p>
            
            <div class="step">
                <span class="step-number">1</span>
                <strong>Webhook URL:</strong>
                <div class="code-box">http://108.175.12.152/manage/whatsapp_webhook.php?hotel_id=<?php echo $hotel_id; ?></div>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Verify Token:</strong>
                <div class="code-box"><?php echo htmlspecialchars($config['whatsapp_webhook_token'] ?? 'hotel_booking_webhook_2024'); ?></div>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Subscribe to webhook fields:</strong> messages
            </div>
        </div>
        
        <div class="steps">
            <h3 style="margin-bottom: 1rem;">📋 Setup Steps</h3>
            <div class="step">
                <span class="step-number">1</span>
                Go to <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                Select your WhatsApp Business app
            </div>
            <div class="step">
                <span class="step-number">3</span>
                Navigate to WhatsApp → API Setup
            </div>
            <div class="step">
                <span class="step-number">4</span>
                Copy the Access Token and Phone Number ID
            </div>
            <div class="step">
                <span class="step-number">5</span>
                Paste them in the form above and save
            </div>
            <div class="step">
                <span class="step-number">6</span>
                Configure the webhook URL in Meta using the information above
            </div>
        </div>
    </div>
</body>
</html>
