#!/usr/bin/env python3
import requests
import time
import json

models = [
    'tinyllama',
    'qwen2.5:1.5b', 
    'gemma2:2b',
    'llama3.2:1b'
]

test_prompts = [
    "What time is hotel check-in? Answer in one sentence.",
    "How do I cancel my hotel reservation? Be brief.",
    "What amenities does a 4-star hotel typically have? List 5."
]

print("=" * 70)
print("OLLAMA MODEL BENCHMARK - Hotel Chatbot Use Case")
print("=" * 70)

results = {}

for model in models:
    print(f"\n🔄 Testing {model}...")
    print("-" * 70)
    
    # Warm up model
    try:
        requests.post(
            'http://localhost:11434/api/generate',
            json={'model': model, 'prompt': 'hi', 'stream': False, 'keep_alive': '10m'},
            timeout=60
        )
        time.sleep(0.5)
    except Exception as e:
        print(f"❌ Warmup failed: {e}")
        continue
    
    model_times = []
    model_responses = []
    
    for i, prompt in enumerate(test_prompts, 1):
        try:
            start = time.time()
            response = requests.post(
                'http://localhost:11434/api/generate',
                json={
                    'model': model,
                    'prompt': prompt,
                    'stream': False,
                    'keep_alive': '10m'
                },
                timeout=30
            )
            
            elapsed = time.time() - start
            result = response.json()
            answer = result.get('response', '').strip()
            
            model_times.append(elapsed)
            model_responses.append(answer[:100])
            
            print(f"  Query {i}: {elapsed:.2f}s")
            print(f"  Answer: {answer[:80]}...")
            
        except Exception as e:
            print(f"  ❌ Query {i} failed: {e}")
            model_times.append(999)
    
    avg_time = sum(model_times) / len(model_times) if model_times else 999
    results[model] = {
        'avg_time': avg_time,
        'times': model_times,
        'responses': model_responses
    }
    
    print(f"\n  📊 Average: {avg_time:.2f}s")

# Summary
print("\n" + "=" * 70)
print("BENCHMARK SUMMARY")
print("=" * 70)

sorted_results = sorted(results.items(), key=lambda x: x[1]['avg_time'])

for i, (model, data) in enumerate(sorted_results, 1):
    avg = data['avg_time']
    
    if avg < 1.5:
        emoji = "🥇 EXCELLENT"
    elif avg < 2.5:
        emoji = "🥈 VERY GOOD"
    elif avg < 4:
        emoji = "🥉 GOOD"
    else:
        emoji = "⚠️  ACCEPTABLE"
    
    print(f"\n{i}. {model}")
    print(f"   {emoji} - Avg: {avg:.2f}s")
    print(f"   Times: {', '.join([f'{t:.2f}s' for t in data['times']])}")

print("\n" + "=" * 70)
print("RECOMMENDATION:")
fastest = sorted_results[0]
print(f"✅ Use {fastest[0]} for production chatbot")
print(f"   Average response time: {fastest[1]['avg_time']:.2f} seconds")
print("=" * 70)
