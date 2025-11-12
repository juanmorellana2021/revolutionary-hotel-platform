# Deployment Notification Template
# Send to Telegram, Discord, Slack, or email

# ==========================
# TELEGRAM NOTIFICATION
# ==========================

# 1. Create a bot via @BotFather on Telegram
# 2. Get your chat ID by messaging @userinfobot
# 3. Add secrets to GitHub: TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID

send_telegram_notification() {
    local status=$1  # "success" or "failure"
    local commit_hash=$2
    local commit_msg=$3
    local duration=$4
    
    if [ "$status" = "success" ]; then
        icon="✅"
        title="Deployment Successful"
    else
        icon="❌"
        title="Deployment Failed"
    fi
    
    message="$icon <b>$title</b>%0A%0A"
    message+="📦 <b>Commit:</b> <code>$commit_hash</code>%0A"
    message+="💬 <b>Message:</b> $commit_msg%0A"
    message+="⏱️ <b>Duration:</b> $duration%0A"
    message+="🕐 <b>Time:</b> $(date '+%Y-%m-%d %H:%M:%S')%0A"
    message+="%0A🔗 <a href='https://ainiflow.com'>View Site</a>"
    
    curl -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
        -d "chat_id=${TELEGRAM_CHAT_ID}" \
        -d "text=$message" \
        -d "parse_mode=HTML"
}

# ==========================
# DISCORD WEBHOOK
# ==========================

# 1. Create webhook in Discord server settings
# 2. Add DISCORD_WEBHOOK_URL to GitHub secrets

send_discord_notification() {
    local status=$1
    local commit_hash=$2
    local commit_msg=$3
    
    if [ "$status" = "success" ]; then
        color=3066993  # Green
        title="✅ Deployment Successful"
    else
        color=15158332  # Red
        title="❌ Deployment Failed"
    fi
    
    curl -H "Content-Type: application/json" \
        -X POST "${DISCORD_WEBHOOK_URL}" \
        -d "{
            \"embeds\": [{
                \"title\": \"$title\",
                \"description\": \"$commit_msg\",
                \"color\": $color,
                \"fields\": [
                    {
                        \"name\": \"Commit\",
                        \"value\": \"\`$commit_hash\`\",
                        \"inline\": true
                    },
                    {
                        \"name\": \"Time\",
                        \"value\": \"$(date '+%H:%M:%S')\",
                        \"inline\": true
                    }
                ],
                \"footer\": {
                    \"text\": \"AiniFlow Deployment\"
                },
                \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%S.000Z)\"
            }]
        }"
}

# ==========================
# SLACK WEBHOOK
# ==========================

send_slack_notification() {
    local status=$1
    local commit_hash=$2
    local commit_msg=$3
    
    if [ "$status" = "success" ]; then
        emoji=":white_check_mark:"
        color="good"
    else
        emoji=":x:"
        color="danger"
    fi
    
    curl -X POST "${SLACK_WEBHOOK_URL}" \
        -H 'Content-Type: application/json' \
        -d "{
            \"attachments\": [{
                \"color\": \"$color\",
                \"title\": \"$emoji Deployment $status\",
                \"text\": \"$commit_msg\",
                \"fields\": [
                    {
                        \"title\": \"Commit\",
                        \"value\": \"$commit_hash\",
                        \"short\": true
                    },
                    {
                        \"title\": \"Time\",
                        \"value\": \"$(date '+%H:%M:%S')\",
                        \"short\": true
                    }
                ]
            }]
        }"
}

# ==========================
# EMAIL NOTIFICATION
# ==========================

send_email_notification() {
    local status=$1
    local commit_hash=$2
    local commit_msg=$3
    
    subject="[$status] AiniFlow Deployment - $commit_hash"
    body="Deployment $status\n\nCommit: $commit_hash\nMessage: $commit_msg\nTime: $(date)"
    
    echo -e "$body" | mail -s "$subject" your-email@example.com
}

# ==========================
# USAGE IN GITHUB ACTIONS
# ==========================

# Add to .github/workflows/deploy.yml:
#
# - name: Send Success Notification
#   if: success()
#   run: |
#     COMMIT_MSG=$(git log -1 --pretty=%B)
#     COMMIT_HASH=$(git rev-parse --short HEAD)
#     
#     curl -X POST "https://api.telegram.org/bot${{ secrets.TELEGRAM_BOT_TOKEN }}/sendMessage" \
#       -d "chat_id=${{ secrets.TELEGRAM_CHAT_ID }}" \
#       -d "text=✅ Deployment Successful%0A%0ACommit: $COMMIT_HASH%0AMessage: $COMMIT_MSG"

# ==========================
# SETUP INSTRUCTIONS
# ==========================

# 1. TELEGRAM:
#    - Message @BotFather: /newbot
#    - Copy bot token
#    - Message bot to activate
#    - Message @userinfobot to get chat ID
#    - Add to GitHub secrets:
#      * TELEGRAM_BOT_TOKEN
#      * TELEGRAM_CHAT_ID

# 2. DISCORD:
#    - Server Settings → Integrations → Webhooks
#    - Create webhook, copy URL
#    - Add to GitHub secrets: DISCORD_WEBHOOK_URL

# 3. SLACK:
#    - Create Slack app: api.slack.com/apps
#    - Enable Incoming Webhooks
#    - Copy webhook URL
#    - Add to GitHub secrets: SLACK_WEBHOOK_URL
