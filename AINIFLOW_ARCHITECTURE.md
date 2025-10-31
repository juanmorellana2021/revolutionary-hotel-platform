# AiniFlow Platform Architecture
**Date:** October 31, 2025  
**Purpose:** Social messaging platform for AINI Hotel Network travelers

---

## 🌐 Platform Overview

### AiniTravel.com (Existing - Servers 1-3)
**Purpose:** Hotel booking and property management  
**Servers:**
- **Server 1:** 108.175.12.152 (Primary hotel - LIVE)
- **Server 2:** TBD (Hotel 2)
- **Server 3:** TBD (Hotel 3)

**Features:**
- Hotel room booking
- Property management dashboards
- Employee management
- Accounting & expenses
- AI chatbot (per-hotel)
- Payment processing
- Room availability calendar

**Stack:** PHP, MySQL, Apache

---

### AiniFlow.com (NEW - Server 4)
**Purpose:** Social network & messaging for travelers  
**Server:** 72.61.217.65  
**URL:** http://72.61.217.65 (will be AiniFlow.com)

**Features:**
1. **Phone-Based Authentication**
   - Login with phone number (like WhatsApp)
   - SMS verification codes
   - No email/username required
   - Phone number = unique user ID

2. **User Profiles**
   - Name, photo, bio
   - Travel preferences
   - Reputation score
   - Review history
   - Badges (Verified, Superguest)
   - Total stays counter

3. **Chatrooms**
   - **Location-Based:** #miami-beach, #cancun-travelers, #paris-tips
   - **Category-Based:** #solo-travelers, #family-vacations, #budget-backpackers
   - **Hotel-Specific:** Private rooms for hotel guests
   - Public vs Private rooms
   - Join/leave functionality

4. **Direct Messaging**
   - Guest ↔ Guest (traveler to traveler)
   - Guest ↔ Hotel AI (24/7 automated help)
   - Guest ↔ Live Agent (real hotel staff)
   - Real-time WebSocket messaging
   - Typing indicators
   - Read receipts
   - Message history

5. **Mutual Review System** (Like Airbnb)
   - After booking ends → both parties can review
   - Guest reviews hotel (rating + text)
   - Hotel reviews guest (rating + text)
   - Reviews hidden until BOTH submit (or 14 days pass)
   - Prevents retaliation
   - Updates reputation scores
   - Superguest badge: 10+ stays, 4.8+ rating

6. **AiniCoin Wallet**
   - Digital currency for bookings
   - Deposit funds
   - Pay for hotels
   - Peer-to-peer transfers
   - Transaction history
   - Refund processing

7. **Social Features**
   - Posts with photos/videos
   - Like, comment, share
   - Follow other travelers
   - Travel timeline/feed

**Stack:** Node.js, Express, Socket.io, PostgreSQL, Redis, Nginx

---

## 🔗 Integration Between Platforms

### AiniTravel.com → AiniFlow.com
1. **After Booking:**
   - Show "Join AiniFlow community!" banner
   - Link: `https://ainiflow.com/join?booking_id={id}`
   - Pre-fill user info from booking

2. **SSO (Single Sign-On):**
   - Share authentication between platforms
   - JWT token bridge
   - Booking ID ↔ User Phone mapping

3. **Review Trigger:**
   - When booking ends on AiniTravel → create review record on AiniFlow
   - Send notifications to both hotel & guest
   - 14-day review window

4. **Wallet Integration:**
   - Pay for bookings with AiniCoin
   - AiniTravel checks wallet balance via API
   - Deduct from wallet on Server 4

### AiniFlow.com → AiniTravel.com
1. **Browse Hotels:**
   - Chatroom members can search hotels
   - Link directly to AiniTravel booking page
   - "Book Now" buttons in chat

2. **Profile Integration:**
   - Show user's booking history
   - Display review scores
   - Verified guest badge

---

## 📊 Database Architecture

### Server 4 (AiniFlow) Database: `aini_platform`

#### Tables:
1. **users**
   - phone (PRIMARY KEY)
   - name
   - profile_photo
   - wallet_balance
   - is_online
   - last_seen
   - created_at, updated_at

2. **guest_profiles**
   - phone (FOREIGN KEY → users)
   - average_rating
   - total_reviews
   - total_stays
   - is_verified
   - is_superguest
   - badges (array)
   - bio

3. **messages**
   - id
   - from_phone → users
   - to_phone → users (NULL if chatroom)
   - room_id (NULL if DM)
   - message_type (text, image, file)
   - content
   - is_read
   - sent_at

4. **chatrooms**
   - id
   - name
   - description
   - room_type (public, private, hotel)
   - hotel_id
   - created_by → users
   - photo_url
   - created_at, updated_at

5. **room_members**
   - room_id → chatrooms
   - phone → users
   - is_admin
   - is_muted
   - joined_at

6. **reviews**
   - id
   - booking_id (from AiniTravel)
   - hotel_id
   - guest_phone → users
   - guest_to_hotel_rating (1-5)
   - guest_to_hotel_review
   - guest_to_hotel_submitted_at
   - hotel_to_guest_rating (1-5)
   - hotel_to_guest_review
   - hotel_to_guest_submitted_at
   - published_at (NULL until both submit)
   - expires_at (14 days after booking)

7. **social_posts**
   - id
   - author_phone → users
   - content
   - media_urls (array)
   - post_type (text, photo, video)
   - likes_count
   - comments_count
   - is_public
   - created_at

