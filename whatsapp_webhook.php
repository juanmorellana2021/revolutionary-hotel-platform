<?php
/**
 * WhatsApp Webhook Endpoint
 * Receives messages from WhatsApp Business API and processes them with AI
 */

// Set headers for WhatsApp webhook
header('Content-Type: application/json');

// Include required files
require_once 'includes/whatsapp_bot.php';

// Initialize WhatsApp bot
$whatsappBot = new WhatsAppHotelBot();

// Handle the webhook
$whatsappBot->handleWebhook();
?>