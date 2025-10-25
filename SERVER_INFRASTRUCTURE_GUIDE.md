# 🖥️ Server Infrastructure Guide
## Revolutionary Hotel Platform - Complete Server Documentation

**Last Updated:** October 24, 2025  
**Maintained by:** Juan Morellana  
**Repository:** https://github.com/juanmorellana2021/revolutionary-hotel-platform

---

## 📡 Server Overview

### **Available Servers (All SSH Passwordless Configured)**

| Server | IP | Alias | Purpose | Status |
|--------|-----|-------|---------|--------|
| **Production VPS** | 108.175.12.152 | `prod-vps` | Main production system | ✅ Active |
| **Test VPS** | 212.227.241.193 | `test-vps` | Testing environment | ✅ Active |
| **Social VPS** | 72.61.217.65 | `social-vps` | Social network (future) | ✅ Active |
| **AI VPS** | 72.60.1.16 | `ai-vps` | AI models & Ollama | ✅ Active |

### **SSH Connection (Passwordless)**

```bash
# Production
ssh prod-vps

# Test
ssh test-vps

# Social Network
ssh social-vps

# AI Server
ssh ai-vps
```

**SSH Keys Location:** `C:\Users\juano\.ssh\`
- `hotel_prod_vps_key` - Production
- `hotel_new_vps_key` - Test
- `hotel_social_vps_key` - Social
- `hotel_ai_vps_key` - AI

---

## 🚀 Production Server (108.175.12.152)

### **Web Server Configuration**

- **Web Server:** Apache 2.4 (NOT Nginx)
- **Status:** ✅ Active and Running
- **PHP Version:** 8.2.28
- **Database:** MySQL Community Server
- **Root Directory:** `/var/www/html/`

### **Project Structure**

```
/var/www/html/
├── manage/                          ⭐ MAIN PROJECT (154 files)
│   ├── public_booking.php           # Hotel search system (Booking.com style)
│   ├── public_booking_api.php       # Public API
│   ├── manager_dashboard.php        # Admin dashboard
│   ├── calendar_view.php            # Booking calendar (292KB)
│   ├── room_management.php          # Room management
│   ├── accounting_dashboard.php     # Financial dashboard
│   ├── employee_management.php      # Employee system
│   ├── whatsapp_management.php      # WhatsApp integration
│   ├── travel_social.php            # Social travel network
│   ├── wallet.php                   # AiNi Coins system
│   ├── includes/                    # PHP classes & helpers
│   ├── config/                      # Configuration files
│   ├── api/                         # API endpoints
│   ├── uploads/                     # File uploads
│   └── vendor/                      # Composer dependencies
│
├── samaywasipisac.com/              # Samay Wasi Hotel site
├── api.samaywasipisac.com/          # Hotel API
├── perubookingtravel.com/           # Travel booking site
├── api.perubookingtravel.com/       # Travel API
├── pma.samaywasipisac.com/          # PhpMyAdmin
├── hotel-booking/                   # Demo booking
├── uploads/                         # Global uploads
├── test_ai_vps.php                  # AI testing
├── test_whatsapp_ai.php             # WhatsApp AI testing
└── test_whatsapp_simple.php         # Simple WhatsApp test
```

### **Database: hotel_booking_system**

**Tables (21 total):**

| Table | Description |
|-------|-------------|
| `bookings` | Hotel reservations |
| `booking_extensions` | Booking extensions |
| `booking_guests` | Guest information per booking |
| `booking_notes` | Booking notes/comments |
| `rooms` | Hotel rooms |
| `room_photos` | Room images |
| `hotels` | Multi-hotel data |
| `hotel_info` | Hotel information |
| `hotel_amenities` | Hotel amenities |
| `hotel_services` | Hotel services |
| `users` | System users |
| `employees` | Employee data |
| `payroll` | Payroll records |
| `time_clock` | Employee time tracking |
| `income` | Income records |
| `expenses` | Expense tracking |
| `email_config` | Email configuration |
| `whatsapp_conversations` | WhatsApp chat history |
| `hotel_whatsapp_config` | WhatsApp settings |
| `bot_responses` | AI bot responses |
| `bot_prices` | Bot pricing data |

### **Accessible URLs**

#### **Main System:**
```
http://108.175.12.152/manage/
http://108.175.12.152/manage/public_booking.php  ⭐ Hotel Search
http://108.175.12.152/manage/manager_dashboard.php
http://108.175.12.152/manage/calendar_view.php
```

#### **Domains with SSL:**
```
https://samaywasipisac.com
https://api.samaywasipisac.com
https://pma.samaywasipisac.com
https://perubookingtravel.com
https://api.perubookingtravel.com
```

### **Apache Configuration**

**Sites Enabled:**
- `000-default.conf` - Default site (IP access)
- `samaywasipisac.com.conf` + SSL
- `api.samaywasipisac.com.conf` + SSL
- `pma.samaywasipisac.com.conf` + SSL
- `perubookingtravel.com.conf` + SSL
- `api.perubookingtravel.com.conf` + SSL

**Document Root:** `/var/www/html`

### **Backups**

**Location:** `/root/`

```bash
# Application backup (Python old app)
/root/hotel_app_backup_.tar.gz  (15MB)

