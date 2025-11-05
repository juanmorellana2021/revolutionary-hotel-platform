<?php
/**
 * AI Administration Panel - Revolutionary Hotel Platform
 * Manages Ollama AI configuration and model deployment
 */

require_once 'db_connection.php';
require_once 'includes/ollama_ai.php';

// Simple authentication - temporarily disabled for testing
session_start();
// Simulate logged in user for testing
$_SESSION['user_id'] = 1;

// Handle configuration updates
$message = '';
$messageType = '';

if ($_POST) {
    try {
        $config = [
            'ollama_url' => $_POST['ollama_url'] ?? 'http://localhost:11434',
            'ollama_model' => $_POST['ollama_model'] ?? 'phi3:mini',
            'ai_enabled' => isset($_POST['ai_enabled']) ? 1 : 0,
            'ai_server_type' => $_POST['ai_server_type'] ?? 'local'
        ];
        
        // Save configuration to database
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($config as $key => $value) {
            $stmt->bind_param('ss', $settingKey, $settingValue);
            $settingKey = 'ai_' . $key;
            $settingValue = $value;
            $stmt->execute();
        }
        $stmt->close();
        
        $message = "AI configuration updated successfully!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $message = "Error updating configuration: " . $e->getMessage();
        $messageType = "error";
    }
}

// Load current configuration
$currentConfig = [];
try {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ai_%'");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $key = str_replace('ai_', '', $row['setting_key']);
        $currentConfig[$key] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // If there's an error, use default config
    $currentConfig = [
        'ollama_url' => 'http://localhost:11434',
        'ollama_model' => 'gemma3:1b',
        'ai_enabled' => 1,
        'ai_server_type' => 'local'
    ];
}

// Test Ollama connection (skip for faster loading)
$ollamaStatus = 'Ready';
$ollamaDetails = 'Click "Test Connection" button to check AI status';

