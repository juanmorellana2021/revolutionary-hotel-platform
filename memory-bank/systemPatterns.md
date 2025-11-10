# System Patterns

## Architectural Patterns

- Pattern 1: Description

## Design Patterns

- Pattern 1: Description

## Common Idioms

- Idiom 1: Description

## Multiple File Versions Pattern

The codebase maintains multiple versions of the same feature with suffixes like _current, _modern, _prod, _live, _server, _fix. This allows testing new features while keeping production stable. Common pattern seen in: room_management, public_booking, calendar_view, photo_upload files.

### Examples

- room_management.php (base)
- room_management_modern.php
- room_management_prod.php
- room_management_live.php
- public_booking.php
- public_booking_current.php
- public_booking_prod.php


## Database Schema Structure

Main MySQL database 'hotel_booking_system' contains: users, rooms, bookings, hotel_info, hotel_services/amenities, and complete Aini Coin ecosystem (partner businesses, transactions, settlements, reserve fund, exchange rates). User roles: guest, manager, admin. Default credentials: hoteluser/hotelpass123.

### Examples

- ainitravel_users table with aini_coins balance
- aini_coin_transactions (immutable ledger)
- aini_partner_businesses (hotels, tours, restaurants)
- aini_coin_purchases (fiat to coins)
- room_status_by_date for availability
- hotel_info with check-in/out times


## AiniFlow API Endpoints

Node.js server on port 3000 with Socket.IO for real-time messaging. Main routes: /api/auth (SMS verification via Twilio), /api/messages, /api/chatrooms, /api/social, /api/wallet, /api/reviews, /api/users. Uses Redis for verification codes (5min expiry) and PostgreSQL for user data.

### Examples

- POST /api/auth/request-code - send SMS verification
- POST /api/auth/verify-code - login/register
- GET /api - API documentation
- GET /health - health check
- WebSocket on ws://0.0.0.0:3000


## Aini Coin Economic Model

Closed-loop travel currency: 1 coin = $1 USD base value. Platform fees: 2.5% on purchases, 3% on partner transactions. Rewards: 10% default cashback. Partners include hotels, tours, restaurants, transportation. Reserve fund maintains 1:1 backing ratio. Multi-currency support (USD, EUR, GBP, PEN, MXN, BRL).

### Examples

- User buys $100 worth of coins = 100 coins (minus 2.5% fee)
- Spend 50 coins at partner hotel, earn 5 coins back (10% reward)
- Partner receives payment minus 3% platform fee
- Daily reserve fund snapshots for health monitoring


## Server Deployment Access

I have SSH passwordless access to all production servers. Use 'prod-vps' as the server alias for deployments. Main paths: /var/www/html/ainitravel.com/ (OTA/landing), /var/www/html/manage/ (PMS system). Always use scp for file uploads to production.

### Examples

- scp file.php prod-vps:/var/www/html/manage/
- scp index.html prod-vps:/var/www/html/ainitravel.com/
- ssh prod-vps 'cd /var/www/html && ls'


## AiniTravel.com Landing Page File

ainitravel.com serves **coming-soon.html** as the default landing page, NOT index.html. Apache config has DirectoryIndex priority: coming-soon.html, public_booking.php, index.php, index.html. Always edit and upload coming-soon.html for landing page changes. Server path: /var/www/html/ainitravel.com/

### Examples

- scp coming-soon.html prod-vps:/var/www/html/ainitravel.com/
- Download before upload: scp prod-vps:/var/www/html/ainitravel.com/coming-soon.html ./backup.html
- index.html exists but is NOT served by default


## Consistent Navigation Header/Footer Pattern

All public-facing pages (profile.php, wallet.php, transfer.php, rewards.php) must use identical header and footer HTML to maintain consistent user experience. The header includes: gradient purple background, AiNi Travel logo, main navigation links (Experiences, Social, Coins), language selector, notification bell with badge count, and user profile dropdown menu. The footer contains: company info, quick links, support links, social media icons, and copyright notice. This pattern was explicitly requested by user and is critical for UX consistency.

### Examples