# Database backup (PostgreSQL old db)
/root/hotel_db_backup_.sql      (5.4KB)
```

**Note:** These are backups of the OLD Python/Flask system that is no longer in use.

---

## 🤖 AI VPS Server (72.60.1.16)

### **AI Software Stack**

- **Ollama:** v0.12.6 ✅
- **Docker:** v27.5.1 ✅
- **Open WebUI:** Running in Docker ✅
- **Frappe/ERPNext:** Running (11 containers) ✅

### **Installed AI Models**

| Model | Size | Provider | Use Case |
|-------|------|----------|----------|
| **gemma2:2b** | 1.6 GB | Google | General purpose |
| **qwen2.5:1.5b** | 986 MB | Alibaba | Multilingual |
| **tinyllama** | 637 MB | Microsoft | Lightweight tasks |
| **llama3.2:1b** | 1.3 GB | Meta | Conversational AI |

### **System Resources**

- **Disk:** 96GB total (29GB used, 67GB free)
- **RAM:** 7.8GB total (2.6GB used, 5.2GB available)
- **OS:** Ubuntu 24.04 LTS

### **Docker Containers**

- Open WebUI (AI chat interface)
- ERPNext backend
- ERPNext frontend
- ERPNext scheduler
- MariaDB database
- Redis cache
- Redis queue
- Queue workers (short & long)
- WebSocket server

---

## 👥 Social VPS Server (72.61.217.65)

### **Purpose**
Future social travel network system

### **System Info**
- **Disk:** 96GB (3% used - 94GB free)
- **RAM:** 7.8GB (531MB used)
- **OS:** Ubuntu 24.04 LTS
- **Python:** 3.12.3 installed
- **pip3:** Not installed yet

### **Status**
Ready for social network deployment

---

## 🧪 Test VPS Server (212.227.241.193)

### **Purpose**
Testing and development environment

### **Status**
Active and accessible for testing deployments

---

## 🗄️ Old Python Application (Not in Use)

**Location:** `/root/hotel_app/`

### **Details**
- **Framework:** Python Flask
- **Database:** PostgreSQL (hotel_db)
- **Status:** ❌ Not actively used
- **Backup:** ✅ Created

### **Files**
```
/root/hotel_app/
├── app.py
├── wsgi.py
├── hotel_app/
│   ├── __init__.py
│   ├── models.py
│   ├── routes.py
│   ├── forms.py
│   └── templates/
├── migrations/
└── venv/
```

**Note:** Switched to PHP system. This is kept as backup.

---

## 📱 Current PHP System Features

### **Main Features Deployed**

1. ✅ **Multi-Hotel Booking System** (like Booking.com)
   - Hotel search by destination
   - Date-based availability
   - Guest count filtering
   - Price range filters
   - Category filtering

2. ✅ **WhatsApp Integration**
   - Direct booking via WhatsApp
   - AI-powered chat bot
   - Automated responses
   - Conversation history

3. ✅ **AiNi Coins Reward System**
   - 20% reward on bookings
   - Digital currency wallet
   - Redemption system

4. ✅ **Hotel Management Dashboard**
   - Calendar view
   - Room management
   - Booking management
   - Guest management

5. ✅ **Employee Management**
   - Time clock system
   - Payroll management
   - Employee records

6. ✅ **Accounting System**
   - Income tracking
   - Expense management
   - Financial reports

7. ✅ **Social Travel Network**
   - User profiles
   - Travel sharing
   - Meetups & events

8. ✅ **Receipt System**
   - PDF generation
   - Email delivery
   - Booking confirmations

---

## 🔐 Security & Access

### **SSH Keys**
All servers configured with passwordless SSH using RSA 4096-bit keys.

### **SSH Config Location**
`C:\Users\juano\.ssh\config`

### **Example SSH Config Entry**
```
Host prod-vps
  HostName 108.175.12.152
  User root
  IdentityFile ~/.ssh/hotel_prod_vps_key
