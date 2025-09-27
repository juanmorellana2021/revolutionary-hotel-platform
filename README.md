# Hotel Booking System - Setup Instructions

## 🚀 Complete Setup Guide

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

## 🏨 System Features

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

## 📋 Default Hotel Services Included:
- 24/7 Front Desk
- Room Service
- Housekeeping
- Concierge Service
- Wake-up Calls
- Luggage Storage
- Express Check-in/out
- Business Center

## 🏊 Default Hotel Amenities Included:
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

## 🔒 Security Features:
- **Password Hashing**: Secure PHP password_hash()
- **SQL Injection Protection**: PDO prepared statements
- **Session Management**: Secure user sessions
- **Role-Based Access**: Manager vs Guest permissions
- **Input Validation**: Server-side form validation

## 📊 Manager Dashboard Features:
- **Real-time Statistics**: Bookings, revenue, occupancy
- **Recent Bookings**: Latest reservation activity
- **User Management**: View all registered users
- **Room Overview**: All rooms with pricing
- **Quick Actions**: Easy access to key functions

## 🎯 Testing Scenarios:

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

## 📱 Responsive Design:
- Works on desktop, tablet, and mobile
- Professional gradient designs
- Intuitive user interface
- Modern card-based layouts

## 💡 Next Steps:
- Add room images
- Email confirmation system
- Payment gateway integration
- Calendar view interface
- Advanced reporting features
- Multi-language support

---

**🎉 Your professional hotel booking system is ready to use!** 

The system uses real MySQL database storage, secure PHP backend, and provides a complete hotel management solution.