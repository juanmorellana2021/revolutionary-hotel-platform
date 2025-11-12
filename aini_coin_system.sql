-- AiNi Coin System - Blockchain-Style Immutable Ledger
-- Database: hotel_booking_system (on prod-vps)
-- This structure is designed to be crypto-ready for future stablecoin backing

-- ============================================
-- Transaction Log (Immutable Blockchain-Style)
-- ============================================
CREATE TABLE aini_coin_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- User Information
    user_id INT NOT NULL,
    
    -- Transaction Details
    transaction_type ENUM(
        'earned_booking',      -- Earned from hotel booking
        'earned_referral',     -- Earned from referring friend
        'earned_bonus',        -- Platform bonus/promotion
        'spent_discount',      -- Used for hotel discount
        'spent_upgrade',       -- Used for room upgrade
        'transfer_out',        -- Sent to another user
        'transfer_in',         -- Received from another user
        'refund',              -- Refund/reversal
        'admin_adjustment'     -- Manual admin correction
    ) NOT NULL,
    
    amount INT NOT NULL COMMENT 'Positive for credits, negative for debits',
    balance_before INT NOT NULL,
    balance_after INT NOT NULL,
    
    -- Blockchain Elements (Immutability)
    previous_transaction_id BIGINT COMMENT 'Links to previous transaction in chain',
    transaction_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash for verification',
    block_number BIGINT COMMENT 'Future: for blockchain integration',
    
    -- Transfer Specific Fields
    counterparty_user_id INT COMMENT 'For transfers: the other user involved',
    transfer_note VARCHAR(255) COMMENT 'Optional message with transfer',
    
    -- Reference Tracking (Link to bookings, etc)
    reference_type VARCHAR(50) COMMENT 'booking, promotion, referral, etc',
    reference_id INT COMMENT 'ID of the related record',
    description TEXT,
    
    -- Security & Audit
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Verification Status
    is_verified TINYINT(1) DEFAULT 1 COMMENT 'For blockchain verification',
    verified_at TIMESTAMP NULL,
    verification_hash VARCHAR(64) COMMENT 'Secondary verification hash',
    
    -- Future: Crypto Integration
    blockchain_tx_hash VARCHAR(66) COMMENT 'Future: Ethereum/Polygon transaction hash',
    stablecoin_value DECIMAL(10,2) COMMENT 'Future: USD value at time of transaction',
    
    -- Constraints
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (previous_transaction_id) REFERENCES aini_coin_transactions(id) ON DELETE RESTRICT,
    FOREIGN KEY (counterparty_user_id) REFERENCES ainitravel_users(id) ON DELETE RESTRICT,
    
    -- Indexes for Performance
    INDEX idx_user_date (user_id, created_at DESC),
    INDEX idx_hash (transaction_hash),
    INDEX idx_type (transaction_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_counterparty (counterparty_user_id, created_at),
    INDEX idx_block (block_number)
    
) ENGINE=InnoDB COMMENT='Immutable transaction ledger - DO NOT DELETE RECORDS';

-- ============================================
-- Transfer Limits & Security
-- ============================================
CREATE TABLE aini_coin_transfer_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Daily Limits
    daily_transfer_limit INT DEFAULT 1000 COMMENT 'Max coins per day',
    daily_transferred_today INT DEFAULT 0,
    last_transfer_date DATE,
    
    -- Security
    account_age_days INT COMMENT 'Days since account creation',
    min_age_to_transfer INT DEFAULT 30 COMMENT 'Minimum days before can transfer',
    is_transfer_enabled TINYINT(1) DEFAULT 0 COMMENT 'Admin can enable/disable',
    is_verified_user TINYINT(1) DEFAULT 0 COMMENT 'Email verified, phone verified, etc',
    
    -- Fraud Detection
    suspicious_activity_count INT DEFAULT 0,
    is_flagged TINYINT(1) DEFAULT 0,
    flagged_reason TEXT,
    flagged_at TIMESTAMP NULL,
    
    -- Stats
    total_sent_lifetime INT DEFAULT 0,
    total_received_lifetime INT DEFAULT 0,
    transfer_count INT DEFAULT 0,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB;

