# AiNi Travel - Page Flow & System Architecture

## 🌐 Public Pages (No Authentication Required)

```
┌─────────────────────────────────────────────────────────────┐
│                    AINITRAVEL.COM                           │
│                  (Public Landing)                           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
        ┌─────────────────────────────────────┐
        │    coming-soon.html                 │
        │    (DirectoryIndex Priority #1)     │
        │                                     │
        │  - Dual Coin Model Explanation     │
        │  - AiNi Rewards (Stable)           │
        │  - AiNi Crypto (Growth)            │
        │  - Multi-Asset Backing Info        │
        │  - Link to Reserve Fund →          │
        │  - Join Waitlist Button            │
        └─────────────────────────────────────┘
                    │              │
                    │              └──────────────┐
                    ↓                             ↓
        ┌──────────────────────┐    ┌──────────────────────┐
        │  public_booking.php  │    │  reserve_fund.php    │
        │  (Hotel Search/Book) │    │  (Live Dashboard)    │
        │                      │    │                      │
        │  - Search hotels     │    │  - EUR/USD Price     │
        │  - View rooms        │    │  - Gold Price        │
        │  - Make reservation  │    │  - Silver Price      │
        │  - Guest checkout    │    │  - Chart.js Graphs   │
        └──────────────────────┘    │  - 24/7 Public       │
                    │                └──────────────────────┘
                    │
                    ↓
        ┌──────────────────────┐
        │     login.php        │
        │   (Authentication)   │
        │                      │
        │  - Email/Password    │
        │  - Session Creation  │
        └──────────────────────┘
                    │
                    │ ✓ Login Success
                    ↓
```

## 🔐 Authenticated Pages (Requires Login)

```
┌─────────────────────────────────────────────────────────────┐
│              MAIN NAVIGATION HEADER                         │
│   🏖️ AiNi Travel | Experiences | Social | 💰 Coins         │
│                    [Language] [🔔] [👤User ▾]               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
        ┌─────────────────────────────────────────┐
        │           wallet.php                    │
        │        (Main Dashboard)                 │
        │                                         │
        │  ┌──────────────┐  ┌──────────────┐   │
        │  │ AiNi Rewards │  │  AiNi Crypto │   │
        │  │   (Blue)     │  │   (Purple)   │   │
        │  │  200,100     │  │     100      │   │
        │  │ 💎 Stable    │  │  🚀 Growth   │   │
        │  └──────────────┘  └──────────────┘   │
        │                                         │
        │  Quick Actions:                         │
        │  ┌──────────────────────────┐          │
        │  │ 🔄 Convert Coins         │──┐       │
        │  │ ✈️  Send Coins           │  │       │
        │  │ 🎁 Rewards Store         │  │       │
        │  │ 📊 Reserve Fund          │  │       │
        │  │ 🏨 Book Experience       │  │       │
        │  └──────────────────────────┘  │       │
        │                                 │       │
        │  📜 Transaction History         │       │
        └─────────────────────────────────┘       │
                    │              │              │
                    │              │              │
        ┌───────────┘              │              └───────────┐
        ↓                          ↓                          ↓
┌────────────────┐      ┌────────────────┐       ┌────────────────┐
│  transfer.php  │      │  rewards.php   │       │convert_coins.php│
│  (P2P Send)    │      │  (Redemption)  │       │ (Conversion)    │
│                │      │                │       │                 │
│ - Find User    │      │ - Browse       │       │ Rewards→Crypto  │
│ - Enter Amount │      │   Rewards      │       │ ┌─────────────┐ │
│ - Add Note     │      │ - Redeem Coins │       │ │Your Balance │ │
│ - Daily Limits │      │ - Get Code     │       │ │  199,950    │ │
│ - Verify       │      │ - Use at       │       │ │             │ │
│   Balance      │      │   Partners     │       │ │Amount: ___  │ │
│                │      │                │       │ │Rate: 1:1    │ │
└────────────────┘      └────────────────┘       │ │Result: ≈    │ │
        │                       │                 │ └─────────────┘ │
        │                       │                 │                 │
        │                       │                 │ Crypto→Rewards  │
        │                       │                 │ ┌─────────────┐ │
        │                       │                 │ │Your Balance │ │
        │                       │                 │ │    100      │ │
        │                       │                 │ │≈ $100 USD   │ │
        │                       │                 │ │Amount: ___  │ │
        │                       │                 │ │Rate: 1:1    │ │
        │                       │                 │ │Result: ≈    │ │
        │                       │                 │ └─────────────┘ │
        │                       │                 │                 │
        │                       │                 │ 📜 History      │
        └───────────────────────┴─────────────────┴─────────────────┘
                                        │
                                        ↓
                            ┌────────────────────┐
                            │   profile.php      │
                            │ (User Settings)    │
                            │                    │
                            │ - Personal Info    │
                            │ - Preferences      │
                            │ - Language         │
                            │ - Currency         │
                            └────────────────────┘
                                        │
                                        ↓
                            ┌────────────────────┐
                            │   logout.php       │
                            │                    │
                            │ - Destroy Session  │
                            │ - Redirect to      │
                            │   public_booking   │
                            └────────────────────┘
```

