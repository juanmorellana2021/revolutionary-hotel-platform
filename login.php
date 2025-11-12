<?php
session_start();
require_once 'db_connection_pdo.php';

// Configuration constants
define('REMEMBER_ME_DAYS', 30);
define('LOCKOUT_MINUTES', 15);
define('MAX_LOGIN_ATTEMPTS', 3);
define('ATTEMPT_WINDOW_SECONDS', 900); // 15 minutes
define('WELCOME_BONUS_COINS', 50);
define('MIN_PASSWORD_LENGTH', 6);

// Auto-login from "Remember Me" cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
    $userId = filter_var($_COOKIE['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['aini_coins'] = $user['aini_coins'];
    }
}

// If already logged in, redirect to booking page
if (isset($_SESSION['user_id'])) {
    header('Location: public_booking.php');
    exit();
}

$error = '';
$success = '';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF validation function
function validateCSRFToken() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Rate Limiter Class
class RateLimiter {
    private $maxAttempts;
    private $windowSeconds;
    private $lockoutMinutes;
    
    public function __construct($maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutMinutes = LOCKOUT_MINUTES) {
        $this->maxAttempts = $maxAttempts;
        $this->lockoutMinutes = $lockoutMinutes;
        $this->windowSeconds = ATTEMPT_WINDOW_SECONDS;
    }
    
    private function getLockFile($identifier) {
        return sys_get_temp_dir() . '/login_' . md5($identifier) . '.lock';
    }
    
    public function check($identifier) {
        $lockfile = $this->getLockFile($identifier);
        
        if (file_exists($lockfile)) {
            $data = json_decode(file_get_contents($lockfile), true);
            $attempts = $data['attempts'] ?? 0;
            $lockout_until = $data['lockout_until'] ?? 0;
            
            // Check if still locked out
            if ($lockout_until > time()) {
                $minutes_left = ceil(($lockout_until - time()) / 60);
                return ['locked' => true, 'minutes' => $minutes_left];
            }
            
            // Check if too many attempts
            if ($attempts >= $this->maxAttempts && ($data['first_attempt'] ?? 0) > time() - $this->windowSeconds) {
                $lockout_until = time() + ($this->lockoutMinutes * 60);
                file_put_contents($lockfile, json_encode([
                    'attempts' => $attempts,
                    'lockout_until' => $lockout_until,
                    'first_attempt' => $data['first_attempt']
                ]));
                return ['locked' => true, 'minutes' => $this->lockoutMinutes];
            }
        }
        
        return ['locked' => false];
    }
    
    public function recordFailedAttempt($identifier) {
        $lockfile = $this->getLockFile($identifier);
        
        $data = ['attempts' => 1, 'first_attempt' => time(), 'lockout_until' => 0];
        if (file_exists($lockfile)) {
            $existing = json_decode(file_get_contents($lockfile), true);
            $data['attempts'] = ($existing['attempts'] ?? 0) + 1;
            $data['first_attempt'] = $existing['first_attempt'] ?? time();
        }
        
        file_put_contents($lockfile, json_encode($data));
    }
    
    public function clearAttempts($identifier) {
        $lockfile = $this->getLockFile($identifier);
        if (file_exists($lockfile)) {
            unlink($lockfile);
        }
    }
}

// Legacy rate limiting functions (for backward compatibility)
function checkRateLimit($identifier) {
    $rateLimiter = new RateLimiter();
    return $rateLimiter->check($identifier);
}

function recordFailedAttempt($identifier) {
    $rateLimiter = new RateLimiter();
    $rateLimiter->recordFailedAttempt($identifier);
}

function clearFailedAttempts($identifier) {
    $rateLimiter = new RateLimiter();
    $rateLimiter->clearAttempts($identifier);
}

// Authentication helper functions
function authenticateUser($email, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['aini_coins'] = $user['aini_coins'];
    session_regenerate_id(true);
}

function setRememberMeCookie($userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (REMEMBER_ME_DAYS * 24 * 60 * 60);
    setcookie('remember_token', $token, $expiry, '/', '', false, true);
    setcookie('user_id', $userId, $expiry, '/', '', false, true);
}

