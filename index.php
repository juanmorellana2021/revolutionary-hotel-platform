<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        $user = new User();
        $result = $user->register(
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['password']
        );
        
        if ($result['success']) {
            $loginResult = $user->login($_POST['email'], $_POST['password']);
            if ($loginResult['success']) {
                $_SESSION['user'] = $loginResult['user'];
                header('Location: dashboard.php');
                exit;
            }
        }
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'login') {
        $user = new User();
        $result = $user->login($_POST['email'], $_POST['password']);
        
        if ($result['success']) {
            // Don't override session - User->login() already sets the correct session variables
            
            // Check user role and redirect accordingly
            $userRole = $_SESSION['user_role'] ?? 'guest';
            
            // Role-based dashboard routing
            switch ($userRole) {
                case 'owner':
                case 'manager':
                case 'admin':
                    header('Location: manager_dashboard.php');
                    break;
                case 'employee':
                    header('Location: employee_dashboard.php');
                    break;
                case 'receptionist':
                    header('Location: receptionist_dashboard.php');
                    break;
                case 'investor':
                    header('Location: investor_dashboard.php');
                    break;
                case 'guest':
                default:
                    header('Location: dashboard.php');
                    break;
            }
            exit;
        }
        $message = $result['message'];
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking System - Login & Register</title>
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

        .container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
        }

        .tab-button {
            flex: 1;
            padding: 1rem;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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

        .features {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }

        .features h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .features ul {
            list-style: none;
            color: #666;
        }

        .features li {
            padding: 0.3rem 0;
            position: relative;
            padding-left: 1.5rem;
        }

        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 Hotel Booking System</h1>
            <p>Professional PHP & MySQL powered booking platform</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-tabs">
            <button class="tab-button active" onclick="showTab('login')">Login</button>
            <button class="tab-button" onclick="showTab('register')">Register</button>
        </div>

        <!-- Login Form -->
        <div id="login" class="form-container active">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login_email">Email Address</label>
                    <input type="email" id="login_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login to Dashboard</button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="register" class="form-container">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="register_email">Email Address</label>
                    <input type="email" id="register_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="register_password">Password</label>
                    <input type="password" id="register_password" name="password" required minlength="6">
                </div>
                
                <button type="submit" class="btn">Create Account</button>
            </form>
        </div>

        <div class="features">
            <h3>🚀 System Features:</h3>
            <ul>
                <li>PHP & MySQL Database Backend</li>
                <li>Secure Password Hashing</li>
                <li>Room Availability Checking</li>
                <li>Booking Management System</li>
                <li>User Dashboard & History</li>
                <li>Manager Portal & Hotel Setup</li>
            </ul>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: #e7f3ff; border-radius: 8px; border: 1px solid #b3d9ff;">
                <h4 style="color: #0366d6; margin-bottom: 0.5rem;">🔑 Manager Access</h4>
                <p style="font-size: 0.9rem; color: #586069; margin-bottom: 0.5rem;">
                    <strong>Email:</strong> manager@hotel.com<br>
                    <strong>Password:</strong> manager123
                </p>
                <p style="font-size: 0.85rem; color: #586069;">
                    Use these credentials to access the hotel management dashboard and configure your hotel settings.
                </p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all form containers
            document.querySelectorAll('.form-container').forEach(container => {
                container.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>