<?php
/**
 * WhatsApp Business API Integration for Revolutionary Hotel Platform
 * Handles incoming WhatsApp messages, processes with AI, manages bookings
 */

class WhatsAppHotelBot {
    private $connection;
    private $ai;
    private $accessToken;
    private $phoneNumberId;
    private $webhookToken;
    
    public function __construct() {
        require_once 'database.php';
        require_once 'ollama_ai.php';
        require_once 'hotel_classes.php';
        
        $this->connection = getConnection();
        $this->ai = new OllamaAI();
        
        // WhatsApp API Configuration
        $this->accessToken = $this->getConfig('whatsapp_access_token');
        $this->phoneNumberId = $this->getConfig('whatsapp_phone_number_id');
        $this->webhookToken = $this->getConfig('whatsapp_webhook_token');
    }
    
    /**
     * Handle incoming WhatsApp webhook
     */
    public function handleWebhook() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Verify webhook (for initial setup)
        if (isset($_GET['hub_mode']) && $_GET['hub_mode'] === 'subscribe') {
            if ($_GET['hub_verify_token'] === $this->webhookToken) {
                echo $_GET['hub_challenge'];
                return;
            }
        }
        
        // Process incoming messages
        if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
            foreach ($data['entry'][0]['changes'][0]['value']['messages'] as $message) {
                $this->processMessage($message, $data['entry'][0]['changes'][0]['value']);
            }
        }
        
        http_response_code(200);
    }
    
    /**
     * Process individual WhatsApp message
     */
    private function processMessage($message, $webhookData) {
        $from = $message['from'];
        $messageId = $message['id'];
        $timestamp = $message['timestamp'];
        
        // Get message content
        $messageText = '';
        $messageType = $message['type'];
        
        switch ($messageType) {
            case 'text':
                $messageText = $message['text']['body'];
                break;
            case 'interactive':
                // Handle button/list responses
                if (isset($message['interactive']['button_reply'])) {
                    $messageText = $message['interactive']['button_reply']['title'];
                } elseif (isset($message['interactive']['list_reply'])) {
                    $messageText = $message['interactive']['list_reply']['title'];
                }
                break;
            default:
                $this->sendMessage($from, "I can help you with hotel bookings and questions! Please send me a text message.");
                return;
        }
        
        if (empty($messageText)) return;
        
        // Get or create user
        $user = $this->getOrCreateWhatsAppUser($from, $webhookData);
        
        // Check for special commands
        if ($this->handleSpecialCommands($messageText, $from, $user)) {
            return;
        }
        
        // Get conversation context
        $context = $this->getConversationContext($from);
        
        // Generate AI response with WhatsApp-specific context
        $aiResponse = $this->generateWhatsAppResponse($messageText, $user, $context);
        
        // Process booking-related responses
        $this->processBookingIntent($messageText, $aiResponse, $from, $user);
        
        // Send response
        $this->sendMessage($from, $aiResponse['response']);
        
        // Log conversation
        $this->logWhatsAppInteraction($from, $user['id'] ?? null, $messageText, $aiResponse['response']);
    }
    
    /**
     * Generate AI response optimized for WhatsApp
     */
    private function generateWhatsAppResponse($message, $user, $context) {
        // Enhanced prompt for WhatsApp context
        $whatsappPrompt = "You are a WhatsApp AI assistant for a revolutionary hotel booking platform. ";
        $whatsappPrompt .= "Keep responses concise (under 160 chars when possible), friendly, and use emojis appropriately. ";
        $whatsappPrompt .= "You can help with: bookings, HotelCoins, loyalty program, room info, local recommendations. ";
        
        if ($user) {
            $whatsappPrompt .= "Guest info: {$user['first_name']} {$user['last_name']}. ";
            if (isset($user['hotelcoin_balance'])) {
                $whatsappPrompt .= "HotelCoin balance: {$user['hotelcoin_balance']} HC. ";
            }
            if (isset($user['loyalty_points'])) {
                $whatsappPrompt .= "Loyalty: {$user['loyalty_points']} points ({$user['membership_tier']} tier). ";
            }
        }
        
        $whatsappPrompt .= "\n\nGuest message: $message\n\nResponse (be helpful and concise):";
        
        // Get AI response
        $result = $this->ai->chat($whatsappPrompt, $user['id'] ?? null, $context);
        
        return $result;
    }
    
    /**
     * Handle special commands (balance, booking, help, etc.)
     */
    private function handleSpecialCommands($message, $from, $user) {
        $message = strtolower(trim($message));
        
        switch ($message) {
            case 'balance':
            case 'my balance':
            case 'hotelcoins':
                $this->sendBalanceInfo($from, $user);
                return true;
                
            case 'help':
            case 'menu':
            case 'start':
                $this->sendWelcomeMenu($from);
                return true;
                
            case 'book':
            case 'booking':
            case 'reserve':
                $this->sendBookingMenu($from);
                return true;
                
            case 'loyalty':
            case 'points':
            case 'tier':
                $this->sendLoyaltyInfo($from, $user);
                return true;
        }
        
        return false;
    }
    
    /**
     * Send balance information
     */
    private function sendBalanceInfo($to, $user) {
        if (!$user) {
            $this->sendMessage($to, "Please register first by telling me your email address!");
            return;
        }
        
        $hotelcoins = $user['hotelcoin_balance'] ?? 0;
        $loyaltyPoints = $user['loyalty_points'] ?? 0;
        $tier = $user['membership_tier'] ?? 'Bronze';
        
        $message = "💰 *Your Balances*\n\n";
        $message .= "🪙 HotelCoins: " . number_format($hotelcoins, 2) . " HC\n";
        $message .= "💎 Loyalty Points: " . number_format($loyaltyPoints) . "\n";
        $message .= "🏆 Tier: $tier\n\n";
        $message .= "Type 'book' to make a reservation or 'help' for more options!";
        
        $this->sendMessage($to, $message);
    }
    
    /**
     * Send welcome menu with interactive buttons
     */
    private function sendWelcomeMenu($to) {
        $message = "🏨 Welcome to our AI Hotel Assistant!\n\n";
        $message .= "I can help you with:\n";
        $message .= "🏨 Hotel bookings\n";
        $message .= "🪙 HotelCoin management\n";
        $message .= "💎 Loyalty program\n";
        $message .= "🌍 Local recommendations\n\n";
        $message .= "What would you like to know?";
        
        // Send with interactive buttons
        $this->sendInteractiveMessage($to, $message, [
            ['id' => 'book_room', 'title' => '🏨 Book Room'],
            ['id' => 'check_balance', 'title' => '💰 Check Balance'],
            ['id' => 'local_info', 'title' => '🌍 Local Info']
        ]);
    }
    
    /**
     * Send booking menu
     */
    private function sendBookingMenu($to) {
        $message = "🏨 *Hotel Booking*\n\n";
        $message .= "To book a room, please tell me:\n";
        $message .= "📅 Check-in date\n";
        $message .= "📅 Check-out date\n";
        $message .= "👥 Number of guests\n";
        $message .= "🌍 Preferred location (if any)\n\n";
        $message .= "Example: 'I need a room for 2 guests from Dec 15 to Dec 17 in downtown'";
        
        $this->sendMessage($to, $message);
    }
    
    /**
     * Process booking intents and handle reservations
     */
    private function processBookingIntent($message, $aiResponse, $from, $user) {
        // Simple booking intent detection (can be enhanced with NLP)
        $bookingKeywords = ['book', 'reserve', 'room', 'stay', 'check-in', 'check-out', 'night'];
        $datePatterns = ['/\b\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}\b/', '/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\w*\s+\d{1,2}\b/i'];
        
        $hasBookingIntent = false;
        foreach ($bookingKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $hasBookingIntent = true;
                break;
            }
        }
        
        $hasDateInfo = false;
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasDateInfo = true;
                break;
            }
        }
        
        if ($hasBookingIntent && $hasDateInfo) {
            // Extract booking details and show available rooms
            $this->showAvailableRooms($from, $message, $user);
        }
    }
    
    /**
     * Show available rooms based on request
     */
    private function showAvailableRooms($to, $request, $user) {
        // This would connect to your room availability system
        // For now, showing sample rooms
        
        $message = "🏨 *Available Rooms*\n\n";
        $message .= "1️⃣ *Deluxe Room* - $120/night\n";
        $message .= "   Earn 2.4 HotelCoins per night\n";
        $message .= "   📱 WiFi, 🛏️ King bed, 🛁 Private bath\n\n";
        
        $message .= "2️⃣ *Suite* - $200/night\n";
        $message .= "   Earn 4.0 HotelCoins per night\n";
        $message .= "   🛋️ Living area, 🍽️ Kitchenette\n\n";
        
        $message .= "3️⃣ *Premium Suite* - $300/night\n";
        $message .= "   Earn 6.0 HotelCoins per night\n";
        $message .= "   🌅 City view, 🛁 Jacuzzi, 🍾 Mini bar\n\n";
        
        $message .= "Reply with room number to book (e.g., 'Book room 1')";
        
        $this->sendMessage($to, $message);
    }
    
    /**
     * Send WhatsApp message
     */
    private function sendMessage($to, $message) {
        $url = "https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        $this->makeAPICall($url, $data);
    }
    
    /**
     * Send interactive message with buttons
     */
    private function sendInteractiveMessage($to, $message, $buttons) {
        $url = "https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages";
        
        $buttonData = [];
        foreach ($buttons as $button) {
            $buttonData[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title']
                ]
            ];
        }
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => ['buttons' => $buttonData]
            ]
        ];
        
        $this->makeAPICall($url, $data);
    }
    
    /**
     * Make API call to WhatsApp
     */
    private function makeAPICall($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Get or create WhatsApp user
     */
    private function getOrCreateWhatsAppUser($phoneNumber, $webhookData) {
        try {
            // Check if user exists
            $stmt = $this->connection->prepare("
                SELECT u.*, hw.balance as hotelcoin_balance, lw.balance as loyalty_points, lw.membership_tier
                FROM users u
                LEFT JOIN hotelcoin_wallets hw ON u.id = hw.user_id
                LEFT JOIN loyalty_wallets lw ON u.id = lw.user_id
                WHERE u.phone = ?
            ");
            $stmt->execute([$phoneNumber]);
            $user = $stmt->fetch();
            
            if ($user) {
                return $user;
            }
            
            // Get profile info from WhatsApp
            $profileName = $webhookData['contacts'][0]['profile']['name'] ?? 'WhatsApp User';
            $nameParts = explode(' ', $profileName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            
            // Create new user
            $stmt = $this->connection->prepare("
                INSERT INTO users (first_name, last_name, phone, email, password, role, created_at)
                VALUES (?, ?, ?, ?, ?, 'guest', NOW())
            ");
            $email = $phoneNumber . '@whatsapp.temp'; // Temporary email
            $password = password_hash('whatsapp_user', PASSWORD_DEFAULT);
            
            $stmt->execute([$firstName, $lastName, $phoneNumber, $email, $password]);
            $userId = $this->connection->lastInsertId();
            
            // Create wallets
            $hotelCoinManager = new HotelCoinManager();
            $loyaltyManager = new LoyaltyManager();
            
            $hotelCoinManager->createWallet($userId);
            $loyaltyManager->createWallet($userId);
            
            // Award welcome bonus
            $hotelCoinManager->addCoins($userId, 5.0, 'WhatsApp welcome bonus');
            $loyaltyManager->addPoints($userId, 100, 'WhatsApp welcome bonus');
            
            return [
                'id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phoneNumber,
                'hotelcoin_balance' => 5.0,
                'loyalty_points' => 100,
                'membership_tier' => 'Bronze'
            ];
            
        } catch (Exception $e) {
            error_log("Error creating WhatsApp user: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get conversation context
     */
    private function getConversationContext($phoneNumber) {
        try {
            $stmt = $this->connection->prepare("
                SELECT user_message, ai_response 
                FROM whatsapp_conversations 
                WHERE phone_number = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$phoneNumber]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Log WhatsApp interaction
     */
    private function logWhatsAppInteraction($phoneNumber, $userId, $userMessage, $aiResponse) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO whatsapp_conversations (phone_number, user_id, user_message, ai_response, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$phoneNumber, $userId, $userMessage, $aiResponse]);
        } catch (Exception $e) {
            error_log("Error logging WhatsApp interaction: " . $e->getMessage());
        }
    }
    
    /**
     * Get configuration value
     */
    private function getConfig($key) {
        try {
            $stmt = $this->connection->prepare("SELECT config_value FROM ai_chat_config WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result ? $result['config_value'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>