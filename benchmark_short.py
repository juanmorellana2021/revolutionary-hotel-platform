#!/usr/bin/env python3
import requests
import time

models = ['tinyllama', 'qwen2.5:1.5b', 'gemma2:2b', 'llama3.2:1b']

# Short prompt with explicit length constraint
prompt = "What time is hotel check-in? Answer in max 10 words."

print("=" * 70)
print("SPEED TEST - Short Chatbot Responses")
print("=" * 70)

results = {}

for model in models:
    print(f"\n🔄 {model}")
    
    # Warm up
    requests.post('http://localhost:11434/api/generate',
        json={'model': model, 'prompt': 'hi', 'stream': False, 'keep_alive': '10m'},
        timeout=60)
    time.sleep(0.5)
    
    times = []
    for i in range(3):
        start = time.time()
        response = requests.post('http://localhost:11434/api/generate',
            json={
                'model': model,
                'prompt': prompt,
                'stream': False,
                'keep_alive': '10m',
                'options': {
                    'num_predict': 30,  # Limit response length
                    'temperature': 0.3   # More focused
                }
            },
            timeout=30)
        
        elapsed = time.time() - start
        times.append(elapsed)
        answer = response.json().get('response', '').strip()
        
        print(f"  Run {i+1}: {elapsed:.2f}s - {answer[:60]}")
    
    avg = sum(times) / len(times)
    results[model] = avg
    print(f"  ⏱️  Average: {avg:.2f}s")

# Summary
print("\n" + "=" * 70)
print("RANKING (Fastest to Slowest)")
print("=" * 70)

for i, (model, avg) in enumerate(sorted(results.items(), key=lambda x: x[1]), 1):
    if avg < 1.0:
        rating = "🥇 BLAZING FAST"
    elif avg < 2.0:
        rating = "🥈 VERY FAST"
    elif avg < 3.0:
        rating = "🥉 FAST"
    else:
        rating = "⚠️  ACCEPTABLE"
    
    print(f"{i}. {model:20s} {avg:.2f}s  {rating}")

print("=" * 70)
