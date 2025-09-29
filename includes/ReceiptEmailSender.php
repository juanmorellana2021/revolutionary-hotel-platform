<?php
require_once __DIR__ . '/../config/EmailConfig.php';

class ReceiptEmailSender {
    private $config;
    private $hotelInfo;
    
    public function __construct() {
        $this->config = EmailConfig::getConfig();
        $this->hotelInfo = EmailConfig::getHotelInfo();
    }
    
    public function sendReceiptEmail($booking, $recipientEmail = null) {
        // Check if email is configured
        if (!EmailConfig::isConfigured()) {
            return ['success' => false, 'message' => 'Email system not configured. Please check config/EmailConfig.php'];
        }
        
        $email = $recipientEmail ?? $booking['guest_email'] ?? $booking['email'];
        
        if (!$email) {
            return ['success' => false, 'message' => 'No email address available'];
        }
        
        $subject = 'Booking Receipt - ' . $booking['booking_reference'];
        $htmlContent = $this->generateEmailHTML($booking);
        $textContent = $this->generateEmailText($booking);
        
        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }
    
    private function generateEmailHTML($booking) {
        $nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
        
        $paymentStatusEmoji = [
            'pending' => '⏳ Pending',
            'paid' => '✅ Paid',
            'partial' => '⚡ Partial',
            'refunded' => '↩️ Refunded'
        ];
        
        $paymentStatus = $paymentStatusEmoji[$booking['payment_status']] ?? ucfirst($booking['payment_status']);
        $guestName = $booking['guest_name'] ?? ($booking['first_name'] . ' ' . $booking['last_name']);
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Booking Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .info-section { margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
                .info-grid { display: table; width: 100%; }
                .info-cell { display: table-cell; width: 50%; padding: 5px; vertical-align: top; }
                .price-breakdown { background: #e8f5e8; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; }
                .price-row { display: table; width: 100%; margin-bottom: 8px; }
                .price-label { display: table-cell; }
                .price-value { display: table-cell; text-align: right; font-weight: bold; }
                .total-row { border-top: 2px solid #28a745; padding-top: 10px; margin-top: 10px; font-size: 1.1em; }
                .status-badge { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 0.9em; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 0.9em; color: #666; }
                h1, h2, h3 { margin: 0 0 15px 0; }
                .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                @media only screen and (max-width: 600px) {
                    .info-cell { display: block; width: 100%; }
                    .content { padding: 20px; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🏨 AiNi Hotel</h1>
                    <h2>Booking Confirmation & Receipt</h2>
                    <p style="margin: 10px 0; font-size: 1.1em;">
                        Reference: <strong>' . htmlspecialchars($booking['booking_reference']) . '</strong>
                    </p>
                </div>
                
                <div class="content">
                    <p>Dear <strong>' . htmlspecialchars($guestName) . '</strong>,</p>
                    
                    <p>Thank you for choosing AiNi Hotel! Your booking has been confirmed. Please find your receipt details below:</p>
                    
                    <div class="info-section">
                        <h3>📋 Booking Details</h3>
                        <div class="info-grid">
                            <div class="info-cell">
                                <strong>🏠 Room:</strong> ' . htmlspecialchars($booking['room_number']) . ' - ' . htmlspecialchars($booking['room_type']) . '<br>
                                <strong>📅 Check-in:</strong> ' . date('M j, Y', strtotime($booking['check_in_date'])) . '<br>
                                <strong>📅 Check-out:</strong> ' . date('M j, Y', strtotime($booking['check_out_date'])) . '<br>
                                <strong>🌙 Nights:</strong> ' . $nights . '
                            </div>
                            <div class="info-cell">
                                <strong>📋 Status:</strong> <span class="status-badge">' . ucfirst($booking['status']) . '</span><br>
                                <strong>💳 Payment:</strong> <span class="status-badge">' . $paymentStatus . '</span><br>
                                ' . ($booking['payment_method'] ? '<strong>💰 Method:</strong> ' . ucfirst($booking['payment_method']) . '<br>' : '') . '
                                <strong>📅 Booked:</strong> ' . date('M j, Y', strtotime($booking['created_at'])) . '
                            </div>
                        </div>
                        ' . ($booking['special_requests'] ? '<p style="margin-top: 15px;"><strong>📝 Special Requests:</strong><br>' . nl2br(htmlspecialchars($booking['special_requests'])) . '</p>' : '') . '
                    </div>
                    
                    <div class="price-breakdown">
                        <h3>💰 Price Summary</h3>
                        
                        <div class="price-row">
                            <div class="price-label">Room Rate (' . $nights . ' night' . ($nights > 1 ? 's' : '') . '):</div>
                            <div class="price-value">$' . number_format(($booking['total_price'] + ($booking['discount_amount'] ?? 0)), 2) . '</div>
                        </div>
                        
                        ' . (($booking['discount_amount'] ?? 0) > 0 ? '
                        <div class="price-row" style="color: #28a745;">
                            <div class="price-label">Discount Applied:</div>
                            <div class="price-value">-$' . number_format($booking['discount_amount'], 2) . '</div>
                        </div>' : '') . '
                        
                        <div class="price-row total-row">
                            <div class="price-label"><strong>Total Amount:</strong></div>
                            <div class="price-value">$' . number_format($booking['total_price'], 2) . ' USD</div>
                        </div>
                        
                        <div class="price-row">
                            <div class="price-label">Amount in PEN:</div>
                            <div class="price-value">S/ ' . number_format($booking['total_price'] * 3.75, 2) . '</div>
                        </div>
                        
                        ' . (($booking['paid_amount'] ?? 0) > 0 ? '
                        <div class="price-row" style="color: #17a2b8; margin-top: 10px;">
                            <div class="price-label">Amount Paid:</div>
                            <div class="price-value">$' . number_format($booking['paid_amount'], 2) . '</div>
                        </div>' : '') . '
                        
                        ' . ($booking['payment_status'] === 'partial' ? '
                        <div class="price-row" style="color: #dc3545; font-weight: bold;">
                            <div class="price-label">Outstanding Balance:</div>
                            <div class="price-value">$' . number_format($booking['total_price'] - ($booking['paid_amount'] ?? 0), 2) . '</div>
                        </div>' : '') . '
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <p><strong>Need to make changes or have questions?</strong></p>
                        <p>Contact us at <a href="mailto:reservas@ainihotel.com">reservas@ainihotel.com</a> or call +51 1 234 5678</p>
                    </div>
                    
                    <div style="background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h4 style="margin: 0 0 10px 0; color: #e65100;">📍 Important Information</h4>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li><strong>Check-in:</strong> 3:00 PM (please bring valid photo ID)</li>
                            <li><strong>Check-out:</strong> 12:00 PM</li>
                            <li><strong>Address:</strong> 123 Main Street, Lima, Peru 15001</li>
                            <li><strong>WiFi:</strong> Free high-speed internet available</li>
                            <li><strong>Parking:</strong> Complimentary parking available</li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer">
                    <p><strong>AiNi Hotel</strong> | 123 Main Street, Lima, Peru 15001</p>
                    <p>Phone: +51 1 234 5678 | Email: reservas@ainihotel.com | Web: www.ainihotel.com</p>
                    <p style="font-size: 0.8em; margin-top: 15px;">
                        This is an automated message. Please do not reply to this email.<br>
                        For assistance, contact us at the phone number or email above.
                    </p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function generateEmailText($booking) {
        $nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
        $guestName = $booking['guest_name'] ?? ($booking['first_name'] . ' ' . $booking['last_name']);
        
        $text = "AiNi Hotel - Booking Receipt\n";
        $text .= "================================\n\n";
        $text .= "Dear " . $guestName . ",\n\n";
        $text .= "Thank you for choosing AiNi Hotel! Your booking has been confirmed.\n\n";
        $text .= "BOOKING DETAILS:\n";
        $text .= "Reference: " . $booking['booking_reference'] . "\n";
        $text .= "Room: " . $booking['room_number'] . " - " . $booking['room_type'] . "\n";
        $text .= "Check-in: " . date('M j, Y', strtotime($booking['check_in_date'])) . "\n";
        $text .= "Check-out: " . date('M j, Y', strtotime($booking['check_out_date'])) . "\n";
        $text .= "Nights: " . $nights . "\n";
        $text .= "Status: " . ucfirst($booking['status']) . "\n";
        $text .= "Payment: " . ucfirst($booking['payment_status']) . "\n\n";
        
        $text .= "PRICE SUMMARY:\n";
        $text .= "Room Rate (" . $nights . " nights): $" . number_format(($booking['total_price'] + ($booking['discount_amount'] ?? 0)), 2) . "\n";
        if (($booking['discount_amount'] ?? 0) > 0) {
            $text .= "Discount Applied: -$" . number_format($booking['discount_amount'], 2) . "\n";
        }
        $text .= "Total Amount: $" . number_format($booking['total_price'], 2) . " USD / S/ " . number_format($booking['total_price'] * 3.75, 2) . " PEN\n";
        
        if (($booking['paid_amount'] ?? 0) > 0) {
            $text .= "Amount Paid: $" . number_format($booking['paid_amount'], 2) . "\n";
        }
        
        if ($booking['payment_status'] === 'partial') {
            $text .= "Outstanding Balance: $" . number_format($booking['total_price'] - ($booking['paid_amount'] ?? 0), 2) . "\n";
        }
        
        $text .= "\nIMPORTANT INFORMATION:\n";
        $text .= "- Check-in: 3:00 PM (bring valid photo ID)\n";
        $text .= "- Check-out: 12:00 PM\n";
        $text .= "- Address: 123 Main Street, Lima, Peru 15001\n";
        $text .= "- Phone: +51 1 234 5678\n";
        $text .= "- Email: reservas@ainihotel.com\n\n";
        
        $text .= "Thank you for choosing AiNi Hotel!\n\n";
        $text .= "Best regards,\n";
        $text .= "The AiNi Hotel Team";
        
        return $text;
    }
    
    private function sendEmail($to, $subject, $htmlContent, $textContent) {
        // Using PHP's built-in mail() function with proper headers
        // For production, consider using PHPMailer or similar library
        
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>';
        $headers[] = 'Reply-To: ' . $this->config['reply_to'];
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        $success = mail($to, $subject, $htmlContent, implode("\r\n", $headers));
        
        if ($success) {
            return ['success' => true, 'message' => 'Receipt sent successfully to ' . $to];
        } else {
            return ['success' => false, 'message' => 'Failed to send email. Please check your mail server configuration in config/EmailConfig.php'];
        }
    }

}
?>