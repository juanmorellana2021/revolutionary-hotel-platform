<?php
// Script to examine and fix existing multi-room bookings
require_once 'includes/classes.php';

echo "<h1>🔍 Multi-Room Booking Analysis & Fix</h1>";

try {
    $database = new Database();
    $connection = $database->getConnection();
    
    // Check recent bookings and their special requests
    echo "<h2>1. Examining Recent Bookings</h2>";
    $stmt = $connection->prepare("
        SELECT b.id, b.booking_reference, b.room_id, r.room_number, 
               b.guest_name, b.special_requests, b.check_in_date, b.check_out_date,
               b.total_price, b.is_multi_room, b.primary_booking_id
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Reference</th><th>Room</th><th>Guest</th><th>Multi-Room Info</th><th>Actions</th></tr>";
    
    $multiRoomBookings = [];
    foreach ($bookings as $booking) {
        echo "<tr>";
        echo "<td>#{$booking['id']}</td>";
        echo "<td>{$booking['booking_reference']}</td>";
        echo "<td>Room {$booking['room_number']}</td>";
        echo "<td>" . htmlspecialchars($booking['guest_name']) . "</td>";
        
        // Check if special_requests contains multi-room info
        if (strpos($booking['special_requests'], 'Multiple Rooms Booking:') !== false) {
            echo "<td style='background: #fff3cd;'>Multi-room detected in special requests</td>";
            echo "<td><button onclick='fixBooking({$booking['id']})' style='background: #28a745; color: white; border: none; padding: 5px 10px;'>Fix This Booking</button></td>";
            $multiRoomBookings[] = $booking;
        } else {
            echo "<td>Single room</td>";
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    if (empty($multiRoomBookings)) {
        echo "<p>No multi-room bookings found in special requests. This is normal if you haven't created any multi-room bookings yet.</p>";
    } else {
        echo "<h2>2. Multi-Room Bookings Found</h2>";
        echo "<p>Found " . count($multiRoomBookings) . " booking(s) that need to be converted to proper multi-room format.</p>";
        
        foreach ($multiRoomBookings as $booking) {
            echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h3>Booking #{$booking['id']} - {$booking['booking_reference']}</h3>";
            echo "<p><strong>Current Room:</strong> {$booking['room_number']}</p>";
            echo "<p><strong>Guest:</strong> " . htmlspecialchars($booking['guest_name']) . "</p>";
            echo "<p><strong>Special Requests:</strong></p>";
            echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>" . htmlspecialchars($booking['special_requests']) . "</pre>";
            
            // Extract room information from special requests
            preg_match_all('/Room \d+: (\d+) \([^)]+\)/', $booking['special_requests'], $matches);
            if (!empty($matches[1])) {
                echo "<p><strong>Rooms mentioned:</strong> " . implode(', ', $matches[1]) . "</p>";
                
                echo "<form method='POST' style='margin-top: 10px;'>";
                echo "<input type='hidden' name='fix_booking_id' value='{$booking['id']}'>";
                echo "<input type='hidden' name='room_numbers' value='" . implode(',', $matches[1]) . "'>";
                echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px;'>🔧 Convert to Multi-Room Booking</button>";
                echo "</form>";
            }
            echo "</div>";
        }
    }
    
    // Handle the conversion
    if (isset($_POST['fix_booking_id']) && isset($_POST['room_numbers'])) {
        echo "<h2>3. Converting Booking to Multi-Room Format</h2>";
        
        $bookingId = (int)$_POST['fix_booking_id'];
        $roomNumbers = explode(',', $_POST['room_numbers']);
        
        // Get the original booking
        $stmt = $connection->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $originalBooking = $stmt->fetch();
        
        if ($originalBooking) {
            echo "<p>Converting booking #{$bookingId} to multi-room format...</p>";
            
            // Get room IDs from room numbers
            $roomIds = [];
            foreach ($roomNumbers as $roomNumber) {
                $stmt = $connection->prepare("SELECT id FROM rooms WHERE room_number = ?");
                $stmt->execute([trim($roomNumber)]);
                $room = $stmt->fetch();
                if ($room) {
                    $roomIds[] = $room['id'];
                }
            }
            
            if (count($roomIds) > 1) {
                // Update the original booking to be the primary
                $stmt = $connection->prepare("UPDATE bookings SET is_multi_room = 1 WHERE id = ?");
                $stmt->execute([$bookingId]);
                
                // Create additional booking records for other rooms
                $createdBookings = [$bookingId];
                $pricePerRoom = $originalBooking['total_price'] / count($roomIds);
                
                for ($i = 1; $i < count($roomIds); $i++) {
                    $stmt = $connection->prepare("
                        INSERT INTO bookings (
                            user_id, room_id, check_in_date, check_out_date, total_price, 
                            selected_currency, special_requests, discount_amount, payment_status, 
                            payment_method, paid_amount, booking_reference, guest_name, 
                            guest_email, guest_phone, passport_number, id_number, status, 
                            is_multi_room, primary_booking_id, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $success = $stmt->execute([
                        $originalBooking['user_id'],
                        $roomIds[$i],
                        $originalBooking['check_in_date'],
                        $originalBooking['check_out_date'],
                        $pricePerRoom,
                        $originalBooking['selected_currency'],
                        $originalBooking['special_requests'],
                        $originalBooking['discount_amount'] / count($roomIds),
                        $originalBooking['payment_status'],
                        $originalBooking['payment_method'],
                        $originalBooking['paid_amount'] / count($roomIds),
                        $originalBooking['booking_reference'] . '-R' . ($i + 1),
                        $originalBooking['guest_name'],
                        $originalBooking['guest_email'],
                        $originalBooking['guest_phone'],
                        $originalBooking['passport_number'],
                        $originalBooking['id_number'],
                        $originalBooking['status'],
                        1, // is_multi_room
                        $bookingId, // primary_booking_id
                        $originalBooking['created_at']
                    ]);
                    
                    if ($success) {
                        $createdBookings[] = $connection->lastInsertId();
                        echo "<p>✅ Created booking for Room " . $roomNumbers[$i] . "</p>";
                    }
                }
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
                echo "<h4>🎉 Conversion Complete!</h4>";
                echo "<p>Successfully converted booking #{$bookingId} to multi-room format.</p>";
                echo "<p><strong>Created bookings:</strong> " . implode(', ', $createdBookings) . "</p>";
                echo "<p><strong>Rooms covered:</strong> " . implode(', ', $roomNumbers) . "</p>";
                echo "</div>";
                
                echo "<p><a href='calendar_view.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Calendar</a></p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<script>
function fixBooking(bookingId) {
    if (confirm('Convert this booking to proper multi-room format?')) {
        // Auto-submit the form for this booking
        document.querySelector(`input[value="${bookingId}"]`).closest('form').submit();
    }
}
</script>