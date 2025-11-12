# Twilio SMS Setup for AiniFlow

## 1. Create Twilio Account

1. Go to **https://www.twilio.com/try-twilio**
2. Sign up for a free trial account
3. Verify your email and phone number

## 2. Get Your Credentials

After signing up, you'll see your **Dashboard**:

- **Account SID**: Found on dashboard (e.g., `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
- **Auth Token**: Click "Show" to reveal (e.g., `your_auth_token_here`)

## 3. Get a Phone Number

1. In Twilio Console, go to **Phone Numbers** → **Buy a Number**
2. Choose a number with **SMS capabilities**
3. For trial accounts: You can send SMS to verified numbers only
4. For production: Upgrade account to send to any number

**Trial Mode:**
- Free $15 credit
- Can only send to verified phone numbers
- SMS will have "Sent from your Twilio trial account" prefix

**Paid Account:**
- ~$1/month per phone number
- ~$0.0075 per SMS sent
- No restrictions on recipient numbers

## 4. Update Server Environment Variables

SSH into your social-vps server:

```bash
ssh social-vps
```

Edit the `.env` file:

```bash
nano /var/www/aini-platform/.env
```

Update these values with your Twilio credentials:

```bash
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
```

Save and exit (Ctrl+X, then Y, then Enter)

## 5. Install Twilio Package

On the server:

```bash
cd /var/www/aini-platform
npm install twilio
```

## 6. Restart AiniFlow Service

```bash
sudo systemctl restart ainiflow
```

Check if it's running:

```bash
sudo systemctl status ainiflow
```

View logs:

```bash
tail -f /var/www/aini-platform/server.log
```

## 7. Test SMS Sending

### For Trial Account:
1. In Twilio Console → Phone Numbers → Verified Caller IDs
2. Add your phone number for testing
3. Go to AiniFlow login page: http://72.61.217.65
4. Enter your verified phone number
5. You should receive an SMS with the code!

### Expected SMS Message:
```
Your AiniFlow verification code is: 123456
```

## 8. Verify Numbers for Trial Testing

To test with multiple numbers during trial:

1. Go to Twilio Console → **Phone Numbers** → **Verified Caller IDs**
2. Click **Add a new Caller ID**
3. Enter the phone number (must include country code, e.g., +1234567890)
4. Twilio will call or text that number with a verification code
5. Enter the code to verify
6. Now that number can receive SMS from your trial account

## 9. Upgrade to Production (Optional)

When ready to go live:

1. Go to Twilio Console → **Billing**
2. Add payment method
3. Upgrade account
4. Remove trial restrictions
5. All phone numbers can now receive SMS!

## Pricing (as of 2025):

- **Phone Number**: ~$1.00/month
- **SMS (US/Canada)**: ~$0.0075 per message
- **SMS (International)**: Varies by country ($0.01 - $0.20 per message)

## 10. Production Checklist

- [ ] Twilio account created
- [ ] Credentials added to `.env` file on server
- [ ] `npm install twilio` completed
- [ ] AiniFlow service restarted
- [ ] Test SMS received successfully
- [ ] Dev mode code display removed (already done in code)
- [ ] Account upgraded for production (if needed)

## Troubleshooting

**No SMS received?**
1. Check server logs: `tail -f /var/www/aini-platform/server.log`
2. Verify phone number format includes country code (e.g., +1234567890)
3. For trial: Ensure number is verified in Twilio Console
4. Check Twilio Console → Monitor → Logs for SMS delivery status

**SMS sent but no code?**
1. Code expires after 5 minutes (stored in Redis)
2. Check if Redis is running: `redis-cli ping`
3. View Redis keys: `redis-cli KEYS verify:*`

**Twilio errors?**
1. Verify credentials are correct in `.env`
2. Check Account SID starts with "AC"
3. Ensure Auth Token is correct (no spaces)
4. Phone number must be in E.164 format: +[country code][number]

## Current Status

✅ Code updated with Twilio integration
✅ Fallback to dev mode if Twilio fails
✅ Environment variables ready for credentials
⏳ Waiting for Twilio credentials to be added
⏳ Twilio npm package needs to be installed on server

## Next Steps

1. Sign up for Twilio account
2. Get credentials and phone number
3. Update `.env` on server
4. Install twilio package
5. Restart service
6. Test with your phone number!
