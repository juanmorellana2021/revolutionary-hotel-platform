-- ============================================
-- AINI COIN: COMPLETE TRAVEL ECOSYSTEM CURRENCY
-- ============================================
-- This is a closed-loop payment network for travel
-- Similar to: Airline miles + Starbucks card + Crypto wallet
-- Partners: Hotels, Tours, Restaurants, Transportation, Shops
-- ============================================

-- ============================================
-- 1. PARTNER NETWORK
-- ============================================

CREATE TABLE aini_partner_businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Business Details
    business_name VARCHAR(200) NOT NULL,
    business_type ENUM(
        'hotel',
        'tour_operator', 
        'restaurant',
        'transportation',
        'shop',
        'spa',
        'experience',
        'other'
    ) NOT NULL,
    
    -- Contact & Location
    owner_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(50),
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    
    -- Onboarding
    partner_since DATE,
    contract_signed TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    
    -- Coin Settings
    accepts_aini_coins TINYINT(1) DEFAULT 1,
    issues_reward_coins TINYINT(1) DEFAULT 1,
    reward_percentage DECIMAL(5,2) DEFAULT 10.00 COMMENT 'e.g., 10% = 10 coins per $100',
    max_coin_payment_percentage INT DEFAULT 100 COMMENT 'Max % of bill payable in coins',
    
    -- Financial
    bank_account_holder VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_routing_number VARCHAR(50),
    settlement_currency VARCHAR(10) DEFAULT 'USD',
    
    -- Stats
    total_transactions INT DEFAULT 0,
    total_revenue_usd DECIMAL(12,2) DEFAULT 0,
    total_coins_issued BIGINT DEFAULT 0,
    total_coins_accepted BIGINT DEFAULT 0,
    
    -- Verification
    is_verified TINYINT(1) DEFAULT 0,
    verified_by VARCHAR(100),
    verified_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (business_type, is_active),
    INDEX idx_location (country, city),
    INDEX idx_verified (is_verified, is_active)
) ENGINE=InnoDB;

-- ============================================
-- 2. COIN PURCHASE/SALE (Fiat <-> Coins)
-- ============================================

CREATE TABLE aini_coin_purchases (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    user_id INT NOT NULL,
    
    -- Purchase Details
    coins_amount INT NOT NULL,
    fiat_amount DECIMAL(10,2) NOT NULL,
    fiat_currency VARCHAR(10) NOT NULL,
    exchange_rate DECIMAL(10,4) COMMENT 'Coins per 1 unit of fiat',
    
    -- Pricing
    platform_fee_percentage DECIMAL(5,2) DEFAULT 2.50,
    platform_fee_amount DECIMAL(10,2),
    total_charged DECIMAL(10,2),
    
    -- Payment Method
    payment_method ENUM('credit_card', 'debit_card', 'bank_transfer', 'paypal', 'crypto'),
    payment_provider VARCHAR(50) COMMENT 'Stripe, Adyen, etc',
    payment_provider_tx_id VARCHAR(100),
    
    -- Status
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    
    -- Linking to transaction log
    coin_transaction_id BIGINT COMMENT 'Links to aini_coin_transactions',
    
    -- Audit
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id),
    FOREIGN KEY (coin_transaction_id) REFERENCES aini_coin_transactions(id),
    INDEX idx_user (user_id, status),
    INDEX idx_status (status, created_at),
    INDEX idx_provider (payment_provider_tx_id)
) ENGINE=InnoDB;

-- ============================================
-- 3. PARTNER TRANSACTIONS (Spending Coins)
-- ============================================

CREATE TABLE aini_partner_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Who & Where
    user_id INT NOT NULL,
    partner_id INT NOT NULL,
    
    -- Transaction Details
    total_amount_usd DECIMAL(10,2) NOT NULL COMMENT 'Total bill in USD',
    coins_used INT DEFAULT 0 COMMENT 'Coins customer paid with',
    fiat_paid_usd DECIMAL(10,2) DEFAULT 0 COMMENT 'Additional fiat paid',
    coins_earned INT DEFAULT 0 COMMENT 'Reward coins earned',
    
    -- Breakdown
    original_price_usd DECIMAL(10,2),
    discount_amount_usd DECIMAL(10,2) DEFAULT 0,
    
    -- Platform Revenue
    platform_fee_percentage DECIMAL(5,2) DEFAULT 3.00,
    platform_fee_usd DECIMAL(10,2),
    
    -- Partner Settlement (what partner receives)
    partner_receives_usd DECIMAL(10,2),
    partner_settlement_status ENUM('pending', 'processing', 'paid') DEFAULT 'pending',
    settled_at TIMESTAMP NULL,
    
    -- Transaction Type
    transaction_type VARCHAR(50) COMMENT 'hotel_booking, tour, meal, etc',
    reference_id INT COMMENT 'booking_id, order_id, etc',
    description TEXT,
    
    -- Coin Transactions (links to immutable ledger)
    debit_transaction_id BIGINT COMMENT 'Spending coins',
    credit_transaction_id BIGINT COMMENT 'Earning coins',
    
    -- Audit
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id),
    FOREIGN KEY (partner_id) REFERENCES aini_partner_businesses(id),
    FOREIGN KEY (debit_transaction_id) REFERENCES aini_coin_transactions(id),
    FOREIGN KEY (credit_transaction_id) REFERENCES aini_coin_transactions(id),
    
    INDEX idx_user (user_id, created_at),
    INDEX idx_partner (partner_id, created_at),
    INDEX idx_settlement (partner_settlement_status, settled_at),
    INDEX idx_reference (transaction_type, reference_id)
) ENGINE=InnoDB;

