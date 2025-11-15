<?php
session_start();
require_once __DIR__ . '/includes/classes.php';
require_once __DIR__ . '/includes/hotel_classes.php';
require_once __DIR__ . '/includes/ReceiptPDFGenerator.php';
require_once __DIR__ . '/includes/ReceiptEmailSender.php';

// Get parameters
$bookingId = $_GET['booking_id'] ?? null;
$action = $_GET['action'] ?? 'view'; // view, pdf, email, print

if (!$bookingId) {
    die('Booking ID is required');
}

// Database connection
$database = new Database();
$connection = $database->getConnection();

// Get booking details with room and user information
$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type, r.price as room_price,
           u.first_name, u.last_name, u.email, u.phone,
           GROUP_CONCAT(DISTINCT CONCAT(bg.guest_name, '|', COALESCE(bg.guest_email, ''), '|', COALESCE(bg.guest_phone, ''), '|', bg.is_primary) SEPARATOR ';;;') as all_guests
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    LEFT JOIN booking_guests bg ON b.id = bg.booking_id
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if ($booking) {
    // Process multiple guests data
    $booking['guests_list'] = [];
    $booking['primary_guest_name'] = null;
    
    if (!empty($booking['all_guests'])) {
        $guestsData = explode(';;;', $booking['all_guests']);
        foreach ($guestsData as $guestData) {
            if (!empty($guestData)) {
                $parts = explode('|', $guestData);
                if (count($parts) >= 4) {
                    $guest = [
                        'name' => $parts[0],
                        'email' => $parts[1],
                        'phone' => $parts[2],
                        'is_primary' => (bool)$parts[3]
                    ];
                    $booking['guests_list'][] = $guest;
                    
                    if ($guest['is_primary']) {
                        $booking['primary_guest_name'] = $guest['name'];
                    }
                }
            }
        }
    }
    
    // Set display names
    $booking['display_guest_name'] = $booking['primary_guest_name'] ?? $booking['guest_name'] ?? ($booking['first_name'] . ' ' . $booking['last_name']);
    $booking['all_guest_names'] = !empty($booking['guests_list']) ? implode(', ', array_column($booking['guests_list'], 'name')) : $booking['display_guest_name'];
}

if (!$booking) {
    die('Booking not found');
}

// Generate unique receipt number
function generateReceiptNumber($bookingId) {
    return 'RCP-' . date('Ymd') . '-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
}

