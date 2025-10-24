<?php
/**
 * AI Configuration for Hotel Platform
 * Configure Ollama connection and model settings
 */

return [
    // AI VPS Connection (dedicated server for AI inference)
    'ollama' => [
        'host' => '72.60.1.16',        // AI VPS IP address
        'port' => 11434,                // Ollama default port
        'timeout' => 10,                // Request timeout in seconds
        'connect_timeout' => 5,         // Connection timeout
    ],
    
    // Model Configuration
    'models' => [
        // Fast model for WhatsApp and simple queries
        'fast' => [
            'name' => 'tinyllama',
            'speed' => '0.69s',
            'use_for' => ['whatsapp', 'faq', 'quick_responses'],
            'max_tokens' => 100,
            'temperature' => 0.3,       // More focused
        ],
        
        // Balanced model for complex queries
        'balanced' => [
            'name' => 'qwen2.5:1.5b',
            'speed' => '1.06s',
            'use_for' => ['booking_help', 'detailed_questions', 'support'],
            'max_tokens' => 200,
            'temperature' => 0.5,
        ],
        
        // Best quality model for important interactions
        'quality' => [
            'name' => 'llama3.2:1b',
            'speed' => '1.55s',
            'use_for' => ['complaints', 'vip_guests', 'complex_support'],
            'max_tokens' => 300,
            'temperature' => 0.7,
        ],
    ],
    
    // Default model for each channel
    'channel_defaults' => [
        'whatsapp' => 'fast',           // TinyLlama for WhatsApp (speed is critical)
        'web_chat' => 'balanced',       // Qwen2.5 for web chat
        'email' => 'quality',           // Llama3.2 for email responses
        'dashboard' => 'balanced',      // Qwen2.5 for dashboard queries
    ],
    
    // Smart routing rules
    'smart_routing' => [
        'enabled' => true,
        'simple_keywords' => [          // Use 'fast' model if message contains these
            'yes', 'no', 'ok', 'thanks', 'hello', 'hi',
            'check-in', 'checkout', 'time', 'price', 'cost',
            'wifi', 'breakfast', 'parking', 'address', 'location'
        ],
        'complex_keywords' => [         // Use 'quality' model if message contains these
            'complaint', 'problem', 'issue', 'refund', 'cancel',
            'disappointed', 'unhappy', 'manager', 'supervisor'
        ],
    ],
    
    // Performance settings
    'performance' => [
        'keep_alive' => '5m',           // Keep model loaded for 5 minutes
        'cache_responses' => true,      // Cache common responses
        'cache_duration' => 3600,       // 1 hour cache
        'max_retries' => 2,             // Retry failed requests
    ],
    
    // Fallback behavior
    'fallback' => [
        'enabled' => true,
        'message_spanish' => 'Lo siento, el asistente AI está temporalmente no disponible. Un miembro del equipo te ayudará pronto. 🙏',
        'message_english' => 'Sorry, the AI assistant is temporarily unavailable. A team member will help you soon. 🙏',
        'notify_staff' => true,         // Alert staff when AI fails
    ],
    
    // Feature flags
    'features' => [
        'multilingual' => true,         // Auto-detect language
        'sentiment_analysis' => true,   // Detect negative sentiment
        'booking_automation' => false,  // Auto-process bookings (not yet implemented)
        'payment_integration' => false, // Handle payments (not yet implemented)
    ],
];
?>
