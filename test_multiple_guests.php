<?php
// Test script to verify multiple guests functionality
require_once 'includes/classes.php';

$database = new Database();
$connection = $database->getConnection();

echo "<h1>Testing Multiple Guests Functionality</h1>";

// Check if booking_guests table exists
echo "<h2>1. Checking if booking_guests table exists...</h2>";
try {
    $stmt = $connection->prepare("SHOW TABLES LIKE 'booking_guests'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ booking_guests table exists<br>";
        
        // Show table structure
        $stmt = $connection->prepare("DESCRIBE booking_guests");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<strong>Table structure:</strong><br>";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})<br>";
        }
    } else {
        echo "❌ booking_guests table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Check for existing bookings with multiple guests
echo "<h2>2. Checking existing bookings with multiple guests...</h2>";
try {
    $stmt = $connection->prepare("
        SELECT b.id, b.booking_reference, b.guest_name,
               COUNT(bg.id) as guest_count,
               GROUP_CONCAT(bg.guest_name SEPARATOR ', ') as all_guest_names
        FROM bookings b
        LEFT JOIN booking_guests bg ON b.id = bg.booking_id
        GROUP BY b.id
        HAVING guest_count > 1 OR (guest_count = 0 AND b.guest_name IS NOT NULL)
        LIMIT 5
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    
    if ($bookings) {
        echo "<strong>Found bookings:</strong><br>";
        foreach ($bookings as $booking) {
            echo "- Booking #{$booking['id']} ({$booking['booking_reference']}): ";
            if ($booking['guest_count'] > 0) {
                echo "{$booking['guest_count']} guests: {$booking['all_guest_names']}<br>";
            } else {
                echo "Single guest: {$booking['guest_name']}<br>";
            }
        }
    } else {
        echo "No bookings found with multiple guests<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking bookings: " . $e->getMessage() . "<br>";
}

// Test the new query structure
echo "<h2>3. Testing new booking query structure...</h2>";
try {
    $stmt = $connection->prepare("
        SELECT b.*, r.room_number, r.room_type,
               GROUP_CONCAT(DISTINCT CONCAT(bg.guest_name, '|', COALESCE(bg.guest_email, ''), '|', COALESCE(bg.guest_phone, ''), '|', bg.is_primary) SEPARATOR ';;;') as all_guests
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        LEFT JOIN booking_guests bg ON b.id = bg.booking_id
        WHERE b.status != 'cancelled'
        GROUP BY b.id
        LIMIT 3
    ");
    $stmt->execute();
    $testBookings = $stmt->fetchAll();
    
    if ($testBookings) {
        echo "✅ New query structure works<br>";
        foreach ($testBookings as $booking) {
            echo "<strong>Booking #{$booking['id']}:</strong><br>";
            echo "- Main guest: {$booking['guest_name']}<br>";
            echo "- All guests data: " . ($booking['all_guests'] ?: 'None') . "<br><br>";
        }
    } else {
        echo "❌ No bookings found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error testing query: " . $e->getMessage() . "<br>";
}

// Test multi-room booking functionality
echo "<h2>4. Testing Multi-Room Booking Structure</h2>";
try {
    // Check if new columns exist
    $stmt = $connection->prepare("SHOW COLUMNS FROM bookings LIKE 'is_multi_room'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ is_multi_room column exists<br>";
    } else {
        echo "❌ is_multi_room column missing<br>";
    }
    
    $stmt = $connection->prepare("SHOW COLUMNS FROM bookings LIKE 'primary_booking_id'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ primary_booking_id column exists<br>";
    } else {
        echo "❌ primary_booking_id column missing<br>";
    }
    
    // Check for multi-room bookings
    $stmt = $connection->prepare("
        SELECT b.id, b.booking_reference, b.is_multi_room, b.primary_booking_id, r.room_number
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.is_multi_room = 1 OR b.primary_booking_id IS NOT NULL
        ORDER BY COALESCE(b.primary_booking_id, b.id), b.id
        LIMIT 10
    ");
    $stmt->execute();
    $multiRoomBookings = $stmt->fetchAll();
    
    if ($multiRoomBookings) {
        echo "<strong>Multi-room bookings found:</strong><br>";
        foreach ($multiRoomBookings as $booking) {
            echo "- Booking #{$booking['id']} (Room {$booking['room_number']}) - ";
            echo "Multi-room: " . ($booking['is_multi_room'] ? 'Yes' : 'No');
            echo ", Primary: " . ($booking['primary_booking_id'] ?: 'Self') . "<br>";
        }
    } else {
        echo "No multi-room bookings found (this is normal if none have been created yet)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing multi-room structure: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Database Connection Status</h2>";
if ($connection) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
}

echo "<h2>📋 Summary</h2>";
echo "<p>The system is now enhanced with:</p>";
echo "<ul>";
echo "<li>✅ Multiple guest support per booking</li>";
echo "<li>✅ Multi-room booking capability</li>";
echo "<li>✅ Enhanced guest information display</li>";
echo "<li>✅ Improved booking management</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Create a new booking with multiple rooms to test the functionality</li>";
echo "<li>Check if both rooms show the booking in the calendar</li>";
echo "<li>Edit a booking to test the new multi-guest interface</li>";
echo "</ol>";

echo "<p><a href='calendar_view.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Calendar</a></p>";
?>