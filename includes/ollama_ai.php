<?php
/**
 * Ollama AI Integration for Revolutionary Hotel Platform
 * Provides AI-powered guest communication with HotelCoin and loyalty system awareness
 */

class OllamaAI {
    private $apiUrl;
    private $model;
    private $connection;
    
    public function __construct($host = '72.60.1.16', $port = 11434, $model = 'tinyllama') {
        // Connect to dedicated AI VPS for faster inference
        $this->apiUrl = "http://{$host}:{$port}/api/generate";
        $this->model = $model;
        
        // Database connection for context awareness
        require_once __DIR__ . '/../db_connection.php';
        global $conn;
        $this->connection = $conn;
    }
    
    /**
     * Generate AI response with hotel platform context
     */
    public function chat($message, $userId = null, $context = []) {
        try {
            // Get user context if provided
            $userContext = '';
            if ($userId) {
                $userContext = $this->getUserContext($userId);
            }
            
            // Build enhanced prompt with hotel platform knowledge
            $enhancedPrompt = $this->buildHotelPrompt($message, $userContext, $context);
            
            // Make API call to Ollama
            $response = $this->callOllamaAPI($enhancedPrompt);
            
            // Log the interaction
            $this->logChatInteraction($userId, $message, $response);
            
            return [
                'success' => true,
                'response' => $response,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'AI service temporarily unavailable',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Get user context for personalized responses
     */
    private function getUserContext($userId) {
        try {
            // Get user information
            $stmt = $this->connection->prepare("
                SELECT u.first_name, u.last_name, u.email, u.role,
                       hw.balance as hotelcoin_balance,
                       lw.balance as loyalty_points, lw.membership_tier
                FROM users u
                LEFT JOIN hotelcoin_wallets hw ON u.id = hw.user_id
                LEFT JOIN loyalty_wallets lw ON u.id = lw.user_id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) return '';
            
            // Get recent bookings
            $stmt = $this->connection->prepare("
                SELECT COUNT(*) as booking_count,
                       MAX(created_at) as last_booking
                FROM bookings 
                WHERE user_id = ? AND status = 'confirmed'
            ");
            $stmt->execute([$userId]);
            $bookingInfo = $stmt->fetch();
            
            // Build context string
            $context = "User Profile: {$user['first_name']} {$user['last_name']} ";
            $context .= "({$user['email']}) - {$user['role']}. ";
            
            if ($user['hotelcoin_balance']) {
                $context .= "HotelCoin Balance: " . number_format($user['hotelcoin_balance'], 4) . " HC. ";
            }
            
            if ($user['loyalty_points']) {
                $context .= "Loyalty Points: " . number_format($user['loyalty_points']) . " points ";
                $context .= "({$user['membership_tier']} tier). ";
            }
            
            if ($bookingInfo['booking_count']) {
                $context .= "Previous bookings: {$bookingInfo['booking_count']}. ";
                if ($bookingInfo['last_booking']) {
                    $context .= "Last booking: {$bookingInfo['last_booking']}. ";
                }
            }
            
            return $context;
            
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Build hotel-specific prompt with platform knowledge
     */
    private function buildHotelPrompt($message, $userContext, $additionalContext) {
        $systemPrompt = "You are Valentina, a friendly hotel receptionist working at our hotel. You speak warmly and directly to guests as a staff member.\n\n";
        
        $systemPrompt .= "IMPORTANT: \n";
        $systemPrompt .= "- Introduce yourself as Valentina when greeting guests\n";
        $systemPrompt .= "- Always use first person (we, our, us) when talking about the hotel\n";
        $systemPrompt .= "- Keep responses SHORT (1-2 sentences for quick replies)\n";
        $systemPrompt .= "Example: 'Hola, soy Valentina! Sí, tenemos habitaciones disponibles' ✅\n\n";
        
        $systemPrompt .= "OUR HOTEL FEATURES:\n";
        $systemPrompt .= "- HotelCoins: Digital currency guests earn (1% of booking value) and can spend\n";
        $systemPrompt .= "- Loyalty Program: Bronze/Silver/Gold/Platinum tiers with multipliers\n";
        $systemPrompt .= "- Partner Network: Local businesses that accept HotelCoins\n";
        $systemPrompt .= "- Multi-hotel platform: Multiple properties with shared currency\n\n";
        
        $systemPrompt .= "LOYALTY TIERS:\n";
        $systemPrompt .= "- Bronze (0+ points): 1.0x multiplier\n";
        $systemPrompt .= "- Silver (1,000+ points): 1.25x multiplier\n";
        $systemPrompt .= "- Gold (5,000+ points): 1.5x multiplier\n";
        $systemPrompt .= "- Platinum (15,000+ points): 2.0x multiplier\n\n";
        
        if ($userContext) {
            $systemPrompt .= "GUEST CONTEXT: $userContext\n\n";
        }
        
        if (!empty($additionalContext)) {
            $systemPrompt .= "ADDITIONAL CONTEXT: " . implode(', ', $additionalContext) . "\n\n";
        }
        
        $systemPrompt .= "INSTRUCTIONS:\n";
        $systemPrompt .= "- Speak as hotel staff (use 'we', 'our', 'us')\n";
        $systemPrompt .= "- Be warm, helpful, and professional\n";
        $systemPrompt .= "- Keep responses SHORT (1-2 sentences for WhatsApp)\n";
        $systemPrompt .= "- If you don't know something, offer to have a staff member help\n\n";
        
        $systemPrompt .= "GUEST QUESTION: $message\n\n";
        $systemPrompt .= "YOUR RESPONSE (as hotel staff):";
        
        return $systemPrompt;
    }
    
    /**
     * Make API call to Ollama
     */
    private function callOllamaAPI($prompt) {
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'keep_alive' => '5m',  // Keep model warm for 5 minutes
            'options' => [
                'temperature' => 0.3,       // More focused responses for chatbot
                'top_p' => 0.9,
                'num_predict' => 100,       // Limit to ~100 tokens for fast WhatsApp responses
                'repeat_penalty' => 1.1     // Reduce repetition
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);  // 10 second timeout (model should respond in <1s)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Ollama API error: HTTP $httpCode - $error");
            throw new Exception("AI service temporarily unavailable");
        }
        
        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['response'])) {
            error_log("Invalid Ollama response: " . substr($response, 0, 200));
            throw new Exception("Invalid response from AI");
        }
        
        return trim($decoded['response']);
    }
    
    /**
     * Log chat interactions for analytics
     */
    private function logChatInteraction($userId, $message, $response) {
        // Temporarily disabled - will create ai_chat_logs table later
        // This prevents errors while AI functionality is being tested
        return;
    }
    
    /**
     * Get chat analytics for admin dashboard
     */
    public function getChatAnalytics($days = 30) {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    COUNT(*) as total_chats,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(LENGTH(ai_response)) as avg_response_length,
                    DATE(created_at) as chat_date,
                    COUNT(*) as daily_count
                FROM ai_chat_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY chat_date DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get common questions for optimization
     */
    public function getCommonQuestions($limit = 20) {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    user_message,
                    COUNT(*) as frequency,
                    AVG(LENGTH(ai_response)) as avg_response_length
                FROM ai_chat_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY LOWER(TRIM(user_message))
                ORDER BY frequency DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}

/**
 * Quick response templates for common questions
 */
class ChatTemplates {
    public static function getQuickResponses() {
        return [
            'hotelcoins' => [
                'title' => '🪙 HotelCoins',
                'questions' => [
                    'How do I earn HotelCoins?',
                    'What can I buy with HotelCoins?',
                    'How do I check my HotelCoin balance?',
                    'Can I transfer HotelCoins to friends?'
                ]
            ],
            'loyalty' => [
                'title' => '💎 Loyalty Program',
                'questions' => [
                    'What are the loyalty tiers?',
                    'How do I earn loyalty points?',
                    'What are my tier benefits?',
                    'How do I redeem points?'
                ]
            ],
            'booking' => [
                'title' => '📅 Booking Help',
                'questions' => [
                    'How do I make a reservation?',
                    'Can I modify my booking?',
                    'What is your cancellation policy?',
                    'Do you accept pets?'
                ]
            ],
            'local' => [
                'title' => '🌍 Local Area',
                'questions' => [
                    'What restaurants accept HotelCoins?',
                    'Local attractions near the hotel?',
                    'Transportation options?',
                    'Partner business discounts?'
                ]
            ]
        ];
    }
}
?>