## 🗄️ Database Architecture

```
┌─────────────────────────────────────────────────────────────┐
│              hotel_booking_system (MySQL)                   │
└─────────────────────────────────────────────────────────────┘

User Management:
├── ainitravel_users
│   ├── id, name, email, password
│   ├── aini_rewards (INT) ← Stable coin balance
│   ├── aini_crypto (INT)  ← Growth coin balance
│   ├── phone, country, preferred_language
│   └── created_at, updated_at

Dual Coin System (13 Tables Total):
├── aini_coin_transactions
│   ├── Blockchain-style ledger
│   ├── transaction_hash (SHA-256)
│   ├── previous_transaction_id (chain)
│   ├── coin_type (rewards/crypto)
│   └── Immutable records

├── aini_coin_conversions ⭐ NEW
│   ├── user_id, from_coin_type, to_coin_type
│   ├── amount_converted, conversion_rate
│   ├── from_balance_before/after
│   ├── to_balance_before/after
│   ├── transaction_hash
│   └── market_price_usd

├── aini_crypto_market_prices ⭐ NEW
│   ├── price_usd (current: $1.00)
│   ├── volume_24h, market_cap
│   ├── circulating_supply
│   ├── price_change_24h
│   └── recorded_at

├── aini_coin_purchases
│   └── Fiat → Coin transactions

├── aini_coin_redemptions
│   └── Coin → Reward codes

├── aini_coin_transfer_limits
│   └── Anti-fraud controls

├── aini_coin_exchange_rates
│   └── Multi-currency (USD, EUR, GBP, PEN, MXN, BRL)

├── aini_coin_reserve_fund
│   └── Asset backing tracking

├── aini_coin_system_config
│   ├── dual_coin_enabled = true
│   ├── crypto_initial_price_usd = 1.0000
│   ├── rewards_crypto_conversion_enabled = true
│   └── crypto_market_trading_enabled = false

├── aini_partner_businesses
├── aini_partner_settlements
├── aini_coin_rewards
└── aini_coin_verification_checkpoints

Price Data:
└── asset_prices
    ├── EUR/USD (open.er-api.com)
    ├── Gold (api.nbp.pl)
    ├── Silver (api.exchangerate.host)
    └── Updated every 15 minutes
```

## 🔄 Key User Flows

### Flow 1: New User Journey
```
1. Visit ainitravel.com
   ↓
2. See coming-soon.html (dual-coin explanation)
   ↓
3. Click "Join Waitlist" or "Login"
   ↓
4. Register/Login via login.php
   ↓
5. Redirected to wallet.php (main dashboard)
   ↓
6. See 0 Rewards, 0 Crypto (new account)
   ↓
7. Option: Buy coins (future Mercado Pago)
```

### Flow 2: Convert Coins
```
1. Login → wallet.php
   ↓
2. Click "Convert Coins" button
   ↓
3. convert_coins.php opens
   ↓
4. Choose direction:
   - Rewards → Crypto (invest for growth)
   - Crypto → Rewards (secure profits)
   ↓
5. Enter amount
   ↓
6. Live calculator shows result
   ↓
7. Click Convert
   ↓
8. Backend: AiniCoinSystem::convertRewardsToCrypto()
   ↓
9. Database updates:
   - Update user balances
   - Record in aini_coin_conversions
   - Generate transaction_hash
   ↓
10. Redirect to convert_coins.php?success=1
    ↓
11. See updated balances + conversion history
```

