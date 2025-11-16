# 🏨 Revolutionary Hotel Platform

A complete hotel management ecosystem that goes beyond traditional booking systems, featuring AI integration, social travel networking, dual currency system, and comprehensive business management tools.

![Platform Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen)
![Version](https://img.shields.io/badge/Version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1)

## 🚀 **What Makes This Revolutionary?**

This isn't just a hotel booking system—it's a complete hospitality ecosystem that transforms how hotels operate and guests experience travel.

### 🌟 **Core Innovation**
- **AI-Powered Guest Services** with intelligent chatbot
- **Social Travel Network** connecting like-minded travelers
- **Dual Digital Currency** (AiNi Coins & Loyalty Points)
- **WhatsApp Business Integration** for seamless communication
- **Multi-Hotel Platform** architecture for scalability

## 🚀 **Quick Start Setup**

> 📖 **New to the AI Assistant?** Check out [HOW_TO_USE_AI_ASSISTANT.md](HOW_TO_USE_AI_ASSISTANT.md) for a comprehensive guide on how to work with GitHub Copilot on this project!

### Step 1: Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Click **Start** for both:
   - **Apache** (Web Server)
   - **MySQL** (Database)

### Step 2: Create Database
1. Open your web browser
2. Go to: `http://localhost/phpmyadmin`
3. Click **"Import"** tab
4. Click **"Choose File"** and select: `C:\xampp\htdocs\hotel-booking-system\database.sql`
5. Click **"Go"** to create the database and tables
6. Then import: `C:\xampp\htdocs\hotel-booking-system\hotel_setup.sql` (follow same steps)

### Step 3: Access the System
1. Open your web browser
2. Go to: `http://localhost/hotel-booking-system`

## � **Key Features**

### 🤖 **AI Integration**
- **Intelligent Chatbot** powered by Ollama (Gemma3:1b model)
- **Multi-language Support** for international guests
- **Context-aware Responses** for personalized service
- **Automated Booking Assistance** 24/7

### 📱 **WhatsApp Business System**
- **Automated Booking Confirmations** via WhatsApp
- **Receipt Delivery** through messaging
- **Guest Service Automation** 
- **Real-time Communication** with hotel staff

### 🌐 **Social Travel Network**
- **Location-based Matching** of travelers with similar interests
- **AiNi Rewards Integration** for social interactions
- **Travel Experience Sharing** platform
- **Community Building Tools** for repeat guests

### 💰 **Dual Currency System**
- **AiNi Coins**: Digital currency for bookings and services
- **Loyalty Points**: Reward system for guest retention
- **USD/PEN Support**: Dual currency pricing
- **Exchange System**: Seamless currency conversion

### 🧾 **Professional Receipt System**
- **Multiple Formats**: HTML, Print, PDF, Email
- **Payment Tracking**: Pending, Paid, Partial, Refunded status
- **Professional Branding** with hotel information
- **Email Integration** with SMTP configuration

### 📊 **Business Management**
- **Dynamic Pricing** with guest count logic
- **Employee Management** and performance tracking
- **Comprehensive Accounting** with financial reports
- **Inventory Management** for hotel resources
- **Photo Management** with room galleries

## 🏨 **System Access**

### For Hotel Managers:
- **Manager Login**: manager@hotel.com / manager123
- **Hotel Information Setup**: Name, location, contact details
- **Operational Settings**: Check-in/out times, total rooms
- **Services Management**: Enable/disable hotel services
- **Amenities Control**: Manage available amenities
- **Dashboard Analytics**: Booking statistics and revenue
- **Guest Management**: View all users and bookings

### For Guests:
- **User Registration**: Create personal accounts
- **Room Browsing**: View available rooms and pricing
- **Booking System**: Make reservations with date selection
- **Booking History**: Track all past and current bookings
- **User Dashboard**: Personalized booking management

## 🏗️ **Technical Architecture**

### **Backend Stack**
- **PHP 8.0+** - Modern server-side development
- **MySQL 8.0+** - Robust database management
- **RESTful API** - Scalable service architecture
- **XAMPP Stack** - Local development environment

### **AI & Integration**
- **Ollama AI** - Local AI model hosting
- **WhatsApp Business API** - Official messaging integration
- **SMTP Email** - Professional communication
- **PDF Generation** - Document automation

### **Frontend Technologies**
- **Responsive HTML/CSS** - Mobile-optimized design
- **JavaScript** - Dynamic user interactions
- **Bootstrap Integration** - Professional UI components
- **Ajax Communication** - Seamless user experience

## 📁 **Key Files**

```
📊 Core System
├── calendar_view.php          # Main booking calendar
├── manager_dashboard.php      # Management interface
├── room_management.php        # Room configuration
└── hotelcoin_admin.php       # Currency management

🧾 Receipt System
├── receipt_handler.php        # Unified receipt management
├── includes/ReceiptPDFGenerator.php
└── includes/ReceiptEmailSender.php

🤖 AI Integration
├── ai_chat_api.php           # AI chatbot endpoint
├── includes/ollama_ai.php    # AI service integration
└── includes/ai_chat_widget.php # Frontend chat widget

📱 WhatsApp System
├── whatsapp_webhook.php      # WhatsApp API endpoint
├── whatsapp_management.php   # WhatsApp configuration
└── includes/whatsapp_bot.php # Bot logic

🌐 Public Platform
├── public_booking.php        # Public booking interface
├── travel_social.php         # Social network platform
└── hotel_details.php         # Hotel information page
```

## 📋 **Default Hotel Services Included:**
- 24/7 Front Desk
- Room Service
- Housekeeping
- Concierge Service
- Wake-up Calls
- Luggage Storage
- Express Check-in/out
- Business Center

## 🏊 **Default Hotel Amenities Included:**
- Free WiFi
- Swimming Pool
- Fitness Center
- Spa & Wellness
- Restaurant
- Bar/Lounge
- Parking
- Pet Friendly
- Airport Shuttle
- Meeting Rooms
- Laundry Service
- Safe Deposit Box

## 🔒 **Security Features:**
- **Password Hashing**: Secure PHP password_hash()
- **SQL Injection Protection**: PDO prepared statements
- **Session Management**: Secure user sessions
- **Role-Based Access**: Manager vs Guest permissions
- **Input Validation**: Server-side form validation

## 📊 **Manager Dashboard Features:**
- **Real-time Statistics**: Bookings, revenue, occupancy
- **Recent Bookings**: Latest reservation activity
- **User Management**: View all registered users
- **Room Overview**: All rooms with pricing
- **Quick Actions**: Easy access to key functions

## 🎯 **Usage Examples**

### **Creating a Booking with Payment**
- Dynamic pricing based on guest count
- Single occupancy discounts
- Extra bed pricing
- Custom pricing adjustments
- Payment status tracking
- Automatic receipt generation

### **AI Chatbot Integration**
- 24/7 intelligent guest assistance
- Multi-language support
- Context-aware responses
- Automated booking help

### **WhatsApp Business Features**
- Automatic booking confirmations
- Receipt delivery via messaging
- Real-time guest communication
- Service automation

## 📈 **Business Impact**

### **Revenue Optimization**
- **Dynamic Pricing**: Automatic rate adjustments
- **Loyalty Program**: Guest retention through AiNi Coins
- **Direct Bookings**: Reduced OTA commissions
- **AI Upselling**: Intelligent service recommendations

### **Operational Efficiency**
- **Automation**: AI and WhatsApp reduce manual tasks
- **Real-time Data**: Instant occupancy and financial metrics
- **Staff Management**: Performance tracking and optimization
- **Guest Satisfaction**: 24/7 support and instant communication

## 🎯 **Testing Scenarios:**

### Test Manager Functions:
1. Login as manager (manager@hotel.com / manager123)
2. Complete hotel setup with your hotel information
3. Enable/disable services and amenities
4. View dashboard statistics
5. Monitor guest bookings

### Test Guest Functions:
1. Register as a new guest
2. Browse available rooms
3. Create sample bookings
4. View booking history
5. Test check-in/out date validation

## 📱 **Responsive Design:**
- Works on desktop, tablet, and mobile
- Professional gradient designs
- Intuitive user interface
- Modern card-based layouts

## 🌟 **What's Next?**

- **Mobile Apps**: Native iOS and Android applications
- **Advanced Analytics**: Machine learning insights
- **Integration Marketplace**: Third-party service connections
- **Multi-language**: Full internationalization support
- **Enterprise Features**: Advanced multi-property management

---

## 👨‍💻 **Contact**

**Developer**: juanmorellana2021@gmail.com

---

⭐ **Star this repository** if you find it interesting!

*"Transforming hospitality through technology, one booking at a time."* 🏨✨

**🎉 Your revolutionary hotel platform is ready to transform hospitality!** 

This complete ecosystem provides everything needed for modern hotel management with AI-powered automation, social networking, and professional business operations.