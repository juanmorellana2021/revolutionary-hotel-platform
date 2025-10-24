# 🔐 Role-Based Access Control (RBAC) System

## Overview
Comprehensive user role and permission management system for the hotel booking platform.

## User Roles & Permissions

### 1. 👑 Owner
- **Access Level**: Full system access
- **Permissions**: Everything (all features)
- **Dashboard**: Manager Dashboard (full access)
- **Use Case**: Hotel owner with complete control

### 2. 👔 Manager  
- **Access Level**: Full operational access
- **Permissions**: All bookings, rooms, employees, financials, settings
- **Dashboard**: Manager Dashboard
- **Use Case**: General manager running day-to-day operations

### 3. 🏨 Receptionist
- **Access Level**: Front desk operations
- **Permissions**:
  - ✅ View & create bookings
  - ✅ Check-in and check-out guests
  - ✅ View room availability
  - ✅ Respond to WhatsApp messages
  - ❌ NO access to financials or payroll
- **Dashboard**: Receptionist Dashboard
- **Use Case**: Front desk staff handling guest arrivals and bookings

### 4. 👷 Employee (Cleaning, Maintenance, etc.)
- **Access Level**: Time tracking only
- **Permissions**:
  - ✅ Clock in and clock out
  - ✅ View own work hours
  - ✅ View own payroll information
  - ❌ NO access to bookings, financials, or other data
- **Dashboard**: Employee Dashboard (time clock)
- **Use Case**: Housekeeping, maintenance, kitchen staff

### 5. 💰 Investor
- **Access Level**: Read-only view
- **Permissions**:
  - ✅ View dashboard and statistics
  - ✅ View bookings and rooms
  - ✅ View financial reports
  - ❌ CANNOT edit or modify anything
- **Dashboard**: Investor Dashboard (read-only)
- **Use Case**: Financial stakeholders monitoring performance

### 6. 🎫 Guest
- **Access Level**: Booking only
- **Permissions**:
  - ✅ View available rooms
  - ✅ Create bookings
  - ✅ View own bookings
- **Dashboard**: Guest Dashboard
- **Use Case**: Hotel guests making reservations

## Database Schema

### New Tables Created:
1. **permissions** - Defines available permissions in the system
2. **role_permissions** - Maps roles to their allowed permissions

### Updated Tables:
1. **users** - Added new role types and employee fields:
   - `role`: ENUM updated to include new roles
   - `employee_id`: Employee identification number
   - `department`: Department (Housekeeping, Maintenance, etc.)
   - `hire_date`: Date of hire
   - `hourly_rate`: Hourly wage for payroll
   - `is_active`: Active status flag

## Key Files Created

### 1. `includes/rbac_helper.php`
Core RBAC functionality:
```php
$rbac = new RBACHelper();

// Check permission
if ($rbac->hasPermission('view_financials')) {
    // Show financial data
}

// Require permission (redirect if not authorized)
$rbac->requirePermission('manage_employees');

// Check role level
if ($rbac->hasRoleLevel('manager')) {
    // Manager-level access
}
```

### 2. `register_user.php`
Admin interface to register new users with specific roles. Features:
- Role selection with permission preview
- Employee-specific fields for staff
- Department and hourly rate setup
- Automatic password hashing

### 3. `employee_dashboard.php`
Time clock dashboard for employees:
- Real-time clock display
- Clock in/out functionality
- View daily and weekly hours
- Estimated earnings calculator
- Recent time entries history

### 4. Updated `index.php`
Role-based login routing:
- Owners/Managers → Manager Dashboard
- Employees → Employee Dashboard  
- Receptionists → Receptionist Dashboard
- Investors → Investor Dashboard
- Guests → Guest Dashboard

## Test Users Created

Login at: http://212.227.241.193/

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@hotel.com | password123 |
| Manager | manager@hotel.com | manager123 |
| Receptionist | receptionist@hotel.com | password123 |
| Employee | maria.cleaning@hotel.com | password123 |
| Investor | investor@hotel.com | password123 |

## Implementation Guide

### Protecting a Page
Add this to the top of any page:

```php
<?php
session_start();
require_once 'includes/rbac_helper.php';

$rbac = new RBACHelper();
$rbac->requirePermission('view_financials'); // Will redirect if unauthorized
?>
```

### Checking Permissions in Code
```php
// Single permission
if ($rbac->hasPermission('manage_rooms')) {
    // Show edit button
}

// Multiple permissions (ANY)
if ($rbac->hasAnyPermission(['view_financials', 'manage_financials'])) {
    // Show financial section
}

// Multiple permissions (ALL)
if ($rbac->hasAllPermissions(['view_bookings', 'edit_booking'])) {
    // Show advanced booking features
}
```

### Role-Based UI Elements
```php
<?php if ($rbac->hasPermission('manage_employees')): ?>
    <a href="register_user.php" class="btn btn-primary">Add Employee</a>
<?php endif; ?>
```

## Next Steps

### Still To Do:
1. **Create Receptionist Dashboard** - Focused on bookings and check-in/out
2. **Create Investor Dashboard** - Read-only financial overview
3. **Add Permission Checks** - Protect financial pages from receptionist/employee access
4. **Update Navigation Menu** - Show/hide menu items based on role
5. **Create My Payroll Page** - For employees to view their earnings
6. **Test Each Role** - Verify all permissions work correctly

### To Add a New Permission:
1. Insert into `permissions` table
2. Map to roles in `role_permissions` table
3. Use `$rbac->hasPermission('new_permission')` in code

## Employee Time Clock Usage

1. Employee logs in with their credentials
2. Redirected to Employee Dashboard automatically
3. Click "Clock In" to start shift
4. Click "Clock Out" to end shift
5. View hours worked and estimated earnings
6. Access payroll information from dashboard

## Security Features

- ✅ Permission-based access control
- ✅ Automatic redirect on unauthorized access
- ✅ Session-based authentication
- ✅ Role hierarchy system
- ✅ Read-only investor access
- ✅ Employee data isolation (can only see own data)

## Future Enhancements

- Email notifications for clock-in/out
- Biometric integration for time clock
- Mobile app for employee time tracking
- Advanced payroll calculations with overtime
- Shift scheduling system
- Department-based permissions
- Multi-factor authentication for sensitive roles
