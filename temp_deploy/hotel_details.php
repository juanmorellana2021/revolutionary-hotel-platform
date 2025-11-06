<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Details - AiNi Travel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(102, 126, 234, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .hotel-header {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .hotel-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .hotel-info h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .hotel-meta {
            display: flex;
            gap: 2rem;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .location {
            color: #666;
            font-size: 1.1rem;
        }

        .booking-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            min-width: 300px;
        }

        .price-info {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .price-note {
            opacity: 0.9;
        }

        .aini-reward {
            background: rgba(255,255,255,0.2);
            padding: 10px 15px;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .book-now-btn {
            width: 100%;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 15px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .book-now-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .section h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .rooms-grid {
            display: grid;
            gap: 1.5rem;
        }

        .room-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .room-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .room-type {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .room-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #667eea;
        }

        .room-amenities {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .room-amenity {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .select-room-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .select-room-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .review-item {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1.5rem;
        }

        .review-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: #333;
        }

        .review-rating {
            color: #ffa726;
            font-weight: 600;
        }

        .review-date {
            color: #666;
            font-size: 0.9rem;
        }

        .whatsapp-widget {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
        }

        .whatsapp-widget h3 {
            margin-bottom: 1rem;
        }

        .whatsapp-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
        }

        .whatsapp-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .hotel-title {
                flex-direction: column;
                align-items: stretch;
            }
            
            .hotel-meta {
                justify-content: center;
            }
            
            .amenities-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                🪙 AiNi Travel
            </div>
            <a href="public_booking.php" class="back-btn">← Back to Hotels</a>
        </div>
    </header>

    <div class="container">
        <!-- Hotel Header -->
        <div id="hotelHeader" class="hotel-header">
            <div class="loading">
                <div class="spinner"></div>
                Loading hotel details...
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Description -->
                <div id="hotelDescription" class="section" style="display: none;">
                    <h2>🏨 About This Hotel</h2>
                    <p id="description"></p>
                </div>

                <!-- Amenities -->
                <div id="hotelAmenities" class="section" style="display: none;">
                    <h2>🌟 Amenities & Features</h2>
                    <div id="amenitiesGrid" class="amenities-grid"></div>
                </div>

                <!-- Available Rooms -->
                <div id="hotelRooms" class="section" style="display: none;">
                    <h2>🛏️ Available Rooms</h2>
                    <div id="roomsGrid" class="rooms-grid"></div>
                </div>

                <!-- Reviews -->
                <div id="hotelReviews" class="section" style="display: none;">
                    <h2>⭐ Guest Reviews</h2>
                    <div id="reviewsList" class="reviews-list"></div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Booking Card -->
                <div id="bookingCard" class="booking-card" style="display: none;">
                    <div class="price-info">
                        <div class="price" id="hotelPrice">$0</div>
                        <div class="price-note">per night</div>
                    </div>
                    <div class="aini-reward" id="ainiReward">
                        🪙 0 AiNi Coins per night
                    </div>
                    <button class="book-now-btn" onclick="bookHotelDirect()">
                        📱 Book via WhatsApp
                    </button>
                </div>

                <!-- WhatsApp Widget -->
                <div id="whatsappWidget" class="whatsapp-widget" style="display: none;">
                    <h3>📱 Instant Booking</h3>
                    <p>Book directly through WhatsApp with our AI assistant</p>
                    <a href="#" id="whatsappLink" class="whatsapp-btn">
                        Start WhatsApp Chat
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentHotel = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hotelId = urlParams.get('hotel_id');
            
            if (!hotelId) {
                showError('Hotel ID not provided');
                return;
            }
            
            loadHotelDetails(hotelId);
        });

        function loadHotelDetails(hotelId) {
            fetch(`public_booking_api.php?action=get_hotel_details&hotel_id=${hotelId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentHotel = data.hotel;
                        displayHotelDetails(data.hotel);
                    } else {
                        showError(data.error || 'Hotel not found');
                    }
                })
                .catch(error => {
                    console.error('Error loading hotel details:', error);
                    showError('Failed to load hotel details. Please try again.');
                });
        }

        function displayHotelDetails(hotel) {
            // Update page title
            document.title = `${hotel.name} - AiNi Travel`;
            
            // Display hotel header
            displayHotelHeader(hotel);
            
            // Display sections
            displayDescription(hotel);
            displayAmenities(hotel);
            displayRooms(hotel);
            displayReviews(hotel);
            displayBookingCard(hotel);
            displayWhatsAppWidget(hotel);
        }

        function displayHotelHeader(hotel) {
            const headerHtml = `
                <div class="hotel-title">
                    <div class="hotel-info">
                        <h1>${hotel.name}</h1>
                        <div class="hotel-meta">
                            <div class="rating">
                                ⭐ ${hotel.avg_rating} (${hotel.review_count} reviews)
                            </div>
                            <div class="location">
                                📍 ${hotel.location}, ${hotel.city}, ${hotel.state}
                            </div>
                            <div class="category">
                                🏷️ ${hotel.category.charAt(0).toUpperCase() + hotel.category.slice(1)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('hotelHeader').innerHTML = headerHtml;
        }

        function displayDescription(hotel) {
            if (hotel.description) {
                document.getElementById('description').textContent = hotel.description;
                document.getElementById('hotelDescription').style.display = 'block';
            }
        }

        function displayAmenities(hotel) {
            if (hotel.amenities && hotel.amenities.length > 0) {
                const amenitiesHtml = hotel.amenities.map(amenity => `
                    <div class="amenity-item">
                        <span>✅</span>
                        <span>${amenity.trim()}</span>
                    </div>
                `).join('');
                
                document.getElementById('amenitiesGrid').innerHTML = amenitiesHtml;
                document.getElementById('hotelAmenities').style.display = 'block';
            }
        }

        function displayRooms(hotel) {
            if (hotel.rooms && hotel.rooms.length > 0) {
                const roomsHtml = hotel.rooms.map(room => `
                    <div class="room-card">
                        <div class="room-header">
                            <div class="room-type">${room.room_type}</div>
                            <div class="room-price">$${room.base_price}/night</div>
                        </div>
                        <p class="room-description">${room.description || 'Comfortable room with modern amenities'}</p>
                        <div class="room-amenities">
                            ${(room.amenities || []).map(amenity => `
                                <span class="room-amenity">${amenity.trim()}</span>
                            `).join('')}
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <small>👥 Up to ${room.max_occupancy} guests</small><br>
                                <small>🪙 ${room.aini_reward} AiNi coins per night</small>
                            </div>
                            <button class="select-room-btn" onclick="selectRoom(${room.room_id}, '${room.room_type}', ${room.base_price})">
                                Select Room
                            </button>
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('roomsGrid').innerHTML = roomsHtml;
                document.getElementById('hotelRooms').style.display = 'block';
            }
        }

        function displayReviews(hotel) {
            if (hotel.reviews && hotel.reviews.length > 0) {
                const reviewsHtml = hotel.reviews.map(review => `
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-name">${review.guest_name}</div>
                            <div class="review-rating">⭐ ${review.rating}</div>
                        </div>
                        <div class="review-date">${new Date(review.created_at).toLocaleDateString()}</div>
                        <p style="margin-top: 0.5rem;">${review.comment}</p>
                    </div>
                `).join('');
                
                document.getElementById('reviewsList').innerHTML = reviewsHtml;
                document.getElementById('hotelReviews').style.display = 'block';
            }
        }

        function displayBookingCard(hotel) {
            const minPrice = hotel.rooms && hotel.rooms.length > 0 
                ? Math.min(...hotel.rooms.map(r => r.base_price))
                : 0;
            const ainiReward = Math.round(minPrice * 0.2);
            
            document.getElementById('hotelPrice').textContent = `$${minPrice}`;
            document.getElementById('ainiReward').textContent = `🪙 ${ainiReward} AiNi Coins per night`;
            document.getElementById('bookingCard').style.display = 'block';
        }

        function displayWhatsAppWidget(hotel) {
            if (hotel.whatsapp_number) {
                const whatsappUrl = generateWhatsAppURL(hotel);
                document.getElementById('whatsappLink').href = whatsappUrl;
                document.getElementById('whatsappWidget').style.display = 'block';
            }
        }

        function generateWhatsAppURL(hotel) {
            const message = `Hi! 🏨 I'm interested in booking ${hotel.name} in ${hotel.city}. Can you help me with availability and AiNi coin rewards? 🪙`;
            const phoneNumber = hotel.whatsapp_number.replace(/[^0-9]/g, '');
            return `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
        }

        function selectRoom(roomId, roomType, price) {
            const message = `Hi! 🏨 I'd like to book the ${roomType} at ${currentHotel.name} for $${price}/night. Can you help me with the booking and AiNi coin rewards? 🪙`;
            const phoneNumber = currentHotel.whatsapp_number.replace(/[^0-9]/g, '');
            window.open(`https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`, '_blank');
        }

        function bookHotelDirect() {
            if (currentHotel && currentHotel.whatsapp_number) {
                const whatsappUrl = generateWhatsAppURL(currentHotel);
                window.open(whatsappUrl, '_blank');
            } else {
                alert('WhatsApp booking not available for this hotel. Please try another booking method.');
            }
        }

        function showError(message) {
            document.getElementById('hotelHeader').innerHTML = `
                <div class="error-message">
                    <h2>❌ Error</h2>
                    <p>${message}</p>
                    <br>
                    <a href="public_booking.php" class="back-btn">← Back to Hotels</a>
                </div>
            `;
        }
    </script>
</body>
</html>