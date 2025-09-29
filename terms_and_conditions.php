<?php
require_once 'includes/classes.php';

// Initialize database connection
$database = new Database();
$connection = $database->getConnection();

// Get hotel information
try {
    $stmt = $connection->prepare("SELECT * FROM hotel_info WHERE id = 1");
    $stmt->execute();
    $hotel = $stmt->fetch();
} catch (Exception $e) {
    $hotel = [
        'hotel_name' => 'Revolutionary Hotel Platform',
        'address' => 'Hotel Address',
        'city' => 'City',
        'country' => 'Country',
        'phone' => '+1-XXX-XXX-XXXX',
        'email' => 'info@hotel.com'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - <?php echo htmlspecialchars($hotel['hotel_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #667eea;
            font-size: 1.5em;
            margin-bottom: 15px;
            padding-left: 20px;
            position: relative;
        }
        
        .section h2:before {
            content: "📋";
            position: absolute;
            left: 0;
            top: 0;
        }
        
        .section h3 {
            color: #764ba2;
            font-size: 1.2em;
            margin: 20px 0 10px 0;
        }
        
        .section p, .section li {
            color: #444;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .section ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .highlight-box {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .warning-box {
            background: #fff8f0;
            border-left: 4px solid #ff9500;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .important-box {
            background: #fff0f0;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 30px;
            transition: transform 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
        }
        
        .effective-date {
            text-align: center;
            color: #666;
            font-style: italic;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Terms & Conditions</h1>
            <p><?php echo htmlspecialchars($hotel['hotel_name']); ?></p>
        </div>
        
        <div class="effective-date">
            <strong>Effective Date:</strong> <?php echo date('F j, Y'); ?> | <strong>Version:</strong> 1.0
        </div>

        <div class="section">
            <h2>🏨 Guest Responsibility & Behavior Agreement</h2>
            <div class="important-box">
                <strong>⚠️ IMPORTANT:</strong> By registering and/or booking with <?php echo htmlspecialchars($hotel['hotel_name']); ?>, you agree to all terms and conditions outlined below. This is a legally binding agreement.
            </div>
            
            <h3>🎯 Guest Conduct Standards</h3>
            <p>All guests must adhere to the following behavior standards:</p>
            <ul>
                <li><strong>Respectful Behavior:</strong> Treat all hotel staff, other guests, and property with respect and courtesy</li>
                <li><strong>Noise Policy:</strong> Maintain reasonable noise levels, especially during quiet hours (10:00 PM - 7:00 AM)</li>
                <li><strong>No Smoking:</strong> Smoking is prohibited in all rooms and designated non-smoking areas</li>
                <li><strong>Capacity Limits:</strong> Adhere to maximum occupancy limits for rooms and common areas</li>
                <li><strong>Dress Code:</strong> Maintain appropriate attire in all public areas of the hotel</li>
                <li><strong>No Illegal Activities:</strong> Strictly prohibited use of illegal substances or engaging in illegal activities</li>
            </ul>
        </div>

        <div class="section">
            <h2>💰 Property Damage & Financial Responsibility</h2>
            <div class="warning-box">
                <strong>🏠 Property Protection:</strong> Guests are fully responsible for any damage to hotel property during their stay.
            </div>
            
            <h3>🔧 Damage Policy</h3>
            <p>Guests agree to the following damage responsibility terms:</p>
            <ul>
                <li><strong>Immediate Reporting:</strong> Report any accidental damage immediately to hotel management</li>
                <li><strong>Full Liability:</strong> Guests are liable for repair or replacement costs of damaged items</li>
                <li><strong>Assessment Process:</strong> Hotel management will assess damage and provide cost estimates</li>
                <li><strong>Payment Terms:</strong> Damage charges must be paid before checkout or will be charged to provided payment method</li>
                <li><strong>Reasonable Wear:</strong> Normal wear and tear is not considered damage</li>
            </ul>
            
            <h3>💳 Damage Deposit & Charges</h3>
            <ul>
                <li><strong>Security Deposit:</strong> A refundable security deposit may be required at check-in</li>
                <li><strong>Credit Card Authorization:</strong> Valid credit card required for incidental charges</li>
                <li><strong>Damage Assessment:</strong> Professional assessment may be conducted for significant damage</li>
                <li><strong>Dispute Resolution:</strong> Disputes regarding damage charges can be addressed through hotel management</li>
            </ul>
        </div>

        <div class="section">
            <h2>🚫 Prohibited Activities</h2>
            <div class="important-box">
                <strong>⛔ Zero Tolerance:</strong> The following activities will result in immediate eviction without refund.
            </div>
            
            <ul>
                <li><strong>Illegal Drug Use:</strong> Possession or use of illegal substances</li>
                <li><strong>Excessive Intoxication:</strong> Disruptive behavior due to alcohol consumption</li>
                <li><strong>Violence or Threats:</strong> Any form of violence, threats, or intimidation</li>
                <li><strong>Theft:</strong> Stealing hotel property or other guests' belongings</li>
                <li><strong>Unauthorized Guests:</strong> Hosting non-registered guests without permission</li>
                <li><strong>Commercial Activities:</strong> Using hotel premises for unauthorized business activities</li>
                <li><strong>Property Misuse:</strong> Using hotel facilities for purposes other than intended</li>
            </ul>
        </div>

        <div class="section">
            <h2>🔐 Privacy & Personal Information</h2>
            <h3>📋 Information Collection</h3>
            <p>We collect and use guest information for:</p>
            <ul>
                <li>Booking confirmation and management</li>
                <li>Guest safety and security</li>
                <li>Legal compliance and identification</li>
                <li>Service improvement and communication</li>
            </ul>
            
            <h3>🛡️ Information Protection</h3>
            <ul>
                <li><strong>Secure Storage:</strong> All personal information is stored securely</li>
                <li><strong>Limited Access:</strong> Only authorized personnel have access to guest data</li>
                <li><strong>No Sale:</strong> We do not sell personal information to third parties</li>
                <li><strong>Retention:</strong> Information retained as required by law and business needs</li>
            </ul>
        </div>

        <div class="section">
            <h2>⚖️ Legal Compliance & Identification</h2>
            <div class="highlight-box">
                <strong>🆔 Identification Required:</strong> Valid government-issued ID and/or passport required for all guests.
            </div>
            
            <h3>📋 Required Documentation</h3>
            <ul>
                <li><strong>Photo ID:</strong> Government-issued photo identification (driver's license, passport, national ID)</li>
                <li><strong>Passport:</strong> Required for international guests</li>
                <li><strong>Visa/Documentation:</strong> Proper travel documentation as required by law</li>
                <li><strong>Age Verification:</strong> Proof of age for age-restricted services</li>
            </ul>
            
            <h3>🏛️ Legal Obligations</h3>
            <ul>
                <li>Compliance with local, state, and federal laws</li>
                <li>Cooperation with law enforcement when required</li>
                <li>Reporting suspicious activities as mandated by law</li>
                <li>Guest registry maintenance for legal compliance</li>
            </ul>
        </div>

        <div class="section">
            <h2>📞 Emergency & Safety Procedures</h2>
            <h3>🚨 Emergency Protocols</h3>
            <ul>
                <li><strong>Emergency Numbers:</strong> Contact front desk immediately for any emergency</li>
                <li><strong>Evacuation:</strong> Follow hotel evacuation procedures when announced</li>
                <li><strong>Medical Emergency:</strong> Hotel staff will assist in contacting medical services</li>
                <li><strong>Security Issues:</strong> Report suspicious activities to hotel security</li>
            </ul>
        </div>

        <div class="section">
            <h2>💼 Limitation of Liability</h2>
            <p>The hotel's liability is limited as follows:</p>
            <ul>
                <li><strong>Personal Property:</strong> Hotel not responsible for loss or theft of personal items</li>
                <li><strong>Safe Usage:</strong> Use in-room safes for valuables</li>
                <li><strong>Force Majeure:</strong> Hotel not liable for events beyond reasonable control</li>
                <li><strong>Maximum Liability:</strong> Hotel liability limited to the value of the booking</li>
            </ul>
        </div>

        <div class="section">
            <h2>📋 Cancellation & Modification Policy</h2>
            <h3>🔄 Booking Changes</h3>
            <ul>
                <li><strong>Advance Notice:</strong> Changes require advance notice as per booking terms</li>
                <li><strong>Availability:</strong> Modifications subject to room availability</li>
                <li><strong>Fees:</strong> Change fees may apply depending on booking type</li>
                <li><strong>Cancellation:</strong> Cancellation terms vary by rate and booking conditions</li>
            </ul>
        </div>

        <div class="section">
            <h2>✍️ Agreement Acknowledgment</h2>
            <div class="important-box">
                <strong>🤝 Legal Agreement:</strong> By checking the "I agree" box during registration or booking, you acknowledge that:
                <ul style="margin-top: 10px;">
                    <li>You have read and understood all terms and conditions</li>
                    <li>You agree to be legally bound by these terms</li>
                    <li>You accept full financial responsibility for any damages</li>
                    <li>You will comply with all hotel policies and local laws</li>
                    <li>You understand consequences of policy violations</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>📧 Contact Information</h2>
            <p>For questions about these terms and conditions, contact us:</p>
            <ul>
                <li><strong>📞 Phone:</strong> <?php echo htmlspecialchars($hotel['phone'] ?? '+1-XXX-XXX-XXXX'); ?></li>
                <li><strong>📧 Email:</strong> <?php echo htmlspecialchars($hotel['email'] ?? 'info@hotel.com'); ?></li>
                <li><strong>🏠 Address:</strong> <?php echo htmlspecialchars($hotel['address'] ?? 'Hotel Address'); ?></li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 2px solid #eee;">
            <p style="color: #666; font-size: 0.9em;">
                <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?> | 
                <strong>Version:</strong> 1.0<br>
                This agreement is effective immediately upon acceptance.
            </p>
            
            <a href="javascript:history.back()" class="back-btn">
                ← Back to Registration
            </a>
        </div>
    </div>
</body>
</html>