```

---

## 📊 GitHub Repository

**Repository:** https://github.com/juanmorellana2021/revolutionary-hotel-platform  
**Branch:** main  
**Last Commit:** "Merge remote changes: Add AI VPS integration and multi-tenant system"

### **Recent Updates**
- AI VPS integration with TinyLlama
- RBAC (Role-Based Access Control) system
- Tenant context for multi-hotel support
- Email configuration improvements
- PDF receipt enhancements

---

## 🛠️ Common Commands

### **Server Management**

```bash
# Connect to servers
ssh prod-vps
ssh test-vps
ssh social-vps
ssh ai-vps

# Check Apache status
ssh prod-vps "systemctl status apache2"

# Restart Apache
ssh prod-vps "systemctl restart apache2"

# Check MySQL
ssh prod-vps "systemctl status mysql"

# View logs
ssh prod-vps "tail -f /var/log/apache2/error.log"
```

### **Database Operations**

```bash
# Access MySQL
ssh prod-vps "mysql -u root -p"

# Show databases
ssh prod-vps "mysql -e 'SHOW DATABASES;'"

# Show tables
ssh prod-vps "mysql -e 'USE hotel_booking_system; SHOW TABLES;'"

# Backup database
ssh prod-vps "mysqldump hotel_booking_system > /root/backup_$(date +%Y%m%d).sql"
```

### **AI Server Operations**

```bash
# List Ollama models
ssh ai-vps "ollama list"

# Run a model
ssh ai-vps "ollama run tinyllama"

# Check Docker containers
ssh ai-vps "docker ps"

# View Open WebUI logs
ssh ai-vps "docker logs open-webui"
```

### **File Management**

```bash
# Upload files to server
scp local_file.php prod-vps:/var/www/html/manage/

# Download from server
scp prod-vps:/var/www/html/manage/file.php ./

# Sync directory
rsync -avz ./local_dir/ prod-vps:/var/www/html/manage/
```

---

## 🎯 Quick Reference

### **Main URLs**

| Service | URL |
|---------|-----|
| Hotel Search | http://108.175.12.152/manage/public_booking.php |
| Dashboard | http://108.175.12.152/manage/manager_dashboard.php |
| Calendar | http://108.175.12.152/manage/calendar_view.php |
| Room Mgmt | http://108.175.12.152/manage/room_management.php |
| Accounting | http://108.175.12.152/manage/accounting_dashboard.php |
| Employees | http://108.175.12.152/manage/employee_management.php |
| WhatsApp | http://108.175.12.152/manage/whatsapp_management.php |
| Social | http://108.175.12.152/manage/travel_social.php |

### **Database Connection Info**

```php
// MySQL (Current System)
Host: localhost
Database: hotel_booking_system
User: root
Port: 3306

