#!/bin/bash
# Fix the toggle map function to use grid layout

FILE="/var/www/html/manage/public_booking.php"

# Add hotelsList variable after line 2323
sed -i '2323a\            const hotelsList = document.getElementById("hotelsList");' $FILE

# Add grid layout code after expanding panel (around line 2335)
sed -i '/hotelListPanel.style.width = .100%/a\                \n                // Change to GRID layout\n                hotelsList.style.display = "grid";\n                hotelsList.style.gridTemplateColumns = "repeat(auto-fill, minmax(400px, 1fr))";\n                hotelsList.style.gap = "1.5rem";' $FILE

# Add block layout reset in else section (around line 2350)
sed -i '/hotelListPanel.style.width = .50%/a\                \n                // Reset to BLOCK layout\n                hotelsList.style.display = "block";' $FILE

echo "Toggle function updated with grid layout!"
