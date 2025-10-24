# WhatsApp Sales Agent with TinyLlama AI

## Overview
Use TinyLlama (0.69s response time) as an automated WhatsApp sales agent for hotel bookings.

## Architecture

```
┌─────────────────┐      ┌──────────────────┐      ┌─────────────────┐      ┌──────────────┐
│  WhatsApp User  │─────▶│  WhatsApp API    │─────▶│  Your Server    │─────▶│  AI VPS      │
│  (Guest/Lead)   │      │  (Twilio/Meta)   │      │  webhook.php    │      │  TinyLlama   │
│                 │◀─────│                  │◀─────│                 │◀─────│  (0.69s)     │
└─────────────────┘      └──────────────────┘      └─────────────────┘      └──────────────┘
```

## WhatsApp Integration Options

### Option 1: **Twilio WhatsApp API** (Easiest, $$$)
- ✅ Quick setup (30 minutes)
- ✅ Official WhatsApp Business API
- ✅ No approval needed for sandbox testing
- 💰 Cost: $0.005-0.008 per message (after free tier)
- 📱 Can use real phone number

### Option 2: **Meta WhatsApp Business API** (Free, Complex)
- ✅ Completely free (unlimited messages)
- ⚠️ Requires Facebook Business verification
- ⚠️ Setup takes 2-3 days (approval process)
- ⚠️ More complex webhook setup
- 📱 Must have Facebook Business account

### Option 3: **WhatsApp Business App + Webhooks** (Unofficial)
- ✅ Free
- ❌ Against WhatsApp TOS (risk of ban)
- ❌ Not reliable for production
- 🚫 NOT RECOMMENDED

## Recommended: Start with Twilio Sandbox

### Why Twilio?
1. **Test immediately** - No approvals needed
2. **Easy webhook integration** - One URL, done
3. **Pay as you grow** - Start small, scale later
4. **Professional** - Same API big companies use

---

## Setup Steps

### Step 1: Create Twilio Account
1. Go to https://www.twilio.com/try-twilio
2. Sign up (free $15 credit)
3. Verify your phone number
4. Go to Console → WhatsApp → Sandbox

### Step 2: Get Your Webhook URL
Your server needs a public URL: `https://your-domain.com/whatsapp-webhook.php`

**For testing (local development):**
- Use **ngrok**: `ngrok http 80` → Get public URL
- Or deploy directly to test VPS: `http://212.227.241.193/whatsapp-webhook.php`

### Step 3: Configure Twilio Webhook
In Twilio Console:
- **When a message comes in**: `https://your-domain.com/whatsapp-webhook.php`
- **Method**: POST
- **Content Type**: application/x-www-form-urlencoded

---

## Sales Agent Features

### 1. **Lead Qualification** 
Customer: "Do you have rooms available?"
AI: "¡Hola! Yes, we have rooms available. What dates are you looking for?"

### 2. **Price Quotes**
Customer: "How much for 2 nights in December?"
AI: "For 2 nights in December, our rooms start at S/ 150 per night. Would you like to see available room types?"

### 3. **Property Information**
Customer: "What amenities do you have?"
AI: "We offer: WiFi, Breakfast, Hot water, Parking, and 24/7 reception. What's most important for your stay?"

### 4. **Booking Process**
Customer: "I want to book"
AI: "Great! I'll connect you with our booking team. Could you share your check-in date, number of guests, and contact name?"

### 5. **Human Handoff**
When AI detects:
- Specific pricing questions it can't answer
- Complaint or negative sentiment
- Complex special requests
→ "Let me connect you with our team for personalized assistance..."

---

## AI Training Context

The AI will be trained with your hotel's specific information:

```json
{
  "hotel_name": "Samay Wasi Hotel",
  "location": "Cusco, Peru",
  "check_in": "3:00 PM",
  "check_out": "12:00 PM",
  "amenities": ["WiFi", "Breakfast", "Hot Water", "Parking", "24/7 Reception"],
  "room_types": [
    {"name": "Standard", "price_from": 120, "capacity": 2},
    {"name": "Deluxe", "price_from": 180, "capacity": 3},
    {"name": "Suite", "price_from": 250, "capacity": 4}
  ],
  "policies": {
    "cancellation": "Free cancellation up to 48 hours before check-in",
    "deposit": "50% deposit required to confirm booking",
    "children": "Children under 5 stay free"
  },
  "languages": ["Spanish", "English"],
  "currency": "PEN (Soles)"
}
```

---

## Cost Estimation

### Twilio Pricing (Peru):
- **Incoming message**: $0.005
- **Outgoing message**: $0.0085

### Example: 100 conversations/month
- Average 10 messages per conversation
- Cost: 100 conversations × 10 messages × $0.0085 = **$8.50/month**

### Compared to:
- Hiring staff for 24/7 WhatsApp: $500+/month
- Missing leads while asleep: Priceless 😴

---

## Key Advantages

✅ **24/7 availability** - Never miss a lead  
✅ **Instant responses** - 0.69 seconds (customers love fast replies)  
✅ **Multilingual** - Spanish & English automatically  
✅ **Lead capture** - Saves all conversations to database  
✅ **Smart handoff** - Routes complex queries to human staff  
✅ **Cost effective** - ~$10-20/month vs hiring staff  

---

## Next Steps

1. **Set up Twilio WhatsApp Sandbox** (10 minutes)
2. **Deploy Flask API on AI VPS** with TinyLlama (20 minutes)
3. **Create webhook.php** on your server (30 minutes)
4. **Test with your phone** (5 minutes)
5. **Train AI with hotel info** (1 hour)
6. **Go live!** 🚀

Want me to start building the webhook integration code?
