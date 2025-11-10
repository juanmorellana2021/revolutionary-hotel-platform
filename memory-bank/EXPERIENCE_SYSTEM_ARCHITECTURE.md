# Experience Registration System - Technical Architecture

**Created**: November 10, 2025  
**Status**: In Development  
**Priority**: High

---

## 🎯 BUSINESS REQUIREMENTS

Allow travel agents, tour guides, and experience providers to register and publish their tours/adventures on AiNi Travel platform. Users can book experiences and pay with AiNi Coins (Rewards or Crypto).

---

## 📊 EXISTING SYSTEM ANALYSIS

### ✅ ALREADY EXISTS:
- `aini_partner_businesses` table with `business_type='experience'`
- `aini_partner_transactions` (payment processing)
- `aini_partner_settlements` (partner payouts)
- `AiniCoinSystem.php` class (coin transaction logic)
- `ainitravel_users` table (user accounts)
- `public_booking.php` (3,545 lines - hotel booking system)

### ❌ NEEDS TO BE BUILT:
- Public interface for experience registration (`experience_register.php`)
- Detailed experience data table (`aini_experiences`)
- Booking system for experiences (`aini_experience_bookings`)
- Admin moderation panel (`admin/experience_moderate.php`)
- Public listing page (integrate into `public_booking.php` or new `experiences_list.php`)

---

## 🏗️ DATABASE SCHEMA (New Tables)

### Table 1: `aini_experiences`
Stores detailed information about each tour/experience offering.

```sql
CREATE TABLE aini_experiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL, -- FK to aini_partner_businesses
    
    -- Basic Info
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE COMMENT 'URL-friendly: machu-picchu-sunrise-tour',
    description TEXT,
    short_description VARCHAR(500),
    
    -- Pricing (Multi-Currency Support)
    price_usd DECIMAL(10,2) NOT NULL,
    price_aini_rewards INT COMMENT 'Price in stable AiNi Rewards coins',
    price_aini_crypto DECIMAL(10,2) COMMENT 'Price in AiNi Crypto',
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- Logistics
    duration_hours INT,
    duration_days INT DEFAULT 1,
    max_participants INT DEFAULT 10,
    min_participants INT DEFAULT 1,
    difficulty_level ENUM('easy', 'moderate', 'hard', 'expert'),
    
    -- Classification
    category ENUM('adventure', 'cultural', 'food', 'nature', 'wellness', 'water_sports', 'city_tour', 'multi_day'),
    tags JSON COMMENT '["hiking", "mountains", "photography"]',
    
    -- Location (Geospatial)
    country VARCHAR(100),
    city VARCHAR(100),
    meeting_point TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Media (File Paths, NOT BLOBs)
    cover_photo VARCHAR(500),
    photo_gallery JSON COMMENT 'Array of photo URLs',
    video_url VARCHAR(500),
    
    -- Availability
    available_days JSON COMMENT '["monday", "wednesday", "friday"]',
    start_date DATE COMMENT 'Season start',
    end_date DATE COMMENT 'Season end',
    
    -- Status (State Machine)
    status ENUM('draft', 'pending_review', 'approved', 'rejected', 'inactive') DEFAULT 'draft',
    rejection_reason TEXT,
    reviewed_by INT COMMENT 'Admin user ID who reviewed',
    reviewed_at TIMESTAMP NULL,
    
    -- Analytics
    views INT DEFAULT 0,
    bookings_count INT DEFAULT 0,
    avg_rating DECIMAL(3,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (partner_id) REFERENCES aini_partner_businesses(id) ON DELETE CASCADE,
    INDEX idx_status_category (status, category),
    INDEX idx_location (country, city),
    INDEX idx_price (price_usd),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB;
```

**Design Decisions**:
- **DRY**: Reuses `aini_partner_businesses` instead of duplicating partner info
- **Normalization**: Partner data in separate table, experience details here
- **Flexibility**: JSON for tags/photos (schema evolution without migrations)
- **Performance**: Indexes on common queries (status, location, price)
- **SEO**: FULLTEXT index for search functionality

---

### Table 2: `aini_experience_bookings`
Tracks user bookings/reservations for experiences.

