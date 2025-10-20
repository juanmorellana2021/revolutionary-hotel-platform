<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/rbac_helper.php';

$rbac = new RBACHelper();

// Only managers and owners can register employees
if (!isset($_SESSION['user_role']) || !$rbac->hasPermission('manage_employees')) {
    header('Location: index.php?error=access_denied');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';
    $phone = $_POST['phone'] ?? '';
    $employeeId = $_POST['employee_id'] ?? '';
    $department = $_POST['department'] ?? '';
    $hireDate = $_POST['hire_date'] ?? date('Y-m-d');
    $hourlyRate = $_POST['hourly_rate'] ?? 0;
    
    // Validate role
    $allowedRoles = ['employee', 'receptionist', 'manager', 'investor'];
    if (!in_array($role, $allowedRoles)) {
        $message = 'Invalid role selected';
        $messageType = 'danger';
    } else {
        // Register user
        $result = $user->register($firstName, $lastName, $email, $password, true, '1.0');
        
        if ($result['success']) {
            // Update additional fields
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE users 
                SET role = ?, phone = ?, employee_id = ?, department = ?, hire_date = ?, hourly_rate = ?
                WHERE id = ?
            ");
            $stmt->execute([$role, $phone, $employeeId, $department, $hireDate, $hourlyRate, $result['user_id']]);
            
            $message = 'User registered successfully! Email: ' . $email . ' | Password: (as entered)';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User - Hotel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🆕 Register New User</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="+51938118436">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">User Role *</label>
                                <select name="role" id="roleSelect" class="form-select" required>
                                    <option value="employee">👷 Employee (Cleaning, Maintenance) - Clock In/Out Only</option>
                                    <option value="receptionist">🏨 Receptionist - Bookings & Check-in/out</option>
                                    <option value="manager">👔 Manager - Full Access</option>
                                    <option value="investor">💰 Investor - Read-Only View</option>
                                </select>
                            </div>
                            
                            <div id="employeeFields" style="display: none;">
                                <hr>
                                <h5>Employee Information</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" name="employee_id" class="form-control" placeholder="EMP001">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select name="department" class="form-select">
                                            <option value="">Select Department</option>
                                            <option value="Housekeeping">Housekeeping</option>
                                            <option value="Maintenance">Maintenance</option>
                                            <option value="Front Desk">Front Desk</option>
                                            <option value="Kitchen">Kitchen</option>
                                            <option value="Security">Security</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hire Date</label>
                                        <input type="date" name="hire_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hourly Rate (S/.)</label>
                                        <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0" placeholder="15.00">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Role Permissions:</strong>
                                <ul class="mb-0 mt-2" id="rolePermissions">
                                    <li>Select a role to see permissions</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Register User
                            </button>
                            <a href="employee_management.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const rolePermissions = {
            employee: [
                'Clock in and clock out',
                'View own work hours',
                'View own payroll information',
                '<strong>NO access to bookings or financials</strong>'
            ],
            receptionist: [
                'View and create bookings',
                'Check-in and check-out guests',
                'View room availability',
                'Respond to WhatsApp messages',
                '<strong>Cannot view financials or payroll</strong>'
            ],
            manager: [
                '<strong>Full access to all features</strong>',
                'Manage bookings, rooms, employees',
                'View and manage financials',
                'Process payroll',
                'Change hotel settings'
            ],
            investor: [
                '<strong>Read-only access</strong>',
                'View dashboard and statistics',
                'View bookings and rooms',
                'View financial reports',
                '<strong>Cannot edit or modify anything</strong>'
            ]
        };
        
        document.getElementById('roleSelect').addEventListener('change', function() {
            const role = this.value;
            const employeeFields = document.getElementById('employeeFields');
            const permissionsList = document.getElementById('rolePermissions');
            
            // Show employee fields only for employee and receptionist roles
            if (role === 'employee' || role === 'receptionist') {
                employeeFields.style.display = 'block';
            } else {
                employeeFields.style.display = 'none';
            }
            
            // Update permissions list
            if (rolePermissions[role]) {
                permissionsList.innerHTML = rolePermissions[role].map(p => '<li>' + p + '</li>').join('');
            }
        });
        
        // Trigger change on page load
        document.getElementById('roleSelect').dispatchEvent(new Event('change'));
    </script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
