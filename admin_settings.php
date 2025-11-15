<?php
session_start();
require_once 'db_connection_pdo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'] ?? 'Admin';

// Create settings table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS aini_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        category VARCHAR(50),
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by INT,
        FOREIGN KEY (updated_by) REFERENCES ainitravel_users(id)
    )");
} catch (PDOException $e) {
    error_log("Settings table creation failed: " . $e->getMessage());
}

// Default settings
$default_settings = [
    // General
    ['key' => 'site_name', 'value' => 'AiniTravel', 'category' => 'general', 'description' => 'Site name'],
    ['key' => 'site_email', 'value' => 'info@ainitravel.com', 'category' => 'general', 'description' => 'Contact email'],
    ['key' => 'site_phone', 'value' => '', 'category' => 'general', 'description' => 'Contact phone'],
    ['key' => 'maintenance_mode', 'value' => '0', 'category' => 'general', 'description' => 'Enable maintenance mode'],
    
    // Booking
    ['key' => 'min_booking_days', 'value' => '1', 'category' => 'booking', 'description' => 'Minimum days in advance for booking'],
    ['key' => 'max_booking_days', 'value' => '365', 'category' => 'booking', 'description' => 'Maximum days in advance for booking'],
    ['key' => 'auto_confirm_bookings', 'value' => '0', 'category' => 'booking', 'description' => 'Auto-confirm bookings'],
    ['key' => 'cancellation_hours', 'value' => '24', 'category' => 'booking', 'description' => 'Free cancellation hours before experience'],
    
    // Payment
    ['key' => 'currency', 'value' => 'USD', 'category' => 'payment', 'description' => 'Default currency'],
    ['key' => 'stripe_enabled', 'value' => '0', 'category' => 'payment', 'description' => 'Enable Stripe payments'],
    ['key' => 'stripe_public_key', 'value' => '', 'category' => 'payment', 'description' => 'Stripe public key'],
    ['key' => 'stripe_secret_key', 'value' => '', 'category' => 'payment', 'description' => 'Stripe secret key'],
    ['key' => 'paypal_enabled', 'value' => '0', 'category' => 'payment', 'description' => 'Enable PayPal payments'],
    ['key' => 'paypal_client_id', 'value' => '', 'category' => 'payment', 'description' => 'PayPal client ID'],
    
    // Email
    ['key' => 'smtp_host', 'value' => '', 'category' => 'email', 'description' => 'SMTP host'],
    ['key' => 'smtp_port', 'value' => '587', 'category' => 'email', 'description' => 'SMTP port'],
    ['key' => 'smtp_username', 'value' => '', 'category' => 'email', 'description' => 'SMTP username'],
    ['key' => 'smtp_password', 'value' => '', 'category' => 'email', 'description' => 'SMTP password'],
    ['key' => 'email_from_name', 'value' => 'AiniTravel', 'category' => 'email', 'description' => 'Email from name'],
    ['key' => 'email_from_address', 'value' => 'noreply@ainitravel.com', 'category' => 'email', 'description' => 'Email from address'],
    
    // SEO
    ['key' => 'seo_title', 'value' => 'AiniTravel - Discover Amazing Experiences', 'category' => 'seo', 'description' => 'Meta title'],
    ['key' => 'seo_description', 'value' => 'Book unique travel experiences worldwide', 'category' => 'seo', 'description' => 'Meta description'],
    ['key' => 'seo_keywords', 'value' => 'travel, experiences, adventures, tours', 'category' => 'seo', 'description' => 'Meta keywords'],
    
    // Social
    ['key' => 'facebook_url', 'value' => '', 'category' => 'social', 'description' => 'Facebook page URL'],
    ['key' => 'instagram_url', 'value' => '', 'category' => 'social', 'description' => 'Instagram profile URL'],
    ['key' => 'twitter_url', 'value' => '', 'category' => 'social', 'description' => 'Twitter profile URL'],
];