```sql
CREATE TABLE aini_experience_bookings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    experience_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Booking Details
    booking_date DATE NOT NULL COMMENT 'Date of the experience',
    participants INT DEFAULT 1,
    
    -- Pricing Snapshot (Denormalization for historical accuracy)
    total_price_usd DECIMAL(10,2),
    paid_in_rewards INT DEFAULT 0 COMMENT 'Amount paid in AiNi Rewards',
    paid_in_crypto DECIMAL(10,2) DEFAULT 0 COMMENT 'Amount paid in AiNi Crypto',
    
    -- Status Tracking
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    
    -- Payment Link
    transaction_id BIGINT COMMENT 'FK to aini_coin_transactions',
    
    -- Special Requests
    customer_notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (experience_id) REFERENCES aini_experiences(id),
    FOREIGN KEY (user_id) REFERENCES ainitravel_users(id),
    INDEX idx_user_bookings (user_id, status),
    INDEX idx_experience_date (experience_id, booking_date)
) ENGINE=InnoDB;
```

**Design Decisions**:
- **Denormalization**: Stores price at booking time (historical record even if experience price changes)
- **Dual-Coin Support**: Separate fields for Rewards vs Crypto payments
- **Audit Trail**: Links to `aini_coin_transactions` for blockchain-style verification

---

## 🔧 FILE ARCHITECTURE (MVC Pattern)

```
/var/www/html/ainitravel.com/
├── experience_register.php       # PUBLIC - Partner registration form
├── experience_submit.php          # CONTROLLER - Processes registration POST
├── experiences_list.php           # PUBLIC - Browse approved experiences
├── experience_detail.php?id=123   # PUBLIC - Single experience details + booking
├── experience_book.php            # CONTROLLER - Processes booking POST
├── classes/
│   └── ExperienceManager.php      # MODEL - Business logic (CRUD operations)
└── admin/
    └── experience_moderate.php    # ADMIN - Approve/reject submissions
```

### Responsibilities:

**experience_register.php** (VIEW)
- Multi-step form (Partner Info → Experience Details → Photos → Preview)
- Client-side validation (required fields, price formats)
- Map integration for selecting meeting point coordinates

**experience_submit.php** (CONTROLLER)
- Validates POST data (server-side)
- Sanitizes inputs
- Calls `ExperienceManager::create()`
- Returns JSON response or redirects

**ExperienceManager.php** (MODEL)
- `create($partnerData, $experienceData)` - Insert new experience
- `getById($id)` - Fetch experience details
- `search($filters)` - Search with category/location/price filters
- `updateStatus($id, $status, $reason)` - Admin moderation
- `recordBooking($experienceId, $userId, $bookingData)` - Create booking

**experiences_list.php** (VIEW)
- Grid/list view of approved experiences
- Filters: category, location, price range, date
- Pagination
- Integration with existing `public_booking.php` navigation

**experience_detail.php** (VIEW)
- Photo gallery
- Detailed description
- Pricing (USD + AiNi Coins)
- Availability calendar
- Book Now button → triggers booking flow

**experience_book.php** (CONTROLLER)
- Validates booking (date, participants, availability)
- Calculates pricing (coin conversion if needed)
- Calls `AiniCoinSystem::recordTransaction()`
- Creates booking record
- Sends confirmation email

**admin/experience_moderate.php** (VIEW - ADMIN ONLY)
- List of pending_review experiences
- Preview experience details
- Approve/Reject buttons with reason field
- Bulk actions

---

## 📈 DATA FLOW (Sequence Diagram)

### Registration Flow:
```
User (Travel Agent) → experience_register.php (Form)
          ↓
experience_submit.php (Validation)
          ↓
ExperienceManager::create()
          ↓
┌─────────────────────────────────┐
│ 1. INSERT aini_partner_businesses│ (if new partner)
│ 2. INSERT aini_experiences       │ (status='pending_review')
└─────────────────────────────────┘
          ↓
Email notification → admin@ainitravel.com
          ↓
Admin → experience_moderate.php
          ↓
UPDATE status='approved'
          ↓
experiences_list.php (Now visible to public)
```

### Booking Flow:
```
User → experience_detail.php (View + Book Button)
          ↓
experience_book.php (Process)
          ↓
ExperienceManager::recordBooking()
          ↓
┌─────────────────────────────────┐
│ 1. Check availability            │
│ 2. Calculate total_price         │
│ 3. AiniCoinSystem::recordTransaction()│
│ 4. INSERT aini_experience_bookings│
│ 5. UPDATE experience bookings_count│
└─────────────────────────────────┘
          ↓
Confirmation email → User + Partner
```

---

## ✅ ENGINEERING PRINCIPLES APPLIED

### 1. **DRY (Don't Repeat Yourself)**
- Reuse `aini_partner_businesses` table (no duplication)
- Single `ExperienceManager` class for all experience logic
- Shared navigation header/footer across all pages

