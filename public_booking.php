<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AiNi Travel - Revolutionary Hotel Booking with AI & WhatsApp</title>
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
            font-size: 2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .cta-button {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .cta-button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .hero {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f0f2f5" width="1200" height="600"/><circle fill="%23667eea" opacity="0.1" cx="200" cy="100" r="80"/><circle fill="%23764ba2" opacity="0.1" cx="800" cy="200" r="120"/><circle fill="%23667eea" opacity="0.1" cx="1000" cy="400" r="100"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 6rem 0;
            text-align: center;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 3rem;
            opacity: 0.95;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-box {
            background: white;
            border-radius: 25px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            display: flex;
            flex-direction: column;
        }

        .search-input label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .search-input input,
        .search-input select {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-input input:focus,
        .search-input select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            height: fit-content;
            margin-top: 1.5rem;
        }

        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .features {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .features h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .features-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 4rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .feature-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 1px solid #e9ecef;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            display: block;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        .hotels-section {
            padding: 6rem 0;
            background: white;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .section-header p {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .hotel-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #e9ecef;
        }

        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .hotel-image {
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            position: relative;
        }

        .hotel-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.9);
            color: #667eea;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .hotel-info {
            padding: 2rem;
        }

        .hotel-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .hotel-location {
            color: #666;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hotel-features {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .feature-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .hotel-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
        }

        .price-info {
            display: flex;
            flex-direction: column;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .price-note {
            font-size: 0.8rem;
            color: #666;
        }

        .aini-reward {
            background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%);
            color: #b8860b;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .book-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .whatsapp-section {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            padding: 6rem 0;
            text-align: center;
        }

        .whatsapp-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .whatsapp-section h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .whatsapp-section p {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            opacity: 0.95;
        }

        .whatsapp-demo {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            backdrop-filter: blur(10px);
        }

        .chat-preview {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            max-width: 400px;
            margin: 0 auto;
            text-align: left;
        }

        .message {
            margin-bottom: 1rem;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 80%;
        }

        .guest-message {
            background: #e3f2fd;
            color: #333;
            margin-left: auto;
            text-align: right;
        }

        .ai-message {
            background: #e8f5e8;
            color: #333;
        }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            color: #ecf0f1;
        }

        .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #667eea;
        }

        .footer-bottom {
            border-top: 1px solid #34495e;
            margin-top: 2rem;
            padding-top: 2rem;
            text-align: center;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .search-box {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .hotels-grid {
                grid-template-columns: 1fr;
            }
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                🪙 AiNi Travel
            </div>
            <nav class="nav-links">
                <a href="#home">Home</a>
                <a href="#hotels">Hotels</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
                <a href="travel_social.php">🌍 Travel Social</a>
                <a href="index.php" class="cta-button">Sign In</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>🚀 Revolutionary Hotel Booking</h1>
            <p>Book hotels through WhatsApp, earn AiNi coins, connect with travelers worldwide. Experience the future of travel with AI-powered assistance and social discovery.</p>
            
            <!-- Search Box -->
            <div class="search-box">
                <div class="search-input">
                    <label>🏨 Destination</label>
                    <input type="text" id="destination" placeholder="Where are you going?">
                </div>
                <div class="search-input">
                    <label>📅 Check-in</label>
                    <input type="date" id="checkin">
                </div>
                <div class="search-input">
                    <label>📅 Check-out</label>
                    <input type="date" id="checkout">
                </div>
                <div class="search-input">
                    <label>👥 Guests</label>
                    <select id="guests">
                        <option>1 Guest</option>
                        <option>2 Guests</option>
                        <option>3 Guests</option>
                        <option>4 Guests</option>
                        <option>5+ Guests</option>
                    </select>
                </div>
                <button class="search-btn" onclick="searchHotels()">
                    🔍 Search Hotels
                </button>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>🌟 Why Choose AiNi Travel?</h2>
            <p class="features-subtitle">The world's first platform combining hotel booking, social travel, and digital rewards</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">📱</span>
                    <h3>WhatsApp Booking</h3>
                    <p>Book hotels directly through WhatsApp chat. No apps to download, just natural conversation with our AI assistant.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🪙</span>
                    <h3>Earn AiNi Coins</h3>
                    <p>Get rewarded with AiNi coins for every booking. Use them for future stays, upgrades, and exclusive experiences.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🌍</span>
                    <h3>Social Travel Network</h3>
                    <p>Connect with fellow travelers, join meetups, share experiences, and discover hidden gems together.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🤖</span>
                    <h3>AI Travel Assistant</h3>
                    <p>Get personalized recommendations, instant support, and smart travel planning powered by advanced AI.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">💰</span>
                    <h3>Best Price Guarantee</h3>
                    <p>We offer the lowest rates plus AiNi coin rewards. Find a better price? We'll match it and give you extra coins.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🌟</span>
                    <h3>Exclusive Experiences</h3>
                    <p>Access member-only deals, room upgrades, and unique local experiences you won't find anywhere else.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Hotels Section -->
    <section class="hotels-section" id="hotels">
        <div class="container">
            <div class="section-header">
                <h2>🏨 Featured Hotels</h2>
                <p>Discover amazing properties and earn AiNi coins with every stay</p>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn active" onclick="filterHotels('all')">All Hotels</button>
                <button class="filter-btn" onclick="filterHotels('luxury')">🌟 Luxury</button>
                <button class="filter-btn" onclick="filterHotels('budget')">💰 Budget</button>
                <button class="filter-btn" onclick="filterHotels('business')">💼 Business</button>
                <button class="filter-btn" onclick="filterHotels('family')">👨‍👩‍👧‍👦 Family</button>
                <button class="filter-btn" onclick="filterHotels('beach')">🏖️ Beach</button>
            </div>
            
            <!-- Hotels Grid -->
            <div class="hotels-grid" id="hotelsGrid">
                <div class="loading">
                    <div class="spinner"></div>
                    Loading amazing hotels for you...
                </div>
            </div>
        </div>
    </section>

    <!-- WhatsApp Section -->
    <section class="whatsapp-section">
        <div class="whatsapp-content">
            <h2>📱 Book Through WhatsApp - It's Revolutionary!</h2>
            <p>Experience the future of hotel booking with natural conversation and instant AI assistance</p>
            
            <div class="whatsapp-demo">
                <div class="chat-preview">
                    <div class="message guest-message">
                        Hi! I need a hotel in Miami for this weekend
                    </div>
                    <div class="message ai-message">
                        🏨 Perfect! I found great options in Miami for this weekend:
                        
                        ⭐ Ocean View Resort - $180/night
                        Earn 36 AiNi coins per night!
                        
                        Which dates work best for you? 😊
                    </div>
                    <div class="message guest-message">
                        That sounds perfect! Book it please
                    </div>
                    <div class="message ai-message">
                        🎉 Booked! Confirmation sent.
                        You earned 72 AiNi coins!
                        
                        Connect with 12 travelers also visiting Miami this weekend? 🌍
                    </div>
                </div>
            </div>
            
            <a href="https://wa.me/1234567890" class="cta-button" style="font-size: 1.1rem; padding: 15px 30px;">
                📱 Start Booking on WhatsApp
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>🪙 AiNi Travel</h3>
                <p>Revolutionary hotel booking with AI, WhatsApp integration, and social travel features.</p>
                <br>
                <p>Earn AiNi coins, connect with travelers, experience the future of hospitality.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#home">Home</a>
                <a href="#hotels">Hotels</a>
                <a href="travel_social.php">Travel Social</a>
                <a href="wallet.php">AiNi Wallet</a>
                <a href="about.php">About Us</a>
            </div>
            
            <div class="footer-section">
                <h3>Support</h3>
                <a href="help.php">Help Center</a>
                <a href="contact.php">Contact Us</a>
                <a href="whatsapp_setup_wizard.php">WhatsApp Booking</a>
                <a href="faq.php">FAQ</a>
            </div>
            
            <div class="footer-section">
                <h3>Connect</h3>
                <a href="https://wa.me/1234567890">📱 WhatsApp</a>
                <a href="mailto:support@ainitravel.com">📧 Email</a>
                <a href="tel:+1234567890">📞 Phone</a>
                <a href="travel_social.php">🌍 Social Network</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 AiNi Travel Platform. All rights reserved. | Revolutionary Hotel Booking with AI & Social Features</p>
        </div>
    </footer>

    <script>
        // Set default dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            document.getElementById('checkin').value = today.toISOString().split('T')[0];
            document.getElementById('checkout').value = tomorrow.toISOString().split('T')[0];
            
            // Load hotels
            loadFeaturedHotels();
        });

        function searchHotels() {
            const destination = document.getElementById('destination').value;
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const guests = document.getElementById('guests').value;
            
            if (!destination) {
                alert('Please enter a destination');
                return;
            }
            
            if (!checkin || !checkout) {
                alert('Please select check-in and check-out dates');
                return;
            }
            
            // Show loading
            document.getElementById('hotelsGrid').innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Searching hotels in ${destination}...
                </div>
            `;
            
            // Simulate search (replace with actual API call)
            setTimeout(() => {
                loadHotelsForDestination(destination, checkin, checkout, guests);
            }, 1500);
        }

        function loadFeaturedHotels() {
            fetch('public_booking_api.php?action=get_featured_hotels')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayHotels(data.hotels);
                    } else {
                        // Fallback to sample data if API fails
                        const sampleHotels = [
                            {
                                hotel_id: 1,
                                name: "Ocean View Resort",
                                location: "Miami Beach, FL",
                                image_emoji: "🏖️",
                                avg_rating: 4.8,
                                price: 180,
                                amenities: ["Ocean View", "Pool", "WiFi", "Spa"],
                                aini_reward: 36,
                                category: "luxury beach"
                            },
                            {
                                hotel_id: 2,
                                name: "Downtown Business Hotel",  
                                location: "New York, NY",
                                image_emoji: "🏙️",
                                avg_rating: 4.6,
                                price: 220,
                                amenities: ["Business Center", "Gym", "WiFi", "Restaurant"],
                                aini_reward: 44,
                                category: "business luxury"
                            },
                            {
                                hotel_id: 3,
                                name: "Budget Traveler Inn",
                                location: "Austin, TX", 
                                image_emoji: "🏨",
                                avg_rating: 4.3,
                                price: 85,
                                amenities: ["WiFi", "Parking", "Breakfast"],
                                aini_reward: 17,
                                category: "budget"
                            }
                        ];
                        displayHotels(sampleHotels);
                    }
                })
                .catch(error => {
                    console.error('Error loading hotels:', error);
                    // Fallback to sample data
                    const sampleHotels = [
                        {
                            hotel_id: 1,
                            name: "Sample Hotel Resort",
                            location: "Demo City, State",
                            image_emoji: "�",
                            avg_rating: 4.5,
                            price: 150,
                            amenities: ["WiFi", "Pool", "Restaurant"],
                            aini_reward: 30,
                            category: "luxury"
                        }
                    ];
                    displayHotels(sampleHotels);
                });
        }

        function loadHotelsForDestination(destination, checkin, checkout, guests) {
            const params = new URLSearchParams({
                action: 'search_hotels',
                destination: destination,
                check_in: checkin,
                check_out: checkout,
                guests: guests
            });
            
            fetch(`public_booking_api.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.hotels);
                    } else {
                        document.getElementById('hotelsGrid').innerHTML = `
                            <div class="loading">
                                <p>No hotels found for "${destination}". Try a different destination or dates.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error searching hotels:', error);
                    document.getElementById('hotelsGrid').innerHTML = `
                        <div class="loading">
                            <p>Search temporarily unavailable. Please try again later.</p>
                        </div>
                    `;
                });
        }
        
        function displaySearchResults(hotels) {
            if (hotels.length === 0) {
                document.getElementById('hotelsGrid').innerHTML = `
                    <div class="loading">
                        <p>No hotels found for your search criteria. Try different dates or destination.</p>
                    </div>
                `;
                return;
            }
            
            displayHotels(hotels.map(hotel => ({
                hotel_id: hotel.hotel_id,
                name: hotel.name,
                location: hotel.location,
                image_emoji: getHotelEmoji(hotel.category),
                avg_rating: hotel.avg_rating,
                price: hotel.min_price,
                amenities: hotel.amenities.slice(0, 4),
                aini_reward: hotel.aini_reward,
                category: hotel.category,
                whatsapp_number: hotel.whatsapp_number,
                available: hotel.available,
                whatsapp_booking_url: hotel.whatsapp_booking_url
            })));
        }
        
        function getHotelEmoji(category) {
            const emojis = {
                'luxury': '🌟',
                'beach': '🏖️',
                'business': '🏙️',
                'family': '🎡',
                'budget': '🏠',
                'resort': '🏖️',
                'boutique': '🏛️'
            };
            return emojis[category] || '🏨';
        }

        function displayHotels(hotels) {
            const grid = document.getElementById('hotelsGrid');
            grid.innerHTML = hotels.map(hotel => `
                <div class="hotel-card" data-category="${hotel.category}">
                    <div class="hotel-image">
                        ${hotel.image_emoji || getHotelEmoji(hotel.category)}
                        <div class="hotel-badge">⭐ ${hotel.avg_rating || hotel.rating || 4.5}</div>
                        ${hotel.available === false ? '<div class="hotel-badge" style="background: #ff5722; left: 15px; right: auto;">Not Available</div>' : ''}
                    </div>
                    <div class="hotel-info">
                        <h3>${hotel.name}</h3>
                        <div class="hotel-location">
                            📍 ${hotel.location}
                        </div>
                        <div class="hotel-features">
                            ${(hotel.amenities || hotel.features || []).map(feature => `<span class="feature-tag">${feature}</span>`).join('')}
                        </div>
                        <div class="hotel-pricing">
                            <div class="price-info">
                                <div class="price">$${hotel.price || hotel.min_price}</div>
                                <div class="price-note">per night</div>
                            </div>
                            <div class="aini-reward">
                                🪙 ${hotel.aini_reward || hotel.ainiReward} AiNi
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                            <button class="book-btn" style="flex: 1;" 
                                    onclick="bookHotel(${hotel.hotel_id || hotel.id}, '${hotel.name}', '${hotel.whatsapp_number || ''}', '${hotel.whatsapp_booking_url || ''}')"
                                    ${hotel.available === false ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                                📱 ${hotel.available === false ? 'Not Available' : 'Book Now'}
                            </button>
                            <button class="book-btn" style="background: #6c757d; flex: 0 0 auto; padding: 12px;" 
                                    onclick="viewHotelDetails(${hotel.hotel_id || hotel.id})"
                                    title="View Details">
                                👁️
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function filterHotels(category) {
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const cards = document.querySelectorAll('.hotel-card');
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category.includes(category)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function bookHotel(hotelId, hotelName, whatsappNumber = '', whatsappUrl = '') {
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const guests = document.getElementById('guests').value;
            
            // Use provided WhatsApp URL if available, otherwise construct one
            if (whatsappUrl) {
                window.open(whatsappUrl, '_blank');
                return;
            }
            
            const phoneNumber = whatsappNumber || '1234567890'; // Fallback number
            const message = `Hi! 🏨 I'd like to book ${hotelName}`;
            const dateMessage = checkin && checkout ? ` from ${checkin} to ${checkout} for ${guests}` : '';
            const fullMessage = `${message}${dateMessage}. Can you help me with the booking and AiNi coin rewards? 🪙`;
            const encodedMessage = encodeURIComponent(fullMessage);
            
            // Open WhatsApp with pre-filled message
            window.open(`https://wa.me/${phoneNumber.replace(/[^0-9]/g, '')}?text=${encodedMessage}`, '_blank');
            
            // Track booking attempt
            trackBookingAttempt(hotelId, hotelName);
        }
        
        function trackBookingAttempt(hotelId, hotelName) {
            // Optional: Track booking attempts for analytics
            console.log(`Booking attempt for Hotel ID: ${hotelId}, Name: ${hotelName}`);
            
            // You could send this to your analytics API
            fetch('public_booking_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'track_booking_attempt',
                    hotel_id: hotelId,
                    hotel_name: hotelName,
                    timestamp: new Date().toISOString()
                })
            }).catch(error => {
                // Silent fail for tracking
                console.log('Tracking error:', error);
            });
        }
        
        function viewHotelDetails(hotelId) {
            window.open(`hotel_details.php?hotel_id=${hotelId}`, '_blank');
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>