// PostgreSQL (Old Python App - Not in use)
Host: localhost
Database: hotel_db
User: hotel
Password: 12345
Port: 5432
```

---

## ⚠️ Important Notes

1. **Web Server:** Using Apache, NOT Nginx (Nginx is installed but not active)
2. **Old Python App:** Located in `/root/hotel_app/` but NOT in use. PHP system is current.
3. **Main Project:** All active code is in `/var/www/html/manage/`
4. **Backups:** Created before making changes to production
5. **SSL Certificates:** Configured via Let's Encrypt for all domains
6. **AI Models:** 4 models installed on AI VPS, ready for integration

---

## 🔄 Deployment Workflow

### **From Local to Production**

1. **Commit changes to GitHub:**
   ```bash
   cd C:\xampp\htdocs\testapp\revolutionary-hotel-platform-github
   git add .
   git commit -m "Description of changes"
   git push origin main
   ```

2. **Pull on server:**
   ```bash
   ssh prod-vps
   cd /var/www/html/manage
   git pull origin main
   ```

3. **Set permissions:**
   ```bash
   chown -R www-data:www-data /var/www/html/manage
   chmod -R 755 /var/www/html/manage
   ```

4. **Restart Apache if needed:**
   ```bash
   systemctl restart apache2
   ```

---

## 📞 Support & Maintenance

**For questions or issues, refer to:**
- This documentation
- GitHub repository README
- Project documentation in `/var/www/html/manage/*.md`

**Key Documentation Files:**
- `COMPREHENSIVE_README.md`
- `VPS_DEPLOYMENT_GUIDE.md`
- `WHATSAPP_SETUP_GUIDE.md`
- `CALENDAR_SYSTEM_GUIDE.md`
- `RECEIPT_SYSTEM_GUIDE.md`

---

---

## 💭 Session Memory & Project Status

### **About This Section**
Hi! I'm **Victor**, your AI assistant. This section helps me remember where we left off in our last conversation.

### **Last Session: October 24, 2025**

#### **What We Accomplished Today:**
1. ✅ Configured SSH passwordless access to all 4 VPS servers
   - prod-vps (108.175.12.152)
   - test-vps (212.227.241.193)
   - social-vps (72.61.217.65)
   - ai-vps (72.60.1.16)

2. ✅ Mapped complete server infrastructure
   - Found PHP system in `/var/www/html/manage/`
   - Confirmed Apache is the web server (NOT Nginx)
   - Verified MySQL database with 21 tables
   - Located public_booking.php (hotel search system)

3. ✅ Created backups
   - Python app backup: `/root/hotel_app_backup_.tar.gz`
   - PostgreSQL backup: `/root/hotel_db_backup_.sql`

4. ✅ Synced GitHub repository
   - Merged local and remote changes
   - Pushed all updates to main branch

5. ✅ Documented AI VPS
   - 4 AI models installed (Ollama)
   - Docker containers running
   - Open WebUI accessible

#### **Current Project State:**
- **Main System:** PHP-based hotel management platform
- **Status:** ✅ Deployed and running on production VPS
- **URL:** http://108.175.12.152/manage/public_booking.php
- **Database:** hotel_booking_system (MySQL)
- **Features Active:** 
  - Multi-hotel booking system (Booking.com style)
  - WhatsApp integration
  - AiNi Coins rewards
  - Calendar management
  - Employee & payroll system
  - Accounting dashboard

#### **What We Were Working On:**
- Verifying the hotel search/listing page exists
- Understanding server infrastructure
- Creating documentation for future sessions

#### **Next Steps / TODO:**
1. ⏳ Test the public_booking.php page in browser
2. ⏳ Verify all hotels are showing correctly
3. ⏳ Check if database has sample hotel data
4. ⏳ Configure social network on social-vps (future)
5. ⏳ Integrate AI models with WhatsApp booking system

#### **Important Decisions Made:**
- ✅ Keep Python app as backup only (not in use)
- ✅ Main system is PHP in `/var/www/html/manage/`
- ✅ Use Apache (Nginx is installed but not active)
- ✅ All SSH connections use passwordless authentication

#### **Key Context for Next Session:**
- **Your Name:** Juan Morellana
- **Repository:** https://github.com/juanmorellana2021/revolutionary-hotel-platform
- **Local Path:** `C:\xampp\htdocs\testapp\revolutionary-hotel-platform-github\`
- **You're Building:** A revolutionary hotel booking platform with AI, WhatsApp, and social features (like Booking.com + Airbnb + rewards)
- **Development Stage:** System deployed, testing and refinement phase

---

**End of Server Infrastructure Guide**

*Keep this document updated as infrastructure changes are made.*
*Victor will update the Session Memory section at the end of each conversation.*
