# Channel Manager API Research
## Third-Party Integration Options for AiniTravel PMS

**Research Date:** November 1, 2025  
**Purpose:** Identify channel manager APIs to connect AiniTravel PMS with OTAs (Online Travel Agencies)

---

## Overview
A Channel Manager allows hotels to manage inventory, rates, and availability across multiple booking platforms (OTAs) from a single interface. Key OTAs include:
- Booking.com
- Expedia
- Airbnb
- Hotels.com
- Agoda
- TripAdvisor
- Vrbo

---

## Top Channel Manager Options

### 1. **Channels.app** (Recommended for Small-Medium Hotels)
- **Website:** https://www.channels.app
- **Type:** Cloud-based channel manager
- **Pricing:** Starting at ~$10-20/month per property
- **Key Features:**
  - Direct API connections to 100+ OTAs
  - Real-time 2-way synchronization
  - Rate parity management
  - Booking import automation
  - Mobile app available
  - RESTful API for PMS integration
- **API Documentation:** Available after signup
- **Pros:**
  - Affordable for small properties
  - Easy setup (can be done in hours)
  - Good support for independent hotels
  - Free trial available
- **Cons:**
  - Limited customization
  - Fewer features than enterprise solutions

---

### 2. **SiteMinder**
- **Website:** https://www.siteminder.com
- **Type:** Enterprise-grade channel manager
- **Pricing:** Custom pricing (typically $50-200+/month depending on property size)
- **Key Features:**
  - 400+ channel connections worldwide
  - Channel Manager + Booking Engine + Website Builder
  - Advanced analytics and reporting
  - Dynamic pricing tools
  - Payment processing integration
  - Comprehensive REST API
- **API Documentation:** https://developers.siteminder.com
- **Pros:**
  - Industry leader with largest OTA network
  - Robust API with webhooks
  - Enterprise-level support
  - Strong presence in Latin America
- **Cons:**
  - Higher cost
  - May be overkill for small properties
  - Requires onboarding process

---

### 3. **Cloudbeds (myallocator)**
- **Website:** https://www.cloudbeds.com/products/myallocator/
- **Type:** All-in-one PMS + Channel Manager
- **Pricing:** Starting at ~$5/room/month
- **Key Features:**
  - Integrated PMS + Channel Manager
  - 300+ OTA connections
  - Revenue management tools
  - Guest communication platform
  - RESTful API for custom integrations
  - Webhook support
- **API Documentation:** https://hotels.cloudbeds.com/api/docs/
- **Pros:**
  - All-in-one solution (could replace custom PMS)
  - Strong API documentation
  - Good for properties scaling up
  - Free migration assistance
- **Cons:**
  - Monthly fees per room
  - May require full PMS migration
  - Some features locked to higher tiers

---

### 4. **Smoobu**
- **Website:** https://www.smoobu.com
- **Type:** Vacation rental focused channel manager
- **Pricing:** €8-30/month per property
- **Key Features:**
  - Strong Airbnb, Booking.com, Vrbo integration
  - Calendar synchronization
  - Automated guest messaging
  - API for custom integrations
  - Multi-property management
- **API Documentation:** https://docs.smoobu.com
- **Pros:**
  - Great for small properties and vacation rentals
  - Affordable pricing
  - Easy to use interface
- **Cons:**
  - Focused on vacation rentals vs hotels
  - Fewer OTA connections than enterprise options

---

### 5. **ChannelRunner (RateGain)**
- **Website:** https://rategain.com/products/channelrunner
- **Type:** Enterprise channel manager
- **Pricing:** Custom (enterprise pricing)
- **Key Features:**
  - 350+ channels globally
  - Real-time rate shopping
  - Advanced rate parity monitoring
  - Enterprise-grade API
  - Multi-property support
- **API Documentation:** Available to partners
- **Pros:**
  - Strong in enterprise market
  - Advanced revenue management
  - Global coverage
- **Cons:**
  - Expensive for small hotels
  - Complex setup process

---

### 6. **Rentals United**
- **Website:** https://rentalsunited.com
- **Type:** Vacation rental channel manager (Property Management Companies focused)
- **Pricing:** 
  - **IMPORTANT:** NO FREE TIER (pricing updated 2025)
  - Pro Plan: 5-99 properties (custom pricing)
  - Business Plan: 100-499 properties (custom pricing)
  - Enterprise: 500+ properties (custom pricing)
  - Must contact sales for exact pricing
