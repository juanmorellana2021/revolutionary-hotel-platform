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
            <h2>🌟 Reviews, Reputation & Defamation Protection</h2>
            <div class="important-box">
                <strong>⚖️ LEGAL NOTICE:</strong> Guests are legally responsible for the accuracy and truthfulness of all reviews, comments, and public statements about the hotel. False, misleading, or malicious statements may result in legal action.
            </div>
            
            <h3>📝 Review Standards & Responsibilities</h3>
            <p>All guests agree to the following review and reputation standards:</p>
            <ul>
                <li><strong>Truthful Reviews Only:</strong> All reviews and public comments must be factual, honest, and based on actual experience</li>
                <li><strong>No False Claims:</strong> Making false statements about hotel services, cleanliness, safety, or staff is strictly prohibited</li>
                <li><strong>No Exaggerated Complaints:</strong> Reviews must be proportionate to actual issues experienced during the stay</li>
                <li><strong>Professional Language:</strong> Reviews should use respectful, professional language without offensive content</li>
                <li><strong>Fair Representation:</strong> Reviews should fairly represent the overall experience, not focus solely on minor issues</li>
                <li><strong>No Blackmail/Threats:</strong> Using reviews or threats of negative reviews to obtain compensation or special treatment is prohibited</li>
            </ul>
            
            <h3>🛡️ Reputation Damage & Legal Liability</h3>
            <div class="warning-box">
                <strong>💼 Financial Liability:</strong> Guests who post false, defamatory, or maliciously misleading reviews may be held financially liable for business damages.
            </div>
            
            <p>Guests acknowledge and agree that:</p>
            <ul>
                <li><strong>Business Impact Responsibility:</strong> False or malicious reviews can cause significant financial harm to the hotel business</li>
                <li><strong>Measurable Damages:</strong> Reputation damage can result in lost bookings, reduced revenue, and decreased property value</li>
                <li><strong>Legal Consequences:</strong> Defamatory reviews may result in civil litigation for damages and legal fees</li>
                <li><strong>Platform Accountability:</strong> This applies to reviews on all platforms (Google, Booking.com, TripAdvisor, social media, etc.)</li>
                <li><strong>Evidence Collection:</strong> The hotel maintains records and evidence to defend against false claims</li>
            </ul>
            
            <h3>⚖️ Defamation & False Statement Policy</h3>
            <p>The following are considered defamatory and may result in legal action:</p>
            <ul>
                <li><strong>False Health/Safety Claims:</strong> Untrue statements about hotel safety, cleanliness, or health hazards</li>
                <li><strong>Staff Defamation:</strong> False accusations against hotel staff members regarding misconduct or unprofessional behavior</li>
                <li><strong>Service Misrepresentation:</strong> Knowingly false claims about services provided or not provided</li>
                <li><strong>Facility Misstatements:</strong> Intentionally misleading descriptions of hotel facilities or amenities</li>
                <li><strong>Policy Violations:</strong> False claims about hotel policies or treatment of guests</li>
                <li><strong>Discriminatory Allegations:</strong> False accusations of discrimination or unfair treatment</li>
            </ul>
            
            <h3>🔍 Review Dispute Resolution Process</h3>
            <p>Before posting negative reviews, guests are encouraged to:</p>
            <ul>
                <li><strong>Contact Management:</strong> Address concerns directly with hotel management first</li>
                <li><strong>Allow Resolution:</strong> Give the hotel opportunity to resolve issues before posting public reviews</li>
                <li><strong>Document Issues:</strong> Provide evidence of any legitimate complaints</li>
                <li><strong>Seek Mediation:</strong> Consider mediation for significant disputes before litigation</li>
            </ul>
            
            <h3>💰 Damages & Recovery</h3>
            <div class="important-box">
                <strong>📈 Calculated Damages:</strong> Hotel may seek recovery for:
                <ul style="margin-top: 10px;">
                    <li>Lost revenue from decreased bookings</li>
                    <li>Reduced property valuation</li>
                    <li>Marketing costs to counter negative publicity</li>
                    <li>Legal fees and court costs</li>
                    <li>Reputation management expenses</li>
                    <li>Staff time and resources addressing false claims</li>
                </ul>
            </div>
            
            <h3>🌐 Digital Footprint Responsibility</h3>
            <p>Guests acknowledge that:</p>
            <ul>
                <li><strong>Permanent Record:</strong> Online reviews and posts create a permanent digital record</li>
                <li><strong>Wide Distribution:</strong> Reviews can be seen by thousands of potential guests</li>
                <li><strong>Long-term Impact:</strong> False reviews can damage business for years</li>
                <li><strong>Legal Discovery:</strong> Digital evidence can be used in legal proceedings</li>
            </ul>
        </div>

        <div class="section">
            <h2>�️ Reputation Protection & Review Policy</h2>
            <div class="important-box">
                <strong>⚠️ CRITICAL:</strong> Guests are legally liable for false, defamatory, or malicious reviews that damage the hotel's reputation.
            </div>
            
            <h3>📝 Review Guidelines & Liability</h3>
            <p>All guests agree to the following review standards:</p>
            <ul>
                <li><strong>Truthful Reviews Only:</strong> All reviews must be factual and based on actual experience</li>
                <li><strong>No False Claims:</strong> Making false statements about services, cleanliness, or staff is prohibited</li>
                <li><strong>No Defamatory Content:</strong> Reviews cannot contain defamatory, libelous, or slanderous statements</li>
                <li><strong>No Malicious Intent:</strong> Reviews motivated by spite or revenge are strictly prohibited</li>
                <li><strong>Constructive Feedback:</strong> Criticism must be constructive and based on genuine service issues</li>
            </ul>
            
            <h3>💰 Financial Liability for Reputation Damage</h3>
            <div class="warning-box">
                <strong>💸 Damages & Consequences:</strong> Guests who post false or defamatory reviews may be held liable for:
                <ul style="margin-top: 10px;">
                    <li><strong>Lost Revenue:</strong> Compensation for bookings lost due to false reviews</li>
                    <li><strong>Reputation Repair Costs:</strong> Marketing and PR expenses to restore reputation</li>
                    <li><strong>Legal Fees:</strong> Attorney costs for defamation litigation</li>
                    <li><strong>Punitive Damages:</strong> Additional penalties for malicious false reviews</li>
                    <li><strong>Platform Removal Costs:</strong> Expenses to remove defamatory content</li>
                </ul>
            </div>
            
            <h3>⚖️ Legal Consequences</h3>
            <ul>
                <li><strong>Defamation Lawsuits:</strong> Hotel reserves right to pursue legal action for false reviews</li>
                <li><strong>Cease & Desist:</strong> Immediate removal demands for defamatory content</li>
                <li><strong>Platform Reporting:</strong> False reviews reported to review platforms for removal</li>
                <li><strong>Evidence Collection:</strong> Hotel maintains evidence of actual service provided</li>
                <li><strong>Witness Testimony:</strong> Staff and other guests may testify to actual events</li>
            </ul>
        </div>

        <div class="section">
            <h2>📱 Social Media & Digital Conduct Policy</h2>
            <div class="highlight-box">
                <strong>🌐 Digital Responsibility:</strong> Guest conduct standards apply to all online platforms and social media.
            </div>
            
            <h3>📸 Photography & Content Policy</h3>
            <ul>
                <li><strong>Respect Privacy:</strong> No photos/videos of other guests without consent</li>
                <li><strong>Staff Privacy:</strong> No unauthorized photos/videos of hotel staff</li>
                <li><strong>Property Respect:</strong> No misleading or deceptive imagery of hotel facilities</li>
                <li><strong>Context Accuracy:</strong> Photos must accurately represent actual conditions</li>
            </ul>
            
            <h3>🚫 Prohibited Online Activities</h3>
            <ul>
                <li><strong>False Documentation:</strong> Posting misleading photos or videos</li>
                <li><strong>Harassment:</strong> Online harassment of staff or other guests</li>
                <li><strong>Fake Accounts:</strong> Creating multiple accounts to post negative reviews</li>
                <li><strong>Review Manipulation:</strong> Coordinating negative review campaigns</li>
                <li><strong>Doxxing:</strong> Publishing personal information of staff or guests</li>
            </ul>
            
            <h3>🎯 Positive Engagement Encouraged</h3>
            <p>We welcome and encourage:</p>
            <ul>
                <li>Honest, constructive feedback for service improvement</li>
                <li>Positive sharing of genuine experiences</li>
                <li>Professional communication for issue resolution</li>
                <li>Direct contact with management for concerns</li>
            </ul>
        </div>

        <div class="section">
            <h2>�🔐 Privacy & Personal Information</h2>
            <h3>📋 Information Collection</h3>
            <p>We collect and use guest information for:</p>
            <ul>
                <li>Booking confirmation and management</li>
                <li>Guest safety and security</li>
                <li>Legal compliance and identification</li>
                <li>Service improvement and communication</li>
                <li>Legal defense in case of false reviews or claims</li>
            </ul>
            
            <h3>🛡️ Information Protection</h3>
            <ul>
                <li><strong>Secure Storage:</strong> All personal information is stored securely</li>
                <li><strong>Limited Access:</strong> Only authorized personnel have access to guest data</li>
                <li><strong>No Sale:</strong> We do not sell personal information to third parties</li>
                <li><strong>Retention:</strong> Information retained as required by law and business needs</li>
                <li><strong>Legal Use:</strong> Information may be used to defend against false claims or reviews</li>
            </ul>
        </div>

        <div class="section">
            <h2>📱 Social Media & Digital Conduct Policy</h2>
            <div class="warning-box">
                <strong>🌐 Digital Responsibility:</strong> All social media posts, photos, videos, and digital content involving the hotel must comply with this policy.
            </div>
            
            <h3>📸 Photography & Video Policy</h3>
            <ul>
                <li><strong>Private Areas:</strong> No photography/video in private guest rooms or restricted areas</li>
                <li><strong>Staff Consent:</strong> Obtain permission before photographing or filming hotel staff</li>
                <li><strong>Guest Privacy:</strong> Respect other guests' privacy and avoid including them in posts without consent</li>
                <li><strong>Appropriate Content:</strong> All visual content must be appropriate and non-offensive</li>
                <li><strong>No Staged Damage:</strong> Creating fake damage or problems for social media content is strictly prohibited</li>
            </ul>
            
            <h3>💬 Social Media Posting Guidelines</h3>
            <ul>
                <li><strong>Accurate Information:</strong> All posts must contain truthful and accurate information</li>
                <li><strong>Respectful Content:</strong> Use respectful language and professional tone</li>
                <li><strong>Context Matters:</strong> Provide fair context for any issues or concerns mentioned</li>
                <li><strong>No Harassment:</strong> Do not target individual staff members or other guests</li>
                <li><strong>Platform Responsibility:</strong> This policy applies to all platforms (Instagram, Facebook, TikTok, Twitter, etc.)</li>
            </ul>
            
            <h3>⚠️ Prohibited Digital Conduct</h3>
            <div class="important-box">
                <strong>🚫 Immediate Legal Action:</strong> The following digital conduct may result in immediate legal action:
                <ul style="margin-top: 10px;">
                    <li>Creating fake incidents for viral content</li>
                    <li>Deliberately staging problems for social media attention</li>
                    <li>Harassment campaigns against the hotel or staff</li>
                    <li>Sharing private guest information without consent</li>
                    <li>Posting defamatory content with intent to harm business</li>
                    <li>Using hotel imagery for unauthorized commercial purposes</li>
                </ul>
            </div>
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
                    <li><strong>You accept legal liability for false, defamatory, or malicious reviews</strong></li>
                    <li><strong>You acknowledge potential financial damages from reputation harm</strong></li>
                    <li><strong>You agree to truthful and fair representation in all public statements</strong></li>
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