// Initialize default settings
foreach ($default_settings as $setting) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO aini_settings (setting_key, setting_value, category, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$setting['key'], $setting['value'], $setting['category'], $setting['description']]);
    } catch (PDOException $e) {
        // Skip if already exists
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        foreach ($_POST as $key => $value) {
            if ($key === 'save_settings') continue;
            
            $sanitized_value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $stmt = $pdo->prepare("UPDATE aini_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?");
            $stmt->execute([$sanitized_value, $admin_id, $key]);
        }
        
        $success_message = "Settings saved successfully!";
    } catch (Exception $e) {
        $error_message = "Error saving settings: " . $e->getMessage();
    }
}

// Get all settings grouped by category
$settings = [];
$stmt = $pdo->query("SELECT * FROM aini_settings ORDER BY category, setting_key");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['category']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - AiniTravel Admin</title>
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
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
        }

        .back-btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            overflow-x: auto;
        }

        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .tab:hover {
            color: #667eea;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .settings-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .settings-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
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
            color: #555;
        }

        .form-group small {
            display: block;
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .save-button {
            position: sticky;
            bottom: 20px;
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
            transition: transform 0.2s;
            width: 100%;
            margin-top: 20px;
        }

        .save-button:hover {
            transform: translateY(-2px);
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .info-box strong {
            color: #1976d2;
            display: block;
            margin-bottom: 5px;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .warning-box strong {
            color: #856404;
            display: block;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ System Settings</h1>
            <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="tabs">
                <button type="button" class="tab active" onclick="switchTab('general')">🏢 General</button>
                <button type="button" class="tab" onclick="switchTab('booking')">📅 Booking</button>
                <button type="button" class="tab" onclick="switchTab('payment')">💳 Payment</button>
                <button type="button" class="tab" onclick="switchTab('email')">📧 Email</button>
                <button type="button" class="tab" onclick="switchTab('seo')">🔍 SEO</button>
                <button type="button" class="tab" onclick="switchTab('social')">📱 Social Media</button>
            </div>

            <!-- General Settings -->
            <div id="general" class="tab-content active">
                <div class="settings-section">
                    <h2>🏢 General Settings</h2>
                    
                    <div class="info-box">
                        <strong>ℹ️ Information</strong>
                        Configure basic site information and operational settings.
                    </div>

                    <?php if (isset($settings['general'])): ?>
                        <?php foreach ($settings['general'] as $setting): ?>
                            <div class="form-group">
                                <label for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                
                                <?php if ($setting['setting_key'] === 'maintenance_mode'): ?>
                                    <div class="checkbox-label">
                                        <input type="checkbox" 
                                               id="<?php echo $setting['setting_key']; ?>" 
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="1"
                                               <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                        <span>Enable maintenance mode (site will be unavailable to users)</span>
                                    </div>
                                <?php else: ?>
                                    <input type="text" 
                                           id="<?php echo $setting['setting_key']; ?>" 
                                           name="<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                                
                                <small><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Settings -->
            <div id="booking" class="tab-content">
                <div class="settings-section">
                    <h2>📅 Booking Settings</h2>
                    
                    <div class="info-box">
                        <strong>ℹ️ Information</strong>
                        Configure booking rules and restrictions.
                    </div>

                    <?php if (isset($settings['booking'])): ?>
                        <div class="form-grid">
                            <?php foreach ($settings['booking'] as $setting): ?>
                                <div class="form-group">
                                    <label for="<?php echo $setting['setting_key']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    </label>
                                    
                                    <?php if (strpos($setting['setting_key'], 'auto_') === 0): ?>
                                        <div class="checkbox-label">
                                            <input type="checkbox" 
                                                   id="<?php echo $setting['setting_key']; ?>" 
                                                   name="<?php echo $setting['setting_key']; ?>"
                                                   value="1"
                                                   <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($setting['description']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <input type="number" 
                                               id="<?php echo $setting['setting_key']; ?>" 
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <small><?php echo htmlspecialchars($setting['description']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Settings -->
            <div id="payment" class="tab-content">
                <div class="settings-section">
                    <h2>💳 Payment Settings</h2>
                    
                    <div class="warning-box">
                        <strong>⚠️ Warning</strong>
                        Payment gateway credentials are sensitive. Never share these keys publicly.
                    </div>

                    <?php if (isset($settings['payment'])): ?>
                        <?php foreach ($settings['payment'] as $setting): ?>
                            <div class="form-group">
                                <label for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                
                                <?php if (strpos($setting['setting_key'], 'enabled') !== false): ?>
                                    <div class="checkbox-label">
                                        <input type="checkbox" 
                                               id="<?php echo $setting['setting_key']; ?>" 
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="1"
                                               <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($setting['description']); ?></span>
                                    </div>
                                <?php elseif (strpos($setting['setting_key'], 'secret') !== false || strpos($setting['setting_key'], 'password') !== false): ?>
                                    <input type="password" 
                                           id="<?php echo $setting['setting_key']; ?>" 
                                           name="<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           placeholder="Enter secret key">
                                    <small><?php echo htmlspecialchars($setting['description']); ?></small>
                                <?php else: ?>
                                    <input type="text" 
                                           id="<?php echo $setting['setting_key']; ?>" 
                                           name="<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <small><?php echo htmlspecialchars($setting['description']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email Settings -->
            <div id="email" class="tab-content">
                <div class="settings-section">
                    <h2>📧 Email Settings</h2>
                    
                    <div class="info-box">
                        <strong>ℹ️ Information</strong>
                        Configure SMTP settings for sending emails. Use port 587 for TLS or 465 for SSL.
                    </div>

                    <?php if (isset($settings['email'])): ?>
                        <div class="form-grid">
                            <?php foreach ($settings['email'] as $setting): ?>
                                <div class="form-group">
                                    <label for="<?php echo $setting['setting_key']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    </label>
                                    
                                    <?php if (strpos($setting['setting_key'], 'password') !== false): ?>
                                        <input type="password" 
                                               id="<?php echo $setting['setting_key']; ?>" 
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                               placeholder="Enter SMTP password">
                                    <?php elseif (strpos($setting['setting_key'], 'port') !== false): ?>
                                        <input type="number" 
                                               id="<?php echo $setting['setting_key']; ?>" 
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <?php else: ?>
                                        <input type="text" 
                                               id="<?php echo $setting['setting_key']; ?>" 
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <?php endif; ?>
                                    
                                    <small><?php echo htmlspecialchars($setting['description']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SEO Settings -->
            <div id="seo" class="tab-content">
                <div class="settings-section">
                    <h2>🔍 SEO Settings</h2>
                    
                    <div class="info-box">
                        <strong>ℹ️ Information</strong>
                        Optimize your site for search engines. Keep titles under 60 characters and descriptions under 160 characters.
                    </div>

                    <?php if (isset($settings['seo'])): ?>
                        <?php foreach ($settings['seo'] as $setting): ?>
                            <div class="form-group">
                                <label for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                
                                <?php if (strpos($setting['setting_key'], 'description') !== false || strpos($setting['setting_key'], 'keywords') !== false): ?>
                                    <textarea id="<?php echo $setting['setting_key']; ?>" 
                                              name="<?php echo $setting['setting_key']; ?>"
                                              rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <?php else: ?>
                                    <input type="text" 
                                           id="<?php echo $setting['setting_key']; ?>" 
                                           name="<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                                
                                <small><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Social Media Settings -->
            <div id="social" class="tab-content">
                <div class="settings-section">
                    <h2>📱 Social Media Settings</h2>
                    
                    <div class="info-box">
                        <strong>ℹ️ Information</strong>
                        Add your social media profile URLs. These will appear in the site footer and contact pages.
                    </div>

                    <?php if (isset($settings['social'])): ?>
                        <?php foreach ($settings['social'] as $setting): ?>
                            <div class="form-group">
                                <label for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                <input type="url" 
                                       id="<?php echo $setting['setting_key']; ?>" 
                                       name="<?php echo $setting['setting_key']; ?>"
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       placeholder="https://">
                                <small><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" name="save_settings" class="save-button">
                💾 Save All Settings
            </button>
        </form>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Handle checkbox values for form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            // Set unchecked checkboxes to 0
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                if (!checkbox.checked) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = checkbox.name;
                    hidden.value = '0';
                    this.appendChild(hidden);
                }
            });
        });
    </script>
</body>
</html>
