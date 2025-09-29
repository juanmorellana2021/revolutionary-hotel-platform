<?php
session_start();
require_once 'includes/classes.php';

$message = '';
$messageType = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $termsAccepted = isset($_POST['terms_accepted']) && $_POST['terms_accepted'] === 'on';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } elseif (!$termsAccepted) {
        $message = 'You must accept the Terms & Conditions to register.';
        $messageType = 'error';
    } else {
        // Register user
        $user = new User();
        $result = $user->register($firstName, $lastName, $email, $password, $termsAccepted, '1.0');
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            // Redirect to login or dashboard after successful registration
            header('Location: calendar_view.php?registered=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Revolutionary Hotel Platform</title>
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
            padding: 20px;
        }
        
        .registration-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .terms-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #e9ecef;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .terms-checkbox input[type="checkbox"] {
            margin-top: 4px;
            transform: scale(1.3);
        }
        
        .terms-checkbox label {
            margin-bottom: 0;
            line-height: 1.5;
            font-size: 0.95em;
        }
        
        .terms-link {
            color: #007bff;
            text-decoration: underline;
        }
        
        .terms-link:hover {
            color: #0056b3;
        }
        
        .warning-text {
            font-size: 0.85em;
            color: #6c757d;
            padding-left: 35px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="header">
            <h1>🏨 User Registration</h1>
            <p>Join the Revolutionary Hotel Platform</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registrationForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">👤 First Name</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">👤 Last Name</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">📧 Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">🔒 Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">🔒 Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <div class="terms-section">
                <div class="terms-checkbox">
                    <input type="checkbox" id="terms_accepted" name="terms_accepted" required>
                    <label for="terms_accepted">
                        <strong>📋 Terms & Conditions Agreement:</strong> I acknowledge that I have read, understood, and agree to be legally bound by the 
                        <a href="terms_and_conditions.php" target="_blank" class="terms-link">
                            Terms & Conditions, Guest Responsibility Agreement, Property Damage & Reputation Protection Policy
                        </a>. 
                        I accept full financial responsibility for any damages to hotel property during my stays, agree to maintain appropriate behavior standards, and acknowledge legal liability for false or defamatory reviews that damage the hotel's reputation.
                    </label>
                </div>
                <div class="warning-text">
                    ⚠️ <strong>Important:</strong> By checking this box, you confirm your legal agreement to all hotel policies including damage liability and guest conduct standards.
                </div>
            </div>
            
            <button type="submit" name="register" class="btn" id="registerBtn" disabled>
                🚀 Create Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="calendar_view.php">Go to Calendar</a>
        </div>
    </div>
    
    <script>
        // Enable/disable register button based on terms acceptance
        document.getElementById('terms_accepted').addEventListener('change', function() {
            const registerBtn = document.getElementById('registerBtn');
            if (this.checked) {
                registerBtn.disabled = false;
                registerBtn.style.opacity = '1';
            } else {
                registerBtn.disabled = true;
                registerBtn.style.opacity = '0.5';
            }
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>