- wallet.php lines 1-150: Full navigation header with gradient-bg class, logo, nav menu, profile dropdown
- transfer.php lines 1-150: Identical header structure
- rewards.php footer section: Standard 4-column grid layout with purple gradient background
- All pages use: <nav class="gradient-bg shadow-lg sticky top-0 z-50">


## Database Credentials - Root Account Issue

CRITICAL PATTERN: MySQL root account on prod-vps uses auth_socket authentication plugin and CANNOT be accessed from PHP using password. Always use hoteluser/hotelpass123 for database connections in PHP code. Root can only authenticate via Unix socket (sudo mysql) for command-line access. This caused initial registration failures until fixed in db_connection_pdo.php. All future database connection code MUST use hoteluser credentials.

### Examples

- db_connection_pdo.php: $username = 'hoteluser'; $password = 'hotelpass123';
- Error encountered: PDOException: SQLSTATE[HY000] [1698] Access denied for user 'root'@'localhost'
- Solution: Changed from root/password123 to hoteluser/hotelpass123
- Shell commands can still use: mysql -u root -ppassword123 for table creation with SUPER privilege


## Blockchain-Style Transaction Recording

All coin transactions use an immutable ledger pattern with SHA-256 hashing. Each transaction links to the previous transaction via transaction_hash, creating a verifiable chain. The hash is generated from: timestamp + user_id + amount + transaction_type + previous_hash. Transactions are NEVER deleted, only marked with status changes. Balance verification occurs before (balance_before) and after (balance_after) each transaction with row-level locking (SELECT FOR UPDATE) to prevent race conditions.

### Examples

- AiniCoinSystem::generateTransactionHash(): hash('sha256', $timestamp . $userId . $amount . $type . $prevHash)
- AiniCoinSystem::recordTransaction(): Uses BEGIN TRANSACTION, SELECT FOR UPDATE, balance verification, INSERT, COMMIT pattern
- Transaction fields: id, user_id, amount, transaction_type, balance_before, balance_after, transaction_hash, previous_hash, metadata (JSON)
- All transfer, purchase, earned, spent, refund operations call recordTransaction() to maintain chain integrity


## Multi-Currency Exchange Rate Handling

Exchange rates are stored with coins_per_unit (how many coins you get per 1 unit of currency), buy_rate (slightly lower for purchasing), sell_rate (slightly higher for conversion back). USD is the base currency at 1.0000. When users buy coins in foreign currency, the amount is converted using the current exchange rate, platform fee is calculated, and net coins are credited. Example: 100 EUR * 1.0800 rate = 108 coins, minus 2.5% fee = 105.30 net coins.

### Examples

- aini_coin_exchange_rates table: currency_code, currency_name, coins_per_unit, buy_rate, sell_rate, is_active, last_updated
- Supported currencies: USD (1.0000), EUR (1.0800), GBP (1.2700), PEN (0.2700), MXN (0.0580), BRL (0.2000)
- wallet.php buy form: SELECT currency dropdown with all active exchange rates
- Purchase calculation: $coins_to_add = $amount_usd * $rate; $net_coins = $coins_to_add - ($coins_to_add * 0.025);


## Transfer Limits and Fraud Prevention

Peer-to-peer transfers implement multiple security layers via aini_coin_transfer_limits table: minimum account age (30 days), minimum transfer amount (10 coins), maximum single transfer (1000 coins), daily transfer limit (1000 coins total), and flagging system for suspicious activity. The checkTransferLimits() method validates all rules before allowing transfer. Daily usage is tracked and reset at midnight. Users receive clear feedback on remaining daily limit with visual progress bar.

### Examples

- AiniCoinSystem::checkTransferLimits($userId, $amount): Returns can_transfer boolean and message
- aini_coin_transfer_limits: user_id, daily_limit, daily_used, last_transfer_date, min_transfer, max_transfer, account_age_requirement
- transfer.php: Progress bar showing daily_used / daily_limit percentage
- Validation checks: account age >= 30 days, amount >= 10 and <= 1000, daily_remaining >= amount, balance >= amount