-- ============================================
-- Coin Rewards Catalog
-- ============================================
CREATE TABLE aini_coin_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Reward Details
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cost_in_coins INT NOT NULL,
    
    -- Reward Type
    reward_type ENUM(
        'discount_percentage',  -- % off booking
        'discount_fixed',       -- Fixed $ off
        'room_upgrade',         -- Free upgrade
        'free_night',          -- Free night stay
        'early_checkin',       -- Early check-in perk
        'late_checkout',       -- Late checkout perk
        'free_amenity',        -- Spa, breakfast, etc
        'priority_support'     -- VIP support
    ) NOT NULL,
    
    -- Value
    value_usd DECIMAL(10,2) COMMENT 'Approximate USD value',
    discount_percentage INT COMMENT 'For percentage discounts',
    discount_fixed_usd DECIMAL(10,2) COMMENT 'For fixed discounts',
    
    -- Availability
    is_active TINYINT(1) DEFAULT 1,
    stock INT DEFAULT -1 COMMENT '-1 = unlimited',
    max_per_user INT DEFAULT -1 COMMENT '-1 = unlimited',
    
    -- Validity
    valid_from DATE,
    valid_until DATE,
    min_booking_value_usd DECIMAL(10,2) COMMENT 'Minimum booking amount to use reward',
    
    -- Metadata
    image_url VARCHAR(255),
    terms_conditions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active, valid_from, valid_until),
    INDEX idx_cost (cost_in_coins)
) ENGINE=InnoDB;

-- ============================================
-- User Reward Redemptions
-- ============================================
CREATE TABLE aini_coin_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    transaction_id BIGINT NOT NULL COMMENT 'Links to aini_coin_transactions',
    
    coins_spent INT NOT NULL,
    
    -- Usage
    is_used TINYINT(1) DEFAULT 0,
    used_at TIMESTAMP NULL,
    booking_id INT COMMENT 'Which booking used this reward',
    
    -- Expiry
    expires_at TIMESTAMP NULL,
    is_expired TINYINT(1) DEFAULT 0,
    
    redemption_code VARCHAR(20) UNIQUE COMMENT 'Unique code for this redemption',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id),
    FOREIGN KEY (reward_id) REFERENCES aini_coin_rewards(id),
    FOREIGN KEY (transaction_id) REFERENCES aini_coin_transactions(id),
    
    INDEX idx_user (user_id, is_used),
    INDEX idx_booking (booking_id),
    INDEX idx_code (redemption_code)
) ENGINE=InnoDB;

-- ============================================
-- Blockchain Verification Checkpoints
-- ============================================
CREATE TABLE aini_coin_verification_checkpoints (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    checkpoint_date DATE NOT NULL,
    last_transaction_id BIGINT NOT NULL,
    
    -- Merkle Root (for future blockchain integration)
    merkle_root VARCHAR(64) NOT NULL COMMENT 'Root hash of all transactions up to this point',
    
    total_transactions BIGINT NOT NULL,
    total_coins_in_circulation BIGINT NOT NULL,
    
    -- Verification
    is_verified TINYINT(1) DEFAULT 0,
    verified_by VARCHAR(100),
    verified_at TIMESTAMP NULL,
    
    -- Future: Anchor to real blockchain
    blockchain_tx_hash VARCHAR(66) COMMENT 'Ethereum/Polygon tx hash',
    blockchain_block_number BIGINT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (last_transaction_id) REFERENCES aini_coin_transactions(id),
    UNIQUE KEY unique_date (checkpoint_date),
    INDEX idx_verified (is_verified)
) ENGINE=InnoDB COMMENT='Daily verification checkpoints for audit trail';

-- ============================================
-- System Configuration
-- ============================================
CREATE TABLE aini_coin_system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(50) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(100)
) ENGINE=InnoDB;

-- Insert default configuration
INSERT INTO aini_coin_system_config (config_key, config_value, description) VALUES
('coins_per_dollar_spent', '10', 'How many coins earned per $1 USD spent on bookings'),
('min_transfer_amount', '10', 'Minimum coins that can be transferred'),
('max_transfer_amount', '1000', 'Maximum coins per single transfer'),
('daily_transfer_limit', '1000', 'Maximum coins that can be transferred per day'),
('min_account_age_days', '30', 'Minimum account age in days before transfers allowed'),
('transfer_fee_percentage', '0', 'Fee percentage on transfers (0 = no fee)'),
('coin_expiry_days', '730', 'Coins expire after X days of inactivity (730 = 2 years)'),
('stablecoin_backing_ratio', '1.00', 'Future: USD value per coin (1 coin = $0.01)'),
('blockchain_enabled', '0', 'Future: Enable real blockchain integration'),
('maintenance_mode', '0', 'Disable coin system during maintenance');

