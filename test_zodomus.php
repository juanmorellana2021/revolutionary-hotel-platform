<?php
/**
 * Zodomus Integration Test Page (No Authentication Required)
 * Quick test to verify Zodomus API integration is working
 */

require_once 'includes/classes.php';
require_once 'includes/zodomus_api.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Zodomus API Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #333; color: #0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔗 Zodomus API Integration Test</h1>
        <p>Testing Zodomus channel manager integration...</p>";

// Test 1: Class exists
echo "<div class='test-section'>
        <h2>Test 1: Zodomus API Class</h2>";
if (class_exists('ZodomusAPI')) {
    echo "<p class='success'>✅ ZodomusAPI class loaded successfully!</p>";
} else {
    echo "<p class='error'>❌ ZodomusAPI class not found!</p>";
}
echo "</div>";

// Test 2: Database connection
echo "<div class='test-section'>
        <h2>Test 2: Database Connection</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "<p class='success'>✅ Database connected successfully!</p>";
        
        // Check if api_config table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'api_config'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ api_config table exists!</p>";
        } else {
            echo "<p class='error'>⚠️ api_config table doesn't exist yet. Run setup_booking_integration.php first.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Initialize Zodomus API
echo "<div class='test-section'>
        <h2>Test 3: Initialize Zodomus API</h2>";
try {
    $zodomusAPI = new ZodomusAPI();
    echo "<p class='success'>✅ ZodomusAPI initialized successfully!</p>";
    echo "<p><em>Note: API credentials need to be configured in the database.</em></p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Save test credentials
echo "<div class='test-section'>
        <h2>Test 4: Save Zodomus API Credentials</h2>";
if (isset($_POST['save_creds'])) {
    try {
        $apiUser = $_POST['api_user'] ?? '';
        $apiPassword = $_POST['api_password'] ?? '';
        $apiPasswordCC = $_POST['api_password_cc'] ?? '';
        
        $zodomusAPI = new ZodomusAPI();
        $result = $zodomusAPI->saveCredentials($apiUser, $apiPassword, $apiPasswordCC);
        
        if ($result['success']) {
            echo "<p class='success'>✅ " . htmlspecialchars($result['message']) . "</p>";
        } else {
            echo "<p class='error'>❌ " . htmlspecialchars($result['message']) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<form method='POST'>
            <input type='hidden' name='save_creds' value='1'>
            <p><strong>Test API Credentials:</strong></p>
            <label>API User:<br>
            <input type='text' name='api_user' value='IWOlIXq5DiHz1TUywbQYzsfmcOxnp1LZk3jUuSFYubw=' style='width: 400px;'></label><br><br>
            
            <label>API Password:<br>
            <input type='text' name='api_password' value='iBqALbzPTYoKp44hIxO1ounfPi8dTS6G56jSdcCLQ/U=' style='width: 400px;'></label><br><br>
            
            <label>API Password (Credit Card):<br>
            <input type='text' name='api_password_cc' value='naFixZRqE2Qq7Xir+IAGk80mJ3YxA+NWR+ae0mxjB2c=' style='width: 400px;'></label><br><br>
            
            <button type='submit' class='btn'>💾 Save Zodomus Credentials</button>
          </form>";
}
echo "</div>";

// Test 5: Get sync stats
echo "<div class='test-section'>
        <h2>Test 5: Get Sync Statistics</h2>";
try {
    $zodomusAPI = new ZodomusAPI();
    $stats = $zodomusAPI->getSyncStats();
    
    echo "<p class='success'>✅ Stats retrieved successfully!</p>";
    echo "<pre>" . print_r($stats, true) . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 6: Check bookings table
echo "<div class='test-section'>
        <h2>Test 6: Check Bookings Table</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if bookings table has required columns
    $stmt = $conn->query("SHOW COLUMNS FROM bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['booking_reference', 'booking_source', 'sync_status', 'synced_at'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<p class='success'>✅ Bookings table has all required columns!</p>";
    } else {
        echo "<p class='error'>⚠️ Missing columns: " . implode(', ', $missingColumns) . "</p>";
        echo "<p>Run <code>setup_booking_integration.php</code> to add missing columns.</p>";
    }
    
    // Show sample data
    $stmt = $conn->query("SELECT id, booking_reference, booking_source, sync_status FROM bookings LIMIT 5");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($bookings)) {
        echo "<p>Sample bookings:</p>";
        echo "<pre>" . print_r($bookings, true) . "</pre>";
    } else {
        echo "<p>No bookings in database yet.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Navigation
echo "<div class='test-section'>
        <h2>🔗 Quick Links</h2>
        <p><a href='zodomus_integration.php' class='btn'>🔐 Full Dashboard (Requires Login)</a></p>
        <p><a href='setup_booking_integration.php' class='btn'>⚙️ Setup Database Tables</a></p>
        <p><a href='index.php' class='btn'>🏠 Home</a></p>
      </div>";

echo "</div>
</body>
</html>";
?>
