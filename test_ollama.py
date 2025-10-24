#!/usr/bin/env python3
import requests
import time
import json

print("Testing Ollama Llama 3.2 1B speed...")
start = time.time()

try:
    response = requests.post(
        'http://localhost:11434/api/generate',
        json={
            'model': 'llama3.2:1b',
            'prompt': 'What time is hotel check-in? Answer in one sentence.',
            'stream': False
        },
        timeout=30
    )
    
    result = response.json()
    elapsed = time.time() - start
    
    print(f"\nResponse: {result.get('response', '')[:150]}")
    print(f"\nTime taken: {elapsed:.2f} seconds")
    
    if elapsed < 3:
        print("✅ FAST! Good for production chatbot")
    elif elapsed < 5:
        print("⚠️  Acceptable speed")
    else:
        print("❌ TOO SLOW for real-time chat")
        
except Exception as e:
    print(f"Error: {e}")
