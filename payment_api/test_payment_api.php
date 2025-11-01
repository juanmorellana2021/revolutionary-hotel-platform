<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment API Test - Adyen Integration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .test-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: transform 0.2s;
            margin: 10px 10px 10px 0;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .test-button:active {
            transform: translateY(0);
        }
        .response-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success {
            background: #28a745;
        }
        .status-error {
            background: #dc3545;
        }
        .status-pending {
            background: #ffc107;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Payment API Test Suite</h1>
        
        <div class="card">
            <h2>📊 API Status</h2>
            <div class="info-box">
                <strong>Endpoint:</strong> <code>http://108.175.12.152/payment_api/index.php</code><br>
                <strong>Environment:</strong> Adyen Sandbox (Testing)<br>
                <strong>Server:</strong> Production VPS (108.175.12.152)
            </div>
            <button class="test-button" onclick="testStatus()">🔍 Check API Status</button>
            <div id="statusResponse" class="response-box" style="display:none;"></div>
        </div>

        <div class="card">
            <h2>💳 Payment Test</h2>
            <div class="warning-box">
                <strong>⚠️ Note:</strong> This endpoint requires Adyen API credentials to be configured. 
                Make sure you've updated the credentials in <code>adyen_payment.php</code>.
            </div>
            <button class="test-button" onclick="testPayment()">💰 Create Test Payment</button>
            <div id="paymentResponse" class="response-box" style="display:none;"></div>
        </div>

        <div class="card">
            <h2>🔔 Webhook Test</h2>
            <div class="info-box">
                <strong>Info:</strong> This simulates an Adyen webhook notification.
            </div>
            <button class="test-button" onclick="testWebhook()">📥 Send Test Webhook</button>
            <div id="webhookResponse" class="response-box" style="display:none;"></div>
        </div>

        <div class="card">
            <h2>📚 Test Card Numbers (Adyen Sandbox)</h2>
            <div class="grid">
                <div>
                    <h3>✅ Success Cards</h3>
                    <ul>
                        <li><strong>Visa:</strong> 4111 1111 1111 1111</li>
                        <li><strong>Mastercard:</strong> 5555 5555 5555 4444</li>
                        <li><strong>Amex:</strong> 3782 822463 10005</li>
                    </ul>
                </div>
                <div>
                    <h3>❌ Failure Cards</h3>
                    <ul>
                        <li><strong>Declined:</strong> 4000 3000 0000 0003</li>
                        <li><strong>Insufficient Funds:</strong> 4000 1111 1111 1118</li>
                    </ul>
                </div>
            </div>
            <div class="info-box" style="margin-top: 20px;">
                <strong>Expiry Date:</strong> Any future date (e.g., 03/2030)<br>
                <strong>CVV:</strong> Any 3 digits (e.g., 737)
            </div>
        </div>
    </div>

    <script>
        const API_BASE = 'http://108.175.12.152/payment_api/index.php';

        async function testStatus() {
            const responseBox = document.getElementById('statusResponse');
            responseBox.style.display = 'block';
            responseBox.innerHTML = '<span class="status-indicator status-pending"></span>Testing API status...';

            try {
                const response = await fetch(`${API_BASE}?endpoint=status`);
                const data = await response.json();
                
                const statusClass = response.ok ? 'status-success' : 'status-error';
                responseBox.innerHTML = `<span class="status-indicator ${statusClass}"></span>` +
                    `<strong>Status:</strong> ${response.status}\n\n` +
                    `<strong>Response:</strong>\n${JSON.stringify(data, null, 2)}`;
            } catch (error) {
                responseBox.innerHTML = `<span class="status-indicator status-error"></span>` +
                    `<strong>Error:</strong>\n${error.message}`;
            }
        }

        async function testPayment() {
            const responseBox = document.getElementById('paymentResponse');
            responseBox.style.display = 'block';
            responseBox.innerHTML = '<span class="status-indicator status-pending"></span>Creating test payment...';

            const testPaymentData = {
                amount: 50.00,
                currency: 'USD',
                reference: 'TEST_BOOKING_' + Date.now(),
                paymentMethod: {
                    type: 'scheme',
                    number: '4111111111111111',
                    expiryMonth: '03',
                    expiryYear: '2030',
                    cvc: '737',
                    holderName: 'Test User'
                },
                returnUrl: 'https://aini.com/payment_return'
            };

            try {
                const response = await fetch(`${API_BASE}?endpoint=create-payment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testPaymentData)
                });
                
                const data = await response.json();
                
                const statusClass = data.status === 200 ? 'status-success' : 'status-error';
                responseBox.innerHTML = `<span class="status-indicator ${statusClass}"></span>` +
                    `<strong>HTTP Status:</strong> ${data.status}\n\n` +
                    `<strong>Request:</strong>\n${JSON.stringify(testPaymentData, null, 2)}\n\n` +
                    `<strong>Response:</strong>\n${JSON.stringify(data, null, 2)}`;
            } catch (error) {
                responseBox.innerHTML = `<span class="status-indicator status-error"></span>` +
                    `<strong>Error:</strong>\n${error.message}`;
            }
        }

        async function testWebhook() {
            const responseBox = document.getElementById('webhookResponse');
            responseBox.style.display = 'block';
            responseBox.innerHTML = '<span class="status-indicator status-pending"></span>Sending webhook notification...';

            const webhookData = {
                live: false,
                notificationItems: [{
                    NotificationRequestItem: {
                        eventCode: 'AUTHORISATION',
                        success: 'true',
                        pspReference: 'TEST_' + Date.now(),
                        merchantReference: 'BOOKING_12345',
                        amount: {
                            currency: 'USD',
                            value: 5000
                        }
                    }
                }]
            };

            try {
                const response = await fetch(`${API_BASE}?endpoint=webhook`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(webhookData)
                });
                
                const data = await response.json();
                
                responseBox.innerHTML = `<span class="status-indicator status-success"></span>` +
                    `<strong>Webhook Data Sent:</strong>\n${JSON.stringify(webhookData, null, 2)}\n\n` +
                    `<strong>Server Response:</strong>\n${JSON.stringify(data, null, 2)}`;
            } catch (error) {
                responseBox.innerHTML = `<span class="status-indicator status-error"></span>` +
                    `<strong>Error:</strong>\n${error.message}`;
            }
        }

        // Auto-test status on load
        window.onload = () => {
            testStatus();
        };
    </script>
</body>
</html>
