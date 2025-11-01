// Patch for map toggle - add this after line with hotelsList ID
const hotelsList = document.getElementById('hotelsList');

// In the if (mapHidden) section, after hotelListPanel.style.width = '100%';
// Add:
hotelsList.style.display = 'grid';
hotelsList.style.gridTemplateColumns = 'repeat(auto-fill, minmax(400px, 1fr))';
hotelsList.style.gap = '1.5rem';

// In the else section, after hotelListPanel.style.width = '50%';
// Add:
hotelsList.style.display = 'block';
hotelsList.style.gridTemplateColumns = 'none';