8. **wallet_transactions**
   - id
   - from_phone → users
   - to_phone → users
   - amount
   - transaction_type (deposit, withdrawal, transfer, booking_payment, refund)
   - booking_id
   - hotel_id
   - status (pending, completed, failed)
   - created_at, completed_at

---

## 🚀 API Endpoints (Server 4)

### Authentication
- `POST /api/auth/request-code` - Request SMS verification code
- `POST /api/auth/verify-code` - Verify code & login/register

### Users
- `GET /api/users/:phone` - Get user profile
- `PUT /api/users/:phone` - Update profile

### Messaging
- `GET /api/messages/conversation/:phone1/:phone2` - Get DM history
- WebSocket event: `send_message` - Send direct message

### Chatrooms
- `GET /api/chatrooms/public` - List all public chatrooms
- `GET /api/chatrooms/:roomId/messages` - Get room messages
- `POST /api/chatrooms/:roomId/join` - Join a chatroom
- WebSocket event: `send_room_message` - Send message to room
- WebSocket event: `join_room` - Join room for real-time updates

### Reviews
- `POST /api/reviews/guest-review` - Submit guest review of hotel
- `POST /api/reviews/hotel-review` - Submit hotel review of guest
- `GET /api/reviews/guest/:phone` - Get all reviews for a guest
- `GET /api/reviews/hotel/:hotelId` - Get all reviews for a hotel

### Wallet
- `GET /api/wallet/:phone` - Get wallet balance
- `POST /api/wallet/deposit` - Deposit funds
- `POST /api/wallet/transfer` - Transfer to another user
- `GET /api/wallet/transactions/:phone` - Transaction history

### Social
- `POST /api/social/post` - Create new post
- `GET /api/social/feed` - Get social feed
- `POST /api/social/like` - Like a post
- `POST /api/social/comment` - Comment on post

---

## 🎯 User Journey

### First-Time User:
1. Books hotel on **AiniTravel.com**
2. Receives confirmation email with "Join AiniFlow!" link
3. Clicks link → lands on AiniFlow.com/join
4. Enters phone number → receives SMS code
5. Verifies code → creates simple profile (name, photo)
6. Automatically joined to hotel's chatroom (#hotel-ocean-view-guests)
7. Can browse public rooms (#miami-beach, #solo-travelers)
8. Chat with other travelers, get local tips
9. After stay → both hotel & guest review each other
10. Reviews publish simultaneously (prevents retaliation)
11. Guest's reputation score updates
12. With 10+ stays & 4.8+ rating → becomes Superguest

### Returning User:
1. Goes to **AiniFlow.com**
2. Enters phone number → SMS code → logged in
3. Sees unread messages, active chatrooms
4. Posts travel photos to social feed
5. Checks wallet balance
6. Books another hotel → pays with AiniCoin

---

## 📱 Future: Mobile App

### AiniFlow Mobile App (iOS/Android)
**When:** After web version is stable  
**Why:** Better UX, push notifications, offline mode  
**Tech Stack:** React Native or Flutter  
**Features:**
- Same backend API (Server 4)
- Native phone number auth
- Push notifications for messages
- Offline message queue
- Camera integration for profile photos
- Location services for nearby chatrooms
- Biometric login (Face ID, fingerprint)

---

## 🔐 Security

1. **Authentication:**
   - SMS verification via Twilio
   - JWT tokens (expire after 30 days)
   - Refresh tokens

2. **Data Protection:**
   - Phone numbers hashed in logs
   - HTTPS only (SSL certificates)
   - Rate limiting on API endpoints

3. **Moderation:**
   - Report abusive messages
   - Ban users from chatrooms
   - Hotel admin can moderate hotel-specific rooms

---

## 📈 Metrics to Track

1. **User Growth:**
   - Daily active users (DAU)
   - Monthly active users (MAU)
   - New registrations per day

2. **Engagement:**
   - Messages sent per day
   - Chatrooms joined
   - Average session duration

3. **Reviews:**
   - Review completion rate
   - Average guest rating
   - Superguest percentage

4. **Wallet:**
   - Total AiniCoin in circulation
   - Transaction volume
   - Average wallet balance

---

## 🛠️ Current Status

### ✅ Completed:
- Server 4 infrastructure setup
- Node.js, PostgreSQL, Redis installed
- Database schema created
- Backend API built
- WebSocket messaging implemented
- Homepage/landing page

### 🚧 In Progress:
- Frontend UI (login, chat, profile pages)
- SMS integration (Twilio)
- JWT authentication
- Image upload for profiles/messages

### 📋 To Do:
- Complete web frontend
- Integrate with AiniTravel servers
- Set up domain (AiniFlow.com)
- SSL certificates
- Email notifications
- Admin dashboard
- Mobile app (future)

---

## 💡 Key Differentiators

**What makes AiniFlow unique:**
1. **Phone-first authentication** (like WhatsApp, not like Facebook)
2. **Mutual reviews** (hotels can rate guests, prevents bad actors)
3. **Reputation system** (Superguest badges encourage good behavior)
4. **Location-based chatrooms** (connect travelers in same city)
5. **Integrated wallet** (seamless payments across hotel network)
6. **Real-time messaging** (instant communication, no email lag)
7. **Hotel AI + Live agents** (24/7 support with human fallback)

---

**Next Steps:** Build the frontend UI for login, chat, and profile management.
