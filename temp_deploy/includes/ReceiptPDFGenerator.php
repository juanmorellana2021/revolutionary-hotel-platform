<?php
// Check if Composer autoload exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (!class_exists('TCPDF')) {
    // Try to load TCPDF from common locations
    $possible_paths = [
        __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/tcpdf/tcpdf.php',
        __DIR__ . '/../tcpdf/tcpdf.php'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

class ReceiptPDFGenerator {
    private $booking;
    private $nights;
    
    public function __construct($booking) {
        $this->booking = $booking;
        $this->nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
        
        // Process guest data if not already processed
        if (empty($booking['display_guest_name']) && !empty($booking['all_guests'])) {
            $this->processGuestData();
        }
    }
    
    private function processGuestData() {
        $this->booking['guests_list'] = [];
        $this->booking['primary_guest_name'] = null;
        
        if (!empty($this->booking['all_guests'])) {
            $guestsData = explode(';;;', $this->booking['all_guests']);
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
                        $this->booking['guests_list'][] = $guest;
                        
                        if ($guest['is_primary']) {
                            $this->booking['primary_guest_name'] = $guest['name'];
                        }
                    }
                }
            }
        }
        
        // Set display names
        $this->booking['display_guest_name'] = $this->booking['primary_guest_name'] ?? $this->booking['guest_name'] ?? ($this->booking['first_name'] . ' ' . $this->booking['last_name']);
        $this->booking['all_guest_names'] = !empty($this->booking['guests_list']) ? implode(', ', array_column($this->booking['guests_list'], 'name')) : $this->booking['display_guest_name'];
    }
    
    public function generatePDF($outputType = 'download') {
        try {
            if (class_exists('TCPDF')) {
                return $this->generateWithTCPDF($outputType);
            } else {
                return $this->generateSimplePDF();
            }
        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            return $this->generateSimplePDF();
        }
    }
    
    public function getPDFContent() {
        // Return PDF content as string for email attachment
        return $this->generatePDF('string');
    }
    
    private function generateWithTCPDF($outputType = 'download') {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('AiNi Hotel Management System');
        $pdf->SetAuthor('AiNi Hotel');
        $pdf->SetTitle('Hotel Receipt - ' . $this->booking['booking_reference']);
        $pdf->SetSubject('Hotel Booking Receipt');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'AiNi Hotel', 'Booking Receipt - ' . $this->booking['booking_reference']);
        
        // Set header and footer fonts
        $pdf->setHeaderFont(['helvetica', '', 14]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Generate content
        $html = $this->generatePDFContent();
        
        // Print text using writeHTMLCell()
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Clean any output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Output based on type
        if ($outputType === 'string') {
            // Return PDF as string for email attachment
            return $pdf->Output('receipt-' . $this->booking['booking_reference'] . '.pdf', 'S');
        } else {
            // Download PDF
            $pdf->Output('receipt-' . $this->booking['booking_reference'] . '.pdf', 'D');
            exit;
        }
    }
    
    private function generateSimplePDF() {
        // Fallback: Generate a simple text-based PDF
        $content = $this->generateTextReceipt();
        
        // Clean any output buffer
        ob_clean();
        
        // Set headers for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="receipt-' . $this->booking['booking_reference'] . '.pdf"');
        header('Content-Length: ' . strlen($content));
        
        echo $content;
        exit;
    }
    
    private function generatePDFContent() {
        $paymentStatusEmoji = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'partial' => 'Partial',
            'refunded' => 'Refunded'
        ];
        
        $paymentStatus = $paymentStatusEmoji[$this->booking['payment_status']] ?? ucfirst($this->booking['payment_status']);
        $guestName = $this->booking['display_guest_name'] ?? $this->booking['guest_name'] ?? ($this->booking['first_name'] . ' ' . $this->booking['last_name']);
        
        return '
        <style>
            .header { background-color: #667eea; color: white; padding: 15px; text-align: center; margin-bottom: 20px; }
            .info-section { margin-bottom: 15px; }
            .info-grid { width: 100%; }
            .info-grid td { width: 50%; vertical-align: top; padding: 10px; }
            .booking-details { background-color: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
            .price-breakdown { background-color: #e8f5e8; padding: 15px; border-left: 4px solid #28a745; margin-bottom: 15px; }
            .price-row { margin-bottom: 8px; }
            .total { border-top: 2px solid #28a745; padding-top: 10px; font-weight: bold; font-size: 14px; }
            .terms { background-color: #f8f9fa; padding: 15px; font-size: 9px; margin-top: 20px; }
            h1, h2, h3 { margin: 0 0 10px 0; color: #333; }
            .status-badge { background-color: #d4edda; color: #155724; padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        </style>
        
        <div class="header">
            <h1>AiNi Hotel</h1>
            <h2>Booking Receipt</h2>
            <p><strong>Reference: ' . htmlspecialchars($this->booking['booking_reference']) . '</strong></p>
            <p>Issued: ' . date('F j, Y g:i A', strtotime($this->booking['created_at'])) . '</p>
        </div>
        
        <table class="info-grid" cellpadding="5" cellspacing="0" border="0">
            <tr>
                <td>
                    <h3>Hotel Information</h3>
                    <p><strong>AiNi Hotel</strong><br>
                    123 Main Street<br>
                    Lima, Peru 15001<br>
                    Phone: +51 1 234 5678<br>
                    Email: reservas@ainihotel.com<br>
                    Web: www.ainihotel.com</p>
                </td>
                <td>
                    <h3>Guest Information</h3>
                    <p><strong>' . htmlspecialchars($guestName) . '</strong><br>
                    Email: ' . htmlspecialchars($this->booking['guest_email'] ?? $this->booking['email']) . '<br>
                    ' . (($this->booking['guest_phone'] ?? $this->booking['phone']) ? 'Phone: ' . htmlspecialchars($this->booking['guest_phone'] ?? $this->booking['phone']) . '<br>' : '') . '
                    Guest ID: #' . $this->booking['user_id'] . '</p>
                </td>
            </tr>
        </table>
        
        <div class="booking-details">
            <h3>Booking Details</h3>
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
            <h3>Price Breakdown</h3>
            
            <div class="price-row">
                <table width="100%" cellpadding="2" cellspacing="0">
                    <tr>
                        <td>Room Rate (' . $this->nights . ' night' . ($this->nights > 1 ? 's' : '') . '):</td>
                        <td align="right">$' . number_format(($this->booking['total_price'] + ($this->booking['discount_amount'] ?? 0)), 2) . ' USD</td>
                    </tr>';
                    
        if (($this->booking['discount_amount'] ?? 0) > 0) {
            $html .= '
                    <tr>
                        <td>Discount Applied:</td>
                        <td align="right" style="color: #28a745;">-$' . number_format($this->booking['discount_amount'], 2) . ' USD</td>
                    </tr>';
        }
        
        $html .= '
                    <tr class="total">
                        <td><strong>Total Amount:</strong></td>
                        <td align="right"><strong>$' . number_format($this->booking['total_price'], 2) . ' USD / S/ ' . number_format($this->booking['total_price'] * 3.75, 2) . ' PEN</strong></td>
                    </tr>';
                    
        if (($this->booking['paid_amount'] ?? 0) > 0) {
            $html .= '
                    <tr>
                        <td>Amount Paid:</td>
                        <td align="right" style="color: #17a2b8;">$' . number_format($this->booking['paid_amount'], 2) . ' USD / S/ ' . number_format($this->booking['paid_amount'] * 3.75, 2) . ' PEN</td>
                    </tr>';
        }
        
        if ($this->booking['payment_status'] === 'partial') {
            $html .= '
                    <tr>
                        <td><strong>Outstanding Balance:</strong></td>
                        <td align="right" style="color: #dc3545;"><strong>$' . number_format($this->booking['total_price'] - ($this->booking['paid_amount'] ?? 0), 2) . ' USD</strong></td>
                    </tr>';
        }
        
        $html .= '
                </table>
            </div>
        </div>
        
        <div class="terms">
            <strong>Terms & Conditions:</strong><br>
            • Check-in time: 3:00 PM | Check-out time: 12:00 PM<br>
            • Valid government-issued photo ID required at check-in<br>
            • Cancellation policy applies as per booking terms<br>
            • For inquiries, contact us at reservas@ainihotel.com or +51 1 234 5678<br>
            <br>
            <em>Thank you for choosing AiNi Hotel!</em>
        </div>';
        
        return $html;
    }
    
    private function generateTextReceipt() {
        $guestName = $this->booking['display_guest_name'] ?? $this->booking['guest_name'] ?? ($this->booking['first_name'] . ' ' . $this->booking['last_name']);
        
        $content = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Resources <<
/Font <<
/F1 4 0 R
>>
>>
/Contents 5 0 R
>>
endobj

4 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

5 0 obj
<<
/Length 2000
>>
stream
BT
/F1 20 Tf
50 750 Td
(AiNi Hotel - Booking Receipt) Tj
0 -30 Td
/F1 12 Tf
(Reference: " . $this->booking['booking_reference'] . ") Tj
0 -20 Td
(Issued: " . date('F j, Y g:i A', strtotime($this->booking['created_at'])) . ") Tj
0 -40 Td
(HOTEL INFORMATION) Tj
0 -15 Td
/F1 10 Tf
(AiNi Hotel) Tj
0 -12 Td
(123 Main Street, Lima, Peru 15001) Tj
0 -12 Td
(Phone: +51 1 234 5678) Tj
0 -12 Td
(Email: reservas@ainihotel.com) Tj
0 -30 Td
/F1 12 Tf
(GUEST INFORMATION) Tj
0 -15 Td
/F1 10 Tf
(Name: " . $guestName . ") Tj
0 -12 Td
(Email: " . ($this->booking['guest_email'] ?? $this->booking['email']) . ") Tj
0 -12 Td
(Guest ID: #" . $this->booking['user_id'] . ") Tj
0 -30 Td
/F1 12 Tf
(BOOKING DETAILS) Tj
0 -15 Td
/F1 10 Tf
(Room: " . $this->booking['room_number'] . " - " . $this->booking['room_type'] . ") Tj
0 -12 Td
(Check-in: " . date('M j, Y', strtotime($this->booking['check_in_date'])) . ") Tj
0 -12 Td
(Check-out: " . date('M j, Y', strtotime($this->booking['check_out_date'])) . ") Tj
0 -12 Td
(Nights: " . $this->nights . ") Tj
0 -12 Td
(Status: " . ucfirst($this->booking['status']) . ") Tj
0 -12 Td
(Payment Status: " . ucfirst($this->booking['payment_status']) . ") Tj
0 -30 Td
/F1 12 Tf
(PRICE BREAKDOWN) Tj
0 -15 Td
/F1 10 Tf
(Total Amount: $" . number_format($this->booking['total_price'], 2) . " USD) Tj
0 -12 Td
(Amount Paid: $" . number_format(($this->booking['paid_amount'] ?? 0), 2) . " USD) Tj
0 -30 Td
(Thank you for choosing AiNi Hotel!) Tj
ET
endstream
endobj

xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000079 00000 n 
0000000173 00000 n 
0000000301 00000 n 
0000000380 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
2435
%%EOF";
        
        return $content;
    }


    

}
?>