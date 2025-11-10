# AiniFlow Database Configuration - CRITICAL INFO

## Database Setup (VERIFIED ✅)
- **Type**: PostgreSQL 16
- **Database Name**: `aini_platform`
- **User**: `postgres`
- **Host**: `localhost`
- **Port**: `5432`
- **Password**: (empty/no password - uses peer/trust authentication)
- **Connection**: Use `sudo -u postgres` for psql commands

## ⚠️ CRITICAL: Users Table Uses PHONE as Primary Key
**NOT user_id! The entire system is phone-based!**

```sql
users table structure:
- phone (VARCHAR(20)) - PRIMARY KEY ⭐
- name (VARCHAR(255))
- profile_photo (TEXT)
- wallet_balance (NUMERIC(10,2))
- is_online (BOOLEAN)
- last_seen (TIMESTAMP)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## Existing Users in Database (Nov 10, 2025):
```
+1234567890     | Test User
+0987654321     | Hotel Manager  
+51997946667    | Juan
+51 997 946 667 | Juan (duplicate)
+14437965990    | Grant Kitchen
```

## Social Tables Created ✅ (Nov 10, 2025)
Successfully created with `social_database_postgres.sql`:

1. **traveler_profiles**
   - Primary key: `phone` (VARCHAR(20), references users.phone)
   - Fields: bio, interests, travel_style, languages, countries_visited, gender, level, aini_coins_balance
   - Sample data: 4 profiles inserted for existing users

2. **profile_swipes**
   - Tracks all left/right swipes
   - Fields: swiper_phone, swiped_phone, swipe_type, direction
   - Constraint: `UNIQUE (swiper_phone, swiped_phone, swipe_type)`
   - Purpose: Prevents showing same profile twice

3. **matches**
   - Stores mutual right swipes (both users liked each other)
   - Fields: phone1, phone2, match_type, match_score, conversation_started
   - Triggers "It's a Match!" popup in UI

4. **friend_requests**
   - Stores pending/accepted/rejected connection requests
   - Fields: sender_phone, receiver_phone, status, message, created_at, responded_at
   - Privacy feature: Rejected requests don't notify sender

## Database Access Commands
```bash
# SSH to server
ssh social-vps

# Connect to database (requires sudo)
sudo -u postgres psql -d aini_platform

# Common psql commands
\dt                 # List all tables
\d users            # Describe users table
\d traveler_profiles # Describe traveler_profiles
\q                  # Quit psql

# Query examples
sudo -u postgres psql -d aini_platform -c "SELECT u.phone, u.name, tp.bio FROM users u LEFT JOIN traveler_profiles tp ON u.phone = tp.phone"

# Run SQL file
sudo -u postgres psql -d aini_platform -f /tmp/yourfile.sql
```

## Backend Setup

### Node.js Server (index.js)
- **Status**: Running (PID varies)
- **Location**: `/var/www/aini-platform/index.js`
- **Database Config**: `/var/www/aini-platform/.env`
- **Environment Variables**:
  ```
  DB_USER=postgres
  DB_HOST=localhost
  DB_NAME=aini_platform
  DB_PASSWORD=
  DB_PORT=5432
  ```

### PHP Status ⚠️ ISSUE FOUND
- **Problem**: PHP files showing source code instead of executing
- **Likely Cause**: PHP-FPM not configured or not running
- **Impact**: `social_api.php` not working yet
- **Files Deployed**:
  - ✅ `/var/www/aini-platform/public/social.html` (frontend - working)
  - ✅ `/var/www/aini-platform/public/social_api.php` (backend - NOT executing PHP)
  - ✅ `/tmp/social_database_postgres.sql` (deployed and run successfully)

## API Implementation

### social_api_postgres.php (Deployed)
Updated to use PostgreSQL and phone-based authentication:

**Key Changes from MySQL version:**
- Uses `phone` instead of `user_id` everywhere
- PostgreSQL syntax: `ON CONFLICT` instead of `ON DUPLICATE KEY UPDATE`
- Session: `$_SESSION['user']['phone']` instead of `$_SESSION['user']['id']`
- PDO connection: `pgsql:host=localhost;dbname=aini_platform`

**Endpoints:**
1. `GET ?action=get_cards&limit=20`
   - Returns travelers user hasn't swiped on
   - Filters out already-swiped profiles
   - Requires traveler_profiles.bio IS NOT NULL

2. `POST ?action=swipe`
   - Params: swiped_id (phone), direction (left/right), type (traveler)
   - Saves to profile_swipes table
   - Checks for mutual match
   - Awards 50 coins if match

3. `POST ?action=send_friend_request`
   - Params: receiver_id (phone), message
   - Saves to friend_requests table
   - Prevents duplicates

## Files in Local Repository
```
c:\xampp\htdocs\testapp\
├── ainiflow_discover_clean.html (frontend - 300 lines)
├── social_api_postgres.php (backend - phone-based)
├── social_database_postgres.sql (PostgreSQL schema)
├── social_database.sql (old MySQL version - deprecated)
└── memory-bank/
    ├── ainiflow_database_config.md (this file)
    └── ainiflow_discover_integration.md (feature overview)
```

## Current Status Summary

### ✅ WORKING:
- PostgreSQL database connected
- Social tables created (traveler_profiles, profile_swipes, matches, friend_requests)
- 4 sample profiles inserted
- Frontend deployed at https://ainiflow.com/social.html
- Authentication check in frontend (redirects to login if not logged in)
- Swipe animations working

### ⚠️ NEEDS FIXING:
- **PHP not executing** - Need to:
  1. Install/configure PHP-FPM
  2. Configure nginx to process .php files
  3. Test social_api.php endpoint

### 📋 TODO:
- Fix PHP execution on server
- Test API endpoints with curl/Postman
- Verify login creates `$_SESSION['user']['phone']`
- Add more sample profiles (currently only 4)
- Test full flow: Login → Discover → Swipe → Match

## Testing Checklist
```bash
# 1. Verify tables exist
ssh social-vps "sudo -u postgres psql -d aini_platform -c '\dt'"

# 2. Check sample data
ssh social-vps "sudo -u postgres psql -d aini_platform -c 'SELECT * FROM traveler_profiles'"

# 3. Test API (once PHP working)
curl https://ainiflow.com/social_api.php?action=get_cards&limit=3

# 4. Test swipe
curl -X POST https://ainiflow.com/social_api.php?action=swipe \
  -d "swiped_id=+1234567890&direction=right&type=traveler"
```

## Quick Reference: Phone vs ID
```
OLD (MySQL):           NEW (PostgreSQL):
user_id               → phone
$_SESSION['user']['id'] → $_SESSION['user']['phone']
users.id              → users.phone
swiper_id             → swiper_phone
swiped_id             → swiped_phone
INT                   → VARCHAR(20)
AUTO_INCREMENT        → SERIAL
ENUM                  → VARCHAR
ON DUPLICATE KEY      → ON CONFLICT
```

## Last Updated
November 10, 2025 - Database tables created, API deployed (PHP execution pending fix)

