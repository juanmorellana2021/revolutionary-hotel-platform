<?php
/**
 * Email Configuration for AiNi Hotel Receipt System
 * Now loads configuration from database (email_config table)
 */

class EmailConfig {
    private static $config = null;
    
    // Fallback constants (used if database is not available)
    const FALLBACK_SMTP_HOST = 'smtp.gmail.com';
    const FALLBACK_SMTP_PORT = 587;
    const FALLBACK_FROM_EMAIL = 'reservas@ainihotel.com';
    const FALLBACK_FROM_NAME = 'AiNi Hotel';
    
    // Hotel Information (can be moved to database too if needed)
    const HOTEL_NAME = 'AiNi Hotel';
    const HOTEL_ADDRESS = '123 Main Street';
    const HOTEL_CITY = 'Lima, Peru 15001';
    const HOTEL_PHONE = '+51 1 234 5678';
    const HOTEL_EMAIL = 'reservas@ainihotel.com';
    const HOTEL_WEBSITE = 'www.ainihotel.com';
    
    private static function loadConfig() {
        if (self::$config !== null) {
            return;
        }
        
        try {
            require_once __DIR__ . '/../includes/classes.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->query("SELECT * FROM email_config WHERE is_enabled = 1 ORDER BY id DESC LIMIT 1");
            $dbConfig = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dbConfig) {
                self::$config = $dbConfig;
            } else {
                // Try to get any config (even if disabled)
                $stmt = $conn->query("SELECT * FROM email_config ORDER BY id DESC LIMIT 1");
                $dbConfig = $stmt->fetch(PDO::FETCH_ASSOC);
                self::$config = $dbConfig ?: false;
            }
        } catch (Exception $e) {
            error_log("EmailConfig Error: " . $e->getMessage());
            self::$config = false;
        }
    }
    
    public static function isConfigured() {
        self::loadConfig();
        
        if (!self::$config || !is_array(self::$config)) {
            return false;
        }
        
        return !empty(self::$config['smtp_username']) && 
               !empty(self::$config['smtp_password']) &&
               self::$config['smtp_username'] !== 'your-email@gmail.com' &&
               self::$config['is_enabled'] == 1;
    }
    
    public static function getConfig() {
        self::loadConfig();
        
        if (self::$config && is_array(self::$config)) {
            return [
                'host' => self::$config['smtp_host'],
                'port' => self::$config['smtp_port'],
                'username' => self::$config['smtp_username'],
                'password' => self::$config['smtp_password'],
                'from_email' => self::$config['from_email'],
                'from_name' => self::$config['from_name'],
                'reply_to' => self::$config['reply_to'],
                'use_ssl' => (bool)self::$config['use_ssl'],
                'use_tls' => (bool)self::$config['use_tls']
            ];
        }
        
        // Fallback configuration
        return [
            'host' => self::FALLBACK_SMTP_HOST,
            'port' => self::FALLBACK_SMTP_PORT,
            'username' => '',
            'password' => '',
            'from_email' => self::FALLBACK_FROM_EMAIL,
            'from_name' => self::FALLBACK_FROM_NAME,
            'reply_to' => self::FALLBACK_FROM_EMAIL,
            'use_ssl' => false,
            'use_tls' => true
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