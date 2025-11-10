# Product Context

Describe the product.

## Overview

Provide a high-level overview of the project.

## Core Features

- Feature 1
- Feature 2

## Technical Stack

- Tech 1
- Tech 2

## Project Description

AiNi Coin: Revolutionary multi-asset backed dual-coin loyalty ecosystem for travel industry. AiNi Rewards (stable, inflation-protected, 1:1 backed by 30% USD + 25% EUR + 20% Gold + 15% Silver + 10% Services) and AiNi Crypto (market-driven investment token). Zero transaction fees. Transparent reserve fund dashboard. Partners earn coins for bookings, travelers redeem for hotels/tours/transfers across Latin America network.



AiNi Travel is a revolutionary travel platform with a complete cryptocurrency-style payment ecosystem. The platform features a dual-currency system: AiNi Coins (blockchain-backed travel currency with 1:1 USD value) for the public booking system at ainitravel.com, and HotelCoins for the internal hotel management system. The AiNi Coin system operates as a closed-loop travel economy where users can buy coins once and spend them across an entire partner network (hotels, tours, restaurants, transportation, shops) throughout their trip, saving 10-15% on traditional payment processing fees. All transactions are secured with SHA-256 hashing in an immutable blockchain-style ledger, providing transparency and fraud prevention while maintaining readiness for future stablecoin backing and public blockchain deployment.



AiNi Travel - Complete travel ecosystem platform with blockchain-style cryptocurrency payment system. Consists of two main platforms: 1) AiNi Travel (ainitravel.com) - Hotel booking OTA with integrated coin rewards system, 2) AiniFlow (ainiflow.com) - Social travel network. The coin system enables a closed-loop travel economy where users can buy, earn, spend, and transfer AiNi Coins across partner businesses (hotels, tours, restaurants, transportation).



Revolutionary hotel management platform with multi-currency Aini Coin rewards ecosystem (closed-loop travel currency), public booking system, WhatsApp customer service bot, social messaging (AiniFlow), partner network management, and role-based dashboards (admin/manager/accounting)



Hotel management platform with booking, room management, rewards system, and WhatsApp integration



## Architecture

Multi-server: XAMPP/PHP localhost (hotel PMS), AiniFlow Node.js port 3000 (real-time chat/AI), Ollama AI 72.60.1.16:11434 (NLP), Production VPS (ainitravel.com landing + APIs). Databases: hotel_booking_system MySQL (13 AiNi Coin tables with SHA-256 blockchain ledger), aini_platform PostgreSQL. Deployment: SSH prod-vps passwordless, paths /var/www/html/ainitravel.com/ (landing), /var/www/html/manage/ (PMS). Landing page: coming-soon.html (NOT index.html per Apache DirectoryIndex). Price updates: cron every 15 min via update_prices.php.



The system uses a multi-server architecture: prod-vps (108.175.12.152) hosts the Apache/PHP/MySQL stack for both the property management system and ainitravel.com public site, while social-vps (72.61.217.65) runs Nginx/Node.js/PostgreSQL for ainiflow.com social platform. The AiNi Coin system implements a complete financial ecosystem with 13 database tables including blockchain-style transaction ledger (aini_coin_transactions with SHA-256 hashing and previous transaction linking), partner network management (aini_partner_businesses), purchase tracking (aini_coin_purchases), settlement system (aini_partner_settlements), reserve fund monitoring (aini_coin_reserve_fund), and multi-currency exchange rates (aini_coin_exchange_rates supporting USD, EUR, GBP, PEN, MXN, BRL). Database credentials: hoteluser/hotelpass123 (NOT root - auth_socket issue). All public pages share consistent navigation header/footer using gradient purple theme.



Dual-server architecture: prod-vps (108.175.12.152) runs Apache+PHP+MySQL for hotel booking system and OTA site. social-vps (72.61.217.65) runs Nginx+Node.js+PostgreSQL for social network. AiNi Coin system uses immutable blockchain-style ledger with SHA-256 transaction hashing for tamper-proof records. Multi-currency support with exchange rate management. Partner network supports hotels, tours, restaurants, transportation, shops. Reserve fund tracking ensures 1:1 USD backing for all coins in circulation.



Multi-server architecture: 
**Server 1 (XAMPP)**: PHP/MySQL hotel management on localhost with PDO connections to 'hotel_booking_system' DB (user: hoteluser/hotelpass123)
**Server 4 (AiniFlow)**: Node.js/Express on port 3000 with PostgreSQL (port 5432, DB: aini_platform) and Redis (port 6379) for real-time social features, messaging via Socket.IO, and WhatsApp integration via Twilio
**AI Server**: Ollama at http://72.60.1.16:11434 for natural language room queries



Monolithic PHP application with separate modules for room management, booking, accounting, AI features, and WhatsApp bot integration. Multiple file versions exist for different features (current, modern, production, server variants).



## Technologies

- PHP 8.2
- MySQL 8.0
- Node.js
- Express
- PostgreSQL
- Redis
- Apache
- Chart.js
- Tailwind CSS
- Socket.IO
- Twilio WhatsApp API
- Ollama AI (llama3.2)
- Mercado Pago API (payment processing)
- Free forex/metals APIs (open.er-api.com, api.nbp.pl, api.exchangerate.host)



- PHP 8.2
- MySQL 8.0
- Apache 2.4
- PDO Database Connections
- Tailwind CSS
- Font Awesome
- JavaScript ES6
- SHA-256 Hashing
- Blockchain-style Ledger
- Multi-currency Exchange
- Session Management
- RESTful Architecture



- PHP 8.2
- MySQL 8.0
- Node.js
- PostgreSQL
- Tailwind CSS
- jQuery
- Google Translate API
- Leaflet Maps
- WhatsApp Business API
- Blockchain-style hashing (SHA-256)



- PHP 7+
- MySQL/MariaDB
- JavaScript ES6+
- Node.js
- PostgreSQL
- Redis
- HTML5/CSS3
- XAMPP stack
- Socket.IO
- Twilio API
- Ollama AI



- PHP
- MySQL
- JavaScript
- HTML/CSS
- XAMPP
- WhatsApp API
- PDO



## Libraries and Dependencies

- PDO (database)
- cURL (API calls)
- Stripe PHP SDK (future global expansion)
- Mercado Pago PHP SDK (primary payment processor)
- Chart.js (data visualization)
- Font Awesome (icons)
- Tailwind CSS (styling)



- Tailwind CSS CDN
- Font Awesome 6.4.0
- PDO MySQL Extension
- OpenSSL for SHA-256
- JSON for metadata storage



- PDO (PHP Data Objects)
- bcrypt password hashing
- Express.js
- Sequelize ORM



- PDO (database access)
- WhatsApp Business API via Twilio
- Socket.IO (real-time messaging)
- PostgreSQL pg module
- Redis client
- Express.js
- Ollama AI (self-hosted)
- Custom AiniCoin System



- PDO for database connections
- WhatsApp Business API
- Aini Coin System (custom)
- Calendar views
- Photo upload handlers

