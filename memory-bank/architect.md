# MemoriPilot: System Architect

## Overview
This file contains the architectural decisions and design patterns for the MemoriPilot project.

## Architectural Decisions

- Multi-asset reserve backing (30% USD, 25% EUR, 20% Gold, 15% Silver, 10% Services) provides inflation hedge and transparency
- Dual-coin model separates stable rewards (AiNi Rewards) from investment growth (AiNi Crypto)
- Zero transaction fee model - profit from network growth not user fees
- Free unlimited price APIs eliminate ongoing costs
- Mercado Pago primary payment processor for Latin American market access
- Blockchain-style SHA-256 transaction ledger for immutability
- Public reserve dashboard for transparency and trust building



- Implemented blockchain-style immutable transaction ledger with SHA-256 hashing and previous transaction linking for transparency and fraud prevention
- Chose 1:1 USD backing for AiNi Coins (1 coin = ## Architectural Decisions purchasing power) to maintain stable value and user trust
- Separated public guest system (ainitravel.com with AiNi Coins) from internal hotel management (HotelCoins) for clear business logic
- Used PDO with prepared statements throughout for SQL injection prevention
- Implemented transfer limits (30-day account age, 10-1000 coins daily) to prevent fraud and money laundering
- Set platform fee at 2.5% for coin purchases vs 10-15% traditional payment fees to provide competitive advantage
- Designed closed-loop partner network ecosystem to keep value circulating within travel industry
- Used session-based authentication with user_id storage for logged-in state management



1. **Decision 1**: Description of the decision and its rationale.
2. **Decision 2**: Description of the decision and its rationale.
3. **Decision 3**: Description of the decision and its rationale.



## Design Considerations

- Payment processor selection critical for market access - Stripe excluded Peru
- Multi-currency support needed (PEN, USD, EUR, MXN, BRL) for regional expansion
- API rate limits: chose free unlimited sources over paid limited tiers
- Cron frequency: 15-min updates balance freshness vs server load
- Landing page file confusion: coming-soon.html is actual page (Apache config)
- Backup pattern: always scp download before upload for disaster recovery



- Future payment gateway integration (Stripe/Adyen) will require PCI compliance and webhook handling
- KYC verification threshold set at $1000 USD for regulatory compliance (FinCEN guidelines)
- Reserve fund must maintain 1.0 minimum ratio (100% backing) and 1.2 target ratio (120% backing) for financial stability
- Partner settlements configured for weekly/monthly cycles to balance cash flow and transaction costs
- Reward redemptions expire after 90 days to prevent indefinite liability
- Daily verification checkpoints enable blockchain integrity auditing and fraud detection
- Multi-currency support requires daily exchange rate updates (currently manual, should automate)
- All transaction amounts stored as DECIMAL(15,2) to prevent floating-point precision errors
- Transaction hash generation includes timestamp + user_id + amount + previous_hash for chain integrity
- Row-level locking (FOR UPDATE) prevents race conditions during concurrent transfers



## Components

### Reserve Fund Dashboard

Public transparency page showing real-time multi-asset backing breakdown

**Responsibilities:**

- Display live asset allocation pie chart
- Calculate reserve ratio health status
- Show last updated timestamps from price APIs
- Provide educational content about backing model

### Price Update Service

Cron-based service fetching live forex and metals prices

**Responsibilities:**

- Fetch EUR/USD from open.er-api.com every 15 min
- Fetch Gold from api.nbp.pl (convert PLN/gram to USD/oz)
- Fetch Silver from api.exchangerate.host (XAG rate)
- Cache prices in asset_prices table
- Log all updates for monitoring
- Handle API failures gracefully with fallbacks

### Payment Processing Layer (Mercado Pago)

Handles fiat-to-coin purchases via Mercado Pago API

**Responsibilities:**

- Process PEN and USD payments
- Generate checkout sessions
- Handle webhooks for payment confirmation
- Update user coin balances
- Record purchases in blockchain ledger
- Support refunds and disputes

### Dual-Coin Wallet System

User wallet managing both AiNi Rewards (stable) and AiNi Crypto (investment)

**Responsibilities:**

- Display separate balances for Rewards vs Crypto
- Enable conversion between coin types
- Show transaction history
- Support P2P transfers
- Fraud detection for suspicious activity





### AiNi Coin System (AiniCoinSystem.php)

Complete financial transaction system with blockchain-style immutable ledger, SHA-256 hashing, balance verification, transfer limits, and fraud detection. Supports earning (10 coins/$1 spent), spending, peer-to-peer transfers, purchases, and refunds.

**Responsibilities:**

- Generate SHA-256 transaction hashes
- Record transactions with balance locking
- Verify blockchain integrity
- Check transfer limits and fraud flags
- Award booking coins (10 per $1)
- Process peer-to-peer transfers
- Track lifetime earned/spent statistics

### Wallet Page (wallet.php)

User-facing wallet interface showing current balance, buy coins form with 6-currency support, transaction history table, lifetime statistics, and quick action buttons. Features live transaction preview and blockchain security notice.

**Responsibilities:**

- Display current coin balance and USD equivalent
- Show transaction history with blockchain hashes
- Provide buy coins form with multi-currency selector
- Calculate platform fee (2.5%)
- Display lifetime earned/spent statistics
- Link to transfer and rewards pages

### Transfer Page (transfer.php)

Peer-to-peer coin transfer interface with recipient search, amount input, transfer limits display, quick amount buttons, optional note field, live preview, and recent transfers sidebar. Implements fraud prevention with daily limits.

**Responsibilities:**

- Search recipient by email
- Validate transfer amount against balance and limits
- Display daily usage progress bar
- Show recent transfer history
- Generate live transfer preview
- Process transfer with AiniCoinSystem::transferCoins()

### Rewards Store (rewards.php)

Reward redemption marketplace with filterable reward cards, redemption flow, code generation, and user redemption tracking. Shows 7 sample rewards ranging from discounts to upgrades to amenities.

**Responsibilities:**

- Display available rewards with type filtering
- Check user balance against reward cost
- Generate unique redemption codes
- Track redemption status (active/used/expired)
- Show user's redemption history
- Deduct coins via transaction system

### Exchange Rate System (aini_coin_exchange_rates)

Multi-currency exchange rate management supporting USD, EUR, GBP, PEN, MXN, BRL with buy/sell spreads. Enables international travelers to purchase coins in their local currency.

**Responsibilities:**

- Store coins_per_unit for each currency
- Maintain buy/sell rate spreads
- Track last_updated timestamps
- Support currency activation/deactivation

### Partner Network (aini_partner_businesses)

Partner business network including hotels, tours, restaurants, transportation, and shops. Tracks partner details, reward percentages, transaction fees, settlement cycles, and revenue statistics.

**Responsibilities:**

- Store partner business information
- Track partner type and location
- Calculate partner reward percentages
- Manage settlement preferences
- Monitor partner revenue and transaction counts

### Reserve Fund Management (aini_coin_reserve_fund)

Financial backing verification system that tracks total coins in circulation, USD reserves, reserve ratios, and daily snapshots for audit compliance and financial stability.

**Responsibilities:**

- Calculate total coins in circulation
- Track USD reserve balance
- Verify reserve ratio (min 1.0, target 1.2)
- Record daily snapshots
- Flag discrepancies for audit

### Configuration System (aini_coin_system_config)

System configuration management with 24 settings including coins per dollar earned (10), purchase fee (2.5%), partner fee (3%), transfer limits (10-1000), account age requirements (30 days), and reserve ratios.

**Responsibilities:**

- Store all system parameters
- Provide default values
- Enable dynamic configuration updates
- Support feature flags (withdrawal_enabled, etc)

### Database Connection (db_connection_pdo.php)

Database authentication handler using PDO with hoteluser credentials. CRITICAL: Do NOT use root account due to auth_socket plugin - root@localhost requires Unix socket authentication and cannot connect from PHP with password.

**Responsibilities:**

- Establish PDO connection to hotel_booking_system
- Use hoteluser/hotelpass123 credentials
- Set UTF-8 charset
- Enable error exceptions
- Provide global $pdo object