-- ============================================
-- Initial Data: Sample Rewards
-- ============================================
INSERT INTO aini_coin_rewards (name, description, cost_in_coins, reward_type, value_usd, discount_percentage, is_active) VALUES
('5% Off Booking', 'Get 5% discount on your next hotel booking', 500, 'discount_percentage', 5.00, 5, 1),
('10% Off Booking', 'Get 10% discount on your next hotel booking', 1000, 'discount_percentage', 10.00, 10, 1),
('$20 Off Booking', 'Get $20 off any booking over $100', 2000, 'discount_fixed', 20.00, NULL, 1),
('Free Room Upgrade', 'Upgrade to next room category for free', 1500, 'room_upgrade', 30.00, NULL, 1),
('Late Checkout', 'Check out 2 hours late at no charge', 300, 'late_checkout', 15.00, NULL, 1),
('Early Check-in', 'Check in 2 hours early at no charge', 300, 'early_checkin', 15.00, NULL, 1),
('Free Breakfast', 'Complimentary breakfast for 2', 400, 'free_amenity', 25.00, NULL, 1);

-- ============================================
-- Triggers for Automatic Updates
-- ============================================

DELIMITER //

-- Trigger: Update balance in ainitravel_users after transaction
CREATE TRIGGER after_coin_transaction_insert
AFTER INSERT ON aini_coin_transactions
FOR EACH ROW
BEGIN
    UPDATE ainitravel_users 
    SET aini_coins = NEW.balance_after
    WHERE id = NEW.user_id;
END//

-- Trigger: Update transfer stats
CREATE TRIGGER after_transfer_transaction
AFTER INSERT ON aini_coin_transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_type IN ('transfer_out', 'transfer_in') THEN
        -- Update sender stats (transfer_out)
        IF NEW.transaction_type = 'transfer_out' THEN
            UPDATE aini_coin_transfer_limits
            SET total_sent_lifetime = total_sent_lifetime + ABS(NEW.amount),
                transfer_count = transfer_count + 1,
                daily_transferred_today = CASE 
                    WHEN last_transfer_date = CURDATE() THEN daily_transferred_today + ABS(NEW.amount)
                    ELSE ABS(NEW.amount)
                END,
                last_transfer_date = CURDATE()
            WHERE user_id = NEW.user_id;
        END IF;
        
        -- Update receiver stats (transfer_in)
        IF NEW.transaction_type = 'transfer_in' AND NEW.counterparty_user_id IS NOT NULL THEN
            UPDATE aini_coin_transfer_limits
            SET total_received_lifetime = total_received_lifetime + NEW.amount
            WHERE user_id = NEW.user_id;
        END IF;
    END IF;
END//

DELIMITER ;

-- ============================================
-- Create indexes on existing table
-- ============================================
ALTER TABLE ainitravel_users ADD INDEX idx_coins (aini_coins);
ALTER TABLE ainitravel_users ADD INDEX idx_created (created_at);

-- ============================================
-- Grant Permissions
-- ============================================
GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_coin_transactions TO 'hoteluser'@'localhost';
GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_coin_transfer_limits TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.aini_coin_rewards TO 'hoteluser'@'localhost';
GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_coin_redemptions TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.aini_coin_system_config TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.aini_coin_verification_checkpoints TO 'hoteluser'@'localhost';

-- ============================================
-- Comments for Future Development
-- ============================================
/*
FUTURE PHASES:

Phase 2: Stablecoin Backing
- Add stablecoin_reserve table (track USDC/USDT held)
- Add exchange rate tracking
- Add withdrawal requests table
- Add KYC verification table

Phase 3: Public Blockchain Launch
- Deploy ERC-20 AiNi Token on Ethereum/Polygon
- Add bridge contract for coin <-> token conversion
- Add blockchain_sync_log table
- Implement merkle proof verification

Phase 4: DeFi Integration  
- Staking rewards
- Liquidity pools
- Governance token features
*/