-- ============================================
-- 4. PARTNER SETTLEMENT (Pay partners)
-- ============================================

CREATE TABLE aini_partner_settlements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    partner_id INT NOT NULL,
    
    -- Period
    settlement_period_start DATE NOT NULL,
    settlement_period_end DATE NOT NULL,
    
    -- Summary
    total_transactions INT,
    total_revenue_usd DECIMAL(12,2),
    coins_accepted INT,
    coins_issued INT,
    platform_fees_usd DECIMAL(10,2),
    
    -- Amount to Pay
    amount_owed_usd DECIMAL(12,2) COMMENT 'After platform fees',
    currency VARCHAR(10) DEFAULT 'USD',
    
    -- Payment
    payment_status ENUM('pending', 'processing', 'paid', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    paid_at TIMESTAMP NULL,
    
    -- Verification
    verified_by VARCHAR(100),
    verified_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (partner_id) REFERENCES aini_partner_businesses(id),
    INDEX idx_partner (partner_id, settlement_period_end),
    INDEX idx_status (payment_status),
    INDEX idx_period (settlement_period_start, settlement_period_end)
) ENGINE=InnoDB;

-- ============================================
-- 5. RESERVE FUND (Backing for coins)
-- ============================================

CREATE TABLE aini_coin_reserve_fund (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    snapshot_date DATE NOT NULL UNIQUE,
    
    -- Coins in Circulation
    total_coins_issued BIGINT NOT NULL,
    total_coins_burned BIGINT DEFAULT 0,
    coins_in_circulation BIGINT NOT NULL,
    
    -- Reserves (Real money backing)
    fiat_reserves_usd DECIMAL(14,2) NOT NULL COMMENT 'Actual USD held',
    partner_commitments_usd DECIMAL(14,2) COMMENT 'Guaranteed partner discounts',
    total_backing_usd DECIMAL(14,2),
    
    -- Reserve Ratio (Should be >= 1.0)
    reserve_ratio DECIMAL(10,4) COMMENT 'backing / coins = should be >= 1.0',
    
    -- Health Check
    is_healthy TINYINT(1) DEFAULT 1,
    alerts TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_date (snapshot_date DESC)
) ENGINE=InnoDB COMMENT='Daily snapshot of coin backing health';

-- ============================================
-- 6. EXCHANGE RATES (For international users)
-- ============================================