### Flow 3: Send Coins (P2P Transfer)
```
1. wallet.php → "Send Coins"
   ↓
2. transfer.php
   ↓
3. Enter recipient email
   ↓
4. Enter amount (Rewards only for now)
   ↓
5. Add optional note
   ↓
6. System checks:
   - Account age ≥ 30 days
   - Balance sufficient
   - Daily limit not exceeded
   - Amount between min/max
   ↓
7. Record transaction (blockchain-style)
   ↓
8. Update both user balances
   ↓
9. Success confirmation
```

### Flow 4: View Reserve Fund
```
1. Any page → Click "Reserve Fund" link
   ↓
2. reserve_fund.php (public/no login)
   ↓
3. See live prices:
   - EUR/USD exchange rate
   - Gold price (USD/oz)
   - Silver price (USD/oz)
   ↓
4. Chart.js graphs showing:
   - Price trends
   - Asset allocation
   - Reserve health
   ↓
5. Auto-refreshes every 15 minutes
```

## 📊 System Config & Pricing

### Current Prices (Nov 9, 2025)
- **AiNi Rewards**: 1 Reward = $1.00 USD (stable)
- **AiNi Crypto**: 1 Crypto = $1.00 USD (initial price)
- **EUR/USD**: ~1.08 (live via API)
- **Gold**: ~$2,650/oz (live via API)
- **Silver**: ~$31/oz (live via API)

### Conversion Rates
- **Rewards → Crypto**: 1 Reward = 1 Crypto (at $1.00 price)
- **Crypto → Rewards**: 1 Crypto = 1 Reward (at $1.00 price)
- **Fee**: FREE (no conversion fees)

### Owner Account
- **Email**: juanorellanawork2018@gmail.com
- **User ID**: 2
- **Rewards Balance**: 199,950
- **Crypto Balance**: 100
- **Purpose**: Distribute bonuses, test system, give welcome rewards

## 🔧 Backend Classes & Methods

### AiniCoinSystem.php
```php
// Core Methods
- __construct($pdo)
- generateTransactionHash()
- recordTransaction()
- transferCoins($fromUserId, $toUserId, $amount, $note)
- checkTransferLimits($userId, $amount = 0)
- getUserCoinStats($userId)
- getTransactionHistory($userId, $limit)

// Dual Coin Methods ⭐ NEW
- getCryptoPrice()
- convertRewardsToCrypto($userId, $rewardsAmount)
- convertCryptoToRewards($userId, $cryptoAmount)
- getConversionHistory($userId, $limit)
```

## 📁 File Structure

```
/var/www/html/ainitravel.com/
├── coming-soon.html          ⭐ Landing (dual-coin model)
├── public_booking.php        📱 Hotel search/booking
├── reserve_fund.php          📊 Live price dashboard
├── login.php                 🔐 Authentication
├── wallet.php                💰 Main dashboard (dual balance)
├── convert_coins.php         🔄 Conversion page ⭐ NEW
├── transfer.php              ✈️  P2P transfers
├── rewards.php               🎁 Redemption system
├── profile.php               👤 User settings
├── logout.php                🚪 Session destroy
├── AiniCoinSystem.php        🔧 Core logic class
├── db_connection_pdo.php     🗄️  Database connection
└── update_prices.php         📈 Price API cron job
```

## 🌍 Multi-Language Support
- English (EN)
- Spanish (ES) - Default
- Portuguese (PT) - Future
- French (FR) - Future

## 💳 Payment Integration (Future)
- **Primary**: Mercado Pago (Peru, LATAM)
- **Future**: Stripe (USA, Europe)
- **Support**: PEN, USD, EUR, BRL, MXN

---

**Last Updated**: November 9, 2025 - 22:45 UTC
**System Status**: ✅ All Core Features Operational
**Next Phase**: Mercado Pago Integration
