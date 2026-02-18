<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Get unread notification count if logged in
$notificationCount = 0;
if ($isLoggedIn) {
    require_once 'db_connection_pdo.php';
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $notificationCount = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        // Silently fail if notifications table doesn't exist yet
        $notificationCount = 0;
    }
}
?>
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

        /* Map Toggle Button - Floating */
        .map-toggle-btn {
            position: fixed;
            top: 140px;
            left: 20px;
            z-index: 1000;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .map-toggle-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .map-toggle-btn:active {
            transform: translateY(0) scale(1);
        }
        
        /* Hide map transitions */
        .split-view-container .w-1\/2:first-child {
            transition: all 0.4s ease;
        }
        
        .split-view-container .w-1\/2:last-child {
            transition: all 0.4s ease;
        }

        /* Map Container Styles */
        .map-container {
            margin-bottom: 3rem;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }
        
        .map-container.hidden {
            display: none !important;
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
        
        /* Vertical layout for hotel cards when map is hidden */
        .hotel-card-vertical .flex {
            flex-direction: column !important;
        }
        
        .hotel-card-vertical .hotel-image-container {
            width: 100% !important;
            height: 220px !important;
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
        
        /* Booking Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        
        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .booking-modal {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* AI Chat Styles */
        .ai-chat-bubble {
            position: fixed;
            bottom: 2rem;
            left: 1.5rem;
            z-index: 50;
        }
        
        /* Filter Button */
        .filter-button {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            position: relative;
            white-space: nowrap;
        }
        
        .filter-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(245, 158, 11, 0.5);
        }
        
        /* Filter Dropdown */
        .filter-dropdown {
            display: none;
            position: absolute;
            top: 4rem;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 1.5rem;
            z-index: 9999;
            border: 2px solid #f59e0b;
        }
        
        .filter-dropdown.active {
            display: block;
            animation: slideUp 0.3s ease;
        }
        
        .filter-section {
            margin-bottom: 1.5rem;
        }
        
        .filter-section:last-child {
            margin-bottom: 0;
        }
        
        .filter-title {
            font-weight: 700;
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-range {
            width: 100%;
        }
        
        .filter-range-values {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .filter-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .filter-checkbox:hover {
            border-color: #667eea;
            background: #f3f4f6;
        }
        
        .filter-checkbox input[type="checkbox"] {
            cursor: pointer;
        }
        
        .filter-checkbox.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        }
        
        .filter-apply-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        
        .filter-apply-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* AI Response Bubble - Comic-style but professional */
        .ai-response-bubble {
            position: fixed;
            bottom: 6rem;
            right: 2rem;
            width: 400px;
            max-width: calc(100vw - 4rem);
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            z-index: 100;
            animation: slideInRight 0.3s ease;
            border: 3px solid #667eea;
        }
        
        .ai-response-bubble::before {
            content: '';
            position: absolute;
            bottom: 20px;
            right: -15px;
            width: 0;
            height: 0;
            border-left: 15px solid #667eea;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }
        
        .ai-response-bubble::after {
            content: '';
            position: absolute;
            bottom: 20px;
            right: -12px;
            width: 0;
            height: 0;
            border-left: 12px solid white;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
        }
        
        .ai-response-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 17px 17px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ai-response-text {
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            line-height: 1.6;
            color: #374151;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .ai-chat-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .ai-chat-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .ai-chat-window {
            display: none;
            position: fixed;
            bottom: 2rem;
            left: 1.5rem;
            width: 400px;
            max-width: calc(100vw - 3rem);
            height: 600px;
            max-height: calc(100vh - 10rem);
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            z-index: 51;
            flex-direction: column;
        }
        
        .ai-chat-window.active {
            display: flex;
            animation: slideUp 0.3s ease;
        }
        
        .ai-chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ai-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .ai-message, .user-message {
            max-width: 80%;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            word-wrap: break-word;
        }
        
        .ai-message {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            align-self: flex-start;
            border: 1px solid #e0e0e0;
            color: #333;
        }
        
        .user-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-self: flex-end;
        }
        
        .ai-typing {
            display: flex;
            gap: 0.3rem;
            padding: 0.75rem;
        }
        
        .ai-typing span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
            animation: typing 1.4s infinite;
        }
        
        .ai-typing span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .ai-typing span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
        
        .ai-chat-input {
            padding: 1rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 0.5rem;
        }
        
        .ai-chat-input input {
            color: #333 !important;
        }
        
        .ai-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0 1rem 1rem;
        }
        
        .suggestion-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .suggestion-btn:hover {
            background: #667eea;
            color: white;
        }
        
        /* Hotel Image Carousel */
        .hotel-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            overflow: hidden;
        }
        
        .hotel-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.5s ease-in-out;
        }
        
        .hotel-carousel-dots {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 10;
        }
        
        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .carousel-dot.active {
            background: white;
            width: 24px;
            border-radius: 4px;
        }
        
        /* Mobile Responsive Styles */
        .mobile-menu-button {
            display: none;
        }
        
        .mobile-menu {
            display: none;
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 45;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .mobile-menu.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .mobile-menu a {
            display: block;
            padding: 0.75rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            transition: background 0.3s;
        }
        
        .mobile-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Mobile Layout Adjustments */
        @media (max-width: 768px) {
            .mobile-menu-button {
                display: block;
            }
            
            /* Hide desktop header icons on small mobile */
            @media (max-width: 480px) {
                header .flex.items-center.space-x-2 {
                    display: none;
                }
            }
            
            /* Stack map and hotels vertically on mobile */
            .split-view-container {
                flex-direction: column !important;
                top: 9rem !important; /* Adjusted for mobile header */
            }
            
            .split-view-container > div {
                width: 100% !important;
                height: 50% !important;
            }
            
            /* Adjust AI search bar for mobile */
            .ai-search-mobile {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .ai-search-mobile button {
                width: 100%;
                justify-content: center;
            }
            
            /* Smaller hotel cards on mobile */
            .hotel-image-container {
                width: 120px !important;
                height: 120px !important;
            }
            
            .hotel-card {
                margin-bottom: 0.5rem;
            }
            
            .hotel-card h3 {
                font-size: 1rem !important;
            }
            
            .hotel-card p {
                font-size: 0.75rem !important;
            }
            
            /* AI response bubble adjustment for mobile */
            .ai-response-bubble {
                width: 90% !important;
                right: 5% !important;
                bottom: 4rem !important;
                max-height: 60vh;
            }
            
            /* Adjust filter button text */
            .ai-search-mobile button span:last-child {
                display: none;
            }
            
            .ai-search-mobile button span:first-child {
                margin-right: 0;
            }
        }
        
        /* Tablet adjustments */
        @media (min-width: 769px) and (max-width: 1024px) {
            .hotel-image-container {
                width: 150px !important;
                height: 150px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Single Menu Bar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-white">
                        ✈️ AiNi Travel
                    </span>
                </div>
                
                <!-- Mobile Hamburger Button -->
                <button class="mobile-menu-button text-white text-3xl" onclick="toggleMobileMenu()">
                    ?
                </button>
                
                <!-- Navigation Links (Center - Desktop) -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="coming_soon_experiences.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🎯 Experiences
                    </a>
                    <a href="coming_soon_social.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🌍 Social
                    </a>
                    <a href="coming_soon_coins.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🪙 Coins
                    </a>
                    <a href="partner_register.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-full transition font-medium">
                        🏨 List Your Property
                    </a>
                <?php if ($isLoggedIn): ?>
                <a href="my_bookings.php" class="bg-cyan-400 text-white hover:bg-cyan-500 px-4 py-2 rounded-full transition font-medium shadow-lg">
                    📋 View Reservations
                </a>
                <?php endif; ?>
            </nav>
            
            <!-- Header Icons (Right) -->
            <div class="flex items-center gap-2">
                    <!-- Language Selector -->
                    <div class="relative">
                        <button onclick="toggleLanguageMenu()" class="h-8 px-3 bg-white/20 hover:bg-white/30 rounded-full text-xs font-semibold text-white transition flex items-center gap-1">
                            🌐 ES
                        </button>
                        <div id="languageMenu" class="hidden absolute right-0 mt-2 w-32 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="?lang=es" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">✨🇪🇸 Español</a>
                            <a href="?lang=en" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🇺🇸 English</a>
                        </div>
                    </div>
                    
                    <?php if ($isLoggedIn): ?>
                    <!-- Notifications -->
                    <button class="relative h-8 w-8 bg-white/20 hover:bg-white/30 rounded-full transition text-white flex items-center justify-center text-sm">
                        🔔
                        <?php if ($notificationCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-bold"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!$isLoggedIn): ?>
                    <!-- Login/Register Button -->
                    <a href="login.php" class="h-8 px-3 bg-white hover:bg-white/90 rounded-full transition text-primary text-xs font-semibold flex items-center gap-1">
                        🔑 Login/Register
                    </a>
                    <?php else: ?>
                    <!-- Profile Menu -->
                    <div class="relative">
                        <button onclick="toggleProfileMenu()" class="h-8 w-8 bg-white/20 hover:bg-white/30 rounded-full transition text-white flex items-center justify-center text-sm">
                            👤
                        </button>
                        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">👤 My Profile</a>
                            <a href="wallet.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">🪙 My Wallet</a>
                            <a href="my_bookings.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">📅 My Bookings</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">🚪 Logout</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu (Hidden by default) -->
    <div id="mobileMenu" class="mobile-menu">
        <a href="coming_soon_experiences.php">🎯 Experiences</a>
        <a href="coming_soon_social.php">🌍 Social</a>
        <a href="coming_soon_coins.php">🪙 Coins</a>
        <?php if ($isLoggedIn): ?>
        <a href="my_bookings.php" class="relative" style="background: #22d3ee; padding: 0.75rem; border-radius: 0.5rem; font-weight: 600;">
            📋 View Reservations
        </a>
        <?php endif; ?>
        <div class="border-t border-white/20 my-2 pt-2">
            <?php if (!$isLoggedIn): ?>
            <a href="login.php" class="text-sm font-semibold" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 0.5rem; display: block; margin-bottom: 0.5rem;">🔑 Login</a>
            <a href="register.php" class="text-sm font-semibold" style="background: white; color: #667eea; padding: 0.5rem 1rem; border-radius: 0.5rem; display: block; margin-bottom: 0.5rem;">✨ Sign Up</a>
            <?php else: ?>
            <a href="#" class="text-sm">🔔 Notifications<?php if ($notificationCount > 0) echo " ($notificationCount)"; ?></a>
            <a href="profile.php" class="text-sm">👤 Profile</a>
            <a href="logout.php" class="text-sm text-red-400">🚪 Logout</a>
            <?php endif; ?>
            <a href="#" class="text-sm">🌐 ES</a>
        </div>
    </div>

    <!-- Main Content - ONE SCREEN SPLIT VIEW -->
    <main class="h-screen flex flex-col">
        
        <!-- Hotel Search Bar - FIXED POSITION -->
        <div class="fixed top-16 left-0 right-0 z-40 bg-gradient-to-r from-purple-50 to-pink-50 shadow-sm border-b border-purple-200 px-4 py-3">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Destination Input -->
                    <div class="flex-1 min-w-[200px] relative">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-xl">
                            📍
                        </div>
                        <input 
                            type="text" 
                            id="searchDestination" 
                            placeholder="¿A dónde vas? (Ciudad, país...)" 
                            class="w-full pl-12 pr-4 py-2.5 border-2 border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        />
                    </div>
                    
                    <!-- Check-in Date -->
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-lg">
                            📅
                        </div>
                        <input 
                            type="date" 
                            id="searchCheckin" 
                            class="pl-10 pr-4 py-2.5 border-2 border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        />
                    </div>
                    
                    <!-- Check-out Date -->
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-lg">
                            📅
                        </div>
                        <input 
                            type="date" 
                            id="searchCheckout" 
                            class="pl-10 pr-4 py-2.5 border-2 border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        />
                    </div>
                    
                    <!-- Guests Selector -->
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-lg">
                            👥
                        </div>
                        <select 
                            id="searchGuests" 
                            class="pl-10 pr-8 py-2.5 border-2 border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent appearance-none bg-white"
                        >
                            <option value="1">1 huésped</option>
                            <option value="2" selected>2 huéspedes</option>
                            <option value="3">3 huéspedes</option>
                            <option value="4">4 huéspedes</option>
                            <option value="5">5+ huéspedes</option>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <button 
                        onclick="searchHotels()" 
                        class="px-6 py-2.5 bg-gradient-to-r from-primary to-secondary text-white rounded-xl hover:opacity-90 transition font-semibold shadow-lg flex items-center gap-2"
                    >
                        <span>✨</span>
                        <span>Buscar</span>
                    </button>
                    
                    <!-- Filter Button -->
                    <div style="position: relative;">
                        <button 
                            id="filterButton" 
                            onclick="toggleFilters()" 
                            class="px-4 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl hover:opacity-90 transition font-semibold flex items-center gap-2 shadow-lg"
                        >
                            🔧 Filtros
                        </button>
                        
                        <!-- Filter Dropdown -->
                        <div id="filterDropdown" class="filter-dropdown">
                            <div class="filter-section">
                                <div class="filter-title">💰 Rango de Precio</div>
                                <input type="range" id="priceRange" class="filter-range" min="0" max="300" value="300" oninput="updatePriceRange(this.value)">
                                <div class="filter-range-values">
                                    <span>$0</span>
                                    <span id="priceValue">$300</span>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-title">? Calificación Mínima</div>
                                <div class="filter-checkbox-group">
                                    <label class="filter-checkbox">
                                        <input type="radio" name="rating" value="0" checked onchange="applyFilters()">
                                        <span>Todas</span>
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="radio" name="rating" value="4" onchange="applyFilters()">
                                        <span>4+ ?</span>
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="radio" name="rating" value="4.5" onchange="applyFilters()">
                                        <span>4.5+ ?</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-title">⚡ Servicios</div>
                                <div class="filter-checkbox-group">
                                    <label class="filter-checkbox">
                                        <input type="checkbox" value="Piscina" onchange="applyFilters()">
                                        <span>🏊 Piscina</span>
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" value="WiFi" onchange="applyFilters()">
                                        <span>📶 WiFi</span>
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" value="Spa" onchange="applyFilters()">
                                        <span>💆 Spa</span>
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" value="Gimnasio" onchange="applyFilters()">
                                        <span>🏋️ Gym</span>
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" value="Estacionamiento" onchange="applyFilters()">
                                        <span>🅿️ Parking</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-title">🪙 AiNi Coins Mínimos</div>
                                <input type="range" id="coinsRange" class="filter-range" min="0" max="50" value="0" oninput="updateCoinsRange(this.value)">
                                <div class="filter-range-values">
                                    <span>0 coins</span>
                                    <span id="coinsValue">0 coins</span>
                                </div>
                            </div>
                            
                            <button class="filter-apply-btn" onclick="applyFilters(); toggleFilters();">
                                Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SPLIT SCREEN: Map Left / Hotels Right - FIXED CONTAINERS -->
        <div class="split-view-container fixed top-32 left-0 right-0 bottom-12 flex overflow-hidden z-20" id="splitContainer">
            
            <!-- Map Toggle Button - Floating -->
            <button class="map-toggle-btn" id="mapToggleBtn" onclick="toggleMapVisibility()">
                <span id="mapToggleIcon">🗺️</span>
                <span id="mapToggleText">Ocultar Mapa</span>
            </button>
            
            <!-- LEFT: Interactive Map -->
            <div class="w-1/2 bg-gray-100 relative overflow-hidden" id="mapPanel">
                <div id="hotelMap" class="hotel-map"></div>
            </div>
            
            <!-- AI Response Bubble (Comic-style but professional) -->
            <div id="aiResponseBubble" class="ai-response-bubble" style="display: none;">
                <div class="ai-response-header">
                    <span class="font-bold">🤖 AiNi Assistant</span>
                    <button onclick="closeAIResponse()" class="text-white hover:text-gray-200">?</button>
                </div>
                <div id="aiResponseText" class="ai-response-text">
                    <!-- AI response will appear here -->
                </div>
            </div>
            
            <!-- RIGHT: Hotels List -->
            <div class="w-1/2 bg-white" id="hotelsPanel">
                <div class="hotel-list-scroll p-6" style="padding-bottom: 100px;">
                    
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

    <!-- Booking Confirmation Modal -->
    <div id="bookingModal" class="modal-overlay" onclick="closeModalOnOutside(event)">
        <div class="booking-modal" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary to-secondary text-white p-6 rounded-t-20">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-2xl font-bold mb-1">Confirma tu Reserva</h3>
                        <p class="text-white/80 text-sm">Revisa los detalles antes de continuar</p>
                    </div>
                    <button onclick="closeBookingModal()" class="text-white/80 hover:text-white text-3xl leading-none">
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <!-- Hotel Info -->
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center text-4xl" id="modalHotelEmoji">
                        🗺️
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-xl text-gray-800" id="modalHotelName">Ocean View Resort</h4>
                        <p class="text-gray-600 text-sm" id="modalHotelLocation">📍 Miami Beach, FL</p>
                        <p class="text-yellow-500 text-sm mt-1" id="modalHotelRating">? 4.8</p>
                    </div>
                </div>
                
                <!-- Booking Details -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            📅 Check-in
                        </label>
                        <input type="date" id="modalCheckin" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            📅 Check-out
                        </label>
                        <input type="date" id="modalCheckout" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            ✨ Huéspedes
                        </label>
                        <select id="modalGuests" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="1">1 Huésped</option>
                            <option value="2" selected>2 Huéspedes</option>
                            <option value="3">3 Huéspedes</option>
                            <option value="4">4 Huéspedes</option>
                            <option value="5+">5+ Huéspedes</option>
                        </select>
                    </div>
                </div>
                
                <!-- Price Summary -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-4 mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-700">Precio por noche</span>
                        <span class="font-semibold text-gray-800" id="modalPricePerNight">$180</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-700" id="modalNightsLabel">🌙 2 noches</span>
                        <span class="font-semibold text-gray-800" id="modalSubtotal">$360</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-300">
                        <span class="text-gray-700">🪙 Ganas AiNi Coins</span>
                        <span class="font-bold text-yellow-600" id="modalAiniCoins">72</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 mt-3 border-t-2 border-gray-400">
                        <span class="text-lg font-bold text-gray-800">Total</span>
                        <span class="text-2xl font-bold text-primary" id="modalTotal">$360</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button onclick="closeBookingModal()" 
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold">
                        ? Cancelar
                    </button>
                    <button onclick="confirmWhatsAppBooking()" 
                            class="flex-1 px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-semibold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Continuar en WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer - FIXED BOTTOM -->
    <footer class="footer fixed bottom-0 left-0 right-0 z-30" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.75rem 0;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: space-between; align-items: center;">
            <p style="opacity: 0.9; font-size: 0.875rem; margin: 0;">✈️ AiNi Travel - Revolutionary hotel booking with AI-powered search © 2025</p>
            <a href="partners/login.php" style="opacity: 0.9; font-size: 0.875rem; color: white; text-decoration: none; padding: 0.25rem 0.75rem; background: rgba(255,255,255,0.2); border-radius: 0.5rem; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                🏨 Owner/Partner Login
            </a>
        </div>
    </footer>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary to-secondary text-white p-6 rounded-t-20">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-2xl font-bold mb-1">Confirma tu Reserva</h3>
                        <p class="text-white/80 text-sm">Revisa los detalles antes de continuar</p>
                    </div>
                    <button onclick="closeBookingModal()" class="text-white/80 hover:text-white text-3xl leading-none">
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <!-- Hotel Info -->
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center text-4xl" id="modalHotelEmoji">
                        🗺️
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-xl text-gray-800" id="modalHotelName">Ocean View Resort</h4>
                        <p class="text-gray-600 text-sm" id="modalHotelLocation">📍 Miami Beach, FL</p>
                        <p class="text-yellow-500 text-sm mt-1" id="modalHotelRating">? 4.8</p>
                    </div>
                </div>
                
                <!-- Booking Details -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            📅 Check-in
                        </label>
                        <input type="date" id="modalCheckin" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            📅 Check-out
                        </label>
                        <input type="date" id="modalCheckout" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            ✨ Huéspedes
                        </label>
                        <select id="modalGuests" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="1">1 Huésped</option>
                            <option value="2" selected>2 Huéspedes</option>
                            <option value="3">3 Huéspedes</option>
                            <option value="4">4 Huéspedes</option>
                            <option value="5+">5+ Huéspedes</option>
                        </select>
                    </div>
                </div>
                
                <!-- Price Summary -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-4 mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-700">Precio por noche</span>
                        <span class="font-semibold text-gray-800" id="modalPricePerNight">$180</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-700" id="modalNightsLabel">🌙 2 noches</span>
                        <span class="font-semibold text-gray-800" id="modalSubtotal">$360</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-300">
                        <span class="text-gray-700">🪙 Ganas AiNi Coins</span>
                        <span class="font-bold text-yellow-600" id="modalAiniCoins">72</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 mt-3 border-t-2 border-gray-400">
                        <span class="text-lg font-bold text-gray-800">Total</span>
                        <span class="text-2xl font-bold text-primary" id="modalTotal">$360</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button onclick="closeBookingModal()" 
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold">
                        ? Cancelar
                    </button>
                    <button onclick="confirmWhatsAppBooking()" 
                            class="flex-1 px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-semibold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Continuar en WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
                    ✨
                </button>
            </div>
        </div>
        <div class="header-nav">
            <nav class="main-nav">
                <a href="coming_soon_experiences.php" class="nav-item">🎯 Experiences</a>
                <span class="nav-dot">●</span>
                <a href="coming_soon_social.php" class="nav-item">🌍 Social</a>
                <span class="nav-dot">●</span>
                <a href="coming_soon_coins.php" class="nav-item">🪙 Coins</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>✨ Revolutionary Hotel Booking</h1>
            <p>Book hotels through WhatsApp, earn AiNi coins, connect with travelers worldwide. Experience the future of travel with AI-powered assistance and social discovery.</p>
            
            <!-- Search Box -->
            <div class="search-box">
                <div class="search-input">
                    <label>✨ Destination</label>
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
                    <label>✨ Guests</label>
                    <select id="guests">
                        <option>1 Guest</option>
                        <option>2 Guests</option>
                        <option>3 Guests</option>
                        <option>4 Guests</option>
                        <option>5+ Guests</option>
                    </select>
                </div>
                <button class="search-btn" onclick="searchHotels()">
                    ✨ Search Hotels
                </button>
            </div>
        </div>
    </section>

    <!-- Propiedades Disponibles Section -->
    <section class="hotels-section" id="hotels">
        <div class="container">
            <div class="section-header">
                <h2>✨ Propiedades Disponibles</h2>
                <p>Descubre increíbles hoteles y gana monedas AiNi con cada estadía</p>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn active" onclick="filterHotels('all')">Todos</button>
                <button class="filter-btn" onclick="filterHotels('luxury')">✨ Lujo</button>
                <button class="filter-btn" onclick="filterHotels('budget')">✨ Económico</button>
                <button class="filter-btn" onclick="filterHotels('business')">✨ Negocios</button>
                <button class="filter-btn" onclick="filterHotels('family')">👨‍👩‍👧‍👦 Familiar</button>
                <button class="filter-btn" onclick="filterHotels('beach')">🗺️ Playa</button>
            </div>

            <!-- Map Toggle Button -->
            <div style="text-align: center; margin: 2rem 0;">
                <button class="map-toggle-btn" id="mapToggleBtn" onclick="toggleMapVisibility()">
                    <span id="mapToggleIcon">🗺️</span>
                    <span id="mapToggleText">Ocultar Mapa - Ver Más Hoteles</span>
                </button>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle" id="viewToggle">
                <button class="view-btn active" onclick="toggleView('list')">
                    ✨ Vista Lista
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
                        <h3>✨ Hoteles en el Mapa</h3>
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
            <h2>✨ Why Choose AiNi Travel?</h2>
            <p class="features-subtitle">The world's first platform combining hotel booking, social travel, and digital rewards</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>WhatsApp Booking</h3>
                    <p>Book hotels directly through WhatsApp chat. No apps to download, just natural conversation with our AI assistant.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>Earn AiNi Coins</h3>
                    <p>Get rewarded with AiNi coins for every booking. Use them for future stays, upgrades, and exclusive experiences.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>Social Travel Network</h3>
                    <p>Connect with fellow travelers, join meetups, share experiences, and discover hidden gems together.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>AI Travel Assistant</h3>
                    <p>Get personalized recommendations, instant support, and smart travel planning powered by advanced AI.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>Best Price Guarantee</h3>
                    <p>We offer the lowest rates plus AiNi coin rewards. Find a better price? We'll match it and give you extra coins.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>Exclusive Experiences</h3>
                    <p>Access member-only deals, room upgrades, and unique local experiences you won't find anywhere else.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- WhatsApp Section -->
    <section class="whatsapp-section">
        <div class="whatsapp-content">
            <h2>✨ Book Through WhatsApp - It's Revolutionary!</h2>
            <p>Experience the future of hotel booking with natural conversation and instant AI assistance</p>
            
            <div class="whatsapp-demo">
                <div class="chat-preview">
                    <div class="message guest-message">
                        Hi! I need a hotel in Miami for this weekend
                    </div>
                    <div class="message ai-message">
                        ✨ Perfect! I found great options in Miami for this weekend:
                        
                        ? Ocean View Resort - $180/night
                        Earn 36 AiNi coins per night!
                        
                        Which dates work best for you? ✨
                    </div>
                    <div class="message guest-message">
                        That sounds perfect! Book it please
                    </div>
                    <div class="message ai-message">
                        ✨ Booked! Confirmation sent.
                        You earned 72 AiNi coins!
                        
                        Connect with 12 travelers also visiting Miami this weekend? ✨
                    </div>
                </div>
            </div>
            
            <a href="https://wa.me/1234567890" class="cta-button" style="font-size: 1.1rem; padding: 15px 30px;">
                ✨ Start Booking on WhatsApp
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>✈️ AiNi Travel</h3>
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
                <a href="https://wa.me/1234567890">✨ WhatsApp</a>
                <a href="mailto:support@ainitravel.com">✨ Email</a>
                <a href="tel:+1234567890">✨ Phone</a>
                <a href="travel_social.php">🌍 Social Network</a>
            </div>
            
            <div class="footer-section">
                <h3>For Partners</h3>
                <a href="partner_register.php">📝 List Your Property</a>
                <a href="partner_dashboard.php">📊 Partner Dashboard</a>
                <a href="partner_support.php">💼 Partner Support</a>
                <a href="partners/login.php">🏨 Owner/Partner Login</a>
            </div>
        </div>
    </footer>

    <!-- AI Chat Assistant - Sofia Travel Agent v1.0.0.2 -->
    <div class="ai-chat-bubble">
        <div id="aiChatButton" class="ai-chat-button" onclick="toggleAIChat()">
            ✨ Sofia IA
        </div>
        
        <div id="aiChatWindow" class="ai-chat-window">
            <div class="ai-chat-header">
                <div>
                    <div class="font-bold text-lg">✨ Sofia - Tu Asistente de Viaje</div>
                    <div class="text-xs text-white/80">Powered by AI ● Disponible 24/7</div>
                </div>
                <button onclick="toggleAIChat()" class="text-white/80 hover:text-white text-2xl">
                </button>
            </div>
            
            <div id="aiChatMessages" class="ai-chat-messages">
                <div class="ai-message">
                    ¡Hola! ✨ Soy Sofia, tu asistente de viaje de AiNi Travel. 
                    <br><br>
                    Puedo ayudarte a:
                    <br>✓ Elegir el hotel perfecto ✨
                    <br>✓ Responder preguntas sobre destinos ✨
                    <br>✓ Guiarte en tu reserva ✨
                    <br><br>
                    ✨ Tus conversaciones se guardan automáticamente.
                    <br><br>
                    ¿En qué puedo ayudarte hoy?
                </div>
            </div>
            
            <div class="ai-suggestions" id="aiSuggestions">
                <button class="suggestion-btn" onclick="askAI('¿Qué hoteles recomiendas para familias?')">
                    🗺️🗺️?? Para familias
                </button>
                <button class="suggestion-btn" onclick="askAI('¿Cuál es el hotel más económico?')">
                    ✨ Más económico
                </button>
                <button class="suggestion-btn" onclick="askAI('¿Hoteles cerca de la playa?')">
                    🗺️ Cerca de playa
                </button>
                <button class="suggestion-btn" onclick="askAI('Quiero reservar un hotel')">
                    ✨ Hacer reserva
                </button>
            </div>
            
            <div class="ai-chat-input">
                <input 
                    type="text" 
                    id="aiChatInput" 
                    placeholder="Escribe tu pregunta..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:ring-2 focus:ring-primary focus:border-transparent"
                    onkeypress="if(event.key === 'Enter') sendAIMessage()"
                />
                <button 
                    onclick="sendAIMessage()" 
                    class="px-4 py-2 bg-gradient-to-r from-primary to-secondary text-white rounded-full hover:opacity-90 transition">
                    ✨
                </button>
            </div>
        </div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let hotelMap = null;
        let hotelMarkers = [];
        let allHotels = [];
        
        // Initialize on page load
        $(document).ready(function() {
            // Initialize map
            initializeMap();
            
            // Load hotels
            loadHotels();
            
            // Initialize Sofia AI Assistant
            initializeSofia();
            
            // Initialize search dates (today and tomorrow)
            initializeSearchDates();
            
            // Handle clicks on links within Sofia chat messages
            $('#aiChatMessages').on('click', 'a', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                if (url) {
                    window.location.href = url;
                }
            });
        });

        function initializeSearchDates() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const todayStr = today.toISOString().split('T')[0];
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            
            $('#searchCheckin').val(todayStr).attr('min', todayStr);
            $('#searchCheckout').val(tomorrowStr).attr('min', todayStr);
            
            // Validate checkout is after checkin
            $('#searchCheckin').on('change', function() {
                const checkin = new Date($(this).val());
                const nextDay = new Date(checkin);
                nextDay.setDate(nextDay.getDate() + 1);
                const nextDayStr = nextDay.toISOString().split('T')[0];
                $('#searchCheckout').attr('min', nextDayStr);
                if (new Date($('#searchCheckout').val()) <= checkin) {
                    $('#searchCheckout').val(nextDayStr);
                }
            });
        }

        function initializeMap() {
            hotelMap = L.map('hotelMap').setView([25.7617, -80.1918], 3); // World view initially
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(hotelMap);
        }

        function loadHotels() {
            // Load hotels from database via API
            $.ajax({
                url: 'api/get_hotels.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.hotels) {
                        allHotels = response.hotels;
                        displayHotels(allHotels);
                        updateMapMarkers(allHotels);
                        console.log(`? Loaded ${response.count} hotels from database`);
                    } else {
                        console.error('? Failed to load hotels:', response.error);
                        // Fallback to sample data if API fails
                        loadSampleHotels();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('? AJAX Error loading hotels:', error);
                    // Fallback to sample data if API fails
                    loadSampleHotels();
                }
            });
        }
        
        // Fallback function with sample data (in case DB is not available)
        function loadSampleHotels() {
            console.warn('✨ Using fallback sample data');
            allHotels = [
                {
                    id: 1,
                    name: "Ocean View Resort",
                    location: "Miami Beach, FL",
                    emoji: "🗺️",
                    rating: 4.8,
                    price: 180,
                    features: ["Vista al Mar", "Piscina", "WiFi", "Spa"],
                    aini_coins: 36,
                    category: "luxury beach",
                    latitude: 25.7907,
                    longitude: -80.1300,
                    images: [
                        "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&q=80",
                        "https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80",
                        "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80",
                        "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80"
                    ]
                },
                {
                    id: 2,
                    name: "Downtown Business Hotel",
                    location: "New York, NY",
                    emoji: "🗺️",
                    rating: 4.6,
                    price: 220,
                    features: ["Centro Negocios", "Gimnasio", "WiFi", "Restaurante"],
                    aini_coins: 44,
                    category: "business luxury",
                    latitude: 40.7580,
                    longitude: -73.9855,
                    images: [
                        "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&q=80",
                        "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80",
                        "https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&q=80",
                        "https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=80"
                    ]
                },
                {
                    id: 3,
                    name: "Budget Traveler Inn",
                    location: "Austin, TX",
                    emoji: "✨",
                    rating: 4.3,
                    price: 85,
                    features: ["WiFi", "Estacionamiento", "Desayuno"],
                    aini_coins: 17,
                    category: "budget",
                    latitude: 30.2672,
                    longitude: -97.7431,
                    images: [
                        "https://images.unsplash.com/photo-1568495248636-6432b97bd949?w=800&q=80",
                        "https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80",
                        "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800&q=80",
                        "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80"
                    ]
                },
                {
                    id: 4,
                    name: "Family Paradise Resort",
                    location: "Orlando, FL",
                    emoji: "🗺️🗺️🗺️??",
                    rating: 4.7,
                    price: 195,
                    features: ["Kids Club", "Piscina", "Transporte Parques", "Restaurante"],
                    aini_coins: 39,
                    category: "family luxury",
                    latitude: 28.3852,
                    longitude: -81.5639,
                    images: [
                        "https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=800&q=80",
                        "https://images.unsplash.com/photo-1584132915807-fd1f5fbc078f?w=800&q=80",
                        "https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800&q=80",
                        "https://images.unsplash.com/photo-1601918774946-25832a4be0d6?w=800&q=80"
                    ]
                },
                {
                    id: 5,
                    name: "Beachfront Paradise",
                    location: "Cancún, México",
                    emoji: "✨",
                    rating: 4.9,
                    price: 250,
                    features: ["Todo Incluido", "Playa", "Spa", "Varias Piscinas"],
                    aini_coins: 50,
                    category: "luxury beach",
                    latitude: 21.1619,
                    longitude: -86.8515,
                    images: [
                        "https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800&q=80",
                        "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&q=80",
                        "https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=800&q=80",
                        "https://images.unsplash.com/photo-1596436889106-be35e843f974?w=800&q=80"
                    ]
                },
                {
                    id: 6,
                    name: "Eco Lodge Retreat",
                    location: "San José, Costa Rica",
                    emoji: "✨",
                    rating: 4.5,
                    price: 120,
                    features: ["Tours Naturaleza", "Restaurante Orgánico", "WiFi", "Yoga"],
                    aini_coins: 24,
                    category: "budget",
                    latitude: 9.9281,
                    longitude: -84.0907,
                    images: [
                        "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80",
                        "https://images.unsplash.com/photo-1587061949409-02df41d5e562?w=800&q=80",
                        "https://images.unsplash.com/photo-1540541338287-41700207dee6?w=800&q=80",
                        "https://images.unsplash.com/photo-1544124499-58912cbddaad?w=800&q=80"
                    ]
                }
            ];
            
            displayHotels(allHotels);
            updateMapMarkers(allHotels);
        }

        // Toggle Map Visibility - 3 states: both, hotels-only, map-only
        let viewState = 'both'; // 'both', 'hotels-only', 'map-only'
        
        function toggleMapVisibility() {
            const mapPanel = document.getElementById('mapPanel');
            const hotelsPanel = document.getElementById('hotelsPanel');
            const toggleBtn = document.getElementById('mapToggleBtn');
            const toggleIcon = document.getElementById('mapToggleIcon');
            const toggleText = document.getElementById('mapToggleText');
            const hotelsList = document.getElementById('hotelsList');
            
            // Cycle through 3 states: both -> hotels-only -> map-only -> both
            if (viewState === 'both') {
                viewState = 'hotels-only';
                
                // Hide map panel completely
                mapPanel.classList.remove('w-1/2');
                mapPanel.classList.add('w-0');
                mapPanel.style.opacity = '0';
                
                // Expand hotels panel to FULL WIDTH
                hotelsPanel.classList.remove('w-1/2', 'w-0');
                hotelsPanel.classList.add('w-full');
                
                // Change hotels to GRID layout (horizontal) - 3-4 columns
                hotelsList.style.display = 'grid';
                hotelsList.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
                hotelsList.style.gap = '1.25rem';
                
                // Add vertical class to all hotel cards
                document.querySelectorAll('.hotel-card').forEach(card => {
                    card.classList.add('hotel-card-vertical');
                });
                
                // Update button
                toggleIcon.textContent = '🗺️';
                toggleText.textContent = 'Solo Mapa';
                
                console.log('? HOTELS ONLY - Full width');
                
            } else if (viewState === 'hotels-only') {
                viewState = 'map-only';
                
                // Hide hotels panel completely
                hotelsPanel.classList.remove('w-1/2', 'w-full');
                hotelsPanel.classList.add('w-0');
                hotelsPanel.style.opacity = '0';
                
                // Expand map to FULL WIDTH
                mapPanel.classList.remove('w-1/2', 'w-0');
                mapPanel.classList.add('w-full');
                mapPanel.style.opacity = '1';
                
                // Update button
                toggleIcon.textContent = '✨';
                toggleText.textContent = 'Ver Ambos';
                
                // Refresh map size
                if (hotelMap) {
                    setTimeout(() => hotelMap.invalidateSize(), 100);
                }
                
                console.log('? MAP ONLY - Full width');
                
            } else {
                viewState = 'both';
                
                // Show both panels (50/50)
                mapPanel.classList.remove('w-0', 'w-full');
                mapPanel.classList.add('w-1/2');
                mapPanel.style.opacity = '1';
                
                hotelsPanel.classList.remove('w-0', 'w-full');
                hotelsPanel.classList.add('w-1/2');
                hotelsPanel.style.opacity = '1';
                
                // Reset hotels to VERTICAL layout
                hotelsList.style.display = 'block';
                
                // Remove vertical class from all hotel cards
                document.querySelectorAll('.hotel-card').forEach(card => {
                    card.classList.remove('hotel-card-vertical');
                });
                
                // Update button
                toggleIcon.textContent = '🗺️';
                toggleText.textContent = 'Solo Hoteles';
                
                // Refresh map size
                if (hotelMap) {
                    setTimeout(() => hotelMap.invalidateSize(), 100);
                }
                
                console.log('? BOTH VISIBLE - Split view');
            }
        }

        function displayHotels(hotels) {
            $('#hotelCount').text(`${hotels.length} hoteles encontrados`);
            
            const hotelsHTML = hotels.map(hotel => {
                const escapedName = hotel.name.replace(/'/g, "\\'");
                return `
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition cursor-pointer hotel-card" 
                     data-id="${hotel.id}" 
                     data-lat="${hotel.latitude}" 
                     data-lng="${hotel.longitude}">
                    <div class="flex">
                        <!-- Hotel Image Carousel -->
                        <div class="hotel-image-container cursor-pointer" id="carousel-${hotel.id}" onclick="if (!event.target.classList.contains('carousel-dot')) viewHotelDetails(${hotel.id})">
                            <img src="${hotel.images[0]}" alt="${hotel.name}" class="carousel-image active">
                            <div class="hotel-carousel-dots">
                                ${hotel.images.map((img, idx) => `
                                    <div class="carousel-dot ${idx === 0 ? 'active' : ''}" 
                                         data-hotel-id="${hotel.id}" 
                                         data-index="${idx}"></div>
                                `).join('')}
                            </div>
                        </div>
                        
                        <!-- Hotel Info -->
                        <div class="flex-1 p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 cursor-pointer hover:text-primary transition" onclick="viewHotelDetails(${hotel.id})">${hotel.name}</h3>
                                    <p class="text-sm text-gray-600">✨ ${hotel.location}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-yellow-500 text-sm">? ${hotel.rating}</div>
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
                                    <span class="text-yellow-600 font-semibold">✨ ${hotel.aini_coins} AiNi</span>
                                </div>
                            </div>
                            
                            <!-- Book Buttons -->
                            <div class="flex gap-2 mt-3">
                                <button onclick="viewHotelDetails(${hotel.id})" 
                                        class="flex-1 bg-purple-100 text-purple-700 py-2 rounded-lg hover:bg-purple-200 transition font-semibold">
                                    ✨ Ver Cuartos
                                </button>
                                <button onclick="viewHotelDetails(${hotel.id})" 
                                        class="flex-1 bg-gradient-to-r from-primary to-secondary text-white py-2 rounded-lg hover:opacity-90 transition font-semibold">
                                    ✨ Reservar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
            
            $('#hotelsList').html(hotelsHTML);
            
            // Initialize carousel auto-rotation
            initCarousels();
            
            // Add click handler to zoom map
            $('.hotel-card').click(function(e) {
                // Don't zoom if clicking on carousel dots, buttons, or clickable elements
                if ($(e.target).hasClass('carousel-dot') || 
                    $(e.target).is('button') || 
                    $(e.target).closest('button').length > 0 ||
                    $(e.target).closest('.hotel-image-container').length > 0 ||
                    $(e.target).is('h3') ||
                    $(e.target).closest('h3').length > 0) {
                    return;
                }
                
                const lat = $(this).data('lat');
                const lng = $(this).data('lng');
                hotelMap.setView([lat, lng], 13);
                
                // Highlight this card
                $('.hotel-card').removeClass('ring-2 ring-primary');
                $(this).addClass('ring-2 ring-primary');
            });
            
            // Carousel dot click handlers
            $('.carousel-dot').click(function(e) {
                e.stopPropagation();
                const hotelId = $(this).data('hotel-id');
                const index = $(this).data('index');
                showCarouselImage(hotelId, index);
            });
        }
        
        // Carousel functionality
        let carouselIntervals = {};
        
        function initCarousels() {
            // Clear existing intervals
            Object.values(carouselIntervals).forEach(interval => clearInterval(interval));
            carouselIntervals = {};
            
            // Start auto-rotation for each hotel
            allHotels.forEach(hotel => {
                let currentIndex = 0;
                carouselIntervals[hotel.id] = setInterval(() => {
                    currentIndex = (currentIndex + 1) % hotel.images.length;
                    showCarouselImage(hotel.id, currentIndex);
                }, 3000); // Change image every 3 seconds
            });
        }
        
        function showCarouselImage(hotelId, index) {
            const hotel = allHotels.find(h => h.id === hotelId);
            if (!hotel) return;
            
            const container = $(`#carousel-${hotelId}`);
            const img = container.find('img');
            
            // Update image
            img.attr('src', hotel.images[index]);
            
            // Update dots
            container.find('.carousel-dot').removeClass('active');
            container.find(`.carousel-dot[data-index="${index}"]`).addClass('active');
            
            // Reset auto-rotation timer
            if (carouselIntervals[hotelId]) {
                clearInterval(carouselIntervals[hotelId]);
                let currentIndex = index;
                carouselIntervals[hotelId] = setInterval(() => {
                    currentIndex = (currentIndex + 1) % hotel.images.length;
                    showCarouselImage(hotelId, currentIndex);
                }, 3000);
            }
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

        // function filterByCategory(category) {
        //     // DEPRECATED - Using main filter dropdown now
        //     $('.filter-btn').removeClass('active-filter bg-primary text-white');
        //     event.target.classList.add('active-filter', 'bg-primary', 'text-white');
        //     
        //     let filtered = allHotels;
        //     if (category !== 'all') {
        //         filtered = allHotels.filter(h => h.category.includes(category));
        //     }
        //     
        //     displayHotels(filtered);
        //     updateMapMarkers(filtered);
        // }

        // ==========================================
        // AI SEARCH FUNCTIONS
        // ==========================================
        
        function setAISearch(query) {
            $('#aiSearchInput').val(query);
            performAISearch();
        }
        
        async function performAISearch() {
            const query = $('#aiSearchInput').val().trim();
            
            if (!query) {
                showAIResponse('Por favor escribe qué tipo de hotel buscas ✨', 'warning');
                return;
            }
            
            // Clear input for next message
            $('#aiSearchInput').val('');
            
            // Show AI thinking
            showAIResponse('✨ Vicky está analizando tu búsqueda...', 'loading');
            
            // Show loading in list
            $('#hotelsList').html(`
                <div class="text-center py-12">
                    <div class="text-5xl mb-4 animate-bounce">✨</div>
                    <p class="text-xl font-semibold text-purple-600">Vicky buscando hoteles...</p>
                    <p class="text-gray-500 mt-2">"${query}"</p>
                </div>
            `);
            
            try {
                console.log('AI Search: Calling TRAINED intelligent search...');
                
                // Step 1: Call trained AI system
                const trainedResponse = await fetch('ai_trained_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ query: query })
                }).catch(err => {
                    console.error('Fetch error:', err);
                    throw new Error('Network error: ' + err.message);
                });
                
                console.log('Trained AI Response status:', trainedResponse.status);
                
                if (!trainedResponse.ok) {
                    const errorText = await trainedResponse.text();
                    console.error('Trained AI error response:', errorText);
                    throw new Error('Trained AI failed: ' + trainedResponse.status);
                }
                
                const trainedResult = await trainedResponse.json();
                
                console.log('Trained AI Response:', trainedResult);
                
                // Check for errors in response
                if (trainedResult.error) {
                    console.warn('AI returned error:', trainedResult.error);
                    // Use fallback if available
                    if (trainedResult.note) {
                        showAIResponse(`✨ ${trainedResult.note}: ${trainedResult.response || trainedResult.error}`, 'warning');
                    } else {
                        showAIResponse(`? ${trainedResult.error}`, 'error');
                    }
                } else if (trainedResult.response) {
                    const emoji = trainedResult.method === 'training_direct' ? '✨' : 
                                 trainedResult.method === 'training_fallback' ? '✨' : '✨';
                    const confidence = trainedResult.confidence ? ` (${Math.round(trainedResult.confidence)}% match)` : '';
                    const note = trainedResult.note ? ` - ${trainedResult.note}` : '';
                    showAIResponse(`${emoji} ${trainedResult.response}${confidence}${note}`, 'info');
                }
                
                // Check if it's a conversational query (not a search)
                const isGreeting = /^(hola|hi|hey|buenos dias|buenas tardes|buenas noches)/i.test(query);
                const isPersonalInfo = /me llamo|soy|mi nombre/i.test(query);
                
                if (isGreeting || isPersonalInfo || trainedResult.category === 'general') {
                    // Show all hotels for conversational queries
                    displayHotels(allHotels);
                    updateMapMarkers(allHotels);
                    return;
                }
                
                // Step 2: Use AI to extract search parameters and search database
                const searchResponse = await fetch('ai_extract_search.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: query })
                });
                
                console.log('AI Search: Response status:', searchResponse.status);
                
                if (!searchResponse.ok) {
                    throw new Error(`HTTP error! status: ${searchResponse.status}`);
                }
                
                const searchData = await searchResponse.json();
                console.log('AI Search: Database results:', searchData);
                
                if (searchData.success && searchData.hotels && searchData.hotels.length > 0) {
                    // Convert database results to display format
                    const dbHotels = searchData.hotels.map(h => ({
                        id: h.id,
                        name: h.name,
                        location: h.location,
                        emoji: h.emoji || '✨',
                        rating: parseFloat(h.rating),
                        price: parseFloat(h.price),
                        features: h.features || [],
                        ainiCoins: h.aini_coins || 0,
                        category: h.category || 'standard',
                        lat: parseFloat(h.latitude),
                        lng: parseFloat(h.longitude),
                        images: h.images || []
                    }));
                    
                    // Show success message
                    const hotelsText = dbHotels.slice(0, 3).map(h => `? ${h.name} ($${h.price})`).join('<br>');
                    showAIResponse(`Encontré ${dbHotels.length} hoteles para ti:<br><br>${hotelsText}`, 'success');
                    
                    // Display hotels
                    displayHotels(dbHotels);
                    updateMapMarkers(dbHotels);
                    
                } else {
                    // No results from database
                    showAIResponse('No encontré hoteles con esos criterios. Te muestro todas las opciones disponibles.', 'info');
                    displayHotels(allHotels);
                    updateMapMarkers(allHotels);
                }
                
            } catch (error) {
                console.error('AI Search Error:', error);
                
                // Show error message
                showAIResponse('Hmm, tuve un problema. Hice una búsqueda básica. ✨', 'error');
                
                // Fallback to simple keyword search
                const filtered = allHotels.filter(h => 
                    h.name.toLowerCase().includes(query.toLowerCase()) || 
                    h.location.toLowerCase().includes(query.toLowerCase()) ||
                    h.features.some(f => f.toLowerCase().includes(query.toLowerCase()))
                );
                
                if (filtered.length > 0) {
                    displayHotels(filtered);
                    updateMapMarkers(filtered);
                } else {
                    displayHotels(allHotels);
                    updateMapMarkers(allHotels);
                }
            }
        }
        
        function showAIResponse(message, type = 'info') {
            const bubble = $('#aiResponseBubble');
            const textDiv = $('#aiResponseText');
            
            // Add emoji based on type
            let emoji = '✨';
            if (type === 'success') emoji = '?';
            if (type === 'warning') emoji = '✨';
            if (type === 'error') emoji = '?';
            if (type === 'loading') emoji = '?';
            
            textDiv.html(`<p>${emoji} ${message}</p>`);
            bubble.fadeIn(300);
            
            // No auto-close - user must close manually
        }
        
        function closeAIResponse() {
            $('#aiResponseBubble').fadeOut(300);
        }
        
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('active');
        }
        
        // Toggle Language Menu
        function toggleLanguageMenu() {
            const menu = document.getElementById('languageMenu');
            const profileMenu = document.getElementById('profileMenu');
            if (profileMenu) profileMenu.classList.add('hidden');
            menu.classList.toggle('hidden');
        }
        
        // Toggle Profile Menu
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            const langMenu = document.getElementById('languageMenu');
            if (langMenu) langMenu.classList.add('hidden');
            menu.classList.toggle('hidden');
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobileMenu');
            const button = document.querySelector('.mobile-menu-button');
            const langMenu = document.getElementById('languageMenu');
            const profileMenu = document.getElementById('profileMenu');
            
            // Close mobile menu
            if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.remove('active');
            }
            
            // Close dropdown menus when clicking outside
            if (langMenu && !langMenu.contains(event.target) && !event.target.closest('button[onclick="toggleLanguageMenu()"]')) {
                langMenu.classList.add('hidden');
            }
            if (profileMenu && !profileMenu.contains(event.target) && !event.target.closest('button[onclick="toggleProfileMenu()"]')) {
                profileMenu.classList.add('hidden');
            }
        });

        function searchHotels() {
            const destination = $('#searchDestination').val().toLowerCase();
            const checkin = $('#searchCheckin').val();
            const checkout = $('#searchCheckout').val();
            const guests = $('#searchGuests').val();
            
            if (!destination) {
                displayHotels(allHotels);
                updateMapMarkers(allHotels);
                hideSearchSummary();
                return;
            }
            
            // Split search terms by spaces and commas for flexible matching
            const searchTerms = destination.split(/[\s,]+/).filter(term => term.length > 0);
            
            const filtered = allHotels.filter(h => {
                const hotelName = h.name.toLowerCase();
                const hotelLocation = h.location.toLowerCase();
                const combinedText = `${hotelName} ${hotelLocation}`;
                
                // Match if ANY search term is found in name or location
                return searchTerms.some(term => combinedText.includes(term));
            });
            
            if (filtered.length > 0) {
                displayHotels(filtered);
                updateMapMarkers(filtered);
                showSearchSummary(destination, filtered.length, checkin, checkout, guests);
            } else {
                $('#hotelsList').html(`
                    <div class="text-center py-12 text-gray-500">
                        <div class="text-5xl mb-4">✨</div>
                        <p>No se encontraron hoteles para "${destination}"</p>
                        <button onclick="clearSearch()" class="mt-4 px-6 py-2 bg-primary text-white rounded-lg">
                            Ver todos los hoteles
                        </button>
                    </div>
                `);
                hideSearchSummary();
            }
        }

        function showSearchSummary(destination, count, checkin, checkout, guests) {
            const nights = calculateNights(checkin, checkout);
            const summary = `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-blue-900">✨ ${count} hoteles en "${destination}"</p>
                        <p class="text-sm text-blue-700">✨ ${checkin} ? ${checkout} (${nights} noche${nights > 1 ? 's' : ''}) ● ✨ ${guests} huésped${guests > 1 ? 'es' : ''}</p>
                    </div>
                    <button onclick="clearSearch()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Limpiar búsqueda
                    </button>
                </div>
            `;
            $('#searchSummary').remove();
            $('#hotelsList').before(summary);
        }

        function hideSearchSummary() {
            $('#searchSummary').remove();
        }

        function clearSearch() {
            $('#searchDestination').val('');
            loadHotels();
            hideSearchSummary();
        }

        function calculateNights(checkin, checkout) {
            if (!checkin || !checkout) return 1;
            const date1 = new Date(checkin);
            const date2 = new Date(checkout);
            const diffTime = Math.abs(date2 - date1);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }

        let selectedHotelData = null;

        function bookHotel(hotelId, hotelName) {
            // Find the hotel data
            selectedHotelData = allHotels.find(h => h.id == hotelId);
            
            if (!selectedHotelData) {
                alert('Hotel no encontrado');
                return;
            }
            
            // Populate modal with hotel data
            $('#modalHotelEmoji').text(selectedHotelData.emoji);
            $('#modalHotelName').text(selectedHotelData.name);
            $('#modalHotelLocation').text(`✨ ${selectedHotelData.location}`);
            $('#modalHotelRating').text(`? ${selectedHotelData.rating}`);
            
            // Set dates from search
            const checkin = $('#searchCheckin').val();
            const checkout = $('#searchCheckout').val();
            const guests = $('#searchGuests').val();
            
            $('#modalCheckin').val(checkin);
            $('#modalCheckout').val(checkout);
            $('#modalGuests').val(guests);
            
            // Calculate and display prices
            updateModalPrices();
            
            // Show modal
            $('#bookingModal').addClass('active');
            $('body').css('overflow', 'hidden'); // Prevent scrolling
        }
        
        // View hotel details page with rooms
        function viewHotelDetails(hotelId) {
            window.location.href = `hotel_details.php?id=${hotelId}`;
        }
        
        function updateModalPrices() {
            if (!selectedHotelData) return;
            
            const checkin = $('#modalCheckin').val();
            const checkout = $('#modalCheckout').val();
            
            let nights = 1;
            if (checkin && checkout) {
                const date1 = new Date(checkin);
                const date2 = new Date(checkout);
                const diffTime = Math.abs(date2 - date1);
                nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            }
            
            const pricePerNight = selectedHotelData.price;
            const subtotal = pricePerNight * nights;
            const ainiCoins = selectedHotelData.aini_coins * nights;
            
            $('#modalPricePerNight').text(`$${pricePerNight}`);
            $('#modalNightsLabel').text(`🌙 ${nights} ${nights === 1 ? 'noche' : 'noches'}`);
            $('#modalSubtotal').text(`$${subtotal}`);
            $('#modalTotal').text(`$${subtotal}`);
            $('#modalAiniCoins').text(ainiCoins);
        }
        
        function closeBookingModal() {
            $('#bookingModal').removeClass('active');
            $('body').css('overflow', 'auto'); // Restore scrolling
            selectedHotelData = null;
        }
        
        function closeModalOnOutside(event) {
            if (event.target.id === 'bookingModal') {
                closeBookingModal();
            }
        }
        
        function confirmWhatsAppBooking() {
            if (!selectedHotelData) return;
            
            const checkin = $('#modalCheckin').val();
            const checkout = $('#modalCheckout').val();
            const guests = $('#modalGuests').val();
            const total = $('#modalTotal').text();
            const coins = $('#modalAiniCoins').text();
            
            // Calculate nights
            let nights = 1;
            if (checkin && checkout) {
                const date1 = new Date(checkin);
                const date2 = new Date(checkout);
                const diffTime = Math.abs(date2 - date1);
                nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            }
            
            // Create detailed WhatsApp message
            const message = `Hola! ✨ Quiero hacer una reserva:

? Hotel: ${selectedHotelData.name}
✨ Ubicación: ${selectedHotelData.location}
? Rating: ${selectedHotelData.rating}

📅 Check-in: ${checkin}
📅 Check-out: ${checkout}
🗺️ ${nights} ${nights === 1 ? 'noche' : 'noches'}
✨ ${guests} ${guests === '1' ? 'huésped' : 'huéspedes'}

✨ Precio Total: ${total}
✨ Ganarás ${coins} AiNi Coins

¿Puedes ayudarme a completar la reserva? ✨`;
            
            const encodedMessage = encodeURIComponent(message);
            
            // Open WhatsApp
            window.open(`https://wa.me/1234567890?text=${encodedMessage}`, '_blank');
            
            // Close modal
            closeBookingModal();
        }
        
        // Update prices when dates change
        $(document).on('change', '#modalCheckin, #modalCheckout', function() {
            updateModalPrices();
        });
        
        // ==========================================
        // FILTER FUNCTIONS
        // ==========================================
        
        let currentFilters = {
            maxPrice: 300,
            minRating: 0,
            amenities: [],
            minCoins: 0
        };
        
        function toggleFilters() {
            $('#filterDropdown').toggleClass('active');
        }
        
        function updatePriceRange(value) {
            $('#priceValue').text('$' + value);
            currentFilters.maxPrice = parseInt(value);
        }
        
        function updateCoinsRange(value) {
            $('#coinsValue').text(value + ' coins');
            currentFilters.minCoins = parseInt(value);
        }
        
        function applyFilters() {
            // Get rating filter
            currentFilters.minRating = parseFloat($('input[name="rating"]:checked').val());
            
            // Get amenities filter
            currentFilters.amenities = [];
            $('input[type="checkbox"]:checked').each(function() {
                currentFilters.amenities.push($(this).val());
            });
            
            // Filter hotels
            let filteredHotels = allHotels.filter(hotel => {
                // Price filter
                if (hotel.price > currentFilters.maxPrice) return false;
                
                // Rating filter
                if (hotel.rating < currentFilters.minRating) return false;
                
                // Coins filter
                if (hotel.aini_coins < currentFilters.minCoins) return false;
                
                // Amenities filter
                if (currentFilters.amenities.length > 0) {
                    const hasAllAmenities = currentFilters.amenities.every(amenity => 
                        hotel.features.includes(amenity)
                    );
                    if (!hasAllAmenities) return false;
                }
                
                return true;
            });
            
            // Display filtered hotels
            displayHotels(filteredHotels);
            updateMapMarkers(filteredHotels);
            
            // Show count
            console.log(`Showing ${filteredHotels.length} of ${allHotels.length} hotels`);
        }
        
        // ==========================================
        // AI CHAT FUNCTIONS
        // ==========================================
        
        function toggleAIChat() {
            $('#aiChatWindow').toggleClass('active');
            if ($('#aiChatWindow').hasClass('active')) {
                $('#aiChatButton').hide();
                $('#aiChatInput').focus();
            } else {
                $('#aiChatButton').show();
            }
        }
        
        function sendAIMessage() {
            const input = $('#aiChatInput');
            const message = input.val().trim();
            
            if (!message) return;
            
            // Add user message
            addMessage(message, 'user');
            input.val('');
            
            // Show typing indicator
            showAITyping();
            
            // Send to AI
            askAI(message);
        }
        
        function addMessage(text, type) {
            const messageClass = type === 'user' ? 'user-message' : 'ai-message';
            const messageHTML = `<div class="${messageClass}">${text}</div>`;
            
            // Remove typing indicator
            $('.ai-typing').remove();
            
            $('#aiChatMessages').append(messageHTML);
            
            // Scroll to bottom
            const messagesDiv = document.getElementById('aiChatMessages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        function showAITyping() {
            const typingHTML = `
                <div class="ai-message ai-typing">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
            $('#aiChatMessages').append(typingHTML);
            
            const messagesDiv = document.getElementById('aiChatMessages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        async function askAI(question) {
            // Hide suggestions after first question
            $('#aiSuggestions').hide();
            
            // If this is from a suggestion button, show it as user message
            if (!$('.user-message:last').text().includes(question)) {
                addMessage(question, 'user');
            }
            
            showAITyping();
            
            try {
                // Build conversation history (last 8 messages)
                const messages = $('#aiChatMessages .user-message, #aiChatMessages .ai-message')
                    .toArray()
                    .slice(-8)
                    .map(el => ({
                        role: $(el).hasClass('user-message') ? 'user' : 'assistant',
                        content: $(el).text().trim()
                    }));
                
                // Call Sofia Travel API with conversation history
                const response = await fetch('sofia_travel_api_simple.php?action=chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: question,
                        history: messages
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let aiResponse = data.response || "Lo siento, no pude procesar tu pregunta.";
                    
                    // Store conversation ID for potential follow-ups
                    window.lastConversationId = data.conversation_id;
                    window.sofiaSessionId = data.session_id;
                    
                    // Remove typing indicator
                    $('.ai-typing').remove();
                    
                    // Add AI response
                    addMessage(aiResponse, 'ai');
                    
                    // Log performance (optional)
                    if (data.response_time_ms) {
                        console.log(`Sofia responded in ${data.response_time_ms}ms (${data.tokens_used} tokens)`);
                    }
                    
                    // Check if response suggests booking
                    if (aiResponse.toLowerCase().includes('reservar') || 
                        aiResponse.toLowerCase().includes('booking') ||
                        question.toLowerCase().includes('reservar')) {
                        setTimeout(() => {
                            addBookingOptions();
                        }, 1000);
                    }
                } else {
                    // API returned error
                    $('.ai-typing').remove();
                    addMessage(data.response || data.message || "Lo siento, estoy teniendo problemas técnicos. ✨", 'ai');
                }
                
            } catch (error) {
                console.error('Sofia API Error:', error);
                $('.ai-typing').remove();
                addMessage("Lo siento, estoy teniendo problemas para conectarme. Por favor, intenta de nuevo o usa WhatsApp para asistencia inmediata. ✨", 'ai');
            }
        }
        
        function buildAIPrompt(userQuestion) {
            const hotelsContext = allHotels.map(h => 
                `${h.name} en ${h.location} - $${h.price}/noche, rating ${h.rating}, categoría: ${h.category}`
            ).join('\n');
            
            const prompt = `Eres un asistente de viajes amigable para AiNi Travel. Responde en Español de forma breve y útil.

Hoteles disponibles:
${hotelsContext}

Usuario pregunta: ${userQuestion}

Responde de forma concisa y amigable. Si preguntan por reservas, ofrece ayuda para reservar. Usa emojis cuando sea apropiado.`;
            
            return prompt;
        }
        
        // ==========================================
        // SOFIA INITIALIZATION & HISTORY
        // ==========================================
        
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        
        async function loadConversationHistory() {
            try {
                const response = await fetch('sofia_travel_api.php?action=history');
                const data = await response.json();
                
                if (data.success && data.messages && data.messages.length > 0) {
                    // Clear welcome message
                    $('#aiChatMessages').html('');
                    
                    // Add all previous messages
                    data.messages.forEach(msg => {
                        addMessage(msg.content, msg.role === 'user' ? 'user' : 'ai');
                    });
                    
                    console.log(`Loaded ${data.messages.length} messages from history`);
                }
            } catch (error) {
                console.error('Error loading Sofia history:', error);
                // Keep welcome message if history fails
            }
        }
        
        function initializeSofia() {
            // Check if user has existing session
            const sessionCookie = getCookie('aini_sofia_session');
            
            if (sessionCookie) {
                // Load conversation history
                console.log('Sofia: Loading conversation history...');
                loadConversationHistory();
            } else {
                console.log('Sofia: New session - showing welcome message');
                // Welcome message already in HTML
            }
        }
        
        function clearSofiaHistory() {
            fetch('sofia_travel_api.php?action=clear')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#aiChatMessages').html(`
                            <div class="ai-message">
                                ¡Conversación reiniciada! ✨
                                <br><br>
                                ¿En qué puedo ayudarte hoy?
                            </div>
                        `);
                    }
                })
                .catch(error => console.error('Clear history error:', error));
        }
        
        function addBookingOptions() {
            const bookingHTML = `
                <div class="ai-message">
                    ¿Te gustaría que te ayude a hacer la reserva? 
                    <br><br>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                        <button onclick="startAIBooking()" 
                                style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 20px; cursor: pointer;">
                            Sí, reservar ahora
                        </button>
                        <button onclick="addMessage('Quiero ver más opciones', 'user'); askAI('Muéstrame más opciones de hoteles')" 
                                style="padding: 0.5rem 1rem; background: white; color: #667eea; border: 2px solid #667eea; border-radius: 20px; cursor: pointer;">
                            Ver más opciones
                        </button>
                    </div>
                </div>
            `;
            $('#aiChatMessages').append(bookingHTML);
            
            const messagesDiv = document.getElementById('aiChatMessages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        function startAIBooking() {
            addMessage("Perfecto! Voy a ayudarte con la reserva. ✨", 'ai');
            
            setTimeout(() => {
                const hotelsHTML = `
                    <div class="ai-message">
                        Aquí están nuestros hoteles disponibles. Haz click en "Reservar" en el que te guste:
                        <br><br>
                        ${allHotels.slice(0, 3).map(h => `
                            <div style="border: 1px solid #e0e0e0; border-radius: 10px; padding: 0.75rem; margin-bottom: 0.5rem; background: white;">
                                <div style="font-weight: bold;">${h.emoji} ${h.name}</div>
                                <div style="font-size: 0.85rem; color: #666;">✨ ${h.location}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                    <span style="color: #667eea; font-weight: bold;">$${h.price}/noche</span>
                                    <button onclick="bookHotelFromAI(${h.id})" 
                                            style="padding: 0.4rem 1rem; background: #667eea; color: white; border: none; border-radius: 15px; cursor: pointer; font-size: 0.85rem;">
                                        Reservar
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                        <br>
                        <button onclick="$('#aiChatWindow').removeClass('active'); $('#aiChatButton').show();" 
                                style="padding: 0.5rem 1rem; background: white; color: #667eea; border: 2px solid #667eea; border-radius: 20px; cursor: pointer; width: 100%;">
                            Ver todos en el mapa
                        </button>
                    </div>
                `;
                $('#aiChatMessages').append(hotelsHTML);
                
                const messagesDiv = document.getElementById('aiChatMessages');
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }, 500);
        }
        
        function bookHotelFromAI(hotelId) {
            // Close AI chat
            $('#aiChatWindow').removeClass('active');
            $('#aiChatButton').show();
            
            // Open booking modal with selected hotel
            bookHotel(hotelId, '');
        }

        // Shopping Cart / Reservations functionality
        let reservationsCart = [];

        function openReservationsCart(event) {
            if (event) event.preventDefault();
            
            // Create modal if it doesn't exist
            if (!document.getElementById('cartModal')) {
                createCartModal();
            }
            
            updateCartDisplay();
            document.getElementById('cartModal').style.display = 'flex';
        }

        function createCartModal() {
            const modalHTML = `
                <div id="cartModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
                    <div style="background: white; border-radius: 20px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 2rem; position: relative;">
                        <button onclick="closeCart()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 2rem; cursor: pointer; color: #666;">×</button>
                        
                        <h2 style="font-size: 2rem; font-weight: bold; margin-bottom: 1.5rem; color: #667eea;">
                            ✨ My Reservations
                        </h2>
                        
                        <div id="cartItems"></div>
                        
                        <div id="cartEmpty" style="text-align: center; padding: 3rem; color: #999;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">✨</div>
                            <p style="font-size: 1.2rem;">No reservations yet</p>
                            <p style="margin-top: 0.5rem;">Add hotels to your cart to get started!</p>
                        </div>
                        
                        <div id="cartSummary" style="display: none; border-top: 2px solid #eee; padding-top: 1.5rem; margin-top: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem;">
                                <span>Total:</span>
                                <span id="cartTotal" style="color: #667eea;">$0</span>
                            </div>
                            <button onclick="proceedToCheckout()" style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: bold; cursor: pointer;">
                                ✨ Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        function closeCart() {
            document.getElementById('cartModal').style.display = 'none';
        }

        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const cartEmpty = document.getElementById('cartEmpty');
            const cartSummary = document.getElementById('cartSummary');
            const cartCount = document.getElementById('cartCount');
            const cartCountMobile = document.getElementById('cartCountMobile');
            
            if (reservationsCart.length === 0) {
                cartItems.innerHTML = '';
                cartEmpty.style.display = 'block';
                cartSummary.style.display = 'none';
                cartCount.classList.add('hidden');
                cartCountMobile.classList.add('hidden');
            } else {
                cartEmpty.style.display = 'none';
                cartSummary.style.display = 'block';
                cartCount.classList.remove('hidden');
                cartCountMobile.classList.remove('hidden');
                cartCount.textContent = reservationsCart.length;
                cartCountMobile.textContent = reservationsCart.length;
                
                let total = 0;
                cartItems.innerHTML = reservationsCart.map((item, index) => {
                    total += item.totalPrice;
                    return `
                        <div style="border: 1px solid #e0e0e0; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; background: #f9f9f9;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <h3 style="font-size: 1.2rem; font-weight: bold; color: #333; margin-bottom: 0.5rem;">
                                        ${item.hotelEmoji} ${item.hotelName}
                                    </h3>
                                    <p style="color: #666; margin-bottom: 0.25rem;">✨ ${item.checkIn} ? ${item.checkOut}</p>
                                    <p style="color: #666; margin-bottom: 0.25rem;">✨ ${item.guests} guest(s) ● ${item.nights} night(s)</p>
                                    <p style="font-weight: bold; color: #667eea; font-size: 1.1rem; margin-top: 0.5rem;">$${item.totalPrice}</p>
                                </div>
                                <button onclick="removeFromCart(${index})" style="background: #ff4444; color: white; border: none; border-radius: 5px; padding: 0.5rem 1rem; cursor: pointer; font-size: 0.9rem;">
                                    🗺️ Remove
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
                
                document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);
            }
        }

        function addToCart(hotelId, hotelName, hotelEmoji, checkIn, checkOut, guests, pricePerNight) {
            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);
            const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
            const totalPrice = nights * pricePerNight;
            
            reservationsCart.push({
                hotelId,
                hotelName,
                hotelEmoji,
                checkIn,
                checkOut,
                guests,
                nights,
                pricePerNight,
                totalPrice
            });
            
            updateCartDisplay();
            
            // Show success message
            alert('? Reservation added to cart!');
        }

        function removeFromCart(index) {
            reservationsCart.splice(index, 1);
            updateCartDisplay();
        }

        function proceedToCheckout() {
            if (reservationsCart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            // For now, show a message. Later we'll integrate with payment API
            alert('✨ Proceeding to payment...\n\nThis will integrate with Adyen payment gateway soon!');
            
            // TODO: Integrate with payment_api
            // window.location.href = '/payment_api/checkout.php';
        }
    </script>
</body>
</html>