- **Key Features:**
  - 90+ channels (Airbnb, Booking.com, Vrbo, etc.)
  - RESTful API + Webhooks
  - **Multi-property management** (designed for PMCs)
  - 2-way synchronization
  - Guest communication tools
  - Analytics & reporting
  - White-label option available
- **API Documentation:** https://developer.rentalsunited.com
- **Multi-Tenant Support:** ✅ YES - Designed for Property Management Companies
  - Can manage properties for multiple owners
  - Each property can have separate settings
  - Per-property channel connections
  - Owner portal available
- **Pros:**
  - **Built for multi-property agencies** (your exact use case!)
  - Strong API documentation
  - 240K properties connected worldwide
  - 15+ years experience
  - White-label option for PMS partners
  - Good for vacation rentals AND hotels
- **Cons:**
  - NO free tier anymore (pricing changed)
  - Must have minimum 5 properties to start
  - Custom pricing (not transparent)
  - Sales process required

---

## ⚠️ IMPORTANT: Multi-Tenant Considerations for AiniTravel

### Your Business Model:
You have a **multi-tenant SaaS PMS** where:
- Multiple hotel owners use your system
- Each owner manages their own property/properties
- Each property needs separate channel manager connections
- Owners should only see/manage their own bookings

### Channel Manager Requirements:
✅ **Must Support:**
1. Multiple properties under ONE API account
2. Separate credentials per property for OTAs
3. Property-level isolation (Owner A can't see Owner B's data)
4. Scalable pricing (pay per property, not flat fee)
5. API allows property-specific operations

### Analysis of Options:

#### ✅ **Rentals United - BEST FIT**
- **Multi-tenant:** YES ✅
- **How it works:**
  - You get ONE agency/PMS account
  - Each hotel owner's property is added as separate property
  - API supports property-level operations
  - Each property has unique PropertyID
  - Owner portal allows owners to see their properties
- **API Structure:**
  ```xml
  <Push_PutProperty_RQ>
    <Authentication>
      <UserName>your_api_user</UserName>
      <Password>your_api_password</Password>
    </Authentication>
    <Property PropertyID="12345">
      <!-- Each of your clients gets unique PropertyID -->
    </Property>
  </Push_PutProperty_RQ>
  ```
- **Pricing Model:** Per property (scales with your growth)
- **White-label:** Available (can brand as AiniTravel Channel Manager)
- **Verdict:** ⭐ IDEAL for your multi-tenant model

#### ✅ **SiteMinder - ALSO GOOD**
- **Multi-tenant:** YES ✅
- **How it works:**
  - Agency/Partner account model
  - Each property gets unique PropertyID
  - API supports multi-property operations
  - Partner portal for managing all properties
- **Pricing Model:** Per property (can be expensive)
- **White-label:** Available for partners
- **Verdict:** ⭐ Good but pricier than Rentals United

#### ⚠️ **Cloudbeds - PROBLEMATIC**
- **Multi-tenant:** PARTIAL ⚠️
- **Issue:** Cloudbeds is a full PMS, not just channel manager
- **Conflict:** Would compete with AiniTravel PMS
- **Could work:** If you ONLY use their channel manager API
- **Verdict:** ⚠️ Not ideal - they want you to use their PMS

#### ❌ **Channels.app - LIMITED**
- **Multi-tenant:** LIMITED ❌
- **Issue:** Designed for single property owners
- **Workaround:** Each hotel owner needs separate account
- **Problem:** You can't centrally manage all properties
- **Verdict:** ❌ Not suitable for multi-tenant PMS

---

## Recommended Multi-Tenant Architecture

### Option 1: Rentals United (Recommended)

```
┌─────────────────────────────────────┐
│      AiniTravel PMS (Master)        │
│  - Multi-tenant database            │
│  - Multiple hotel owners            │
└───────────┬─────────────────────────┘
            │
            │ Single API Account
            │ (Agency/PMS Partner)
            │
┌───────────▼─────────────────────────┐
│    Rentals United API               │
│  - Property 12345 (Hotel A)         │
│  - Property 12346 (Hotel B)         │
│  - Property 12347 (Hotel C)         │
└───────────┬─────────────────────────┘
            │
            │ Distributes each property
            │ to connected channels
            │
    ┌───────┼────────┬────────┐
    ▼       ▼        ▼        ▼
  Booking Expedia Airbnb   Vrbo
   .com

Each property syncs independently
Owners only see their own bookings
```

