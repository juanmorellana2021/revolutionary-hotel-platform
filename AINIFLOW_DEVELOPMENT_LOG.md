# AiniFlow Development Log
## Social Travel Network Platform

**Last Updated:** November 6, 2025  
**Status:** MVP Development In Progress  
**Live URL:** http://72.61.217.65/login.html

---

## 🎯 Project Overview

**AiniFlow** is a social travel network for Cusco/Peru travelers that combines:
- WhatsApp-style messaging between travelers
- Travel groups (Machu Picchu, Sacred Valley, etc.)
- Hotel discovery and booking
- AiNi Coins wallet system
- Mutual review system (Hotels ↔ Guests)

---

## 📱 Current Features Implemented

### ✅ Authentication System
- **Phone-based login** with SMS verification
- **Twilio integration** for SMS (currently via WhatsApp format)
- **Dev mode fallback** - shows code when SMS fails
- **User profiles** stored in PostgreSQL database
- **localStorage** session management

**Files:**
- `ainiflow_login.html` → `/var/www/aini-platform/public/login.html`
- Backend: `/var/www/aini-platform/index.js` (Node.js + Express)

**Current Issue:** 
- SMS not arriving on Peruvian carriers (Claro blocking international SMS)
- **Workaround:** Dev mode shows verification code in API response
- **Solution planned:** Enable Twilio WhatsApp or get Peru local number

### ✅ Main UI (chat-ui.html)
**Navigation Structure:**
- **Top Tabs:** Chats | Groups | Hotels
- **Bottom Nav:** Contacts | Explore | + | Wallet | Me

**Current State:**
- ✅ Design complete (orange/red gradient theme)
- ✅ Responsive mobile-first layout
- ⚠️ Dummy data for chats/groups/hotels
- ✅ Search functionality
- ✅ Floating action button

**Files:**
- `ainiflow_chat_ui.html` → `/var/www/aini-platform/public/chat-ui.html`

### ✅ Contacts System
**Features Implemented:**
- ✅ **Add contacts manually** (phone + name)
- ✅ **Contact permission request** (browser Contact Picker API)
- ✅ **Check contacts on AiniFlow** via API (`/api/users/check-contacts`)
- ✅ **Two lists:** On AiniFlow / Not on AiniFlow
- ✅ **Invite via WhatsApp** for contacts not on platform
- ✅ **QR Code generation** - "My QR" button shows your QR code
- ✅ **QR Code scanner** - Scan someone's QR to add them

**QR Code Libraries:**
- `qrcodejs` for generating QR codes
- `html5-qrcode` for camera scanning

