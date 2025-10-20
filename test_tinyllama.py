#!/usr/bin/env python3
import requests
import time

print("Testing TinyLlama speed...")

# Warm up
print("Warming up model...")
requests.post(
    'http://localhost:11434/api/generate',
    json={'model': 'tinyllama', 'prompt': 'hi', 'stream': False, 'keep_alive': '5m'},
    timeout=60
)

time.sleep(1)

# Test
start = time.time()
response = requests.post(
    'http://localhost:11434/api/generate',
    json={
        'model': 'tinyllama',
        'prompt': 'What time is hotel check-in? Answer in one sentence.',
        'stream': False,
        'keep_alive': '5m'
    },
    timeout=30
)

result = response.json()
elapsed = time.time() - start

print(f"\nResponse: {result.get('response', '')[:150]}")
print(f"\nTime taken: {elapsed:.2f} seconds")

if elapsed < 2:
    print("✅ EXCELLENT! Perfect for production chatbot")
elif elapsed < 3:
    print("✅ FAST! Good for production chatbot")
elif elapsed < 5:
    print("⚠️  Acceptable speed")
else:
    print("❌ TOO SLOW for real-time chat")
