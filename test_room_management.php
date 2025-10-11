<?php
// Test script for new room management in edit reservations
require_once 'includes/classes.php';

echo "<h1>🏨 Room Management Test - Edit Reservations</h1>";

try {
    $database = new Database();
    $connection = $database->getConnection();
    
    echo "<h2>1. Testing Edit Booking Capabilities</h2>";
    
    // Check if we have any bookings to test with
    $stmt = $connection->prepare("
        SELECT b.id, b.booking_reference, b.room_id, r.room_number, 
               b.guest_name, b.is_multi_room, b.primary_booking_id
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    
    if ($bookings) {
        echo "<h3>Available Bookings for Testing:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Booking ID</th><th>Reference</th><th>Room</th><th>Guest</th><th>Type</th><th>Edit Test</th></tr>";
        
        foreach ($bookings as $booking) {
            echo "<tr>";
            echo "<td>#{$booking['id']}</td>";
            echo "<td>{$booking['booking_reference']}</td>";
            echo "<td>Room {$booking['room_number']}</td>";
            echo "<td>" . htmlspecialchars($booking['guest_name']) . "</td>";
            
            if ($booking['is_multi_room']) {
                echo "<td style='background: #d4edda;'>Multi-Room (Primary)</td>";
            } elseif ($booking['primary_booking_id']) {
                echo "<td style='background: #cce5ff;'>Multi-Room (Linked)</td>";
            } else {
                echo "<td>Single Room</td>";
            }
            
            echo "<td><a href='calendar_view.php' target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Test Edit</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No bookings found. Create a booking first to test the edit functionality.</p>";
    }
    
    echo "<h2>2. Available Rooms for Testing</h2>";
    
    // Show available rooms
    $stmt = $connection->prepare("SELECT id, room_number, room_type, price FROM rooms ORDER BY room_number");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    if ($rooms) {
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;'>";
        foreach ($rooms as $room) {
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #007bff;'>";
            echo "<strong>Room {$room['room_number']}</strong><br>";
            echo "<small>{$room['room_type']}</small><br>";
            echo "<span style='color: #28a745;'>\$" . number_format($room['price'] ?? 0, 2) . "/night</span>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "<h2>3. New Edit Features Available</h2>";
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>✅ Enhanced Edit Modal Features:</h4>";
    echo "<ul>";
    echo "<li><strong>➕ Add Additional Rooms:</strong> Click 'Add Room' button to include more rooms in the booking</li>";
    echo "<li><strong>🗑️ Remove Rooms:</strong> Remove additional rooms with the '✕' button</li>";
    echo "<li><strong>👥 Manage Multiple Guests:</strong> Add/remove guests for the booking</li>";
    echo "<li><strong>📊 Room Summary:</strong> See all selected rooms in real-time</li>";
    echo "<li><strong>💰 Price Distribution:</strong> Prices automatically distributed across rooms</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>4. How to Test</h2>";
    echo "<ol>";
    echo "<li><strong>Go to Calendar:</strong> <a href='calendar_view.php' target='_blank'>Open Calendar View</a></li>";
    echo "<li><strong>Click on any booking</strong> to view booking details</li>";
    echo "<li><strong>Click 'Edit Reservation'</strong> to open the enhanced edit modal</li>";
    echo "<li><strong>Try adding rooms:</strong> Click the '➕ Add Room' button</li>";
    echo "<li><strong>Try adding guests:</strong> Click the '➕ Add Guest' button</li>";
    echo "<li><strong>Save changes</strong> and verify both rooms show the booking</li>";
    echo "</ol>";
    
    echo "<h2>5. Multi-Room Booking Workflow</h2>";
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>🔄 Complete Multi-Room Management:</h4>";
    echo "<p><strong>Create:</strong> New bookings can select multiple rooms from the start</p>";
    echo "<p><strong>Edit:</strong> Existing bookings can add/remove rooms dynamically</p>";
    echo "<p><strong>Display:</strong> All rooms show the booking in the calendar</p>";
    echo "<p><strong>Guests:</strong> Multiple guests can be managed per booking</p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='calendar_view.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 1.1em;'>🚀 Test the Enhanced Edit Modal</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>