function updateLastLogin($userId, $pdo) {
    $stmt = $pdo->prepare("UPDATE ainitravel_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

function getPMSUserRole($email, $pdo) {
    $stmt = $pdo->prepare("SELECT id, user_role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function redirectByRole($pmsUser) {
    if (!$pmsUser) {
        header('Location: public_booking.php');
        exit();
    }
    
    $_SESSION['pms_user_id'] = $pmsUser['id'];
    $_SESSION['user_role'] = $pmsUser['user_role'] ?? 'guest';
    
    $location = ($pmsUser['user_role'] === 'admin' || $pmsUser['user_role'] === 'manager') 
        ? 'dashboard_current.php' 
        : 'public_booking.php';
    
    header("Location: $location");
    exit();
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Verify CSRF token
    if (!validateCSRFToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        // Check rate limiting
        $rateLimitCheck = checkRateLimit($email);
        if ($rateLimitCheck['locked']) {
            $error = 'Too many failed attempts. Please try again in ' . $rateLimitCheck['minutes'] . ' minutes.';
        } else {
            $user = authenticateUser($email, $password, $pdo);
            
            if ($user) {
                // Successful login
                clearFailedAttempts($email);
                setUserSession($user);
                
                if (isset($_POST['remember_me'])) {
                    setRememberMeCookie($user['id']);
                }
                
                updateLastLogin($user['id'], $pdo);
                
                // Redirect based on PMS role
                $pmsUser = getPMSUserRole($email, $pdo);
                redirectByRole($pmsUser);
            } else {
                // Failed login
                recordFailedAttempt($email);
                $error = 'Invalid credentials. Please check your email and password.';
            }
        }
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Verify CSRF token
    if (!validateCSRFToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $name = htmlspecialchars($_POST['name']);
        $email = filter_var($_POST['reg_email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $error = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM ainitravel_users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            // Create user with welcome bonus of AiNi Coins
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO ainitravel_users (name, email, password, aini_coins, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$name, $email, $hashed_password, WELCOME_BONUS_COINS])) {
                $success = 'Account created successfully! You received ' . WELCOME_BONUS_COINS . ' AiNi Coins as welcome bonus! Please login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AiNi Travel</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2'
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        
        .tab-button {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #9ca3af;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .divider span {
            padding: 0 1rem;
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .social-btn {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .social-btn:hover {
            border-color: #667eea;
            background: #f9fafb;
        }
        
        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        /* Password Strength Meter */
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }
        
        /* Loading Spinner */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Header -->
        <div class="text-center p-6 bg-gradient-to-r from-primary to-secondary text-white">
            <h1 class="text-3xl font-bold mb-2">🪙 AiNi Travel</h1>
            <p class="text-white/90">Welcome! Login or create your account</p>
        </div>
        
        <!-- Tabs -->
        <div class="flex border-b">
            <button class="tab-button active" onclick="showTab('login')">Login</button>
            <button class="tab-button" onclick="showTab('register')">Register</button>
        </div>
        
        <!-- Login Form -->
        <div id="loginTab" class="tab-content active p-6">
            <?php if ($error && isset($_POST['login'])): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="input-group">
                    <label for="email">📧 Email</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>
                </div>
                
                <div class="input-group">
                    <label for="password">🔒 Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember_me" id="remember_me" class="cursor-pointer">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-primary hover:underline" onclick="alert('Password reset coming soon!'); return false;">Forgot password?</a>
                </div>
                
                <button type="submit" name="login" class="btn-primary" onclick="showLoading(this)">
                    Login to Your Account
                </button>
            </form>
            
            <div class="divider">
                <span>or continue with</span>
            </div>
            
            <div class="social-login">
                <button class="social-btn" onclick="alert('Google login coming soon!')">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Google
                </button>
                <button class="social-btn" onclick="alert('Facebook login coming soon!')">
                    <svg class="w-5 h-5" fill="#1877F2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </button>
            </div>
            
            <p class="text-center text-sm text-gray-600 mt-4">
                Don't have an account? <button onclick="showTab('register')" class="text-primary font-semibold hover:underline">Register here</button>
            </p>
        </div>
        
        <!-- Register Form -->
        <div id="registerTab" class="tab-content p-6">
            <?php if ($error && isset($_POST['register'])): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="input-group">
                    <label for="name">👤 Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Juan Perez" required>
                </div>
                
                <div class="input-group">
                    <label for="reg_email">📧 Email</label>
                    <input type="email" id="reg_email" name="reg_email" placeholder="your@email.com" required>
                </div>
                
                <div class="input-group">
                    <label for="reg_password">🔒 Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="reg_password" name="reg_password" placeholder="At least 6 characters" required minlength="6" oninput="checkPasswordStrength(this.value)">
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('reg_password')"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="strengthText"></div>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">🔒 Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" class="mt-1 cursor-pointer" required>
                        <span class="text-sm text-gray-600">I agree to the <a href="#" class="text-primary hover:underline">Terms of Service</a> and <a href="#" class="text-primary hover:underline">Privacy Policy</a></span>
                    </label>
                </div>
                
                <button type="submit" name="register" class="btn-primary" onclick="showLoading(this)">
                    Create Account
                </button>
            </form>
            
            <p class="text-center text-sm text-gray-600 mt-4">
                Already have an account? <button onclick="showTab('login')" class="text-primary font-semibold hover:underline">Login here</button>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="text-center p-4 bg-gray-50 border-t">
            <a href="public_booking.php" class="text-sm text-primary hover:underline">← Back to Hotels</a>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.target;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = '⚠️ Weak password';
                strengthText.style.color = '#ef4444';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = '✓ Medium password';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = '✓ Strong password';
                strengthText.style.color = '#10b981';
            }
        }
        
        // Show loading state
        function showLoading(button) {
            button.classList.add('btn-loading');
            button.textContent = 'Please wait...';
        }
        
        // Tab switching
        function showTab(tab) {
            // Update buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (tab === 'login') {
                document.getElementById('loginTab').classList.add('active');
            } else {
                document.getElementById('registerTab').classList.add('active');
            }
        }
    </script>
</body>
</html>
