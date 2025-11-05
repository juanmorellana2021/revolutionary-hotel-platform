#!/bin/bash
# Fix emoji question marks in manager_dashboard.php

sed -i \
  -e 's/????Welcome to Hotel Management!/👋 Welcome to Hotel Management!/g' \
  -e 's/Click here to complete your hotel setup ???/Click here to complete your hotel setup ➡️/g' \
  -e 's/<div class="banner-icon">????<\/div>/<div class="banner-icon">📱<\/div>/g' \
  -e 's/????Revolutionary Feature Available!/🚀 Revolutionary Feature Available!/g' \
  -e 's/????Quick Start/⚡ Quick Start/g' \
  -e 's/????Full Setup/🔧 Full Setup/g' \
  -e 's/<div class="h1 mb-3">????<\/div>/<div class="h1 mb-3">📊<\/div>/g' \
  /var/www/html/manager_dashboard.php

echo "Emoji fixes applied!"