### 2. **SOLID Principles**
- **Single Responsibility**: Each file/class has one job
  - `ExperienceManager` = Database operations
  - `experience_submit.php` = Form processing
  - `experience_register.php` = Display form
- **Open/Closed**: Easy to add new categories/types without modifying existing code
- **Dependency Inversion**: Controllers depend on `ExperienceManager` interface, not direct SQL

### 3. **Separation of Concerns**
- **Database Layer**: SQL schemas, indexes, constraints
- **Model Layer**: `ExperienceManager.php` (business logic)
- **Controller Layer**: `*_submit.php`, `*_book.php` (request handling)
- **View Layer**: `*.php` display files (HTML/CSS/JS)

### 4. **Security Best Practices**
- **SQL Injection**: All queries use PDO prepared statements
- **XSS**: Escape all output with `htmlspecialchars()`
- **CSRF**: Token validation on all forms
- **Authentication**: Admin pages check `$_SESSION['is_admin']`
- **File Upload**: Whitelist image types, validate size, rename files
- **Input Validation**: Server-side validation for all user input

### 5. **Performance Optimization**
- **Database Indexes**: On commonly queried fields (status, category, location)
- **FULLTEXT Search**: For title/description searches
- **JSON Storage**: Flexible schema for tags/photos without ALTER TABLE
- **Lazy Loading**: Photos load on scroll (images_list.php)
- **Caching**: Future-ready for Redis/Memcached (search results)

### 6. **Scalability**
- **Geospatial Ready**: Latitude/longitude for future map-based search
- **Pagination**: List views paginated (20 per page)
- **Foreign Keys**: CASCADE on delete maintains referential integrity
- **Normalized Data**: Partner info separate from experience details

### 7. **Maintainability**
- **Clear Naming**: `experience_register.php` (obvious purpose)
- **Comments**: Inline documentation for complex logic
- **Error Handling**: Try-catch blocks with user-friendly messages
- **Logging**: Admin actions logged for audit trail

---

## 🔐 SECURITY CONSIDERATIONS

### Access Control:
- **Public**: Can view approved experiences, register as partner
- **Logged-in Users**: Can book experiences
- **Partners**: Can edit own experiences (future feature)
- **Admins**: Can moderate all submissions

### Data Validation:
```php
// Example validation in experience_submit.php
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
if (strlen($title) < 10 || strlen($title) > 255) {
    throw new Exception("Title must be 10-255 characters");
}

$price = filter_input(INPUT_POST, 'price_usd', FILTER_VALIDATE_FLOAT);
if ($price < 0 || $price > 10000) {
    throw new Exception("Invalid price range");
}
```

### File Upload Security:
```php
// Photo upload validation
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    throw new Exception("Invalid file type");
}
// Rename: exp_123_cover_abc123.jpg (prevents overwrite)
```

---

## 📱 FUTURE ENHANCEMENTS

1. **Mobile App API**: RESTful endpoints for iOS/Android apps
2. **Review System**: User ratings/reviews for experiences
3. **Availability Calendar**: Real-time booking slots
4. **Multi-language**: i18n support (ES, EN, PT, FR)
5. **Partner Analytics**: Dashboard with booking trends
6. **WhatsApp Integration**: Booking confirmations via Twilio
7. **AI Recommendations**: Suggest experiences based on user history

---

## 🚀 DEPLOYMENT PLAN

### Phase 1: Database Setup (This session)
1. Create SQL migration file
2. Execute on prod-vps
3. Verify tables created
4. Grant permissions to hoteluser

### Phase 2: Core Files (This session)
1. Create `ExperienceManager.php` class
2. Create `experience_register.php` form
3. Create `experience_submit.php` controller
4. Test registration flow

### Phase 3: Admin Panel (Next session)
1. Create `admin/experience_moderate.php`
2. Test approval workflow
3. Email notifications

### Phase 4: Public Listing (Next session)
1. Create `experiences_list.php`
2. Create `experience_detail.php`
3. Integrate with `public_booking.php` navigation

### Phase 5: Booking System (Future)
1. Create `experience_book.php`
2. Integrate with `AiniCoinSystem`
3. Email confirmations
4. Partner notifications

---

## 📊 SUCCESS METRICS

- **Partner Acquisition**: 10+ experience providers in first month
- **User Engagement**: 50+ experience bookings in first month
- **Revenue**: $1,000 USD equivalent in AiNi Coin transactions
- **Quality**: 90%+ approval rate for submissions
- **Performance**: Page load < 2 seconds
- **Security**: Zero SQL injection incidents

---

**Status**: Architecture approved ✅  
**Next Step**: Begin implementation (database schema + core files)