// Log receipt generation to database
function logReceiptGeneration($connection, $bookingId, $booking, $action, $emailTo = null) {
    $receiptNumber = generateReceiptNumber($bookingId);
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $connection->prepare("
        INSERT INTO receipts (
            booking_id, receipt_number, receipt_type, 
            guest_name, guest_email, total_amount, payment_status,
            generated_by, email_sent_to, email_sent_at, 
            ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $emailSentAt = ($action === 'email' && $emailTo) ? date('Y-m-d H:i:s') : null;
    
    $stmt->execute([
        $bookingId,
        $receiptNumber,
        $action,
        $booking['display_guest_name'],
        $booking['guest_email'] ?? $booking['email'],
        $booking['total_price'],
        $booking['payment_status'],
        $userId,
        $emailTo,
        $emailSentAt,
        $ipAddress,
        $userAgent
    ]);
    
    return $receiptNumber;
}

// Handle different actions
switch ($action) {
    case 'pdf':
        // Log PDF generation
        $receiptNumber = logReceiptGeneration($connection, $bookingId, $booking, 'pdf');
        
        $pdfGenerator = new ReceiptPDFGenerator($booking);
        $pdfGenerator->generatePDF();
        exit;
        
    case 'email':
        // Log email action
        $customEmail = $_GET['email'] ?? null;
        $receiptNumber = logReceiptGeneration($connection, $bookingId, $booking, 'email', $customEmail);
        
        $emailSender = new ReceiptEmailSender();
        $result = $emailSender->sendReceiptEmail($booking, $customEmail);
        
        // Return JSON response for AJAX calls
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        // Redirect with message for regular requests
        $message = $result['success'] ? 'success' : 'error';
        header("Location: receipt.php?booking_id={$bookingId}&message={$message}&msg=" . urlencode($result['message']));
        exit;
        
    case 'print':
        // Log print action
        $receiptNumber = logReceiptGeneration($connection, $bookingId, $booking, 'print');
        
        // This will show the receipt with print-optimized styles
        $printMode = true;
        break;
        
    default:
        // Log view action
        $receiptNumber = logReceiptGeneration($connection, $bookingId, $booking, 'view');
        
        $printMode = false;
        break;
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
            padding: <?php echo $printMode ? '0' : '20px'; ?>;
            background: <?php echo $printMode ? 'white' : '#f5f5f5'; ?>;
        }
        
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: <?php echo $printMode ? '0' : '8px'; ?>;
            box-shadow: <?php echo $printMode ? 'none' : '0 2px 10px rgba(0,0,0,0.1)'; ?>;
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
            <?php echo $printMode ? 'display: none;' : ''; ?>
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 8px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
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
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
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
        
        #emailModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt {
                box-shadow: none;
                border: none;
            }
            
            .actions {
                display: none !important;
            }
            
            .message {
                display: none !important;
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
            
            .btn {
                display: block;
                margin: 10px auto;
                width: 80%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['message']) && isset($_GET['msg'])): ?>
        <div class="message <?php echo $_GET['message']; ?>">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

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
                    <?php if (!empty($booking['guests_list']) && count($booking['guests_list']) > 1): ?>
                        <p><strong>Guests (<?php echo count($booking['guests_list']); ?>):</strong></p>
                        <?php foreach ($booking['guests_list'] as $index => $guest): ?>
                            <div style="margin-bottom: 8px; padding: 8px; background: <?php echo $guest['is_primary'] ? '#e8f5e8' : '#f8f9fa'; ?>; border-radius: 4px;">
                                <strong><?php echo htmlspecialchars($guest['name']); ?></strong>
                                <?php if ($guest['is_primary']): ?>
                                    <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8em; margin-left: 5px;">Primary</span>
                                <?php endif; ?>
                                <br>
                                <?php if (!empty($guest['email'])): ?>
                                    📧 <?php echo htmlspecialchars($guest['email']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($guest['phone'])): ?>
                                    📱 <?php echo htmlspecialchars($guest['phone']); ?><br>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><strong><?php echo htmlspecialchars($booking['display_guest_name']); ?></strong><br>
                        📧 <?php echo htmlspecialchars($booking['guest_email'] ?? $booking['email']); ?><br>
                        <?php if ($booking['guest_phone'] ?? $booking['phone']): ?>
                        📱 <?php echo htmlspecialchars($booking['guest_phone'] ?? $booking['phone']); ?><br>
                        <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <p>🆔 Guest ID: #<?php echo $booking['user_id']; ?></p>
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
                <a href="receipt_handler.php?booking_id=<?php echo $booking['id']; ?>&action=pdf" class="btn btn-success" target="_blank">📄 Download PDF</a>
                <button onclick="showEmailModal()" class="btn btn-info">📧 Email Receipt</button>
                <a href="javascript:window.close()" class="btn btn-warning">✅ Close</a>
            </div>
        </div>
    </div>
    
    <!-- Email Modal -->
    <div id="emailModal">
        <div class="modal-content">
            <span class="close" onclick="closeEmailModal()">&times;</span>
            <h3>📧 Send Receipt via Email</h3>
            <form id="emailForm">
                <div class="form-group">
                    <label for="emailAddress">Email Address:</label>
                    <input type="email" id="emailAddress" name="email" value="<?php echo htmlspecialchars($booking['guest_email'] ?? $booking['email']); ?>" required>
                </div>
                <div style="text-align: center;">
                    <button type="button" onclick="sendEmail()" class="btn btn-success">📧 Send Receipt</button>
                    <button type="button" onclick="closeEmailModal()" class="btn" style="background: #6c757d; color: white;">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showEmailModal() {
            document.getElementById('emailModal').style.display = 'block';
        }
        
        function closeEmailModal() {
            document.getElementById('emailModal').style.display = 'none';
        }
        
        function sendEmail() {
            const email = document.getElementById('emailAddress').value;
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            // Show loading state
            const originalText = event.target.innerHTML;
            event.target.innerHTML = '⏳ Sending...';
            event.target.disabled = true;
            
            // Send email via AJAX
            fetch(`receipt_handler.php?booking_id=<?php echo $booking['id']; ?>&action=email&email=${encodeURIComponent(email)}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        closeEmailModal();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Error sending email: ' + error.message);
                })
                .finally(() => {
                    event.target.innerHTML = originalText;
                    event.target.disabled = false;
                });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('emailModal');
            if (event.target === modal) {
                closeEmailModal();
            }
        }
        
        // Auto-print if requested
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>