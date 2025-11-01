#!/bin/bash
# Setup hotels table on production VPS
# Run this script to create the hotels table and insert testing data

echo "🏨 Setting up Hotels Table on Production VPS..."
echo "================================================"

# SSH into VPS and run SQL
ssh prod-vps << 'ENDSSH'
cd /var/www/html/manage

# Import SQL file
mysql -u root hotel_booking_system < setup_hotels_table.sql

if [ $? -eq 0 ]; then
    echo "✅ Hotels table created successfully!"
    echo ""
    echo "📊 Verifying data..."
    mysql -u root hotel_booking_system -e "SELECT id, name, location, price FROM hotels;"
    echo ""
    echo "🎉 Setup complete! You now have 6 hotels in the database."
else
    echo "❌ Error setting up hotels table"
    exit 1
fi
ENDSSH

echo ""
echo "✅ All done! The hotels are now loaded from database."