## Reward Redemption Code Generation

When users redeem rewards, a unique 12-character alphanumeric redemption code is generated using MD5 hash of uniqid($user_id . $reward_id, true) and taking first 12 characters uppercased. Codes are stored in aini_coin_redemptions with 90-day expiry, used/unused status tracking, and optional used_at timestamp. This prevents duplicate redemptions while providing user-friendly codes for validation at partner businesses.

### Examples

- rewards.php redemption: $redemption_code = strtoupper(substr(md5(uniqid($user_id . $reward_id, true)), 0, 12));
- $expires_at = date('Y-m-d H:i:s', strtotime('+90 days'));
- Sample code format: A7B3C9D2E4F1
- Redemption table: redemption_code, user_id, reward_id, coins_spent, is_used, used_at, expires_at, redeemed_at


## Session-Based Authentication

User authentication uses PHP sessions with $_SESSION['user_id'] as the primary identifier. All protected pages check for session existence at the top and redirect to login.php if not set. User data is fetched from ainitravel_users table on each page load using the session user_id. Logout destroys the session and redirects to public_booking.php. This pattern keeps authentication simple while maintaining security through server-side session management.

### Examples

- All pages start with: session_start(); if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
- login.php sets: $_SESSION['user_id'] = $user['id']; after successful authentication
- logout.php: session_unset(); session_destroy(); header('Location: public_booking.php');
- User data fetch: $stmt = $pdo->prepare('SELECT * FROM ainitravel_users WHERE id = ?'); $stmt->execute([$_SESSION['user_id']]);


## Mercado Pago Integration Pattern

**SUPERSEDED by Niubiz** - Payment flow for Latin American markets using Mercado Pago API. User initiates purchase from wallet.php, backend creates preference (checkout session) with item details and callback URLs, Mercado Pago hosted checkout page handles payment, webhook receives IPN (Instant Payment Notification) with payment status, backend verifies signature, updates user balance in ainitravel_users table, records transaction in aini_coin_purchases with SHA-256 hash chain. Supports PEN (Peruvian soles) and USD. Test environment uses sandbox credentials, production uses live keys stored in environment variables or config file outside web root. **NOTE**: Mercado Pago requires separate accounts per country (can't process Mexico payment to Peru account). Replaced with Niubiz for Peru market.

### Examples

- wallet.php: Buy coins button → POST to create_payment.php
- create_payment.php: Initialize Mercado Pago SDK, create preference, redirect to checkout URL
- webhook_mercadopago.php: Receive IPN, verify payment_id, update database, send confirmation email
- aini_coin_purchases table: payment_id (Mercado Pago ID), amount_paid (PEN/USD), coins_received, transaction_hash (SHA-256)


## Cron-Based Price Update with Database Caching

Live asset prices (EUR/USD, Gold, AiNi Crypto) updated every 15 minutes via cron job running update_prices.php. Prices cached in asset_prices table for fast page loads. Cron configured as: */15 * * * * /usr/bin/php /path/to/update_prices.php >> logs/cron.log 2>&1. Always use full absolute paths for executables. APIs: open.er-api.com (EUR), api.nbp.pl (Gold in PLN/gram converted to USD/oz), internal simulation for AiNi Crypto (±5% volatility). Logs to logs/price_updates.log. SQL uses ON DUPLICATE KEY UPDATE with VALUES() function to avoid parameter binding errors. Fallback to last known prices if API fails.

### Examples

- Cron setup: (crontab -l; echo '*/15 * * * * /usr/bin/php /var/www/html/ainitravel.com/update_prices.php >> logs/cron.log 2>&1') | crontab -
- EUR update: INSERT INTO asset_prices (asset_type, current_price, ...) VALUES ('eur', :price, ...) ON DUPLICATE KEY UPDATE current_price = VALUES(current_price)
- Reserve fund pulls cached prices: SELECT current_price FROM asset_prices WHERE asset_type IN ('eur', 'gold')
- Gold conversion: (PLN_per_gram * PLN_to_USD_rate) * 31.1035 grams/oz = USD_per_oz


## SQL Parameter Binding in ON DUPLICATE KEY UPDATE

When using PDO prepared statements with ON DUPLICATE KEY UPDATE, NEVER use the same :placeholder in both INSERT and UPDATE clauses as it requires duplicate binding. Use VALUES(column_name) function which references INSERT values without additional parameters. Pattern: INSERT INTO table (col) VALUES (:val) ON DUPLICATE KEY UPDATE col = VALUES(col). This prevents SQLSTATE[HY093] Invalid parameter number errors. Apply to all UPSERT operations.

### Examples

- WRONG: INSERT INTO asset_prices (price) VALUES (:price) ON DUPLICATE KEY UPDATE price = :price (needs ['price' => $val, 'price' => $val])
- CORRECT: INSERT INTO asset_prices (price) VALUES (:price) ON DUPLICATE KEY UPDATE price = VALUES(price) (only needs ['price' => $val])
- Applied in update_prices.php for EUR, Gold, Silver updates
- MySQL VALUES() function refers to the value that would be inserted


## Server Backup Before Infrastructure Upgrades

Mandatory backup procedure before any server changes (RAM upgrades, OS updates, migrations). Steps: 1) Create timestamped local directory (backup-prod-vps-YYYY-MM-DD), 2) scp -r download web files recursively, 3) SSH mysqldump databases to /tmp on server, 4) scp download SQL dumps, 5) verify backup sizes, 6) SSH cleanup temp files. For hotel_booking_system: mysqldump -u hoteluser -photelpass123 (warning about PROCESS privilege is normal/ignorable). Expected dump size ~170KB. Restore ready: mysql -u hoteluser -p hotel_booking_system < backup.sql. Keep backups 30+ days.

