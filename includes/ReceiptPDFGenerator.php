<?php
class ReceiptPDFGenerator {
    private $booking;
    private $nights;
    
    public function __construct($booking) {
        $this->booking = $booking;
        $this->nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
    }
    
    public function generatePDF() {
        // Start output buffering
        ob_start();
        
        // Create HTML content for PDF conversion
        $html = $this->generateHTML();
        
        // Clean any previous output
        ob_end_clean();
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="receipt-' . $this->booking['booking_reference'] . '.pdf"');
        
        // For now, we'll use a simple HTML to PDF conversion
        // In production, you'd want to use a proper PDF library
        $this->htmlToPDF($html);
    }
    
    private function generateHTML() {
        $paymentStatusEmoji = [
            'pending' => '⏳ Pending',
            'paid' => '✅ Paid',
            'partial' => '⚡ Partial',
            'refunded' => '↩️ Refunded'
        ];
        
        $paymentStatus = $paymentStatusEmoji[$this->booking['payment_status']] ?? ucfirst($this->booking['payment_status']);
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Receipt - ' . htmlspecialchars($this->booking['booking_reference']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; background: #667eea; color: white; padding: 20px; margin-bottom: 20px; }
                .info-grid { display: table; width: 100%; margin-bottom: 20px; }
                .info-section { display: table-cell; width: 50%; padding: 10px; vertical-align: top; }
                .booking-details { background: #f8f9fa; padding: 15px; margin-bottom: 15px; }
                .price-breakdown { background: #e8f5e8; padding: 15px; border-left: 4px solid #28a745; }
                .price-row { display: table; width: 100%; margin-bottom: 8px; }
                .price-label { display: table-cell; }
                .price-value { display: table-cell; text-align: right; }
                .total { border-top: 2px solid #28a745; padding-top: 10px; font-weight: bold; }
                h1, h2, h3 { margin: 0 0 10px 0; }
                .status-badge { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🏨 AiNi Hotel</h1>
                <h2>Booking Receipt</h2>
                <p>Reference: <strong>' . htmlspecialchars($this->booking['booking_reference']) . '</strong></p>
                <p>Issued: ' . date('F j, Y g:i A', strtotime($this->booking['created_at'])) . '</p>
            </div>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3>🏨 Hotel Information</h3>
                    <p><strong>AiNi Hotel</strong><br>
                    123 Main Street<br>
                    Lima, Peru 15001<br>
                    Phone: +51 1 234 5678<br>
                    Email: reservas@ainihotel.com<br>
                    Web: www.ainihotel.com</p>
                </div>
                
                <div class="info-section">
                    <h3>👤 Guest Information</h3>
                    <p><strong>' . htmlspecialchars($this->booking['guest_name'] ?? ($this->booking['first_name'] . ' ' . $this->booking['last_name'])) . '</strong><br>
                    Email: ' . htmlspecialchars($this->booking['guest_email'] ?? $this->booking['email']) . '<br>
                    ' . (($this->booking['guest_phone'] ?? $this->booking['phone']) ? 'Phone: ' . htmlspecialchars($this->booking['guest_phone'] ?? $this->booking['phone']) . '<br>' : '') . '
                    Guest ID: #' . $this->booking['user_id'] . '</p>
                </div>
            </div>
            
            <div class="booking-details">
                <h3>📋 Booking Details</h3>
                <p><strong>Room:</strong> ' . htmlspecialchars($this->booking['room_number']) . ' - ' . htmlspecialchars($this->booking['room_type']) . '</p>
                <p><strong>Check-in:</strong> ' . date('M j, Y', strtotime($this->booking['check_in_date'])) . '</p>
                <p><strong>Check-out:</strong> ' . date('M j, Y', strtotime($this->booking['check_out_date'])) . '</p>
                <p><strong>Nights:</strong> ' . $this->nights . '</p>
                <p><strong>Status:</strong> <span class="status-badge">' . ucfirst($this->booking['status']) . '</span></p>
                <p><strong>Payment:</strong> <span class="status-badge">' . $paymentStatus . '</span></p>
                ' . ($this->booking['payment_method'] ? '<p><strong>Payment Method:</strong> ' . ucfirst($this->booking['payment_method']) . '</p>' : '') . '
                ' . ($this->booking['special_requests'] ? '<p><strong>Special Requests:</strong><br>' . nl2br(htmlspecialchars($this->booking['special_requests'])) . '</p>' : '') . '
            </div>
            
            <div class="price-breakdown">
                <h3>💰 Price Breakdown</h3>
                
                <div class="price-row">
                    <div class="price-label">Room Rate (' . $this->nights . ' night' . ($this->nights > 1 ? 's' : '') . '):</div>
                    <div class="price-value">$' . number_format(($this->booking['total_price'] + ($this->booking['discount_amount'] ?? 0)), 2) . ' USD</div>
                </div>
                
                ' . (($this->booking['discount_amount'] ?? 0) > 0 ? '
                <div class="price-row">
                    <div class="price-label">Discount Applied:</div>
                    <div class="price-value" style="color: #28a745;">-$' . number_format($this->booking['discount_amount'], 2) . ' USD</div>
                </div>' : '') . '
                
                <div class="price-row total">
                    <div class="price-label">Total Amount:</div>
                    <div class="price-value">$' . number_format($this->booking['total_price'], 2) . ' USD / S/ ' . number_format($this->booking['total_price'] * 3.75, 2) . ' PEN</div>
                </div>
                
                ' . (($this->booking['paid_amount'] ?? 0) > 0 ? '
                <div class="price-row">
                    <div class="price-label">Amount Paid:</div>
                    <div class="price-value" style="color: #17a2b8;">$' . number_format($this->booking['paid_amount'], 2) . ' USD / S/ ' . number_format($this->booking['paid_amount'] * 3.75, 2) . ' PEN</div>
                </div>' : '') . '
                
                ' . ($this->booking['payment_status'] === 'partial' ? '
                <div class="price-row">
                    <div class="price-label">Outstanding Balance:</div>
                    <div class="price-value" style="color: #dc3545; font-weight: bold;">$' . number_format($this->booking['total_price'] - ($this->booking['paid_amount'] ?? 0), 2) . ' USD</div>
                </div>' : '') . '
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; font-size: 12px;">
                <strong>Terms & Conditions:</strong><br>
                • Check-in time: 3:00 PM | Check-out time: 12:00 PM<br>
                • Valid government-issued photo ID required at check-in<br>
                • Cancellation policy applies as per booking terms<br>
                • For inquiries, contact us at reservas@ainihotel.com or +51 1 234 5678<br>
                <br>
                <em>Thank you for choosing AiNi Hotel!</em>
            </div>
        </body>
        </html>';
    }
    
    private function htmlToPDF($html) {
        // For basic PDF generation without external libraries
        // This is a simplified approach - in production use a proper PDF library
        
        // We'll use DomPDF-like approach with wkhtmltopdf if available
        // or fallback to HTML output with PDF headers
        
        if (function_exists('exec') && $this->commandExists('wkhtmltopdf')) {
            $this->generateWithWkhtmltopdf($html);
        } else {
            // Fallback: output HTML with PDF mime type
            echo $html;
        }
    }
    
    private function commandExists($command) {
        $return = shell_exec(sprintf("which %s", escapeshellarg($command)));
        return !empty($return);
    }
    
    private function generateWithWkhtmltopdf($html) {
        // Create temporary HTML file
        $tempHtml = tempnam(sys_get_temp_dir(), 'receipt_') . '.html';
        file_put_contents($tempHtml, $html);
        
        // Generate PDF
        $pdfOutput = tempnam(sys_get_temp_dir(), 'receipt_') . '.pdf';
        $command = sprintf('wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in %s %s',
            escapeshellarg($tempHtml),
            escapeshellarg($pdfOutput)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($pdfOutput)) {
            readfile($pdfOutput);
            unlink($pdfOutput);
        } else {
            echo $html; // Fallback
        }
        
        unlink($tempHtml);
    }
}
?>