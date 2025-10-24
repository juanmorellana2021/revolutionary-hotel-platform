# Tenant Context System - Usage Guide

## Overview
The Tenant Context system ensures complete data isolation between hotels in the AINI.com multi-tenant platform. It automatically filters all database queries by `hotel_id` to prevent data leaks between hotels.

---

## Quick Start

### 1. Initialize in Your PHP Files

```php
<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/tenant_context.php';

// Require hotel context (redirects if not set)
TenantContext::requireHotel();

// Now safe to query hotel-specific data
$hotel_id = TenantContext::getHotelId();
```

### 2. Set Hotel Context on Login

```php
// In your login handler (index.php or login.php)
if ($login_successful) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['hotel_id'] = $user['hotel_id']; // From users table
    
    // Set tenant context
    if ($user['hotel_id']) {
        TenantContext::setHotel($user['hotel_id']);
    }
    
    // Redirect to dashboard
    header('Location: manager_dashboard.php');
}
```

---

## Core Methods

### Setting Context

```php
// Set current hotel context
TenantContext::setHotel(1); // Sets to Hotel ID 1 (Samay Wasi)

// Get current hotel ID
$hotel_id = TenantContext::getHotelId(); // Returns: 1

// Require hotel context (redirect if not set)
TenantContext::requireHotel('/select-hotel.php');

// Clear context (on logout)
TenantContext::clear();
```

### Querying Data

#### Method 1: Automatic Filtering (Recommended)

```php
// Add hotel filter to WHERE clause
$where = "status = 'confirmed'";
$where_filtered = TenantContext::addHotelFilter($where);
// Result: "(status = 'confirmed') AND hotel_id = 1"

$query = "SELECT * FROM bookings WHERE {$where_filtered}";
$result = $db->query($query);
```

#### Method 2: Using TenantDatabase Class

```php
$tenant_db = new TenantDatabase();

// SELECT with automatic filtering
$result = $tenant_db->query("SELECT * FROM rooms WHERE room_type = 'deluxe'");

// INSERT with automatic hotel_id
$new_booking_id = $tenant_db->insert('bookings', [
    'guest_id' => 123,
    'room_id' => 5,
    'check_in_date' => '2025-11-01',
    'check_out_date' => '2025-11-03',
    'total_amount' => 500.00,
    'status' => 'confirmed'
    // hotel_id is added automatically!
]);

// UPDATE with automatic hotel_id filter
$tenant_db->update('rooms', 
    ['price' => 150.00], // Data to update
    'id = ?', // WHERE clause
    [5] // Parameters
);
// Only updates room 5 IF it belongs to current hotel

// DELETE with automatic hotel_id filter
$tenant_db->delete('bookings', 'id = ? AND status = ?', [456, 'cancelled']);
// Only deletes IF booking belongs to current hotel
```

#### Method 3: Manual Filtering

```php
$hotel_id = TenantContext::getHotelId();

$query = "SELECT * FROM rooms WHERE hotel_id = ? AND room_type = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("is", $hotel_id, $room_type);
$stmt->execute();
```

---

## Access Control

### Check Hotel Access

```php
// Check if user has access to a specific hotel
if (TenantContext::hasAccessToHotel($hotel_id)) {
    // Allow access
} else {
    // Deny access
    die("Unauthorized");
}
```

### Platform Admin Bypass (Use Carefully!)

```php
// Execute query without tenant filtering (ADMIN ONLY!)
$all_hotels = TenantContext::withoutTenantFilter(function() use ($db) {
    $query = "SELECT * FROM hotels";
    $result = $db->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
});

// Returns ALL hotels across the platform
// Regular queries will still be filtered
```

---

## Hotel Information

### Get Current Hotel Data

```php
// Get full hotel data
$hotel_data = TenantContext::getHotelData();
echo $hotel_data['hotel_name']; // "Samay Wasi Hotel"
echo $hotel_data['city']; // "Cusco"
echo $hotel_data['star_rating']; // 4

// Get specific hotel info
$hotel_name = TenantContext::getHotelName();
$hotel_slug = TenantContext::getHotelSlug();
$commission = TenantContext::getCommissionRate(); // 15.00

// Check hotel status
if (TenantContext::isHotelActive()) {
    // Hotel is active
}
```

---

## Example: Manager Dashboard

```php
<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/tenant_context.php';
require_once 'includes/rbac_helper.php';

// Require login and hotel context
RBACHelper::requireLogin();
TenantContext::requireHotel();

// Check permission
RBACHelper::requirePermission('view_bookings');

// Get hotel info
$hotel_name = TenantContext::getHotelName();
$hotel_id = TenantContext::getHotelId();

// Query bookings (automatically filtered by hotel_id)
$tenant_db = new TenantDatabase();
$today_checkins = $tenant_db->query("
    SELECT b.*, u.name as guest_name, r.room_name
    FROM bookings b
    JOIN users u ON b.guest_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.check_in_date = CURDATE()
    AND b.status = 'confirmed'
    ORDER BY b.check_in_date
");
// Automatically adds: AND b.hotel_id = 1

?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($hotel_name) ?> - Dashboard</title>
</head>
<body>
    <h1>Welcome to <?= htmlspecialchars($hotel_name) ?></h1>
    
    <h2>Today's Check-ins</h2>
    <table>
        <?php while ($booking = $today_checkins->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($booking['guest_name']) ?></td>
                <td><?= htmlspecialchars($booking['room_name']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
```