// Only test connection if specifically requested
if (isset($_GET['test_connection'])) {
    try {
        $ai = new OllamaAI('localhost', 11434, 'gemma3:1b');
        $testResponse = $ai->chat("Hello", null, ['context' => 'test']);
        $ollamaStatus = 'Connected';
        $ollamaDetails = 'Successfully connected to Ollama API with Gemma3:1b model';
    } catch (Exception $e) {
        $ollamaStatus = 'Disconnected';
        $ollamaDetails = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Administration - Revolutionary Hotel Platform</title>
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            border-left: 5px solid #667eea;
        }
        
        .status-connected {
            border-left-color: #28a745;
        }
        
        .status-disconnected {
            border-left-color: #dc3545;
        }
        
        .status-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-details {
            color: #666;
            line-height: 1.6;
        }
        
        .config-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
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
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .resource-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .resource-warning h4 {
            margin-bottom: 10px;
        }
        
        .deployment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .deployment-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .deployment-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .deployment-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 AI Administration</h1>
            <p>Configure and manage your revolutionary AI-powered hotel assistant</p>
        </div>
        
        <div class="content">
            <div class="back-btn">
                <a href="manager_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Resource Warning -->
            <div class="resource-warning">
                <h4>⚠️ Important: AI Resource Requirements</h4>
                <p><strong>AI models require significant system resources:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>RAM:</strong> 4-8GB minimum for small models (Phi3, TinyLlama)</li>
                    <li><strong>RAM:</strong> 16-32GB for larger models (Llama 3.2)</li>
                    <li><strong>Storage:</strong> 1-10GB per model</li>
                    <li><strong>CPU:</strong> Modern multi-core processor recommended</li>
                </ul>
                <p><strong>Recommendation:</strong> Consider using a separate dedicated server for AI workloads to ensure optimal performance of your main hotel management system.</p>
            </div>
            
            <!-- Status Overview -->
            <div class="status-grid">
                <div class="status-card <?php echo $ollamaStatus === 'Connected' ? 'status-connected' : 'status-disconnected'; ?>">
                    <div class="status-title">
                        <?php echo $ollamaStatus === 'Connected' ? '✅' : '❌'; ?>
                        Ollama AI Status
                    </div>
                    <div class="status-details">
                        <strong>Status:</strong> <?php echo $ollamaStatus; ?><br>
                        <strong>Details:</strong> <?php echo htmlspecialchars($ollamaDetails); ?><br><br>
                        <a href="?test_connection=1" class="btn" style="font-size: 0.9rem; padding: 8px 15px;">🧪 Test Connection</a>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-title">
                        📊 Current Model
                    </div>
                    <div class="status-details">
                        <strong>Model:</strong> <?php echo htmlspecialchars($currentConfig['ollama_model'] ?? 'phi3:mini'); ?><br>
                        <strong>Server:</strong> <?php echo htmlspecialchars($currentConfig['ollama_url'] ?? 'http://localhost:11434'); ?><br>
                        <strong>Status:</strong> <?php echo isset($currentConfig['ai_enabled']) && $currentConfig['ai_enabled'] ? 'Enabled' : 'Disabled'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Configuration Form -->
            <form method="POST">
                <!-- Server Deployment Options -->
                <div class="config-section">
                    <h3 class="section-title">🖥️ AI Server Deployment</h3>
                    
                    <div class="deployment-options">
                        <div class="deployment-card <?php echo ($currentConfig['ai_server_type'] ?? 'local') === 'local' ? 'selected' : ''; ?>" onclick="selectDeployment('local')">
                            <h4>🏠 Local Server</h4>
                            <p>Run AI on this machine</p>
                            <small>Good for testing, requires 4-16GB RAM</small>
                        </div>
                        
                        <div class="deployment-card <?php echo ($currentConfig['ai_server_type'] ?? 'local') === 'dedicated' ? 'selected' : ''; ?>" onclick="selectDeployment('dedicated')">
                            <h4>🚀 Dedicated Server</h4>
                            <p>Separate AI server</p>
                            <small>Recommended for production</small>
                        </div>
                        
                        <div class="deployment-card <?php echo ($currentConfig['ai_server_type'] ?? 'local') === 'cloud' ? 'selected' : ''; ?>" onclick="selectDeployment('cloud')">
                            <h4>☁️ Cloud AI</h4>
                            <p>External AI service</p>
                            <small>Scalable, pay-per-use</small>
                        </div>
                    </div>
                    
                    <input type="hidden" name="ai_server_type" id="ai_server_type" value="<?php echo htmlspecialchars($currentConfig['ai_server_type'] ?? 'local'); ?>">
                </div>
                
                <!-- Ollama Configuration -->
                <div class="config-section">
                    <h3 class="section-title">⚙️ Ollama Configuration</h3>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="ai_enabled" id="ai_enabled" <?php echo isset($currentConfig['ai_enabled']) && $currentConfig['ai_enabled'] ? 'checked' : ''; ?>>
                        <label for="ai_enabled">Enable AI Features</label>
                    </div>
                    
                    <div class="form-group">
                        <label for="ollama_url">Ollama Server URL</label>
                        <input type="url" name="ollama_url" id="ollama_url" 
                               value="<?php echo htmlspecialchars($currentConfig['ollama_url'] ?? 'http://localhost:11434'); ?>"
                               placeholder="http://localhost:11434">
                    </div>
                    
                    <div class="form-group">
                        <label for="ollama_model">AI Model</label>
                        <select name="ollama_model" id="ollama_model">
                            <option value="gemma3:1b" <?php echo ($currentConfig['ollama_model'] ?? 'gemma3:1b') === 'gemma3:1b' ? 'selected' : ''; ?>>Gemma3 1B (Available - Working!) ✅</option>
                            <option value="gemma3:4b" <?php echo ($currentConfig['ollama_model'] ?? '') === 'gemma3:4b' ? 'selected' : ''; ?>>Gemma3 4B (Needs 4.3GB RAM) ⚠️</option>
                            <option value="gpt-oss:20b" <?php echo ($currentConfig['ollama_model'] ?? '') === 'gpt-oss:20b' ? 'selected' : ''; ?>>GPT-OSS 20B (Needs 16GB+ RAM) ⚠️</option>
                            <option value="tinyllama" <?php echo ($currentConfig['ollama_model'] ?? '') === 'tinyllama' ? 'selected' : ''; ?>>TinyLlama (1GB RAM - Fastest)</option>
                            <option value="phi3:mini" <?php echo ($currentConfig['ollama_model'] ?? '') === 'phi3:mini' ? 'selected' : ''; ?>>Phi3 Mini (4GB RAM - Recommended)</option>
                            <option value="qwen2:1.5b" <?php echo ($currentConfig['ollama_model'] ?? '') === 'qwen2:1.5b' ? 'selected' : ''; ?>>Qwen2 1.5B (3GB RAM - Good balance)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Installation Instructions -->
                <div class="config-section">
                    <h3 class="section-title">📋 Installation Instructions</h3>
                    
                    <div id="local-instructions" style="display: <?php echo ($currentConfig['ai_server_type'] ?? 'local') === 'local' ? 'block' : 'none'; ?>;">
                        <h4>🏠 Local Installation:</h4>
                        <ol style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                            <li>Download Ollama from <strong>ollama.ai</strong></li>
                            <li>Install and run: <code style="background: #f1f3f4; padding: 2px 6px; border-radius: 4px;">ollama serve</code></li>
                            <li>Pull your chosen model: <code style="background: #f1f3f4; padding: 2px 6px; border-radius: 4px;">ollama pull phi3:mini</code></li>
                            <li>Verify installation by testing the connection above</li>
                        </ol>
                    </div>
                    
                    <div id="dedicated-instructions" style="display: <?php echo ($currentConfig['ai_server_type'] ?? 'local') === 'dedicated' ? 'block' : 'none'; ?>;">
                        <h4>🚀 Dedicated Server Setup:</h4>
                        <ol style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                            <li>Set up a separate server with at least 8GB RAM</li>
                            <li>Install Ollama on the dedicated server</li>
                            <li>Configure firewall to allow access on port 11434</li>
                            <li>Update the Ollama URL above to point to your server</li>
                            <li>Consider using Docker for easier deployment</li>
                        </ol>
                    </div>
                    
                    <div id="cloud-instructions" style="display: <?php echo ($currentConfig['ai_server_type'] ?? 'local') === 'cloud' ? 'block' : 'none'; ?>;">
                        <h4>☁️ Cloud AI Setup:</h4>
                        <ol style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                            <li>Consider services like OpenAI, Google AI, or Azure Cognitive Services</li>
                            <li>Set up API keys and endpoints</li>
                            <li>Modify the AI integration to use cloud APIs</li>
                            <li>Benefits: No local resources, better scalability</li>
                            <li>Considerations: Ongoing costs, internet dependency</li>
                        </ol>
                    </div>
                </div>
                
                <button type="submit" class="btn">💾 Save Configuration</button>
            </form>
        </div>
    </div>
    
    <script>
        function selectDeployment(type) {
            // Update visual selection
            document.querySelectorAll('.deployment-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Update hidden input
            document.getElementById('ai_server_type').value = type;
            
            // Show/hide instructions
            document.getElementById('local-instructions').style.display = type === 'local' ? 'block' : 'none';
            document.getElementById('dedicated-instructions').style.display = type === 'dedicated' ? 'block' : 'none';
            document.getElementById('cloud-instructions').style.display = type === 'cloud' ? 'block' : 'none';
        }
    </script>
</body>
</html>