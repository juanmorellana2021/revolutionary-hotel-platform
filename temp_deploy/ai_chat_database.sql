-- AI Chat System Database Setup
-- Add to your existing hotel_booking_system database

-- Table for AI chat interaction logs
CREATE TABLE IF NOT EXISTS ai_chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    response_time_ms INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table for chat session management
CREATE TABLE IF NOT EXISTS ai_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    context TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table for AI configuration and templates
CREATE TABLE IF NOT EXISTS ai_chat_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default AI configuration
INSERT INTO ai_chat_config (config_key, config_value, description) VALUES
('ollama_host', 'localhost', 'Ollama server host'),
('ollama_port', '11434', 'Ollama server port'),
('default_model', 'tinyllama', 'Default AI model to use'),
('max_response_length', '500', 'Maximum AI response length'),
('chat_enabled', '1', 'Enable/disable AI chat system'),
('welcome_message', 'Hello! I''m your AI assistant. I can help you with HotelCoins, loyalty points, bookings, and local recommendations. How can I assist you today?', 'Default welcome message');

-- Table for quick response templates
CREATE TABLE IF NOT EXISTS ai_quick_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    response_text TEXT NOT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default quick responses
INSERT INTO ai_quick_responses (category, title, response_text, display_order) VALUES
('hotelcoins', 'How do I earn HotelCoins?', 'You earn HotelCoins automatically when you make bookings! You receive 1% of your booking value in HotelCoins, plus bonus coins based on your loyalty tier. You can also earn coins through special promotions and partner activities.', 1),
('hotelcoins', 'What can I buy with HotelCoins?', 'HotelCoins can be used for future bookings, room upgrades, hotel services, and at our partner businesses including restaurants, tours, and shops. Check your wallet for current redemption options!', 2),
('hotelcoins', 'How do I transfer HotelCoins?', 'You can easily transfer HotelCoins to friends and family through your wallet. Just go to your wallet page, select "Transfer Coins," enter their email address and amount. It''s instant and free!', 3),
('loyalty', 'What are the loyalty tiers?', 'We have 4 tiers: Bronze (0+ points), Silver (1,000+ points), Gold (5,000+ points), and Platinum (15,000+ points). Each tier increases your HotelCoin earning multiplier and unlocks exclusive benefits!', 1),
('loyalty', 'How do I earn loyalty points?', 'You earn 10 loyalty points for every $1 spent on bookings, multiplied by your tier level. You also get bonus points for reviews, referrals, and special activities. Points never expire!', 2),
('booking', 'How do I make a reservation?', 'Simply search for your dates, select your preferred room, and complete the booking process. You can pay with cash and earn HotelCoins, or use your existing HotelCoins for discounts!', 1),
('booking', 'Can I modify my booking?', 'Yes! You can modify most bookings up to 24 hours before check-in through your dashboard. Changes may affect pricing, but you''ll always see the new rate before confirming.', 2),
('local', 'Which local businesses accept HotelCoins?', 'Many local restaurants, tour operators, and shops accept HotelCoins! Check the "Partner Businesses" section in your wallet or ask me for specific recommendations in your area.', 1);