### Implementation Steps:

1. **Sign up as PMS Partner with Rentals United**
   - Contact: https://rentalsunited.com/info-for-pms/
   - Mention you're building multi-tenant PMS
   - Ask about white-label partnership

2. **Database Schema:**
```sql
-- Add to your hotels table
ALTER TABLE hotels ADD COLUMN ru_property_id VARCHAR(50);
ALTER TABLE hotels ADD COLUMN ru_active BOOLEAN DEFAULT FALSE;

-- Track sync status
CREATE TABLE channel_sync_log (
    id SERIAL PRIMARY KEY,
    hotel_id INT REFERENCES hotels(id),
    sync_type VARCHAR(50), -- 'availability', 'rate', 'booking'
    direction VARCHAR(10), -- 'push', 'pull'
    status VARCHAR(20), -- 'success', 'failed'
    message TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

3. **API Integration:**
```php
// When hotel owner activates channel manager
function activateChannelManager($hotelId) {
    // 1. Create property in Rentals United
    $propertyId = createRUProperty($hotelId);
    
    // 2. Save PropertyID to database
    $db->query("UPDATE hotels SET ru_property_id = ?, ru_active = TRUE 
                WHERE id = ?", [$propertyId, $hotelId]);
    
    // 3. Push initial inventory
    syncRoomsToRU($hotelId);
    
    // 4. Setup webhook for this property
    registerWebhook($propertyId);
}

// Push availability for specific hotel
function updateAvailability($hotelId, $roomId, $date, $available) {
    $hotel = getHotel($hotelId);
    $ruPropertyId = $hotel['ru_property_id'];
    
    // API call to Rentals United
    $ruApi->pushAvailability($ruPropertyId, [
        'RoomTypeID' => $roomId,
        'Date' => $date,
        'Available' => $available
    ]);
}
```

4. **Webhook Handler:**
```php
// /api/rentals-united/webhook
// Receives bookings from all properties
public function handleRUWebhook() {
    $xml = file_get_contents('php://input');
    $data = parseXML($xml);
    
    $ruPropertyId = $data['PropertyID'];
    
    // Find which hotel this belongs to
    $hotel = $db->query(
        "SELECT * FROM hotels WHERE ru_property_id = ?", 
        [$ruPropertyId]
    )->fetch();
    
    if (!$hotel) {
        return error('Property not found');
    }
    
    // Create booking for correct hotel
    createBooking([
        'hotel_id' => $hotel['id'],
        'guest_name' => $data['GuestName'],
        'check_in' => $data['CheckIn'],
        'check_out' => $data['CheckOut'],
        'source' => 'Booking.com', // from OTA
        'channel_reservation_id' => $data['ReservationID']
    ]);
}
```

5. **Owner Dashboard Integration:**
```php
// In each hotel owner's dashboard
if ($hotel['ru_active']) {
    echo '<div class="channel-manager-status">';
    echo '  <h3>Channel Manager: Active</h3>';
    echo '  <p>Connected to: Booking.com, Expedia, Airbnb</p>';
    echo '  <button onclick="syncNow()">Sync Now</button>';
    echo '  <button onclick="viewChannelBookings()">View OTA Bookings</button>';
    echo '</div>';
}
```

### Pricing Model for Your Clients:

**Option A: Include in Base Price**
- Charge $50-100/month for "Channel Manager Access"
- Your cost from Rentals United: ~$20-40/property
- Your profit: $10-60/property

**Option B: Commission-Based**
- Free channel manager access
- Take 3-5% commission on OTA bookings
- Rentals United handles distribution
- You make money on successful bookings

**Option C: Tiered Pricing**
- Basic: No channel manager
- Pro: Channel manager included ($99/month)
- Enterprise: Channel manager + priority support ($199/month)

---

## Updated Recommendation for AiniTravel

### ⭐ PRIMARY CHOICE: Rentals United
**Why:**
- ✅ Built for multi-property management companies (your exact model)
- ✅ Single API account manages all your clients' properties
- ✅ Property-level isolation (each owner only sees their data)
- ✅ White-label partnership available
- ✅ 90+ channels (covers all major OTAs)
- ✅ Good API documentation for PMS integration
- ✅ Scales with your business (pay per property)

**Action Steps:**
1. Contact Rentals United PMS Partnership team
2. Request demo and pricing for 5-10 properties initially
3. Ask about white-label options
4. Negotiate per-property rate for your platform
5. Build API integration (2-3 weeks development time)

### 🥈 BACKUP CHOICE: SiteMinder
**Why:**
- ✅ Also supports multi-property agencies
- ✅ More OTA connections (400+)
- ✅ Better for traditional hotels vs vacation rentals
- ❌ More expensive
- ❌ Longer onboarding process

Use if: You focus on traditional hotels, need specific Latin American OTAs, or Rentals United pricing too high

---

## Direct Integration Alternative

**If budget is very tight**, you could build direct integrations:

### Phase 1: Booking.com Only
- Apply for Booking.com Connectivity API
- Build XML integration (2-4 weeks)
- Cost: $0 API fees (just Booking.com commission)
- Covers ~40-50% of OTA market

### Phase 2: Add Expedia
- Apply for Expedia Partner Solutions API
- Build REST integration
- Cost: $0 API fees
- Now covers ~70-80% of market

### Phase 3: Add Airbnb (if possible)
- Airbnb API restricted to approved partners
- May need channel manager for Airbnb access

**Direct Integration Pros:**
- No channel manager fees
- Full control over data
- Direct relationship with OTAs

**Direct Integration Cons:**
- 2-3 months development per OTA
- Ongoing maintenance for each API
- Certification/approval process
- No coverage for smaller OTAs
- More technical complexity

---

## Final Verdict

For AiniTravel's multi-tenant PMS with multiple hotel owners:

🏆 **Use Rentals United** 
- Perfect fit for your architecture
- Handles multi-property scenario natively
- White-label potential
- Reasonable pricing
- Fast time-to-market

💰 **Estimated Costs:**
- Rentals United: ~$25-50 per property/month
- If you have 20 hotels: $500-1000/month
- Charge clients $50-100/month: $1000-2000/month revenue
- Net profit: $0-1000/month (scales with more properties)

📅 **Timeline:**
- Week 1: Contact Rentals United, get API access
- Week 2-3: Build API integration
- Week 4: Test with 1-2 properties
- Week 5-6: Roll out to all interested clients
- **Go-live:** 6 weeks from start

---

Instead of using a channel manager, you could integrate directly with major OTAs:

#### **Booking.com Connectivity API**
- **Documentation:** https://connect.booking.com
- **Type:** Direct XML/JSON API
- **Features:**
  - Real-time availability updates
  - Booking notifications
  - Rate management
- **Requirements:**
  - Must be accepted into partner program
  - Property must meet standards
  - Technical integration required
- **Cost:** No API fees, but Booking.com takes commission (15-25%)

#### **Expedia Partner Solutions (EPS)**
- **Documentation:** https://developer.expedia.com
- **Type:** REST API
- **Features:**
  - Property content management
  - Availability & rates
  - Booking retrieval
  - GraphQL API available
- **Requirements:**
  - Partner account required
  - Technical certification
- **Cost:** Commission-based (15-25%)

#### **Airbnb API (Limited)**
- **Status:** Currently no public API for new partners
- **Alternative:** Use channel manager with Airbnb integration
- **Note:** Airbnb restricts API access to approved channel managers

---

---

## 🇵🇪 Peruvian & Latin American Budget-Friendly Options

### **1. Omnitec (Peru/Latin America)**
- **Website:** https://www.omnitec.com.pe
- **Type:** Latin American hotel software provider
- **Focus:** Peru, Colombia, Mexico, Chile
- **Pricing:** More affordable for local market (~$15-30/month estimated)
- **Features:**
  - Local OTA connections (Booking.com, Despegar, Decameron)
  - Channel manager + basic PMS
  - Spanish-language support
  - Local payment gateways (Niubiz, MercadoPago)
- **Status:** Need to contact for API access and multi-property support
- **Pros:**
  - Local pricing (cheaper for Peru)
  - Spanish support
  - Understands local market
- **Cons:**
  - Smaller OTA network
  - Less documentation
  - Unknown API quality

### **2. HotelRunner (Emerging Markets Focus)** ⭐ BEST VALUE
- **Website:** https://www.hotelrunner.com
- **Type:** All-in-one hotel platform (PMS + Channel Manager)
- **Pricing:** Starting ~€19/month (~$20 USD) for complete platform
- **Key Features:**
  - ✅ **Booking.com Certified Partner** (Premier Partner 2025)
  - ✅ **Expedia Group Preferred Partner** (2025)
  - ✅ **Agoda Strategic Partner**
  - ✅ Airbnb, Google Hotel Ads, TripAdvisor
  - Full PMS included
  - Booking engine included
  - Multi-property support (Groups & Chains solution)
  - API available for integration
  - Mobile app
  - Free trial available
- **Certifications:**
  - Booking.com Premier Partner Badge 2025
  - Expedia Group Preferred Partner Badge 2025
  - Agoda Strategic Badge
  - PCI DSS compliant
  - ISO 27001 certified
- **Multi-Property Support:** ✅ YES - Has "Groups and Chains" solution
- **Clients:** Wyndham, Accor, Hilton, Radisson properties use them
- **Pros:**
  - ✅ INCLUDES BOOKING.COM (certified integration!)
  - ✅ Extremely affordable (€19/month all-inclusive)
  - ✅ Full PMS + Channel Manager combo
  - ✅ Multi-property/agency support
  - ✅ Trusted by major hotel chains
  - ✅ API for custom PMS integration
  - ✅ Great reviews (4.5+ stars)
- **Cons:**
  - Turkish company (but 24/7 support)
  - Could replace your PMS entirely (maybe a pro?)

### **3. Octorate (Budget-Friendly European)**
- **Website:** https://www.octorate.com
- **Type:** Cloud channel manager
- **Pricing:** Starting €29/month (~$32 USD) for small properties
- **Features:**
  - 100+ channels
  - Booking engine included
  - Multi-property dashboard
  - API + webhooks
- **Pros:**
  - Affordable for small hotels
  - Good API documentation
  - Multi-property support
- **Cons:**
  - European focus
  - Limited Latin American presence

### **4. Wubook (Now Zak)**
- **Website:** https://en.wubook.net
- **Type:** Channel manager + PMS
- **Pricing:** FREE tier available! Paid from €10/month
- **Features:**
  - 70+ channels including Booking.com, Expedia
  - Free basic plan (limited features)
  - API access
  - Multi-calendar
- **Pros:**
  - FREE option exists!
  - Very affordable paid tiers
  - API available
- **Cons:**
  - Basic features on free tier
  - Smaller channel network
  - Unknown multi-property support

### **5. Direct Integration with Booking.com (FREE)**
- **Website:** https://join.booking.com
- **Type:** Direct API integration
- **Pricing:** FREE (only commission on bookings ~15%)
- **Features:**
  - Direct connection to Booking.com
  - XML API for rates/availability
  - Booking notifications
  - No monthly fees
- **Implementation:**
  - Apply as accommodation provider
  - Get API credentials
  - Build XML integration
  - 2-3 weeks development
- **Pros:**
  - Completely FREE
  - Booking.com is #1 in Peru/Latin America
  - Full control
  - No middleman fees
- **Cons:**
  - Only ONE channel (but it's the biggest)
  - Development work required
  - Maintenance responsibility

### **6. AvaiBook (Spanish/Latin America)**
- **Website:** https://www.avaibook.com
- **Type:** Vacation rental channel manager
- **Pricing:** From €19/month (~$20 USD)
- **Features:**
  - Airbnb, Booking.com, Vrbo, Despegar
  - Spanish-language platform
  - Calendar sync
  - API available
- **Pros:**
  - Spanish interface
  - Latin American market knowledge
  - Affordable
- **Cons:**
  - Vacation rental focused
  - Unknown multi-property API support

---

## 💰 Budget Comparison for Peru Market

| Solution | Monthly Cost (Small Hotel) | Multi-Property Support | Peru/LatAm Focus | API Quality |
|----------|---------------------------|------------------------|------------------|-------------|
| **Wubook (FREE)** | €0 (FREE!) | Unknown | ❌ | ⭐⭐⭐ |
| **Booking.com Direct** | €0 (FREE!) | ✅ | ✅ | ⭐⭐⭐⭐ |
| **HotelRunner** | €19 (~$20) | ✅ | ❌ | ⭐⭐⭐ |
| **AvaiBook** | €19 (~$20) | Unknown | ✅ | ⭐⭐ |
| **Omnitec** | ~$15-30 | Need to verify | ✅✅ | Unknown |
| **Octorate** | €29 (~$32) | ✅ | ❌ | ⭐⭐⭐⭐ |
| **Channels.app** | $10-20 | ❌ | ❌ | ⭐⭐⭐ |
| **Rentals United** | $25-50 | ✅✅ | ❌ | ⭐⭐⭐⭐⭐ |
| **SiteMinder** | $50-200 | ✅✅ | ✅ (has LatAm office) | ⭐⭐⭐⭐⭐ |

---

## 🎯 UPDATED Recommendation for Peru Market

### **BEST FREE Option: Direct Booking.com Integration**
**Why:**
- ✅ Completely FREE (no monthly fees!)
- ✅ Booking.com is the dominant OTA in Peru
- ✅ Multi-property support via API
- ✅ Your dev team can build it (2-3 weeks)
- ✅ No ongoing fees (just booking commissions)

**How it works:**
```
Step 1: Register each property on Booking.com
Step 2: Apply for API access (Connectivity API)
Step 3: Build XML integration in AiniTravel
Step 4: 2-way sync (rates, availability, bookings)
```

**Development effort:**
- 2-3 weeks for first integration
- Reusable code for all properties
- One-time cost vs monthly fees

### **BEST Budget Paid Option: Wubook (FREE tier) or HotelRunner (€19/month)**
**Why:**
- ✅ Very affordable or FREE
- ✅ Multiple OTA connections
- ✅ API access included
- ✅ Quick setup

**Downside:**
- Must verify multi-property support
- Smaller channel network than premium options

### **BEST Local Option: Omnitec (Peru)**
**Why:**
- ✅ Peruvian company - understands local market
- ✅ Local pricing (affordable)
- ✅ Spanish support
- ✅ Despegar integration (big in LatAm)

**Next steps:**
- Contact them for pricing
- Ask about multi-property API
- Request demo

---

## 💡 Recommended Strategy for AiniTravel (Budget-Conscious)

### **Phase 1: FREE - Start with Booking.com Direct** (Month 1-2)
```
Cost: $0/month
Coverage: 40-50% of Peru market
Development: 2-3 weeks
```

**Benefits:**
- Zero monthly fees
- Booking.com is #1 in Peru
- Learn channel integration
- Prove value to clients

**Implementation:**
1. Register properties on Booking.com extranet
2. Apply for XML API access
3. Build integration into AiniTravel PMS
4. Test with 2-3 pilot properties
5. Roll out to all interested clients

### **Phase 2: Add Budget Channel Manager** (Month 3-4)
```
Cost: $20-40/month total (HotelRunner or Wubook)
Coverage: +30% market (Expedia, Airbnb, etc.)
```

**Why add:**
- Cover Expedia, Airbnb, Agoda
- Minimal cost ($20-40/month for all properties combined)
- Quick setup (no development)

### **Phase 3: Scale to Premium (if successful)** (Month 6+)
```
Cost: $200-500/month (SiteMinder or Rentals United)
Coverage: 90%+ market
```

**When to upgrade:**
- Managing 20+ properties
- Clients willing to pay premium
- Need advanced features
- Revenue justifies cost

---

## 📊 ROI Calculation for Peru

### Scenario: 10 Small Hotels (5 rooms each)

**Option A: Booking.com Direct (FREE)**
- Monthly cost: $0
- Dev cost: $500 one-time
- Commission: 15% of bookings
- Hotels save: $50/month each = $500/month total

**Option B: HotelRunner (€19/month)**
- Monthly cost: $20
- You charge hotels: $30/month each
- Your revenue: $300/month
- Your profit: $280/month
- Hotels save vs premium: $70/month each

**Option C: Rentals United ($25-50 per property)**
- Monthly cost: $250-500 (10 hotels)
- You charge hotels: $50/month each
- Your revenue: $500/month
- Your profit: $0-250/month
- More features but less profitable

**Winner for Peru market: Booking.com Direct + HotelRunner combo**
- Total cost: $20/month
- Charge clients: $30-40/month each
- Profit: $280-380/month with 10 hotels
- Coverage: 70-80% of market

---

## Recommended Approach
**Option A: Channels.app**
- Cost-effective ($10-20/month)
- Quick setup
- Good OTA coverage
- RESTful API for PMS integration

**Option B: Rentals United (Free tier)**
- Free to start
- API access included
- Good for testing

### **For Medium Hotels (10-50 rooms):**
**SiteMinder or Cloudbeds**
- More OTA connections
- Better support
- Advanced features
- Worth the investment (~$100-300/month)

### **For Large Hotels/Chains:**
**SiteMinder or RateGain ChannelRunner**
- Enterprise features
- Multi-property support
- Dedicated account manager

---

## Integration Architecture

### How It Would Work:

```
┌─────────────────┐
│  AiniTravel PMS │
│  (Your System)  │
└────────┬────────┘
         │
         │ REST API Calls
         │
┌────────▼────────────┐
│  Channel Manager    │
│  (e.g., SiteMinder) │
│  - Inventory Sync   │
│  - Rate Updates     │
│  - Booking Import   │
└────────┬────────────┘
         │
         │ Distributes to:
         │
    ┌────┴────┬─────────┬──────────┐
    ▼         ▼         ▼          ▼
┌─────────┐ ┌──────┐ ┌───────┐ ┌──────┐
│Booking  │ │Expedia│ │Airbnb │ │Agoda │
│.com     │ │       │ │       │ │      │
└─────────┘ └──────┘ └───────┘ └──────┘
```

### API Integration Points:

1. **Push Updates to Channel Manager:**
   - Room availability changes
   - Rate updates
   - Min stay restrictions
   - Stop sales

2. **Pull Bookings from Channel Manager:**
   - New reservation notifications (webhook)
   - Modification notifications
   - Cancellation notifications
   - Guest details

3. **2-Way Sync:**
   - Automatic inventory blocking
   - Rate parity maintenance
   - Real-time availability

---

## Sample API Workflow (SiteMinder Example)

### 1. Update Room Availability
```php
// POST to SiteMinder API
$data = [
    'property_id' => 'YOUR_PROPERTY_ID',
    'room_type' => 'DELUXE_ROOM',
    'date' => '2025-11-15',
    'availability' => 5,  // 5 rooms available
    'rate' => 150.00,
    'min_stay' => 1
];

$response = callChannelManagerAPI('/availability/update', $data);
```

### 2. Receive Booking (Webhook)
```php
// Webhook endpoint: https://ainitravel.com/api/channel-manager/booking
// POST data from channel manager
{
    "reservation_id": "BK12345",
    "source": "Booking.com",
    "guest_name": "John Doe",
    "check_in": "2025-11-20",
    "check_out": "2025-11-22",
    "room_type": "DELUXE_ROOM",
    "total_amount": 300.00,
    "commission": 45.00
}
```

---

## Next Steps

1. **Choose a Channel Manager:**
   - Start with free trial (Channels.app or Rentals United)
   - Test API integration with 1-2 OTAs
   - Evaluate ease of use and support

2. **API Integration Development:**
   - Set up webhook endpoints in AiniTravel PMS
   - Build API client for channel manager
   - Create sync service for availability/rates
   - Implement booking import automation

3. **Testing:**
   - Use sandbox/test environment
   - Verify 2-way sync works correctly
   - Test edge cases (overbooking, cancellations)

4. **Go Live:**
   - Connect to production OTAs
   - Monitor for issues
   - Set up alerts for sync failures

---

## Cost Comparison (Monthly)

| Solution | Small Hotel (5 rooms) | Medium (20 rooms) | Large (50 rooms) |
|----------|----------------------|-------------------|------------------|
| Channels.app | $10-20 | $30-50 | $80-120 |
| SiteMinder | $80-150 | $150-300 | $400-800 |
| Cloudbeds | $25 (5 rooms) | $100 (20 rooms) | $250 (50 rooms) |
| Rentals United | Free-$20 | $40-80 | $100-200 |
| Direct Integration | Dev time only | Dev time only | Dev time only |

---

## Recommendation for AiniTravel

**Phase 1 (Immediate):**
Start with **Channels.app** or **Rentals United**
- Low cost to test
- Quick setup (1-2 weeks)
- API documentation available
- Can switch later if needed

**Phase 2 (3-6 months):**
Evaluate migration to **SiteMinder** if:
- Managing 10+ properties
- Need more OTA connections
- Revenue grows to justify cost
- Need advanced features

**Phase 3 (Future):**
Consider **direct OTA integrations** for:
- Major booking sources (Booking.com, Expedia)
- Reduce dependency on channel manager
- Lower per-booking fees
- More control over guest data

---

## Resources

- **Channel Manager Comparison:** https://www.hoteltechreport.com/channel-managers
- **OTA Commission Rates:** https://www.phocuswire.com/hotel-distribution-costs
- **API Best Practices:** https://restfulapi.net/
- **Webhook Security:** https://hookdeck.com/webhooks/guides/webhook-security-best-practices