### Examples

- mkdir backup-prod-vps-2025-11-09
- scp -r prod-vps:/var/www/html/ainitravel.com ./backup-2025-11-09/
- ssh prod-vps "mysqldump -u hoteluser -photelpass123 hotel_booking_system > /tmp/backup.sql"
- scp prod-vps:/tmp/backup.sql ./backup-2025-11-09/
- Restore: mysql -u hoteluser -photelpass123 hotel_booking_system < backup.sql


## PowerShell SSH Command Escaping

When executing complex MySQL commands via SSH from PowerShell, quote escaping is problematic. Instead of inline SQL strings, upload .sql file to server /tmp via scp then execute: ssh prod-vps "mysql -u user -ppass db < /tmp/file.sql". Avoids PowerShell quote parsing errors with parentheses, special chars. Especially for CREATE TABLE with multiple columns, indexes, ON DUPLICATE KEY statements. Clean up temp file after: ssh prod-vps "rm /tmp/file.sql".

### Examples

- AVOID: ssh prod-vps "mysql -e \"CREATE TABLE asset_prices (id INT, ...)\""  (PowerShell quote hell)
- USE: scp create_table.sql prod-vps:/tmp/ && ssh prod-vps "mysql -u hoteluser -ppass hotel_booking_system < /tmp/create_table.sql"
- Cleanup: ssh prod-vps "rm /tmp/create_table.sql"


## PowerShell SSH + sed/awk CRITICAL PATTERN

**NEVER use sed/awk commands directly via SSH from PowerShell** - quote escaping ALWAYS fails. PowerShell mangles quotes in: ssh prod-vps "sed -i 's/old/new/' file" or ssh prod-vps 'command'. **ALWAYS use the 3-step download-edit-upload pattern instead.**

### THE ONLY RELIABLE METHOD:

```powershell
# Step 1: Download file from production
scp prod-vps:/var/www/html/ainitravel.com/file.php ./file_backup.php

# Step 2: Edit locally using PowerShell or text editor
(Get-Content file_backup.php) -replace '#experiences', 'experiences_list.php' | Set-Content file_backup.php

# Step 3: Upload back to production
scp file_backup.php prod-vps:/var/www/html/ainitravel.com/file.php
```

### Why This is The ONLY Way:

