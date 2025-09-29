-- WhatsApp Integration Database Setup
-- Add to your existing hotel_booking_system database

-- Table for WhatsApp conversations
CREATE TABLE IF NOT EXISTS whatsapp_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    user_id INT DEFAULT NULL,
    user_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    message_type ENUM('text', 'interactive', 'media') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table for WhatsApp booking sessions
CREATE TABLE IF NOT EXISTS whatsapp_booking_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    user_id INT DEFAULT NULL,
    session_data JSON,
    status ENUM('started', 'collecting_info', 'showing_rooms', 'confirming', 'completed', 'cancelled') DEFAULT 'started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table for WhatsApp templates (for marketing/notifications)
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    template_content TEXT NOT NULL,
    template_type ENUM('booking_confirmation', 'reminder', 'promotion', 'welcome') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add WhatsApp configuration to existing ai_chat_config table
INSERT INTO ai_chat_config (config_key, config_value, description) VALUES
('whatsapp_access_token', '', 'WhatsApp Business API Access Token'),
('whatsapp_phone_number_id', '', 'WhatsApp Business Phone Number ID'),
('whatsapp_webhook_token', 'hotel_webhook_secret_2025', 'WhatsApp Webhook Verification Token'),
('whatsapp_business_phone', '', 'WhatsApp Business Phone Number'),
('whatsapp_welcome_bonus_coins', '5.0', 'Welcome bonus HotelCoins for new WhatsApp users'),
('whatsapp_welcome_bonus_points', '100', 'Welcome bonus loyalty points for new WhatsApp users'),
('whatsapp_enabled', '1', 'Enable/disable WhatsApp integration');

-- Insert default WhatsApp templates
INSERT INTO whatsapp_templates (template_name, template_content, template_type) VALUES
('booking_confirmation', 
'🎉 *Booking Confirmed!* 

Reservation #{booking_id}
🏨 {room_type}
📅 {check_in} to {check_out}
👥 {guests} guests
💰 Total: ${total_amount}
🪙 Earned: {hotelcoins} HotelCoins

Check-in: 3:00 PM
Check-out: 11:00 AM

Need help? Just message us anytime!', 
'booking_confirmation'),

('booking_reminder', 
'📅 *Booking Reminder*

Your stay is tomorrow!
🏨 {hotel_name}
📅 Check-in: {check_in_date}
🕒 Time: 3:00 PM

Your room is ready and we''re excited to welcome you!
🪙 Don''t forget - you can use your HotelCoins for upgrades and services.

See you soon! 🌟', 
'reminder'),

('welcome_message', 
'🎉 Welcome to our WhatsApp Hotel Service!

You''ve received:
🪙 5 HotelCoins (welcome bonus)
💎 100 Loyalty Points

I''m your AI assistant and I can help with:
🏨 Hotel bookings
💰 HotelCoin management  
💎 Loyalty program info
🌍 Local recommendations

Type ''help'' for menu or just ask me anything!', 
'welcome'),

('hotelcoin_promotion', 
'🪙 *Special HotelCoin Promotion!*

Book your next stay through WhatsApp and get:
✨ Double HotelCoins (2% instead of 1%)
🎁 Bonus 500 loyalty points
🍽️ Free breakfast upgrade

Valid for bookings made via WhatsApp chat before {expiry_date}

Ready to book? Just tell me your dates! 📅', 
'promotion');

-- Table for WhatsApp automation rules
CREATE TABLE IF NOT EXISTS whatsapp_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    trigger_type ENUM('booking_confirmed', 'check_in_reminder', 'check_out_reminder', 'birthday', 'inactivity') NOT NULL,
    trigger_timing INT DEFAULT 0 COMMENT 'Hours before/after trigger event',
    template_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES whatsapp_templates(id)
);

-- Insert default automation rules
INSERT INTO whatsapp_automation_rules (rule_name, trigger_type, trigger_timing, template_id) VALUES
('Booking Confirmation', 'booking_confirmed', 0, 1),
('Check-in Reminder', 'check_in_reminder', 24, 2),
('Check-out Thank You', 'check_out_reminder', -2, 1);

-- Table for WhatsApp broadcast campaigns
CREATE TABLE IF NOT EXISTS whatsapp_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(200) NOT NULL,
    message_content TEXT NOT NULL,
    target_criteria JSON COMMENT 'JSON criteria for targeting users',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    total_recipients INT DEFAULT 0,
    successful_sends INT DEFAULT 0,
    failed_sends INT DEFAULT 0,
    status ENUM('draft', 'scheduled', 'sending', 'completed', 'failed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update users table to better support WhatsApp users
ALTER TABLE users ADD COLUMN whatsapp_phone VARCHAR(20) NULL AFTER phone;
ALTER TABLE users ADD COLUMN preferred_communication ENUM('email', 'whatsapp', 'both') DEFAULT 'email' AFTER whatsapp_phone;
ALTER TABLE users ADD COLUMN whatsapp_opt_in BOOLEAN DEFAULT FALSE AFTER preferred_communication;

-- Create index for WhatsApp phone lookup
CREATE INDEX idx_whatsapp_phone ON users(whatsapp_phone);

-- Table for WhatsApp analytics
CREATE TABLE IF NOT EXISTS whatsapp_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_recorded DATE NOT NULL,
    total_messages_received INT DEFAULT 0,
    total_messages_sent INT DEFAULT 0,
    unique_users_contacted INT DEFAULT 0,
    bookings_initiated INT DEFAULT 0,
    bookings_completed INT DEFAULT 0,
    hotelcoins_distributed DECIMAL(10,4) DEFAULT 0,
    loyalty_points_awarded INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date_recorded)
);

-- Create a view for WhatsApp user stats
CREATE VIEW whatsapp_user_stats AS
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.whatsapp_phone,
    u.whatsapp_opt_in,
    COUNT(wc.id) as total_messages,
    MAX(wc.created_at) as last_message,
    hw.balance as hotelcoin_balance,
    lw.balance as loyalty_points,
    lw.membership_tier
FROM users u
LEFT JOIN whatsapp_conversations wc ON u.phone = wc.phone_number OR u.whatsapp_phone = wc.phone_number
LEFT JOIN hotelcoin_wallets hw ON u.id = hw.user_id
LEFT JOIN loyalty_wallets lw ON u.id = lw.user_id
WHERE u.whatsapp_phone IS NOT NULL OR u.phone IS NOT NULL
GROUP BY u.id;