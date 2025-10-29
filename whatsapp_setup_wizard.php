<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Check if user is logged in (support both session formats)
$isLoggedIn = isset($_SESSION['user']) || isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// Get user ID and role (support both session formats)
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? 'guest';

// Check if user is manager/admin
if ($userRole !== 'manager' && $userRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once 'db_connection.php';
$connection = $conn;

// Get current step from URL or default to 1
$currentStep = isset($_GET['step']) ? intval($_GET['step']) : 1;
$maxSteps = 5;

// Handle form submissions
$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['save_step_1'])) {
        // Save basic information
        $configs = [
            'hotel_name' => $_POST['hotel_name'] ?? '',
            'hotel_phone' => $_POST['hotel_phone'] ?? '',
            'hotel_email' => $_POST['hotel_email'] ?? '',
            'hotel_address' => $_POST['hotel_address'] ?? ''
        ];
        
        foreach ($configs as $key => $value) {
            $stmt = $connection->prepare("
                INSERT INTO ai_chat_config (config_key, config_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
            $stmt->close();
        }
        
        header('Location: whatsapp_setup_wizard.php?step=2');
        exit;
    }
    
    if (isset($_POST['save_step_2'])) {
        // Save WhatsApp Business credentials
        $configs = [
            'whatsapp_access_token' => $_POST['access_token'] ?? '',
            'whatsapp_phone_number_id' => $_POST['phone_number_id'] ?? '',
            'whatsapp_business_phone' => $_POST['business_phone'] ?? '',
            'whatsapp_verify_token' => $_POST['verify_token'] ?? ''
        ];
        
        foreach ($configs as $key => $value) {
            $stmt = $connection->prepare("
                INSERT INTO ai_chat_config (config_key, config_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        header('Location: whatsapp_setup_wizard.php?step=3');
        exit;
    }
    
    if (isset($_POST['save_step_3'])) {
        // Save AI configuration
        $configs = [
            'ollama_url' => $_POST['ollama_url'] ?? 'http://localhost:11434',
            'ollama_model' => $_POST['ollama_model'] ?? 'phi3:mini',
            'ai_enabled' => isset($_POST['ai_enabled']) ? '1' : '0'
        ];
        
        foreach ($configs as $key => $value) {
            $stmt = $connection->prepare("
                INSERT INTO ai_chat_config (config_key, config_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        header('Location: whatsapp_setup_wizard.php?step=4');
        exit;
    }
    
    if (isset($_POST['test_connection'])) {
        // Test WhatsApp connection
        $accessToken = $_POST['test_access_token'] ?? '';
        $phoneNumberId = $_POST['test_phone_number_id'] ?? '';
        
        if (!empty($accessToken) && !empty($phoneNumberId)) {
            // Simple API test
            $testUrl = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";
            $testData = json_encode([
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumberId, // Self-test
                'type' => 'text',
                'text' => ['body' => 'Test connection from Revolutionary Hotel Platform']
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $message = "✅ WhatsApp connection successful! API is working properly.";
            } else {
                $error = "❌ Connection failed. Please check your credentials. Response: " . $response;
            }
        } else {
            $error = "Please enter both Access Token and Phone Number ID to test.";
        }
    }
    
    if (isset($_POST['complete_setup'])) {
        // Enable WhatsApp and mark setup as complete
        $stmt = $connection->prepare("
            INSERT INTO ai_chat_config (config_key, config_value) 
            VALUES ('whatsapp_enabled', '1'), ('setup_completed', '1')
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
        ");
        $stmt->execute();
        
        header('Location: whatsapp_management.php?setup_complete=1');
        exit;
    }
}

// Get existing configuration
$stmt = $connection->prepare("SELECT config_key, config_value FROM ai_chat_config");
$stmt->execute();
$result = $stmt->get_result();
$config = [];
while ($row = $result->fetch_assoc()) {
    $config[$row['config_key']] = $row['config_value'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Setup Wizard - Revolutionary Hotel Platform</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .wizard-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .wizard-header {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .wizard-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s;
        }

        .step.active {
            background: white;
            color: #25D366;
            transform: scale(1.2);
        }

        .step.completed {
            background: rgba(255,255,255,0.8);
            color: #25D366;
        }

        .step.pending {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        .wizard-content {
            padding: 3rem;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .step-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-description {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #25D366;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .info-box h4 {
            color: #25D366;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-box ol {
            margin-left: 1.5rem;
        }

        .info-box li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-test {
            background: #17a2b8;
            color: white;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 1rem 0;
            overflow-x: auto;
        }

        .highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .success-animation {
            text-align: center;
            padding: 3rem;
        }

        .success-icon {
            font-size: 5rem;
            color: #25D366;
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <div class="wizard-title">🚀 WhatsApp Setup Wizard</div>
            <p>Set up your Revolutionary Hotel Platform with AI-powered WhatsApp booking in 5 easy steps</p>
            
            <div class="step-indicator">
                <?php for ($i = 1; $i <= $maxSteps; $i++): ?>
                    <div class="step <?php 
                        if ($i < $currentStep) echo 'completed';
                        elseif ($i == $currentStep) echo 'active';
                        else echo 'pending';
                    ?>">
                        <?php echo $i; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="wizard-content">
            <?php if ($message): ?>
                <div class="alert success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Step 1: Hotel Information -->
            <div class="step-content <?php echo $currentStep == 1 ? 'active' : ''; ?>">
                <div class="step-title">🏨 Hotel Information</div>
                <div class="step-description">
                    Let's start by setting up your hotel's basic information. This will be used in WhatsApp messages and AI responses.
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Hotel Name</label>
                        <input type="text" name="hotel_name" 
                               value="<?php echo htmlspecialchars($config['hotel_name'] ?? ''); ?>"
                               placeholder="Grand Paradise Hotel" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Hotel Phone</label>
                            <input type="tel" name="hotel_phone" 
                                   value="<?php echo htmlspecialchars($config['hotel_phone'] ?? ''); ?>"
                                   placeholder="+1 (555) 123-4567">
                        </div>
                        
                        <div class="form-group">
                            <label>Hotel Email</label>
                            <input type="email" name="hotel_email" 
                                   value="<?php echo htmlspecialchars($config['hotel_email'] ?? ''); ?>"
                                   placeholder="reservations@grandparadise.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Hotel Address</label>
                        <textarea name="hotel_address" rows="3" 
                                  placeholder="123 Paradise Boulevard, Miami Beach, FL 33139"><?php echo htmlspecialchars($config['hotel_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <a href="manager_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                        <button type="submit" name="save_step_1" class="btn btn-primary">Next Step →</button>
                    </div>
                </form>
            </div>

            <!-- Step 2: WhatsApp Business API -->
            <div class="step-content <?php echo $currentStep == 2 ? 'active' : ''; ?>">
                <div class="step-title">📱 WhatsApp Business API</div>
                <div class="step-description">
                    Connect your WhatsApp Business API to enable revolutionary booking through WhatsApp chat.
                </div>
                
                <div class="info-box">
                    <h4>📋 How to Get WhatsApp Business API Credentials:</h4>
                    <ol>
                        <li>Go to <strong>developers.facebook.com</strong> and create a Meta app</li>
                        <li>Add <strong>WhatsApp</strong> product to your app</li>
                        <li>In WhatsApp section, go to <strong>API Setup</strong></li>
                        <li>Copy your <strong>Phone Number ID</strong> and <strong>Access Token</strong></li>
                        <li>Set up your webhook URL: <code>https://yourdomain.com/hotel-booking-system/whatsapp_webhook.php</code></li>
                    </ol>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Access Token</label>
                        <input type="password" name="access_token" 
                               value="<?php echo htmlspecialchars($config['whatsapp_access_token'] ?? ''); ?>"
                               placeholder="EAAxxxxxxxxxxxxxxxxx">
                        <small style="color: #666;">Your permanent access token from Meta Business</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number ID</label>
                            <input type="text" name="phone_number_id" 
                                   value="<?php echo htmlspecialchars($config['whatsapp_phone_number_id'] ?? ''); ?>"
                                   placeholder="123456789012345">
                        </div>
                        
                        <div class="form-group">
                            <label>Business Phone</label>
                            <input type="text" name="business_phone" 
                                   value="<?php echo htmlspecialchars($config['whatsapp_business_phone'] ?? ''); ?>"
                                   placeholder="+1234567890">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Webhook Verify Token</label>
                        <input type="text" name="verify_token" 
                               value="<?php echo htmlspecialchars($config['whatsapp_verify_token'] ?? ''); ?>"
                               placeholder="your_secure_verify_token">
                        <small style="color: #666;">Create a secure token for webhook verification</small>
                    </div>
                    
                    <div class="highlight">
                        <strong>Webhook URL:</strong><br>
                        <code>https://yourdomain.com/hotel-booking-system/whatsapp_webhook.php</code><br>
                        <small>Use this URL in your Meta app webhook configuration</small>
                    </div>
                    
                    <div class="btn-group">
                        <a href="whatsapp_setup_wizard.php?step=1" class="btn btn-secondary">← Previous</a>
                        <button type="submit" name="save_step_2" class="btn btn-primary">Next Step →</button>
                    </div>
                </form>
            </div>

            <!-- Step 3: AI Configuration -->
            <div class="step-content <?php echo $currentStep == 3 ? 'active' : ''; ?>">
                <div class="step-title">🤖 AI Configuration</div>
                <div class="step-description">
                    Configure the AI engine that will power intelligent conversations with your guests.
                </div>
                
                <div class="info-box">
                    <h4>🧠 Setting up Ollama AI:</h4>
                    <ol>
                        <li>Download and install <strong>Ollama</strong> from ollama.ai</li>
                        <li>Run: <code>ollama serve</code> to start the server</li>
                        <li>Install a model: <code>ollama pull phi3:mini</code></li>
                        <li>Test: <code>curl http://localhost:11434/api/generate</code></li>
                    </ol>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Ollama Server URL</label>
                        <input type="url" name="ollama_url" 
                               value="<?php echo htmlspecialchars($config['ollama_url'] ?? 'http://localhost:11434'); ?>"
                               placeholder="http://localhost:11434">
                    </div>
                    
                    <div class="form-group">
                        <label>AI Model</label>
                        <select name="ollama_model">
                            <option value="phi3:mini" <?php echo ($config['ollama_model'] ?? '') === 'phi3:mini' ? 'selected' : ''; ?>>Phi3 Mini (Recommended)</option>
                            <option value="llama3.2:3b" <?php echo ($config['ollama_model'] ?? '') === 'llama3.2:3b' ? 'selected' : ''; ?>>Llama 3.2 3B</option>
                            <option value="qwen2:1.5b" <?php echo ($config['ollama_model'] ?? '') === 'qwen2:1.5b' ? 'selected' : ''; ?>>Qwen2 1.5B</option>
                            <option value="tinyllama" <?php echo ($config['ollama_model'] ?? '') === 'tinyllama' ? 'selected' : ''; ?>>TinyLlama (Fastest)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="ai_enabled" 
                                   <?php echo ($config['ai_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            Enable AI-Powered Responses
                        </label>
                        <small style="color: #666;">AI will provide intelligent, context-aware responses to guest inquiries</small>
                    </div>
                    
                    <div class="btn-group">
                        <a href="whatsapp_setup_wizard.php?step=2" class="btn btn-secondary">← Previous</a>
                        <button type="submit" name="save_step_3" class="btn btn-primary">Next Step →</button>
                    </div>
                </form>
            </div>

            <!-- Step 4: Test Connection -->
            <div class="step-content <?php echo $currentStep == 4 ? 'active' : ''; ?>">
                <div class="step-title">🧪 Test Your Setup</div>
                <div class="step-description">
                    Let's test your WhatsApp and AI configuration to make sure everything is working perfectly.
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Access Token</label>
                            <input type="password" name="test_access_token" 
                                   value="<?php echo htmlspecialchars($config['whatsapp_access_token'] ?? ''); ?>"
                                   placeholder="Your WhatsApp access token">
                        </div>
                        
                        <div class="form-group">
                            <label>Test Phone Number ID</label>
                            <input type="text" name="test_phone_number_id" 
                                   value="<?php echo htmlspecialchars($config['whatsapp_phone_number_id'] ?? ''); ?>"
                                   placeholder="Your phone number ID">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="test_connection" class="btn btn-test">🧪 Test WhatsApp Connection</button>
                    </div>
                </form>
                
                <div class="info-box">
                    <h4>✅ Pre-Launch Checklist:</h4>
                    <ol>
                        <li><strong>WhatsApp Business API</strong> - Credentials configured</li>
                        <li><strong>Webhook URL</strong> - Set up in Meta app settings</li>
                        <li><strong>SSL Certificate</strong> - Required for webhook</li>
                        <li><strong>Ollama Server</strong> - Running and accessible</li>
                        <li><strong>Database</strong> - All tables created</li>
                        <li><strong>Phone Verification</strong> - Business number verified</li>
                    </ol>
                </div>
                
                <div class="btn-group">
                    <a href="whatsapp_setup_wizard.php?step=3" class="btn btn-secondary">← Previous</a>
                    <a href="whatsapp_setup_wizard.php?step=5" class="btn btn-primary">Complete Setup →</a>
                </div>
            </div>

            <!-- Step 5: Completion -->
            <div class="step-content <?php echo $currentStep == 5 ? 'active' : ''; ?>">
                <div class="success-animation">
                    <div class="success-icon">🎉</div>
                    <div class="step-title">Congratulations! Setup Complete</div>
                    <div class="step-description">
                        Your Revolutionary Hotel Platform with AI-powered WhatsApp booking is now ready to transform your guest experience!
                    </div>
                </div>
                
                <div class="info-box">
                    <h4>🚀 What You've Accomplished:</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ <strong>World's First</strong> WhatsApp-native hotel booking system</li>
                        <li>✅ <strong>AI-powered</strong> guest communication and support</li>
                        <li>✅ <strong>HotelCoin integration</strong> for digital rewards</li>
                        <li>✅ <strong>Complete hotel management</strong> platform</li>
                        <li>✅ <strong>Revolutionary guest experience</strong> that no competitor has</li>
                    </ul>
                </div>
                
                <div class="highlight">
                    <strong>🌟 Your Platform Features:</strong><br>
                    • Guests can book rooms through WhatsApp chat<br>
                    • AI provides instant, intelligent responses 24/7<br>
                    • HotelCoins earned with every booking<br>
                    • Complete analytics and management dashboard<br>
                    • Multi-language support and advanced automation
                </div>
                
                <form method="POST">
                    <div class="btn-group">
                        <a href="whatsapp_setup_wizard.php?step=4" class="btn btn-secondary">← Previous</a>
                        <button type="submit" name="complete_setup" class="btn btn-primary">🚀 Launch Platform!</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-advance steps after successful saves
        <?php if ($currentStep < $maxSteps && !$error): ?>
        // Add any JavaScript for enhanced UX
        <?php endif; ?>
    </script>
</body>
</html>