1. **sed via SSH fails**: `ssh prod-vps "sed -i 's/old/new/' file"` → PowerShell quote hell, ALWAYS fails
2. **Escaping doesn't work**: Single quotes, double quotes, backticks, escape chars ALL fail in different ways
3. **Temp scripts fail**: `echo 'sed command' > script.sh && bash script.sh` → still has quote issues from PowerShell
4. **Direct editing works**: Download → local edit → upload = NO quote escaping issues

### PowerShell String Replacement Commands:

```powershell
# Simple replacement
(Get-Content file.php) -replace 'old_text', 'new_text' | Set-Content file.php

# Regex replacement
(Get-Content file.php) -replace 'href="#experiences"', 'href="experiences_list.php"' | Set-Content file.php

# Multiple replacements
$content = Get-Content file.php
$content = $content -replace 'pattern1', 'replacement1'
$content = $content -replace 'pattern2', 'replacement2'
$content | Set-Content file.php

# Case-sensitive replacement
(Get-Content file.php) -creplace 'OldText', 'NewText' | Set-Content file.php
```

### Examples - What FAILS vs What WORKS:

**❌ FAILS (sed via SSH):**
```powershell
# NEVER DO THIS - Always fails with quote escaping errors
ssh prod-vps "sed -i 's/#experiences/experiences_list.php/g' file.php"
ssh prod-vps 'sed -i "s/#experiences/experiences_list.php/g" file.php'
ssh prod-vps "sed -i \"s|#experiences|experiences_list.php|g\" file.php"
```

**✅ WORKS (Download-Edit-Upload):**
```powershell
# This ALWAYS works
scp prod-vps:/var/www/html/ainitravel.com/public_booking.php ./temp.php
(Get-Content temp.php) -replace 'href="#experiences"', 'href="experiences_list.php"' | Set-Content temp.php
scp temp.php prod-vps:/var/www/html/ainitravel.com/public_booking.php
rm temp.php
```

### Key Lessons Learned:

- User has repeatedly pointed out this recurring sed/SSH failure
- Download-Edit-Upload is slower but 100% reliable
- PowerShell has excellent text replacement with `-replace` operator
- Never waste time trying to escape quotes in SSH commands
- This pattern saved for future: **ALWAYS download first, NEVER sed via SSH from PowerShell**


## MVC Architecture Pattern (Model-View-Controller)

Organize code into three distinct layers for maintainability and separation of concerns. MODEL: Business logic classes (ExperienceManager.php) with database operations using PDO prepared statements. VIEW: Display files (*_register.php, *_list.php) containing HTML/CSS/JS for user interface. CONTROLLER: Request handlers (*_submit.php, *_book.php) that validate input, call Model methods, return responses. Each layer has single responsibility. Models are reusable across multiple controllers. Views never contain direct database queries.

### Examples

- Model: classes/ExperienceManager.php - createExperience(), search(), updateStatus() methods
- View: experience_register.php - Multi-step form HTML with JavaScript validation
- Controller: experience_submit.php - Processes POST, validates CSRF, calls ExperienceManager::createExperience(), returns JSON
- experience_detail.php (View) → experience_book.php (Controller) → ExperienceManager::recordBooking() (Model)


## SOLID Principles in Practice

**Single Responsibility**: Each class/file has ONE job. ExperienceManager handles database operations only. experience_submit.php handles request processing only. experience_register.php handles display only. **Open/Closed**: Easy to add new categories/types without modifying existing code (ENUM extensibility). **Liskov Substitution**: ExperienceManager constructor accepts any PDO instance (swappable dependencies). **Interface Segregation**: Methods are specific (createExperience vs recordBooking), not monolithic. **Dependency Inversion**: Controllers depend on ExperienceManager interface, not direct SQL.

### Examples

- Single Responsibility: ExperienceManager.php does ONLY database operations, no HTML rendering, no file uploads
- Dependency Injection: new ExperienceManager($pdo) - PDO injected, testable with mock database
- Open/Closed: Adding 'diving' category only requires ALTER TABLE, no code changes
- Interface methods: search($filters), getById($id), updateStatus($id, $status) - each focused task


