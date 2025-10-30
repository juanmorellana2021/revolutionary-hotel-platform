<?php
session_start();
require_once 'db_connection.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $propertyType = $_POST['property_type'] ?? '';
        $propertyName = trim($_POST['property_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $timezone = $_POST['timezone'] ?? 'America/Lima';
        $totalRooms = intval($_POST['total_rooms'] ?? 0);

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            throw new Exception('Please fill in all required fields');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email already registered. Please login instead.');
        }

        // Start transaction
        $conn->begin_transaction();

        // 1. Create user account
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password, user_role, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, 'admin', 'owner', NOW())
        ");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashedPassword);
        $stmt->execute();
        $userId = $conn->insert_id;

        // 2. Create hotel property
        $stmt = $conn->prepare("
            INSERT INTO hotel_info (
                owner_id, hotel_name, property_type, address_line1, city, country, 
                timezone, total_rooms, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssssi", $userId, $propertyName, $propertyType, $address, $city, $country, $timezone, $totalRooms);
        $stmt->execute();
        $hotelId = $conn->insert_id;

        // 3. Set user's current hotel
        $stmt = $conn->prepare("UPDATE users SET current_hotel_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $hotelId, $userId);
        $stmt->execute();

        // 4. Give user access to this hotel
        $stmt = $conn->prepare("
            INSERT INTO user_hotel_access (user_id, hotel_id, access_level) 
            VALUES (?, ?, 'owner')
        ");
        $stmt->bind_param("ii", $userId, $hotelId);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Log the user in
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_type'] = 'owner';
        $_SESSION['current_hotel_id'] = $hotelId;

        // Redirect to dashboard
        header('Location: manager_dashboard.php?welcome=1');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Property - AiNi Hotel Platform</title>
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
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .form-content {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 25px;
            right: 25px;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #999;
        }

        .step.active .step-circle {
            background: #667eea;
            color: white;
        }

        .step.completed .step-circle {
            background: #4caf50;
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            color: #666;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .property-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .property-type-option {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .property-type-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .property-type-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .property-type-option .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .property-type-option .name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-next {
            background: #667eea;
            color: white;
            flex: 1;
        }

        .btn-next:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-prev {
            background: #e0e0e0;
            color: #666;
        }

        .btn-prev:hover {
            background: #d0d0d0;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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

        .timezone-select {
            max-height: 300px;
        }

        small {
            color: #666;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="header">
            <h1>🏨 Start Your Hotel Business</h1>
            <p>Register your property on AiNi Platform</p>
        </div>

        <div class="form-content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Your Info</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Property Type</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Property Details</div>
                </div>
            </div>

            <form id="registrationForm" method="POST">
                <!-- Step 1: Owner Information -->
                <div class="form-step active" data-step="1">
                    <h2 style="margin-bottom: 20px;">Tell us about yourself</h2>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                        <small>We'll send your login credentials here</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Create Password *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <!-- Step 2: Property Type -->
                <div class="form-step" data-step="2">
                    <h2 style="margin-bottom: 20px;">What type of property do you have?</h2>
                    
                    <input type="hidden" id="property_type" name="property_type" required>
                    
                    <div class="property-type-grid">
                        <div class="property-type-option" data-type="hotel">
                            <div class="icon">🏨</div>
                            <div class="name">Hotel</div>
                        </div>
                        <div class="property-type-option" data-type="motel">
                            <div class="icon">🏩</div>
                            <div class="name">Motel</div>
                        </div>
                        <div class="property-type-option" data-type="bnb">
                            <div class="icon">🏠</div>
                            <div class="name">B&B</div>
                        </div>
                        <div class="property-type-option" data-type="guesthouse">
                            <div class="icon">🏡</div>
                            <div class="name">Guest House</div>
                        </div>
                        <div class="property-type-option" data-type="hostel">
                            <div class="icon">🏢</div>
                            <div class="name">Hostel</div>
                        </div>
                        <div class="property-type-option" data-type="resort">
                            <div class="icon">🏖️</div>
                            <div class="name">Resort</div>
                        </div>
                        <div class="property-type-option" data-type="inn">
                            <div class="icon">🏘️</div>
                            <div class="name">Inn</div>
                        </div>
                        <div class="property-type-option" data-type="boutique">
                            <div class="icon">🏰</div>
                            <div class="name">Boutique Hotel</div>
                        </div>
                        <div class="property-type-option" data-type="vacation_rental">
                            <div class="icon">🏠</div>
                            <div class="name">Vacation Rental</div>
                        </div>
                        <div class="property-type-option" data-type="serviced_apartment">
                            <div class="icon">🏢</div>
                            <div class="name">Serviced Apt</div>
                        </div>
                        <div class="property-type-option" data-type="lodge">
                            <div class="icon">🏚️</div>
                            <div class="name">Lodge</div>
                        </div>
                        <div class="property-type-option" data-type="villa">
                            <div class="icon">🏡</div>
                            <div class="name">Villa</div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Property Details -->
                <div class="form-step" data-step="3">
                    <h2 style="margin-bottom: 20px;">Tell us about your property</h2>
                    
                    <div class="form-group">
                        <label for="property_name">Property Name *</label>
                        <input type="text" id="property_name" name="property_name" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address *</label>
                        <input type="text" id="address" name="address" required>
                    </div>

                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country" required>
                    </div>

                    <div class="form-group">
                        <label for="timezone">Timezone *</label>
                        <select id="timezone" name="timezone" required class="timezone-select">
                            <optgroup label="Americas">
                                <option value="America/Lima">Peru (Lima) - UTC-5</option>
                                <option value="America/New_York">USA (New York) - UTC-5/-4</option>
                                <option value="America/Chicago">USA (Chicago) - UTC-6/-5</option>
                                <option value="America/Denver">USA (Denver) - UTC-7/-6</option>
                                <option value="America/Los_Angeles">USA (Los Angeles) - UTC-8/-7</option>
                                <option value="America/Mexico_City">Mexico (Mexico City) - UTC-6/-5</option>
                                <option value="America/Bogota">Colombia (Bogotá) - UTC-5</option>
                                <option value="America/Buenos_Aires">Argentina (Buenos Aires) - UTC-3</option>
                                <option value="America/Santiago">Chile (Santiago) - UTC-3/-4</option>
                            </optgroup>
                            <optgroup label="Europe">
                                <option value="Europe/London">UK (London) - UTC+0/+1</option>
                                <option value="Europe/Paris">France (Paris) - UTC+1/+2</option>
                                <option value="Europe/Madrid">Spain (Madrid) - UTC+1/+2</option>
                                <option value="Europe/Berlin">Germany (Berlin) - UTC+1/+2</option>
                                <option value="Europe/Rome">Italy (Rome) - UTC+1/+2</option>
                            </optgroup>
                            <optgroup label="Asia">
                                <option value="Asia/Dubai">UAE (Dubai) - UTC+4</option>
                                <option value="Asia/Bangkok">Thailand (Bangkok) - UTC+7</option>
                                <option value="Asia/Singapore">Singapore - UTC+8</option>
                                <option value="Asia/Tokyo">Japan (Tokyo) - UTC+9</option>
                                <option value="Asia/Shanghai">China (Shanghai) - UTC+8</option>
                            </optgroup>
                        </select>
                        <small>Select your property's local timezone</small>
                    </div>

                    <div class="form-group">
                        <label for="total_rooms">Number of Rooms *</label>
                        <input type="number" id="total_rooms" name="total_rooms" min="1" required>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="btn-group">
                    <button type="button" class="btn btn-prev" id="prevBtn" style="display: none;">← Previous</button>
                    <button type="button" class="btn btn-next" id="nextBtn">Next →</button>
                    <button type="submit" class="btn btn-next" id="submitBtn" style="display: none;">Create Account 🎉</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        // Property type selection
        document.querySelectorAll('.property-type-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.property-type-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('property_type').value = this.dataset.type;
            });
        });

        // Navigation
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
            // Show current step
            document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
            
            // Update step indicator
            document.querySelectorAll('.step').forEach((s, index) => {
                s.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    s.classList.add('completed');
                } else if (index + 1 === step) {
                    s.classList.add('active');
                }
            });

            // Show/hide buttons
            document.getElementById('prevBtn').style.display = step > 1 ? 'block' : 'none';
            document.getElementById('nextBtn').style.display = step < totalSteps ? 'block' : 'none';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'block' : 'none';
        }

        function validateStep(step) {
            const currentStepElement = document.querySelector(`.form-step[data-step="${step}"]`);
            const inputs = currentStepElement.querySelectorAll('input[required], select[required]');
            
            for (let input of inputs) {
                if (!input.value) {
                    input.focus();
                    alert('Please fill in all required fields');
                    return false;
                }
            }

            // Step-specific validation
            if (step === 1) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                if (password !== confirmPassword) {
                    alert('Passwords do not match');
                    return false;
                }
            }

            if (step === 2) {
                if (!document.getElementById('property_type').value) {
                    alert('Please select a property type');
                    return false;
                }
            }

            return true;
        }

        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateStep(currentStep)) {
                // Submit form via AJAX or regular form submission
                this.submit();
            }
        });
    </script>
</body>
</html>
