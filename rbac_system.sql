-- Role-Based Access Control System
-- Comprehensive user roles and permissions

-- Update users table to support new roles
ALTER TABLE users 
MODIFY COLUMN role ENUM('guest', 'employee', 'receptionist', 'manager', 'owner', 'investor') DEFAULT 'guest';

-- Add employee-specific fields to users table (ignore if exists)
ALTER TABLE users ADD COLUMN employee_id VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN department VARCHAR(50) DEFAULT NULL;
ALTER TABLE users ADD COLUMN hire_date DATE DEFAULT NULL;
ALTER TABLE users ADD COLUMN hourly_rate DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(50) UNIQUE NOT NULL,
    permission_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert core permissions
INSERT INTO permissions (permission_name, permission_description) VALUES
('view_dashboard', 'View main dashboard'),
('view_bookings', 'View all bookings'),
('create_booking', 'Create new bookings'),
('edit_booking', 'Edit existing bookings'),
('cancel_booking', 'Cancel bookings'),
('checkin_checkout', 'Perform check-in and check-out'),
('view_rooms', 'View room information'),
('manage_rooms', 'Add, edit, delete rooms'),
('view_financials', 'View financial reports'),
('manage_financials', 'Edit financial data'),
('view_employees', 'View employee list'),
('manage_employees', 'Add, edit, delete employees'),
('view_payroll', 'View all payroll data'),
('manage_payroll', 'Process payroll'),
('view_own_payroll', 'View own payroll information'),
('clock_in_out', 'Use time clock system'),
('view_own_hours', 'View own time clock hours'),
('manage_settings', 'Change hotel settings'),
('manage_users', 'Add, edit, delete users'),
('view_whatsapp', 'View WhatsApp messages'),
('respond_whatsapp', 'Respond to WhatsApp messages')
ON DUPLICATE KEY UPDATE permission_description=VALUES(permission_description);

-- Create role_permissions junction table
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role, permission_id)
);

-- Clear existing role permissions
TRUNCATE TABLE role_permissions;

-- OWNER: Full access to everything
INSERT INTO role_permissions (role, permission_id) 
SELECT 'owner', id FROM permissions;

-- MANAGER: Full operational access (same as owner)
INSERT INTO role_permissions (role, permission_id) 
SELECT 'manager', id FROM permissions;

-- RECEPTIONIST: Front desk operations only
INSERT INTO role_permissions (role, permission_id)
SELECT 'receptionist', id FROM permissions WHERE permission_name IN (
    'view_dashboard',
    'view_bookings',
    'create_booking',
    'edit_booking',
    'cancel_booking',
    'checkin_checkout',
    'view_rooms',
    'view_whatsapp',
    'respond_whatsapp'
);

-- EMPLOYEE: Time tracking and own payroll only
INSERT INTO role_permissions (role, permission_id)
SELECT 'employee', id FROM permissions WHERE permission_name IN (
    'clock_in_out',
    'view_own_hours',
    'view_own_payroll'
);

-- INVESTOR: Read-only access to all data
INSERT INTO role_permissions (role, permission_id)
SELECT 'investor', id FROM permissions WHERE permission_name IN (
    'view_dashboard',
    'view_bookings',
    'view_rooms',
    'view_financials',
    'view_employees',
    'view_payroll'
);

-- GUEST: Booking only
INSERT INTO role_permissions (role, permission_id)
SELECT 'guest', id FROM permissions WHERE permission_name IN (
    'view_rooms',
    'create_booking'
);

-- Create default users for each role (for testing)
-- Password for all test users: 'password123'
INSERT INTO users (first_name, last_name, email, password, role, phone) VALUES
('Hotel', 'Owner', 'owner@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', '+51938118436'),
('Front', 'Receptionist', 'receptionist@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', '+51938118437'),
('Maria', 'Cleaning', 'maria.cleaning@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+51938118438'),
('John', 'Investor', 'investor@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'investor', '+51938118439')
ON DUPLICATE KEY UPDATE role=VALUES(role);

-- Update existing manager to owner if needed
UPDATE users SET role = 'owner' WHERE email = 'admin@hotel.com' AND role = 'admin';