## DRY (Don't Repeat Yourself) via Reusable Components

Eliminate code duplication by extracting common functionality. Reuse aini_partner_businesses table for all partner types (hotels, tours, experiences) instead of creating separate tables. Single ExperienceManager class methods used by multiple controllers (register, moderate, display). Helper functions like uploadPhoto(), generateSlug() prevent copy-paste. Database triggers (when enabled) auto-update avg_rating instead of manual updates in every review operation.

### Examples

- aini_partner_businesses business_type ENUM supports: 'hotel', 'tour_operator', 'experience', 'restaurant' - one table, many uses
- uploadPhoto($file, $uploadDir, $prefix) function used for cover photo AND gallery photos
- generateSlug($title) creates unique URL-friendly slugs with auto-increment if duplicate
- ExperienceManager::search() used by public listing, admin moderation, partner dashboard


## Security-First Development Pattern

Validate and sanitize ALL user input. CSRF tokens on forms. PDO prepared statements prevent SQL injection. htmlspecialchars() on output prevents XSS. File upload validation: check MIME type (finfo_file), size limits, whitelist extensions, rename files (uniqid() to prevent overwrites). Never trust $_POST/$_GET directly. Use filter_input(INPUT_POST, 'field', FILTER_SANITIZE_STRING). Session validation on protected pages. Error messages generic to users, detailed to logs.

### Examples

- CSRF: $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); validate in controller
- SQL Safe: $stmt->prepare("SELECT * FROM users WHERE id = :id"); $stmt->execute(['id' => $id]);
- XSS Safe: echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
- File Upload: finfo_file() checks MIME, move_uploaded_file() to safe directory, uniqid() prefix prevents collisions
- Input Sanitization: filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);


## Multi-Step Form UX Pattern

Complex forms broken into 4 digestible steps with visual progress indicator. Step indicator shows: completed (✓ green), active (highlighted blue), upcoming (gray). Client-side validation before advancing. Each step validates required fields with .is-invalid class on error. Navigation buttons: Previous (hidden on step 1), Next (hidden on final step), Submit (only on step 4). Map/complex widgets lazy-load when step becomes active to improve initial page load. Form data persists across steps using single <form> element.

### Examples

- experience_register.php: Step 1 (Partner Info), Step 2 (Details), Step 3 (Pricing), Step 4 (Photos/Location)
- Step indicator: <div class="step completed" data-step="1"><div class="step-number">✓</div></div>
- Validation: validateStep(currentStep) checks all [required] inputs before nextStep()
- Lazy load: if (currentStep === 4 && !map) { setTimeout(initMap, 100); } - Map initialized only when needed
- JavaScript: .form-step.active { display: block; } all others display: none


## JSON Field Pattern for Schema Flexibility

Use JSON columns for array/object data that doesn't need relational queries. Allows schema evolution without ALTER TABLE migrations. Store tags as JSON array ["hiking", "photography"], photo_gallery as URL array, available_days as day names. Encode in PHP with json_encode($array), decode with json_decode($json, true). Add JSON indexes for searchability if needed. Never store complex relational data (users, bookings) in JSON - only supplementary metadata.

### Examples

- aini_experiences.tags JSON COMMENT '["hiking", "mountains", "photography"]'
- PHP insert: $stmt->execute(['tags' => json_encode(['hiking', 'mountains'])]);
- PHP retrieve: $experience = $stmt->fetch(); $tags = json_decode($experience['tags'], true);
- photo_gallery JSON: ["/uploads/exp1.jpg", "/uploads/exp2.jpg", "/uploads/exp3.jpg"]
- MySQL 5.7+ supports: SELECT * FROM experiences WHERE JSON_CONTAINS(tags, '["hiking"]')


## File Upload Organization Pattern

Store uploaded files in organized directory structure: /uploads/{module}/{timestamp}_{uniqueid}.{ext}. Save relative paths in database (/uploads/experiences/cover_673a5b2f.jpg), not absolute paths. Create directories with mkdir($dir, 0755, true) recursive flag. Validate MIME type with finfo_file() not extension alone (users can rename .exe to .jpg). Generate unique filenames: uniqid($prefix, true) + sanitized original extension. Set proper permissions: 755 for dirs, 644 for files. Never serve uploads from document root without validation (separate domain ideal).

