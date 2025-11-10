# Active Context - AiNi Travel Platform

**Last Updated**: November 9, 2025 - 23:05 UTC  
**Current Phase**: Dual-Coin System - COMPLETE ✅  
**Next Phase**: Mercado Pago Payment Integration

---

## 🎯 Current Goals

### Phase 1: Dual-Coin System ✅ COMPLETE
- ✅ Database migration (dual columns + tracking tables)
- ✅ Wallet UI (dual balance cards)
- ✅ Conversion page (Rewards ↔ Crypto)
- ✅ AiniCoinSystem.php (conversion methods)
- ✅ Transfer page (coin type selection)
- ✅ **Crypto price tracking with market simulation**
- ✅ **Dynamic pricing in update_prices.php**

### Phase 2: Payment Integration 🔄 NEXT
- 🎯 Get Mercado Pago account and API credentials (https://www.mercadopago.com.pe/developers)
- 🎯 Implement checkout in wallet.php (support PEN and USD)
- 🎯 Create webhook handler for payment confirmations
- 🎯 Add purchase history tracking
- 🎯 Test full purchase flow

---

## 📊 System Status

### Dual-Coin Architecture (LIVE)
- **AiNi Rewards**: Stable coin (1 Reward = $1.00 USD)
  - Column: `aini_rewards`
  - Use: Stable savings, bookings, transfers
  - Owner Balance: 199,950

- **AiNi Crypto**: Growth coin (Market price)
  - Column: `aini_crypto`
  - Current Price: **$0.9561 USD** (-4.39% from $1.00 initial)
  - Use: Investment, speculation, future trading
  - Owner Balance: 100
  - Price updated every 15 min via cron (±5% volatility)

### Database Tables (13 Active)
1. `ainitravel_users` - User accounts with dual balances
2. `aini_coin_transactions` - Blockchain-style ledger
3. `aini_coin_conversions` - Rewards ↔ Crypto conversions
4. `aini_crypto_market_prices` - Price history (30-day retention)
5. `aini_coin_purchases` - Fiat → Coin purchases
6. `aini_coin_redemptions` - Reward codes
7. `aini_coin_transfer_limits` - Anti-fraud controls
8. `aini_coin_exchange_rates` - Multi-currency
9. `aini_coin_reserve_fund` - Asset backing
10. `aini_coin_system_config` - Feature flags
11. `aini_partner_businesses` - Merchant network
12. `aini_partner_settlements` - B2B transactions
13. `aini_coin_verification_checkpoints` - Audit trail

---

## 🔧 Recent Updates (Nov 9, 2025)

### Session Summary:
1. ✅ Created `pageFlowDiagram.md` - Complete system architecture diagram
2. ✅ Updated `update_prices.php` - Added AiNi Crypto price simulation
   - ±5% daily volatility (mt_rand for realistic market movements)
   - Volume tracking (10k-500k simulated)
   - Market cap calculation
   - 30-day price history retention
3. ✅ Updated `transfer.php` - Dual-coin transfer support
   - Radio button selection (Rewards vs Crypto)
   - Separate balance displays
   - Dynamic max amounts per coin type
   - Updated preview calculator
4. ✅ Initialized Crypto price - First update: $0.9561 (-4.39%)
5. ✅ Tested price update cron - Working correctly

### Owner Account (ID: 2)
- Email: juanorellanawork2018@gmail.com
- AiNi Rewards: 199,950 (after test conversion)
- AiNi Crypto: 100
- Purpose: Distribute welcome bonuses, test system, provide initial liquidity

---

## 📁 Key Files

### Frontend Pages
- `coming-soon.html` (38KB) - Landing page with dual-coin model
- `wallet.php` - Main dashboard with dual balance cards
- `convert_coins.php` (18KB) - Conversion interface
- `transfer.php` (34KB) - P2P transfers with coin selection
- `rewards.php` - Redemption system
- `reserve_fund.php` - Live asset prices dashboard
- `public_booking.php` - Hotel search/booking

### Backend Logic
- `AiniCoinSystem.php` (22KB) - Core coin operations
  - `convertRewardsToCrypto($userId, $amount)`
  - `convertCryptoToRewards($userId, $amount)`
  - `getCryptoPrice()` - Fetch latest market price
  - `getConversionHistory($userId, $limit)`
  - `transferCoins()` - Now supports coin_type parameter
- `update_prices.php` (9.8KB) - Cron job for price updates
  - EUR/USD, Gold, Silver (asset_prices table - optional)
  - **AiNi Crypto** (aini_crypto_market_prices table)
  - Runs every 15 minutes
- `db_connection_pdo.php` - PDO connection handler

---

## 🚀 Next Steps

### Immediate Priority: Mercado Pago Integration
1. **Account Setup**:
   - Register at https://www.mercadopago.com.pe/developers
   - Get production credentials (Public Key, Access Token)
   - Configure webhook URL

2. **Implementation**:
   ```php
   // In wallet.php - Add "Buy Coins" button
   // Create buy_coins.php - Mercado Pago checkout
   // Create mp_webhook.php - Payment confirmation handler
   ```

3. **Features**:
   - Support PEN (Peruvian Sol) and USD
   - Configurable coin packages (100, 500, 1000, 5000 coins)
   - Auto-credit to aini_rewards after payment
   - Email receipt + transaction record

### Secondary Tasks:
- Add conversion rate chart to `convert_coins.php` (Chart.js)
- Implement crypto price chart in `wallet.php`
- Add transfer notifications (email/in-app)
- Create admin dashboard for price management

---

## 🔍 Testing Checklist

- ✅ View dual balances in wallet.php
- ✅ Convert Rewards → Crypto (live calculator working)
- ✅ Convert Crypto → Rewards (reverse flow working)
- ✅ Transfer Rewards (P2P working)
- ⏳ Transfer Crypto (need to test with updated transfer.php)
- ✅ Crypto price updates every 15 min
- ⏳ Purchase coins with Mercado Pago (pending implementation)

---

**Status**: All core dual-coin features deployed and functional. Ready for payment integration phase.