**Current Issue:**
- Camera access requires **HTTPS** - currently on HTTP
- **Workaround:** Manual contact adding works fine
- **Solution planned:** Set up SSL certificate (Let's Encrypt)

**API Endpoints Used:**
- `POST /api/users/check-contacts` - Check which contacts are on AiniFlow
- `GET /api/users/:phone` - Get user profile

---

## 🗄️ Backend (Node.js + PostgreSQL)

**Server:** social-vps (72.61.217.65)  
**Stack:** Node.js + Express + Socket.IO + PostgreSQL + Redis  
**Service:** `ainiflow.service` (systemd)  
**Logs:** `/var/www/aini-platform/server.log`

### Database Tables (PostgreSQL)
1. **users** - User accounts (phone, name, profile_photo, wallet_balance, etc.)
2. **guest_profiles** - Extended guest info (ratings, badges, bio, etc.)
3. **messages** - Chat messages (1-to-1 and group)
4. **chatrooms** - Public/private group chats
5. **room_members** - Group membership
6. **wallet_transactions** - AiNi Coin transfers
7. **reviews** - Mutual hotel ↔ guest reviews

### API Endpoints Implemented

#### Authentication
- `POST /api/auth/request-code` - Send verification code via WhatsApp/SMS
- `POST /api/auth/verify-code` - Verify code and login/register

#### Users
- `GET /api/users/:phone` - Get user profile
- `PUT /api/users/:phone` - Update profile
- `POST /api/users/check-contacts` - Check which contacts are on AiniFlow

#### Messaging
- `GET /api/messages/conversation/:phone1/:phone2` - Get chat history
- Socket.IO events: `send_message`, `new_message`, `typing`

#### Chatrooms
- `GET /api/chatrooms/public` - List public groups
- `GET /api/chatrooms/:roomId` - Get group details
- `GET /api/chatrooms/:roomId/messages` - Get group messages
- `POST /api/chatrooms/:roomId/join` - Join a group

#### Wallet
- `GET /api/wallet/transactions/:phone` - Transaction history
- `POST /api/wallet/deposit` - Add AiNi Coins (dev mode)
- `POST /api/wallet/transfer` - Send coins to another user

#### Reviews
- `POST /api/reviews/guest-review` - Submit guest review of hotel

### Real-time Features (Socket.IO)
- ✅ WebSocket connection on port 3000
- ✅ User online/offline status
- ✅ Direct messaging
- ✅ Group chat messages
- ✅ Typing indicators
- ✅ Read receipts (backend ready, UI pending)

---

## 🚀 Deployment Setup

### Server Configuration
```bash
# Location
Server: social-vps (72.61.217.65)
Directory: /var/www/aini-platform/

# Structure
/var/www/aini-platform/
├── public/
│   ├── login.html              # Phone login (v2.0-CHAT indicator)
│   ├── chat-ui.html            # Main app interface
│   ├── dashboard.html          # Old dashboard (deprecated)
│   ├── chat.html               # Old chat (deprecated)
│   ├── contacts.html           # Old contacts (deprecated)
│   └── wallet.html             # Old wallet (deprecated)
├── index.js                    # Node.js backend
├── package.json
├── .env                        # Environment variables
├── server.log                  # Application logs
└── error.log                   # Error logs
```

### Nginx Configuration
```nginx
# /etc/nginx/sites-enabled/ainiflow
server {
    listen 80;
    server_name 72.61.217.65;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

**Important:** Nginx proxies to Node.js - files served by Node.js, not Nginx directly!

### Systemd Service
```bash
# Service: ainiflow.service
sudo systemctl status ainiflow
sudo systemctl restart ainiflow
sudo systemctl stop ainiflow

# Logs
journalctl -u ainiflow -f
tail -f /var/www/aini-platform/server.log
```

### Deployment Commands
```bash
# Upload files from Windows
scp ainiflow_login.html social-vps:/var/www/aini-platform/public/login.html
scp ainiflow_chat_ui.html social-vps:/var/www/aini-platform/public/chat-ui.html
scp ainiflow_index.js social-vps:/var/www/aini-platform/index.js

# ALWAYS restart after uploading
ssh social-vps "systemctl restart ainiflow"

# Check service status
ssh social-vps "systemctl status ainiflow | head -10"

# Get verification codes from logs
ssh social-vps "tail -30 /var/www/aini-platform/server.log | grep 'Verification code'"
```

---

## 🔧 Environment Variables (.env)

```bash
# Server
PORT=3000
NODE_ENV=production

# PostgreSQL
DB_USER=postgres
DB_HOST=localhost
DB_NAME=aini_platform
DB_PASSWORD=
DB_PORT=5432

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# Twilio (SMS/WhatsApp)
TWILIO_ACCOUNT_SID=AC67d08a7d1b6ebcfeabf8778d63a14bc9
TWILIO_AUTH_TOKEN=def3054a6ed6fd769458fe30fd7b6745
TWILIO_PHONE_NUMBER=+18257933703

# Feature Flags
ENABLE_SMS_VERIFICATION=false  # Currently disabled
ENABLE_PUSH_NOTIFICATIONS=false
ENABLE_EMAIL_NOTIFICATIONS=false
```

---

## 🐛 Known Issues & Solutions

### Issue 1: Login Redirect Goes to Dashboard Instead of Chat-UI
**Problem:** After login, redirected to dashboard.html instead of chat-ui.html  
**Cause:** Old code had `/chat.html` redirect, which redirected back to `/dashboard.html`  
**Solution:** Changed login.html to redirect to `/chat-ui.html` (lines 337, 380)  
**Status:** ✅ Fixed

### Issue 2: SMS Not Arriving
**Problem:** Twilio SMS not received on Peruvian phones (Claro carrier)  
**Cause:** Carriers blocking international automated SMS  
**Current Workaround:** Dev mode shows `dev_code` in API response  
**Permanent Solutions:**
1. Enable Twilio WhatsApp messaging (changed from SMS to WhatsApp format)
2. Buy Peru local Twilio number
3. Use email verification as alternative

**Status:** ⚠️ Workaround active, WhatsApp format implemented but needs Twilio sandbox setup

### Issue 3: QR Code Scanner Camera Access Denied
**Problem:** Camera won't open for QR scanning  
**Cause:** HTTP sites can't access camera - requires HTTPS  
**Workaround:** Manual contact adding works fine  
**Solution:** Set up SSL certificate (Let's Encrypt)  
**Status:** ⏳ Planned for later

### Issue 4: Node.js File Caching
**Problem:** After uploading new files, changes don't appear  
**Cause:** Node.js caches files in memory  
**Solution:** ALWAYS restart ainiflow service after uploads  
**Status:** ✅ Documented in deployment guide

### Issue 5: Browser Cache
**Problem:** Old login page loads even after uploading new version  
**Cause:** Browser caching HTML files  
**Solution:** Hard refresh (Ctrl+Shift+R) or incognito mode  
**Status:** ✅ Documented

---

## 📋 Next Steps / TODO

### High Priority
- [ ] **Set up HTTPS/SSL** - Enable camera access for QR scanner
- [ ] **Build actual chat interface** - Currently just shows alert when clicking contact
- [ ] **Real-time messaging** - Connect Socket.IO to UI
- [ ] **Replace dummy data** - Load real chats/groups from database
- [ ] **Twilio WhatsApp setup** - Configure sandbox or get approved number

### Medium Priority
- [ ] **Groups functionality** - Create/join travel groups
- [ ] **Hotels integration** - Connect to hotel booking system
- [ ] **Wallet UI** - Show balance, transactions, send/receive coins
- [ ] **Profile page** - Edit profile, view stats, badges
- [ ] **Notifications** - Push notifications for new messages
- [ ] **File sharing** - Send photos/videos in chat
- [ ] **Voice messages** - Record and send audio

### Low Priority
- [ ] **Email verification** - Alternative to SMS
- [ ] **Google/Facebook login** - Social auth
- [ ] **Multi-language** - Spanish/English toggle
- [ ] **Dark mode** - Theme switching
- [ ] **PWA setup** - Install as app

---

## 🧪 Testing

### Test Accounts
- **Primary:** +51938118436 (Juan - main test account)
- **Secondary:** (Need second number for chat testing)

### Test Scenarios
1. ✅ **Login flow** - Request code → Enter code → Redirect to chat-ui
2. ✅ **Add contact manually** - Phone + name → Check if on AiniFlow
3. ⏳ **QR code generation** - Show your QR (works)
4. ⏳ **QR scanning** - Scan someone's QR (needs HTTPS)
5. ⏳ **Send message** - Chat 1-to-1 (UI pending)
6. ⏳ **Group chat** - Join group, send message (UI pending)
7. ⏳ **Wallet transfer** - Send AiNi Coins (UI pending)

---

## 📚 Technical Stack

### Frontend
- **HTML/CSS/JS** - No framework, vanilla + Alpine.js
- **Tailwind CSS** - Utility-first styling
- **Alpine.js** - Reactive UI components
- **Font Awesome** - Icons
- **QRCode.js** - QR code generation
- **html5-qrcode** - QR code scanning

### Backend
- **Node.js 18+** - Runtime
- **Express** - Web framework
- **Socket.IO** - WebSocket/real-time
- **PostgreSQL** - Primary database
- **Redis** - Caching + verification codes
- **Twilio** - SMS/WhatsApp
- **dotenv** - Environment config

### Infrastructure
- **VPS:** 72.61.217.65 (social-vps)
- **OS:** Ubuntu 24.04 LTS
- **Web Server:** Nginx (reverse proxy)
- **Process Manager:** systemd
- **Deployment:** SCP + manual restart

---

## 🎨 Design System

### Colors (Cusco Theme)
- **Primary:** #E07A5F (Orange-red)
- **Primary Dark:** #C4624A
- **Secondary:** #3D5A80 (Blue)
- **Accent:** #F2CC8F (Gold)
- **Background:** #F4F1DE (Cream)
- **Surface:** #FFFFFF (White)
- **Text:** #2D3142 (Dark gray)

### Typography
- **Font:** Inter (Google Fonts)
- **Weights:** 300, 400, 500, 600, 700

### Components
- **Gradient buttons:** Orange to Red
- **Rounded corners:** 8px-24px
- **Shadows:** Subtle elevation
- **Icons:** Font Awesome 6.4.0

---

## 📞 Support & Contact

**Project Lead:** Juan Morellana  
**Repository:** https://github.com/juanmorellana2021/revolutionary-hotel-platform  
**Server Access:** SSH passwordless (hotel_social_vps_key)

---

## 📝 Change Log

### November 6, 2025
- ✅ Built login system with phone verification
- ✅ Created main chat-ui.html interface
- ✅ Implemented contacts system with manual adding
- ✅ Added QR code generation and scanning
- ✅ Changed bottom nav from Chats to Contacts
- ✅ Fixed login redirect to chat-ui.html
- ✅ Changed SMS to WhatsApp format in backend
- ✅ Added dev_code fallback for SMS failures
- ✅ Documented deployment process
- ✅ Created this development log

---

## 🔗 Important URLs

- **Login:** http://72.61.217.65/login.html
- **Main App:** http://72.61.217.65/chat-ui.html
- **API Base:** http://72.61.217.65:3000/api
- **WebSocket:** ws://72.61.217.65:3000
- **Server Logs:** `ssh social-vps "tail -f /var/www/aini-platform/server.log"`

---

*This document is maintained as the single source of truth for AiniFlow development progress.*