### Examples

- Upload structure: /var/www/html/ainitravel.com/uploads/experiences/cover_673a5b2f12345.jpg
- Database stores: /uploads/experiences/cover_673a5b2f12345.jpg (relative path)
- Filename: uniqid('cover_', true) . '.' . $extension = cover_673a5b2f12345.abc123.jpg
- MIME check: $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $tmpFile);
- Directory creation: mkdir(__DIR__ . '/uploads/experiences/', 0755, true);


## Database Migration Pattern with Safe Rollback

Create standalone .sql migration files (create_experience_tables.sql) with: USE database; CREATE TABLE IF NOT EXISTS; INSERT sample data; SELECT verification queries. Comment triggers/procedures that require SUPER privilege - implement equivalent logic in application code instead. Test locally first, upload to /tmp on production, execute via mysql command. Backup database before running. Migrations are additive (CREATE new tables) never destructive (DROP existing tables). Version control all migration files.

### Examples

- Migration file: create_experience_tables.sql with CREATE TABLE IF NOT EXISTS aini_experiences, aini_experience_bookings, aini_experience_reviews
- Comment out triggers: -- DELIMITER // -- CREATE TRIGGER ... (moved logic to ExperienceManager::updateAverageRating())
- Deploy: scp migration.sql prod-vps:/tmp/ && ssh prod-vps "mysql -u user -p db < /tmp/migration.sql"
- Verification: SELECT COUNT(*) FROM aini_experiences; SHOW TABLES LIKE 'aini_exp%';
- Rollback ready: Always CREATE not ALTER existing tables, can DROP new tables if needed


## Foreign Key CASCADE Pattern

Use ON DELETE CASCADE for child records that lose meaning without parent. Use ON DELETE SET NULL for references that should remain (e.g., reviews after booking deleted). Examples: experience → partner (CASCADE - no orphan experiences), booking → experience (CASCADE - no bookings for deleted experiences), booking → user (CASCADE or SET NULL depending on data retention policy). Always define foreign keys in CREATE TABLE with proper indexes for performance. Prevents orphaned records and maintains referential integrity automatically.

### Examples

- FOREIGN KEY (partner_id) REFERENCES aini_partner_businesses(id) ON DELETE CASCADE - delete experience if partner deleted
- FOREIGN KEY (experience_id) REFERENCES aini_experiences(id) ON DELETE CASCADE - delete bookings if experience deleted
- FOREIGN KEY (booking_id) REFERENCES aini_experience_bookings(id) ON DELETE SET NULL - keep review if booking deleted
- INDEX idx_partner (partner_id) - required for FK performance
- Automatic cleanup: DELETE FROM aini_partner_businesses WHERE id = 5; -- also deletes related experiences


## Geospatial Data Pattern (Latitude/Longitude)

Store coordinates as DECIMAL(10,8) for latitude, DECIMAL(11,8) for longitude (precision ~1mm). Use Leaflet.js (open-source) for interactive maps, not Google Maps API (requires billing). Meeting point stored as TEXT description + lat/lng for exact location. Map click event captures coordinates: map.on('click', function(e) { lat = e.latlng.lat; lng = e.latlng.lng; }). Distance calculations use Haversine formula. Future enhancement: SPATIAL index with MySQL/PostGIS for radius searches.

### Examples

- Schema: latitude DECIMAL(10, 8), longitude DECIMAL(11, 8) - covers -180 to +180 degrees
- Leaflet map init: L.map('map').setView([-13.5319, -71.9675], 13); // Cusco, Peru
- Click handler: map.on('click', function(e) { document.getElementById('latitude').value = e.latlng.lat.toFixed(8); });
- Database query: SELECT *, (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(latitude)))) AS distance FROM experiences HAVING distance < 50;


## FULLTEXT Search Index Pattern

