<?php
/**
 * Email Configuration for AiNi Hotel Receipt System
 * 
 * To enable email functionality:
 * 1. Update the SMTP settings below with your email provider's details
 * 2. For Gmail: Use App Passwords instead of your regular password
 * 3. For other providers: Check their SMTP settings documentation
 */

class EmailConfig {
    // SMTP Configuration
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'your-email@gmail.com'; // Change this to your email
    const SMTP_PASSWORD = 'your-app-password';    // Change this to your app password
    
    // Email Settings
    const FROM_EMAIL = 'reservas@ainihotel.com';
    const FROM_NAME = 'AiNi Hotel';
    const REPLY_TO = 'reservas@ainihotel.com';
    
    // Hotel Information
    const HOTEL_NAME = 'AiNi Hotel';
    const HOTEL_ADDRESS = '123 Main Street';
    const HOTEL_CITY = 'Lima, Peru 15001';
    const HOTEL_PHONE = '+51 1 234 5678';
    const HOTEL_EMAIL = 'reservas@ainihotel.com';
    const HOTEL_WEBSITE = 'www.ainihotel.com';
    
    /**
     * Gmail Setup Instructions:
     * 1. Enable 2-Factor Authentication on your Google account
     * 2. Go to Google Account Settings > Security > App passwords
     * 3. Generate an app password for "Mail"
     * 4. Use that app password in SMTP_PASSWORD above
     * 5. Update SMTP_USERNAME with your Gmail address
     * 
     * For other email providers:
     * - Outlook/Hotmail: smtp-mail.outlook.com, port 587
     * - Yahoo: smtp.mail.yahoo.com, port 587 or 465
     * - Custom SMTP: Check with your hosting provider
     */
    
    public static function isConfigured() {
        return self::SMTP_USERNAME !== 'your-email@gmail.com' && 
               self::SMTP_PASSWORD !== 'your-app-password';
    }
    
    public static function getConfig() {
        return [
            'host' => self::SMTP_HOST,
            'port' => self::SMTP_PORT,
            'username' => self::SMTP_USERNAME,
            'password' => self::SMTP_PASSWORD,
            'from_email' => self::FROM_EMAIL,
            'from_name' => self::FROM_NAME,
            'reply_to' => self::REPLY_TO
        ];
    }
    
    public static function getHotelInfo() {
        return [
            'name' => self::HOTEL_NAME,
            'address' => self::HOTEL_ADDRESS,
            'city' => self::HOTEL_CITY,
            'phone' => self::HOTEL_PHONE,
            'email' => self::HOTEL_EMAIL,
            'website' => self::HOTEL_WEBSITE
        ];
    }
}
?>