CREATE TABLE aini_coin_exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    currency_code VARCHAR(10) NOT NULL,
    currency_name VARCHAR(50),
    
    -- Rate (how many coins per 1 unit of currency)
    coins_per_unit DECIMAL(10,4) NOT NULL COMMENT 'e.g., 1 USD = 1.00 coins',
    usd_per_unit DECIMAL(10,6) COMMENT 'Official exchange rate to USD',
    
    -- Pricing (buy/sell spread)
    buy_rate DECIMAL(10,4) COMMENT 'Rate when user buys coins',
    sell_rate DECIMAL(10,4) COMMENT 'Rate when user sells coins',
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_currency (currency_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Insert major currencies
INSERT INTO aini_coin_exchange_rates (currency_code, currency_name, coins_per_unit, usd_per_unit, buy_rate, sell_rate) VALUES
('USD', 'US Dollar', 1.0000, 1.0000, 1.0000, 1.0000),
('EUR', 'Euro', 1.0800, 1.0800, 1.0700, 1.0900),
('GBP', 'British Pound', 1.2700, 1.2700, 1.2600, 1.2800),
('PEN', 'Peruvian Sol', 0.2700, 0.2700, 0.2680, 0.2720),
('MXN', 'Mexican Peso', 0.0580, 0.0580, 0.0575, 0.0585),
('BRL', 'Brazilian Real', 0.2000, 0.2000, 0.1980, 0.2020);

-- ============================================
-- 7. SYSTEM CONFIGURATION (Updated)
-- ============================================

INSERT INTO aini_coin_system_config (config_key, config_value, description) VALUES
-- Purchase settings
('purchase_fee_percentage', '2.5', 'Fee when buying coins with fiat (2.5%)'),
('min_purchase_usd', '10', 'Minimum purchase amount in USD'),
('max_purchase_usd', '10000', 'Maximum single purchase in USD'),

-- Spending settings  
('partner_transaction_fee', '3.0', 'Platform fee on partner transactions (3%)'),
('max_coins_per_transaction', '10000', 'Maximum coins in single transaction'),

-- Rewards
('default_reward_percentage', '10', 'Default reward: 10% back in coins'),

-- Withdrawal (future)
('withdrawal_enabled', '0', 'Allow users to cash out coins'),
('withdrawal_fee_percentage', '5', 'Fee for cashing out'),
('min_withdrawal_coins', '100', 'Minimum coins to withdraw'),

-- Reserve requirements
('min_reserve_ratio', '1.00', 'Minimum backing ratio (100%)'),
('target_reserve_ratio', '1.20', 'Target backing ratio (120%)'),

-- Compliance
('kyc_required', '0', 'Require KYC for large purchases'),
('kyc_threshold_usd', '1000', 'KYC required above this amount'),
('aml_monitoring', '1', 'Anti-money laundering monitoring enabled');

-- ============================================
-- 8. USER WALLET VIEW (Easy balance check)
-- ============================================

CREATE VIEW user_wallet_summary AS
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    u.aini_coins as current_balance,
    
    -- Lifetime stats
    COALESCE(SUM(CASE WHEN t.transaction_type LIKE 'earned%' THEN t.amount ELSE 0 END), 0) as total_earned,
    COALESCE(SUM(CASE WHEN t.transaction_type LIKE 'spent%' THEN ABS(t.amount) ELSE 0 END), 0) as total_spent,
    COALESCE(SUM(CASE WHEN t.transaction_type = 'transfer_in' THEN t.amount ELSE 0 END), 0) as total_received,
    COALESCE(SUM(CASE WHEN t.transaction_type = 'transfer_out' THEN ABS(t.amount) ELSE 0 END), 0) as total_sent,
    
    -- Purchase stats
    COALESCE((SELECT SUM(coins_amount) FROM aini_coin_purchases WHERE user_id = u.id AND status = 'completed'), 0) as total_purchased,
    COALESCE((SELECT SUM(total_charged) FROM aini_coin_purchases WHERE user_id = u.id AND status = 'completed'), 0) as total_spent_usd,
    
    -- USD equivalent (1 coin = $1)
    u.aini_coins * 1.00 as balance_usd_value,
    
    u.created_at as member_since
    
FROM ainitravel_users u
LEFT JOIN aini_coin_transactions t ON t.user_id = u.id
GROUP BY u.id;

-- ============================================
-- 9. PARTNER DASHBOARD VIEW
-- ============================================

CREATE VIEW partner_dashboard AS
SELECT 
    p.id as partner_id,
    p.business_name,
    p.business_type,
    p.is_active,
    
    -- Transaction stats
    COUNT(pt.id) as total_transactions,
    COALESCE(SUM(pt.total_amount_usd), 0) as total_revenue_usd,
    COALESCE(SUM(pt.coins_used), 0) as coins_accepted,
    COALESCE(SUM(pt.coins_earned), 0) as coins_issued,
    COALESCE(SUM(pt.platform_fee_usd), 0) as platform_fees_paid,
    COALESCE(SUM(pt.partner_receives_usd), 0) as total_earnings,
    
    -- Pending settlement
    COALESCE(SUM(CASE WHEN pt.partner_settlement_status = 'pending' THEN pt.partner_receives_usd ELSE 0 END), 0) as pending_payout,
    
    p.partner_since,
    p.reward_percentage
    
FROM aini_partner_businesses p
LEFT JOIN aini_partner_transactions pt ON pt.partner_id = p.id
GROUP BY p.id;

-- ============================================
-- GRANT PERMISSIONS
-- ============================================

GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_partner_businesses TO 'hoteluser'@'localhost';
GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_coin_purchases TO 'hoteluser'@'localhost';
GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_partner_transactions TO 'hoteluser'@'localhost';
GRANT SELECT, INSERT, UPDATE ON hotel_booking_system.aini_partner_settlements TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.aini_coin_reserve_fund TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.aini_coin_exchange_rates TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.user_wallet_summary TO 'hoteluser'@'localhost';
GRANT SELECT ON hotel_booking_system.partner_dashboard TO 'hoteluser'@'localhost';