Add FULLTEXT index on text columns for natural language search: FULLTEXT idx_search (title, description). Query with MATCH...AGAINST: WHERE MATCH(title, description) AGAINST(:query IN NATURAL LANGUAGE MODE). Supports relevance ranking (results sorted by match quality). Requires MyISAM or InnoDB with MySQL 5.6+. Minimum word length: 4 characters by default (configure ft_min_word_len). Combine with traditional filters: WHERE MATCH(...) AND category = 'adventure' AND price_usd <= 200.

### Examples

- Index: FULLTEXT idx_search (title, description) in aini_experiences table
- Query: SELECT * FROM experiences WHERE MATCH(title, description) AGAINST('machu picchu sunrise trek' IN NATURAL LANGUAGE MODE);
- With filters: WHERE MATCH(title, description) AGAINST(:search) AND category = :category AND price_usd BETWEEN :min AND :max;
- Relevance: ORDER BY MATCH(title, description) AGAINST(:search) DESC;


## Price Conversion Auto-Calculator Pattern

When user enters price in base currency (USD), JavaScript calculates equivalent in alternative currencies (AiNi Rewards, AiNi Crypto) in real-time. 1 USD = 100 AiNi Rewards (stable 1:100 ratio). AiNi Crypto fetched from asset_prices table (volatile ~1:1 but fluctuates). Display conversion in visual boxes with currency symbols. Store all three prices in database for historical record (denormalization - prices at time of creation). Hidden form inputs auto-populate for submission.

### Examples

- JavaScript: document.getElementById('priceUSD').addEventListener('input', function() { const rewards = parseFloat(this.value) * 100; document.getElementById('priceRewards').textContent = rewards + ' 🪙'; });
- Database: price_usd DECIMAL(10,2), price_aini_rewards INT, price_aini_crypto DECIMAL(10,2)
- Conversion: 1 USD = 100 Rewards stable, 1 USD ≈ 1.028 Crypto (fluctuates with market simulation)
- Hidden inputs: <input type="hidden" name="price_aini_rewards" value="${rewards}">


## Admin Moderation Workflow Pattern

User-generated content starts with status='pending_review', hidden from public. Admin panel lists pending items with preview functionality. Admin actions: Approve (status='approved', publicly visible), Reject (status='rejected' + rejection_reason), Flag (status='inactive'). Track moderator: reviewed_by (admin_id), reviewed_at (timestamp). Send email notifications to submitter on approval/rejection. Public search/listing queries always filter: WHERE status = 'approved'. Admin queries include all statuses with filter options.

### Examples

- Submission: status='pending_review' (default on creation)
- Public query: SELECT * FROM experiences WHERE status = 'approved' AND category = :category;
- Admin query: SELECT * FROM experiences WHERE status IN ('pending_review', 'approved', 'rejected') ORDER BY created_at DESC;
- Approve: UPDATE experiences SET status='approved', reviewed_by=:admin_id, reviewed_at=NOW() WHERE id=:id;
- Notify: sendEmail($partner['email'], 'Experience Approved', "Your experience '{$title}' is now live!");


## Error Logging vs User Messages Pattern

Log detailed errors to file (error_log()) with full context: stack traces, variable values, SQL queries. Display generic user-friendly messages to avoid exposing system internals: "An error occurred. Please try again." vs "PDOException: Connection failed on line 42". Set error_reporting(E_ALL) and display_errors=0 in production. Log to dedicated files: /logs/experience_submit_errors.log. Include timestamp, user_id, request data in logs for debugging. Never show database errors, file paths, or internal logic to end users.

### Examples

- Production config: ini_set('display_errors', 0); ini_set('log_errors', 1); ini_set('error_log', '/logs/errors.log');
- Detailed log: error_log("Experience Submit Error [User: {$userId}]: " . $e->getMessage() . "\n" . $e->getTraceAsString());
- User message: throw new Exception("Unable to process your request. Please contact support.");
- Try-catch: catch (PDOException $e) { error_log($e); return json(['error' => 'Database error']); }
- Log file rotation: Use logrotate on Linux to prevent logs growing unbounded
