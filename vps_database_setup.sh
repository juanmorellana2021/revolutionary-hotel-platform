#!/bin/bash

# 🗄️ Database Import and Configuration Script
# Run this after the main deployment script

echo "🗄️ Setting up Hotel Booking System Database..."
echo "============================================="

# Check if we're in the right directory
if [ ! -f "database.sql" ]; then
    echo "❌ database.sql not found. Make sure you're in the hotel-booking-system directory."
    exit 1
fi

# Import the database
echo "📦 Importing database schema..."
mysql -u hotel_user -pHotel@2025! hotel_booking_system < database.sql

# Check if there are additional SQL files to import
if [ -f "hotel_booking_system_production.sql" ]; then
    echo "📦 Importing production database..."
    mysql -u hotel_user -pHotel@2025! hotel_booking_system < hotel_booking_system_production.sql
fi

if [ -f "setup-database.sql" ]; then
    echo "📦 Running setup database script..."
    mysql -u hotel_user -pHotel@2025! hotel_booking_system < setup-database.sql
fi

# Run any additional setup scripts
if [ -f "setup_multi_room.php" ]; then
    echo "🏨 Setting up multi-room functionality..."
    php setup_multi_room.php
fi

# Update database configuration in the main system
echo "⚙️ Updating database configuration..."
sed -i 's/private $host = "localhost";/private $host = "localhost";/' includes/classes.php
sed -i 's/private $username = "root";/private $username = "hotel_user";/' includes/classes.php
sed -i 's/private $password = "";/private $password = "Hotel@2025!";/' includes/classes.php
sed -i 's/private $database = "hotel_booking_system";/private $database = "hotel_booking_system";/' includes/classes.php

# Test the database connection
echo "🧪 Testing database connection..."
php vps_db_config.php

echo ""
echo "✅ Database setup complete!"
echo "=========================="
echo "🌐 Visit http://108.175.12.152 to access your hotel booking system"
echo ""