<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

// Get booking ID from URL parameter
$bookingId = $_GET['booking_id'] ?? null;

if (!$bookingId) {
    die('Booking ID is required');
}

// Database connection
$database = new Database();
$connection = $database->getConnection();

// Get booking details with room and user information
$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type, r.price as room_price,
           u.first_name, u.last_name, u.email, u.phone
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking not found');
}

$nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($booking['booking_reference']); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .receipt-body {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .booking-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .price-breakdown {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .price-row.total {
            border-top: 2px solid #28a745;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-partial {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        @media print {
            body {
                background: white;
            }
            
            .receipt {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .actions {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .receipt-header {
                padding: 20px;
            }
            
            .receipt-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="receipt-header">
            <h1>🏨 AiNi Hotel</h1>
            <h2>Booking Receipt</h2>
            <p style="margin: 10px 0 0 0; font-size: 1.1em;">
                Reference: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
            </p>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">
                Issued: <?php echo date('F j, Y g:i A', strtotime($booking['created_at'])); ?>
            </p>
        </div>
        
        <!-- Body -->
        <div class="receipt-body">
            <!-- Hotel and Guest Information -->
            <div class="info-grid">
                <div class="info-section">
                    <h3>🏨 Hotel Information</h3>
                    <p><strong>AiNi Hotel</strong><br>
                    123 Main Street<br>
                    Lima, Peru 15001<br>
                    📞 +51 1 234 5678<br>
                    📧 reservas@ainihotel.com<br>
                    🌐 www.ainihotel.com</p>
                </div>
                
                <div class="info-section">
                    <h3>👤 Guest Information</h3>
                    <p><strong><?php echo htmlspecialchars($booking['guest_name'] ?? ($booking['first_name'] . ' ' . $booking['last_name'])); ?></strong><br>
                    📧 <?php echo htmlspecialchars($booking['guest_email'] ?? $booking['email']); ?><br>
                    <?php if ($booking['guest_phone'] ?? $booking['phone']): ?>
                    📱 <?php echo htmlspecialchars($booking['guest_phone'] ?? $booking['phone']); ?><br>
                    <?php endif; ?>
                    🆔 Guest ID: #<?php echo $booking['user_id']; ?></p>
                </div>
            </div>
            
            <!-- Booking Details -->
            <div class="booking-details">
                <h3 style="margin-top: 0;">📋 Booking Details</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <strong>🏠 Room:</strong> <?php echo htmlspecialchars($booking['room_number']); ?> - <?php echo htmlspecialchars($booking['room_type']); ?>
                    </div>
                    <div>
                        <strong>📅 Check-in:</strong> <?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?>
                    </div>
                    <div>
                        <strong>📅 Check-out:</strong> <?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?>
                    </div>
                    <div>
                        <strong>🌙 Nights:</strong> <?php echo $nights; ?>
                    </div>
                    <div>
                        <strong>📋 Status:</strong> 
                        <span class="status-badge <?php echo $booking['status'] === 'confirmed' ? 'status-paid' : 'status-pending'; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>
                    <div>
                        <strong>💳 Payment:</strong> 
                        <span class="status-badge status-<?php echo $booking['payment_status']; ?>">
                            <?php 
                            $statusEmoji = [
                                'pending' => '⏳ Pending',
                                'paid' => '✅ Paid',
                                'partial' => '⚡ Partial',
                                'refunded' => '↩️ Refunded'
                            ];
                            echo $statusEmoji[$booking['payment_status']] ?? ucfirst($booking['payment_status']);
                            ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($booking['payment_method']): ?>
                <div style="margin-top: 15px;">
                    <strong>💰 Payment Method:</strong> <?php echo ucfirst($booking['payment_method']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($booking['special_requests']): ?>
                <div style="margin-top: 15px; padding: 10px; background: #fff3e0; border-radius: 5px;">
                    <strong>📝 Special Requests:</strong><br>
                    <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Price Breakdown -->
            <div class="price-breakdown">
                <h3 style="margin-top: 0; color: #28a745;">💰 Price Breakdown</h3>
                
                <div class="price-row">
                    <span>Room Rate (<?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>):</span>
                    <span>$<?php echo number_format(($booking['total_price'] + ($booking['discount_amount'] ?? 0)), 2); ?> USD</span>
                </div>
                
                <?php if (($booking['discount_amount'] ?? 0) > 0): ?>
                <div class="price-row" style="color: #28a745;">
                    <span>Discount Applied:</span>
                    <span>-$<?php echo number_format($booking['discount_amount'], 2); ?> USD</span>
                </div>
                <?php endif; ?>
                
                <div class="price-row total">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($booking['total_price'], 2); ?> USD / S/ <?php echo number_format($booking['total_price'] * 3.75, 2); ?> PEN</span>
                </div>
                
                <?php if (($booking['paid_amount'] ?? 0) > 0): ?>
                <div class="price-row" style="color: #17a2b8; margin-top: 10px;">
                    <span>Amount Paid:</span>
                    <span>$<?php echo number_format($booking['paid_amount'], 2); ?> USD / S/ <?php echo number_format($booking['paid_amount'] * 3.75, 2); ?> PEN</span>
                </div>
                <?php endif; ?>
                
                <?php if ($booking['payment_status'] === 'partial'): ?>
                <div class="price-row" style="color: #dc3545; font-weight: bold; margin-top: 5px;">
                    <span>Outstanding Balance:</span>
                    <span>$<?php echo number_format($booking['total_price'] - ($booking['paid_amount'] ?? 0), 2); ?> USD</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Terms and Conditions -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 0.9em; color: #666;">
                <strong>Terms & Conditions:</strong><br>
                • Check-in time: 3:00 PM | Check-out time: 12:00 PM<br>
                • Valid government-issued photo ID required at check-in<br>
                • Cancellation policy applies as per booking terms<br>
                • For inquiries, contact us at reservas@ainihotel.com or +51 1 234 5678
            </div>
            
            <!-- Actions -->
            <div class="actions">
                <button onclick="window.print()" class="btn btn-primary">🖨️ Print Receipt</button>
                <a href="mailto:?subject=Hotel Booking Receipt&body=Please find attached your booking receipt for reservation <?php echo htmlspecialchars($booking['booking_reference']); ?>" class="btn btn-info">📧 Email Receipt</a>
                <a href="javascript:window.close()" class="btn btn-success">✅ Done</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print if requested
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>