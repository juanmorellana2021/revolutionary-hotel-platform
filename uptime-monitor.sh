#!/bin/bash

# Simple Uptime Monitor for AiniFlow
# Pings /health endpoint and logs downtime

HEALTH_URL="https://ainiflow.com/health"
LOG_FILE="/var/log/ainiflow-uptime.log"
ALERT_FILE="/var/log/ainiflow-alerts.log"

# Create logs directory if it doesn't exist
mkdir -p "$(dirname "$LOG_FILE")"

# Check health endpoint
response=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL" --max-time 10)

timestamp=$(date '+%Y-%m-%d %H:%M:%S')

if [ "$response" = "200" ]; then
    echo "[$timestamp] UP - Status: $response" >> "$LOG_FILE"
else
    echo "[$timestamp] DOWN - Status: $response" >> "$LOG_FILE"
    echo "[$timestamp] ALERT: Service is DOWN - Status: $response" >> "$ALERT_FILE"
    
    # Optional: Send alert (uncomment to enable)
    # curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/sendMessage" \
    #   -d "chat_id=<YOUR_CHAT_ID>" \
    #   -d "text=🚨 AiniFlow is DOWN! Status: $response"
fi

# Keep only last 1000 lines of logs
tail -n 1000 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
tail -n 1000 "$ALERT_FILE" > "$ALERT_FILE.tmp" && mv "$ALERT_FILE.tmp" "$ALERT_FILE"
