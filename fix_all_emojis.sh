#!/bin/bash
# Comprehensive emoji fix for manager_dashboard.php

sed -i \
  -e 's/???? Revolutionary Feature Available!/🚀 Revolutionary Feature Available!/g' \
  -e 's/???? Full Setup/🔧 Full Setup/g' \
  -e 's/??? Quick Start/⚡ Quick Start/g' \
  -e 's/???? Recent Bookings/📋 Recent Bookings/g' \
  -e 's/??? Quick Actions/⚡ Quick Actions/g' \
  -e 's/???? Setup Hotel Info/🏨 Setup Hotel Info/g' \
  -e 's/??????? View Guest Experience/👁️ View Guest Experience/g' \
  -e 's/???? Refresh Dashboard/🔄 Refresh Dashboard/g' \
  -e 's/???? Recent Users/👥 Recent Users/g' \
  -e 's/???? Room Overview/🏠 Room Overview/g' \
  -e 's/<div class="h1 mb-3">???<\/div>/<div class="h1 mb-3">✅<\/div>/g' \
  -e "s/str_repeat('???'/str_repeat('⭐'/g" \
  /var/www/html/manager_dashboard.php

echo "All emoji fixes applied!"
