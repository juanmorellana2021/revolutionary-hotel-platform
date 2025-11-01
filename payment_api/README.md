# Payment API - Adyen Integration

This directory contains the payment processing API using Adyen sandbox.

## Files
- `index.php` - Main API entry point
- `adyen_payment.php` - Adyen integration class

## Endpoints

### 1. Status Check
```
GET http://108.175.12.152/payment_api/index.php?endpoint=status
```

### 2. Create Payment
```
POST http://108.175.12.152/payment_api/index.php?endpoint=create-payment
Content-Type: application/json

{
  "amount": 10.00,
  "currency": "USD",
  "reference": "BOOKING_12345",
  "paymentMethod": {
    "type": "scheme",
    "encryptedCardNumber": "...",
    "encryptedExpiryMonth": "...",
    "encryptedExpiryYear": "...",
    "encryptedSecurityCode": "..."
  },
  "returnUrl": "https://aini.com/payment_return"
}
```

### 3. Webhook Handler
```
POST http://108.175.12.152/payment_api/index.php?endpoint=webhook
```

## Configuration

Edit `adyen_payment.php` and replace:
- `YOUR_ADYEN_SANDBOX_API_KEY` with your Adyen test API key
- `YOUR_MERCHANT_ACCOUNT` with your merchant account name

## Testing

Use Adyen's test card numbers:
- Success: 4111 1111 1111 1111
- Failure: 4000 3000 0000 0003

For more test cards: https://docs.adyen.com/development-resources/testing/test-card-numbers