---

## Example: Room Management

```php
<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/tenant_context.php';

TenantContext::requireHotel();

$tenant_db = new TenantDatabase();

// Add new room (hotel_id added automatically)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $tenant_db->insert('rooms', [
        'room_name' => $_POST['room_name'],
        'room_type' => $_POST['room_type'],
        'price' => $_POST['price'],
        'quantity' => $_POST['quantity']
        // No need to specify hotel_id - it's automatic!
    ]);
    
    if ($room_id) {
        echo "Room created with ID: {$room_id}";
    }
}

// List rooms (automatically filtered)
$rooms = $tenant_db->query("SELECT * FROM rooms ORDER BY room_name");

while ($room = $rooms->fetch_assoc()) {
    echo "<div>{$room['room_name']} - \${$room['price']}</div>";
}
// Only shows rooms for current hotel
```

---

## Example: Employee Time Clock

```php
<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/tenant_context.php';

TenantContext::requireHotel();

$employee_id = $_SESSION['employee_id'];
$tenant_db = new TenantDatabase();

// Clock in (hotel_id added automatically)
if (isset($_POST['clock_in'])) {
    $tenant_db->insert('time_clock', [
        'employee_id' => $employee_id,
        'clock_in' => date('Y-m-d H:i:s'),
        'status' => 'clocked_in'
    ]);
}

// Get today's time entries (automatically filtered by hotel_id)
$today_entries = $tenant_db->query("
    SELECT * FROM time_clock 
    WHERE employee_id = {$employee_id} 
    AND DATE(clock_in) = CURDATE()
");
// Automatically adds: AND hotel_id = 1
```

---

## Security Best Practices

### ✅ DO:

1. **Always call `TenantContext::requireHotel()`** at the top of hotel management pages
2. **Use TenantDatabase class** for automatic filtering
3. **Verify access** before showing hotel data
4. **Clear context on logout**
5. **Test with multiple hotels** to ensure isolation

### ❌ DON'T:

1. **Don't bypass filters** unless absolutely necessary (admin functions only)
2. **Don't trust client-side hotel_id** - always use session
3. **Don't query without context** on hotel management pages
4. **Don't share sessions** between hotels
5. **Don't forget to add hotel_id** to new tables

---

## Testing Data Isolation

```php
// Test script to verify isolation
<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/tenant_context.php';

// Test with Hotel 1
TenantContext::setHotel(1);
$tenant_db = new TenantDatabase();
$hotel1_rooms = $tenant_db->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc();
echo "Hotel 1 rooms: {$hotel1_rooms['count']}\n";

// Switch to Hotel 2
TenantContext::setHotel(2);
$hotel2_rooms = $tenant_db->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc();
echo "Hotel 2 rooms: {$hotel2_rooms['count']}\n";

// Should show different counts!
```

---

## Debugging

```php
// Get debug information
$debug = TenantContext::getDebugInfo();
print_r($debug);

// Output:
// Array (
//     [hotel_id] => 1
//     [bypass_filter] => false
//     [session_hotel_id] => 1
//     [hotel_loaded] => true
//     [hotel_name] => 'Samay Wasi Hotel'
// )
```

---

## Migration Checklist

When converting existing pages to use tenant context:

- [ ] Add `require_once 'includes/tenant_context.php'` at top
- [ ] Add `TenantContext::requireHotel()` after session_start()
- [ ] Replace direct database queries with `TenantDatabase` class
- [ ] Or add `TenantContext::addHotelFilter()` to WHERE clauses
- [ ] Test page with different hotel contexts
- [ ] Verify no data leaks between hotels

---

## Common Issues

### Issue: "Hotel context not set" error
**Solution:** Make sure hotel_id is set during login:
```php
TenantContext::setHotel($_SESSION['hotel_id']);
```

### Issue: Query returns empty results
**Check:** Is hotel_id actually in the table?
```php
$debug = TenantContext::getDebugInfo();
var_dump($debug);
```

### Issue: Guest users can't access platform
**Solution:** Guest users (role='guest') should have `hotel_id = NULL` and don't need tenant context for public pages.

---

## Summary

The Tenant Context system provides:
✅ **Automatic data isolation** between hotels
✅ **Security by default** - can't forget to filter
✅ **Simple API** - one line to enable filtering
✅ **Flexible** - manual override when needed
✅ **Performance** - uses indexes on hotel_id
✅ **Audit trail** - logs all context changes

Use it on **every hotel management page** to ensure data security!
