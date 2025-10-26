<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AiNi Travel - Reserva Revolucionaria de Hoteles con IA & WhatsApp</title>
    
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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
            padding-top: 110px; /* Space for fixed header */
        }

        /* Split Menu Header Styles */
        .header {
            background: white;
            color: #333;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .header-top {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-icons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .icon-btn {
            background: #f8f9fa;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            position: relative;
        }

        .icon-btn:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff5722;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .language-selector button {
            font-weight: 600;
        }

        .header-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0.8rem 0;
        }

        .main-nav {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
        }

        .nav-item {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .nav-dot {
            color: rgba(255,255,255,0.5);
            font-weight: bold;
        }

        /* Responsive Header */
        @media (max-width: 768px) {
            body {
                padding-top: 140px;
            }

            .header-top {
                padding: 0.8rem 1rem;
            }

            .logo {
                font-size: 1.3rem;
            }

            .icon-btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.9rem;
            }

            .main-nav {
                flex-wrap: wrap;
                padding: 0 1rem;
                gap: 0.8rem;
            }

            .nav-item {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }

            .nav-dot {
                display: none;
            }
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

        /* View Toggle Styles */
        .view-toggle {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .view-btn {
            padding: 12px 30px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1rem;
        }

        .view-btn:hover,
        .view-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Map Container Styles */
        .map-container {
            margin-bottom: 3rem;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .map-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            height: 600px;
        }

        .hotel-map {
            background: #f8f9fa;
            position: relative;
        }

        .map-sidebar {
            background: white;
            padding: 2rem;
            overflow-y: auto;
            border-left: 1px solid #e9ecef;
        }

        .map-sidebar h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .map-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .map-hotel-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .map-hotel-item {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .map-hotel-item:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateX(5px);
        }

        .map-hotel-item.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }

        .map-hotel-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .map-hotel-price {
            color: #667eea;
            font-weight: 700;
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

            .map-wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }

            .hotel-map {
                height: 400px;
            }

            .map-sidebar {
                border-left: none;
                border-top: 1px solid #e9ecef;
                max-height: 400px;
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

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            50% { transform: scale(1.02); box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3); }
        }
    </style>
