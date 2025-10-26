<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AiNi Travel - Encuentra tu Hotel Perfecto</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Minimal custom CSS for map */
        .hotel-map {
            height: 100%;
            min-height: 500px;
        }
        
        .hotel-list-scroll {
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        /* Smooth scrollbar */
        .hotel-list-scroll::-webkit-scrollbar {
            width: 8px;
        }
        
        .hotel-list-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .hotel-list-scroll::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }
        
        .hotel-list-scroll::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }
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
<body class="bg-gray-50">
    
    <!-- Split Menu Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
        <!-- Top Bar -->
        <div class="border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <span class="text-2xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                            🪙 AiNi Travel
                        </span>
                    </div>
                    
                    <!-- Header Icons -->
                    <div class="flex items-center space-x-3">
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-full text-sm font-semibold transition">
                            🌐 ES
                        </button>
                        <button class="relative px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-full transition">
                            🔔
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                        </button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-full transition">
                            👤
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Bar -->
        <div class="bg-gradient-to-r from-primary to-secondary">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center justify-center space-x-8 h-12">
                    <a href="#hotels" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🏨 Hotels
                    </a>
                    <span class="text-white/50">•</span>
                    <a href="#experiences" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🎯 Experiences
                    </a>
                    <span class="text-white/50">•</span>
                    <a href="travel_social.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        � Social
                    </a>
                    <span class="text-white/50">•</span>
                    <a href="wallet.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🪙 Coins
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content - ONE SCREEN SPLIT VIEW -->
    <main class="pt-28 h-screen flex flex-col">
        
        <!-- Search Bar (Compact at top) -->
        <div class="bg-white shadow-sm border-b border-gray-200 px-4 py-3">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center space-x-3">
                    <div class="flex-1 relative">
                        <input 
                            type="text" 
                            id="searchDestination" 
                            placeholder="🔍 ¿A dónde quieres ir?" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        />
                    </div>
                    <input 
                        type="date" 
                        id="searchCheckin"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />
                    <input 
                        type="date" 
                        id="searchCheckout"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />
                    <select 
                        id="searchGuests"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    >
                        <option>1 Guest</option>
                        <option>2 Guests</option>
                        <option>3 Guests</option>
                        <option>4 Guests</option>
                        <option>5+ Guests</option>
                    </select>
                    <button 
                        onclick="searchHotels()" 
                        class="px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-lg hover:opacity-90 transition font-semibold"
                    >
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        <!-- SPLIT SCREEN: Map Left / Hotels Right -->
        <div class="flex-1 flex overflow-hidden">
            
            <!-- LEFT: Interactive Map -->
            <div class="w-1/2 bg-gray-100 relative">
                <div id="hotelMap" class="hotel-map"></div>
                
                <!-- Map Controls -->
                <div class="absolute top-4 left-4 bg-white rounded-lg shadow-lg p-3">
                    <div class="text-sm font-semibold text-gray-700 mb-2">📍 Filtros</div>
                    <div class="space-y-2">
                        <button onclick="filterByCategory('all')" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm filter-btn active-filter">
                            ✨ Todos
                        </button>
                        <button onclick="filterByCategory('luxury')" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm filter-btn">
                            ⭐ Lujo
                        </button>
                        <button onclick="filterByCategory('budget')" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm filter-btn">
                            💰 Económico
                        </button>
                        <button onclick="filterByCategory('beach')" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm filter-btn">
                            🏖️ Playa
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT: Hotels List -->
            <div class="w-1/2 bg-white">
                <div class="hotel-list-scroll p-6">
                    
                    <!-- Header -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">🏨 Propiedades Disponibles</h2>
                        <p class="text-gray-600" id="hotelCount">Cargando hoteles...</p>
                    </div>
                    
                    <!-- Hotels Grid -->
                    <div id="hotelsList" class="space-y-4">
                        <!-- Loading -->
                        <div class="text-center py-12">
                            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-primary"></div>
                            <p class="mt-4 text-gray-600">Cargando hoteles increíbles...</p>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
        
    </main>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/1234567890" 
       class="fixed bottom-6 right-6 bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600 transition z-50">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
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
    </a>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let hotelMap = null;
        let hotelMarkers = [];
        let allHotels = [];
        
        // Initialize on page load
        $(document).ready(function() {
            // Set default dates
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            $('#searchCheckin').val(today.toISOString().split('T')[0]);
            $('#searchCheckout').val(tomorrow.toISOString().split('T')[0]);
            
            // Initialize map
            initializeMap();
            
            // Load hotels
            loadHotels();
        });

        function initializeMap() {
            hotelMap = L.map('hotelMap').setView([25.7617, -80.1918], 3); // World view initially
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(hotelMap);
        }

        function loadHotels() {
            // Sample hotels data with coordinates
            allHotels = [
                {
                    id: 1,
                    name: "Ocean View Resort",
                    location: "Miami Beach, FL",
                    emoji: "🏖️",
                    rating: 4.8,
                    price: 180,
                    features: ["Vista al Mar", "Piscina", "WiFi", "Spa"],
                    aini_coins: 36,
                    category: "luxury beach",
                    latitude: 25.7907,
                    longitude: -80.1300
                },
                {
                    id: 2,
                    name: "Downtown Business Hotel",
                    location: "New York, NY",
                    emoji: "🏙️",
                    rating: 4.6,
                    price: 220,
                    features: ["Centro Negocios", "Gimnasio", "WiFi", "Restaurante"],
                    aini_coins: 44,
                    category: "business luxury",
                    latitude: 40.7580,
                    longitude: -73.9855
                },
                {
                    id: 3,
                    name: "Budget Traveler Inn",
                    location: "Austin, TX",
                    emoji: "🏨",
                    rating: 4.3,
                    price: 85,
                    features: ["WiFi", "Estacionamiento", "Desayuno"],
                    aini_coins: 17,
                    category: "budget",
                    latitude: 30.2672,
                    longitude: -97.7431
                },
                {
                    id: 4,
                    name: "Family Paradise Resort",
                    location: "Orlando, FL",
                    emoji: "👨‍👩‍👧‍👦",
                    rating: 4.7,
                    price: 195,
                    features: ["Kids Club", "Piscina", "Transporte Parques", "Restaurante"],
                    aini_coins: 39,
                    category: "family luxury",
                    latitude: 28.3852,
                    longitude: -81.5639
                },
                {
                    id: 5,
                    name: "Beachfront Paradise",
                    location: "Cancún, México",
                    emoji: "🌴",
                    rating: 4.9,
                    price: 250,
                    features: ["Todo Incluido", "Playa", "Spa", "Varias Piscinas"],
                    aini_coins: 50,
                    category: "luxury beach",
                    latitude: 21.1619,
                    longitude: -86.8515
                },
                {
                    id: 6,
                    name: "Eco Lodge Retreat",
                    location: "San José, Costa Rica",
                    emoji: "🌿",
                    rating: 4.5,
                    price: 120,
                    features: ["Tours Naturaleza", "Restaurante Orgánico", "WiFi", "Yoga"],
                    aini_coins: 24,
                    category: "budget",
                    latitude: 9.9281,
                    longitude: -84.0907
                }
            ];
            
            displayHotels(allHotels);
            updateMapMarkers(allHotels);
        }

        function displayHotels(hotels) {
            $('#hotelCount').text(`${hotels.length} hoteles encontrados`);
            
            const hotelsHTML = hotels.map(hotel => `
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition cursor-pointer hotel-card" 
                     data-id="${hotel.id}" 
                     data-lat="${hotel.latitude}" 
                     data-lng="${hotel.longitude}">
                    <div class="flex">
                        <!-- Hotel Image/Emoji -->
                        <div class="w-32 h-32 bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-5xl">
                            ${hotel.emoji}
                        </div>
                        
                        <!-- Hotel Info -->
                        <div class="flex-1 p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">${hotel.name}</h3>
                                    <p class="text-sm text-gray-600">📍 ${hotel.location}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-yellow-500 text-sm">⭐ ${hotel.rating}</div>
                                </div>
                            </div>
                            
                            <!-- Features -->
                            <div class="flex flex-wrap gap-1 mb-3">
                                ${hotel.features.slice(0, 3).map(f => `
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-full">${f}</span>
                                `).join('')}
                            </div>
                            
                            <!-- Price and Coins -->
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-2xl font-bold text-primary">$${hotel.price}</span>
                                    <span class="text-gray-600 text-sm">/noche</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-yellow-600 font-semibold">🪙 ${hotel.aini_coins} AiNi</span>
                                </div>
                            </div>
                            
                            <!-- Book Button -->
                            <button onclick="bookHotel(${hotel.id}, '${hotel.name}')" 
                                    class="mt-3 w-full bg-gradient-to-r from-primary to-secondary text-white py-2 rounded-lg hover:opacity-90 transition font-semibold">
                                📱 Reservar Ahora
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            $('#hotelsList').html(hotelsHTML);
            
            // Add click handler to zoom map
            $('.hotel-card').click(function() {
                const lat = $(this).data('lat');
                const lng = $(this).data('lng');
                hotelMap.setView([lat, lng], 13);
                
                // Highlight this card
                $('.hotel-card').removeClass('ring-2 ring-primary');
                $(this).addClass('ring-2 ring-primary');
            });
        }

        function updateMapMarkers(hotels) {
            // Clear existing markers
            hotelMarkers.forEach(marker => hotelMap.removeLayer(marker));
            hotelMarkers = [];
            
            // Add markers for each hotel
            hotels.forEach(hotel => {
                const marker = L.marker([hotel.latitude, hotel.longitude])
                    .addTo(hotelMap)
                    .bindPopup(`
                        <div class="text-center p-2">
                            <div class="text-3xl mb-2">${hotel.emoji}</div>
                            <h4 class="font-bold">${hotel.name}</h4>
                            <p class="text-sm text-gray-600">${hotel.location}</p>
                            <p class="text-primary font-bold mt-2">$${hotel.price}/noche</p>
                            <button onclick="bookHotel(${hotel.id}, '${hotel.name}')" 
                                    class="mt-2 px-4 py-1 bg-primary text-white rounded text-sm">
                                Reservar
                            </button>
                        </div>
                    `);
                
                hotelMarkers.push(marker);
            });
            
            // Fit map to show all markers
            if (hotelMarkers.length > 0) {
                const group = new L.featureGroup(hotelMarkers);
                hotelMap.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function filterByCategory(category) {
            // Update active button
            $('.filter-btn').removeClass('active-filter bg-primary text-white');
            event.target.classList.add('active-filter', 'bg-primary', 'text-white');
            
            let filtered = allHotels;
            if (category !== 'all') {
                filtered = allHotels.filter(h => h.category.includes(category));
            }
            
            displayHotels(filtered);
            updateMapMarkers(filtered);
        }

        function searchHotels() {
            const destination = $('#searchDestination').val().toLowerCase();
            
            if (!destination) {
                displayHotels(allHotels);
                updateMapMarkers(allHotels);
                return;
            }
            
            const filtered = allHotels.filter(h => 
                h.name.toLowerCase().includes(destination) || 
                h.location.toLowerCase().includes(destination)
            );
            
            if (filtered.length > 0) {
                displayHotels(filtered);
                updateMapMarkers(filtered);
            } else {
                $('#hotelsList').html(`
                    <div class="text-center py-12 text-gray-500">
                        <div class="text-5xl mb-4">🔍</div>
                        <p>No se encontraron hoteles para "${destination}"</p>
                        <button onclick="loadHotels()" class="mt-4 px-6 py-2 bg-primary text-white rounded-lg">
                            Ver todos los hoteles
                        </button>
                    </div>
                `);
            }
        }

        function bookHotel(hotelId, hotelName) {
            const checkin = $('#searchCheckin').val();
            const checkout = $('#searchCheckout').val();
            const guests = $('#searchGuests').val();
            
            const message = `Hola! 🏨 Quiero reservar ${hotelName} del ${checkin} al ${checkout} para ${guests}. ¿Me puedes ayudar con la reserva y las monedas AiNi? 🪙`;
            const encodedMessage = encodeURIComponent(message);
            
            window.open(`https://wa.me/1234567890?text=${encodedMessage}`, '_blank');
        }
    </script>
</body>
</html>