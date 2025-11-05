# WhatsApp Business API Setup Guide

## What You Need from Meta/Facebook Business

To connect your WhatsApp Business account, you'll need these credentials from Meta:

### 1. WhatsApp Access Token
- Go to: https://developers.facebook.com/apps
- Select your WhatsApp Business app
- Navigate to: WhatsApp → API Setup
- Copy the **Temporary Access Token** (or generate a permanent one)

### 2. Phone Number ID
- In the same WhatsApp API Setup page
- You'll see your business phone number
- Copy the **Phone Number ID** (not the phone number itself)

### 3. Webhook Configuration
Once you have the credentials entered in the system, you'll need to configure the webhook in Meta:

**Webhook URL:** 
```
http://108.175.12.152/manage/whatsapp_webhook.php
```

**Verify Token:** 
```
hotel_booking_webhook_2024
```
(You can change this, but it must match what's in your database)

### 4. Webhook Events to Subscribe
In Meta, subscribe to these webhook events:
- ✅ messages
- ✅ message_status (optional, for delivery receipts)

## Testing the Connection

After setup, you can test by:
1. Sending a WhatsApp message to your business number
2. Valentina (AI agent) should respond within 1-2 seconds
3. Check logs: `ssh hotel-vps "tail -f /var/log/apache2/access.log"`

## Important Notes

- The webhook URL must be accessible from the internet (it is: 108.175.12.152)
- Meta will send a verification request to the webhook URL
- The AI is already configured and ready (Valentina with Qwen2.5:1.5b model)
- Response time: 1-3 seconds typical

## Current AI Setup
- **Model:** Qwen2.5:1.5b (fast + quality)
- **AI Server:** 72.60.1.16:11434
- **Agent Name:** Valentina
- **Languages:** Spanish & English
- **Response Style:** Friendly, concise (10 words max)