</head>
<body>
    <!-- Split Menu Header -->
    <header class="header">
        <div class="header-top">
            <div class="logo">
                🪙 AiNi Travel
            </div>
            <div class="header-icons">
                <div class="language-selector">
                    <button class="icon-btn">🌐 ES</button>
                </div>
                <button class="icon-btn notification-btn">
                    🔔
                    <span class="notification-badge">3</span>
                </button>
                <button class="icon-btn profile-btn">
                    👤
                </button>
            </div>
        </div>
        <div class="header-nav">
            <nav class="main-nav">
                <a href="#hotels" class="nav-item">🏨 Hotels</a>
                <span class="nav-dot">•</span>
                <a href="#experiences" class="nav-item">🎯 Experiences</a>
                <span class="nav-dot">•</span>
                <a href="travel_social.php" class="nav-item">🌍 Social</a>
                <span class="nav-dot">•</span>
                <a href="wallet.php" class="nav-item">🪙 Coins</a>
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

    <!-- Propiedades Disponibles Section -->
    <section class="hotels-section" id="hotels">
        <div class="container">
            <div class="section-header">
                <h2>🏨 Propiedades Disponibles</h2>
                <p>Descubre increíbles hoteles y gana monedas AiNi con cada estadía</p>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn active" onclick="filterHotels('all')">Todos</button>
                <button class="filter-btn" onclick="filterHotels('luxury')">🌟 Lujo</button>
                <button class="filter-btn" onclick="filterHotels('budget')">💰 Económico</button>
                <button class="filter-btn" onclick="filterHotels('business')">💼 Negocios</button>
                <button class="filter-btn" onclick="filterHotels('family')">👨‍👩‍👧‍👦 Familiar</button>
                <button class="filter-btn" onclick="filterHotels('beach')">🏖️ Playa</button>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="view-btn active" onclick="toggleView('list')">
                    📋 Vista Lista
                </button>
                <button class="view-btn" onclick="toggleView('map')">
                    🗺️ Vista Mapa
                </button>
            </div>

            <!-- Map Section -->
            <div class="map-container" id="mapContainer" style="display: none;">
                <div class="map-wrapper">
                    <div id="hotelMap" class="hotel-map"></div>
                    <div class="map-sidebar">
                        <h3>📍 Hoteles en el Mapa</h3>
                        <p class="map-info">Haz clic en los marcadores para ver detalles</p>
                        <div id="mapHotelList" class="map-hotel-list"></div>
                    </div>
                </div>
            </div>
            
            <!-- Hotels List View -->
            <div class="hotels-list-view" id="listContainer">
                <div class="hotels-grid" id="hotelsGrid">
                    <div class="loading">
                        <div class="spinner"></div>
                        Cargando hoteles increíbles para ti...
                    </div>
                </div>
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

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let hotelMap = null;
        let hotelMarkers = [];
        let allHotels = [];
        
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
                                category: "luxury beach",
                                latitude: 25.7907,
                                longitude: -80.1300,
                                stars: "⭐⭐⭐⭐⭐"
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
                                category: "business luxury",
                                latitude: 40.7580,
                                longitude: -73.9855,
                                stars: "⭐⭐⭐⭐⭐"
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
                                category: "budget",
                                latitude: 30.2672,
                                longitude: -97.7431,
                                stars: "⭐⭐⭐⭐"
                            },
                            {
                                hotel_id: 4,
                                name: "Family Paradise Resort",
                                location: "Orlando, FL",
                                image_emoji: "👨‍👩‍👧‍👦",
                                avg_rating: 4.7,
                                price: 195,
                                amenities: ["Kids Club", "Pool", "Theme Park Shuttle", "Restaurant"],
                                aini_reward: 39,
                                category: "family luxury",
                                latitude: 28.3852,
                                longitude: -81.5639,
                                stars: "⭐⭐⭐⭐⭐"
                            },
                            {
                                hotel_id: 5,
                                name: "Beachfront Paradise",
                                location: "Cancún, México",
                                image_emoji: "🌴",
                                avg_rating: 4.9,
                                price: 250,
                                amenities: ["All-Inclusive", "Beach Access", "Spa", "Multiple Pools"],
                                aini_reward: 50,
                                category: "luxury beach",
                                latitude: 21.1619,
                                longitude: -86.8515,
                                stars: "⭐⭐⭐⭐⭐"
                            },
                            {
                                hotel_id: 6,
                                name: "Eco Lodge Retreat",
                                location: "San José, Costa Rica",
                                image_emoji: "🌿",
                                avg_rating: 4.5,
                                price: 120,
                                amenities: ["Nature Tours", "Organic Restaurant", "WiFi", "Yoga"],
                                aini_reward: 24,
                                category: "budget",
                                latitude: 9.9281,
                                longitude: -84.0907,
                                stars: "⭐⭐⭐⭐"
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
            // Store hotels globally for map view
            allHotels = hotels;
            
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

        // Toggle between list and map view
        function toggleView(view) {
            const listContainer = document.getElementById('listContainer');
            const mapContainer = document.getElementById('mapContainer');
            const viewBtns = document.querySelectorAll('.view-btn');
            
            viewBtns.forEach(btn => btn.classList.remove('active'));
            
            if (view === 'map') {
                listContainer.style.display = 'none';
                mapContainer.style.display = 'block';
                document.querySelector('.view-btn:nth-child(2)').classList.add('active');
                
                // Initialize map if not already done
                if (!hotelMap) {
                    initializeMap();
                }
                updateMapMarkers();
            } else {
                listContainer.style.display = 'block';
                mapContainer.style.display = 'none';
                document.querySelector('.view-btn:nth-child(1)').classList.add('active');
            }
        }

        // Initialize the map
        function initializeMap() {
            hotelMap = L.map('hotelMap').setView([25.7617, -80.1918], 12); // Default to Miami
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(hotelMap);
        }

        // Update map markers with hotels
        function updateMapMarkers() {
            if (!hotelMap) return;
            
            // Clear existing markers
            hotelMarkers.forEach(marker => hotelMap.removeLayer(marker));
            hotelMarkers = [];
            
            const mapHotelList = document.getElementById('mapHotelList');
            mapHotelList.innerHTML = '';
            
            // Add markers for each hotel
            allHotels.forEach((hotel, index) => {
                if (hotel.latitude && hotel.longitude) {
                    // Create marker
                    const marker = L.marker([hotel.latitude, hotel.longitude])
                        .addTo(hotelMap)
                        .bindPopup(`
                            <div style="min-width: 200px;">
                                <h4 style="margin: 0 0 8px 0;">${hotel.name}</h4>
                                <p style="margin: 0 0 8px 0; color: #666;">${hotel.location}</p>
                                <p style="margin: 0; color: #667eea; font-weight: 700;">$${hotel.price}/noche</p>
                                <button onclick="scrollToHotel(${index})" 
                                    style="margin-top: 10px; padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                    Ver Detalles
                                </button>
                            </div>
                        `);
                    
                    hotelMarkers.push(marker);
                    
                    // Add to sidebar list
                    const listItem = document.createElement('div');
                    listItem.className = 'map-hotel-item';
                    listItem.innerHTML = `
                        <div class="map-hotel-name">${hotel.name}</div>
                        <div style="color: #666; font-size: 0.9rem; margin: 0.3rem 0;">
                            <span style="margin-right: 0.5rem;">${hotel.stars}</span>
                            ${hotel.location}
                        </div>
                        <div class="map-hotel-price">$${hotel.price}/noche</div>
                    `;
                    
                    listItem.addEventListener('click', () => {
                        hotelMap.setView([hotel.latitude, hotel.longitude], 15);
                        marker.openPopup();
                        
                        // Highlight this item
                        document.querySelectorAll('.map-hotel-item').forEach(item => {
                            item.classList.remove('active');
                        });
                        listItem.classList.add('active');
                    });
                    
                    mapHotelList.appendChild(listItem);
                }
            });
            
            // Fit map to show all markers
            if (hotelMarkers.length > 0) {
                const group = new L.featureGroup(hotelMarkers);
                hotelMap.fitBounds(group.getBounds().pad(0.1));
            }
        }

        // Scroll to hotel in list view
        function scrollToHotel(index) {
            toggleView('list');
            setTimeout(() => {
                const hotelCards = document.querySelectorAll('.hotel-card');
                if (hotelCards[index]) {
                    hotelCards[index].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    hotelCards[index].style.animation = 'pulse 0.5s';
                }
            }, 300);
        }
    </script>
</body>
</html>