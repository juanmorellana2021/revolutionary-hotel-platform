#!/bin/bash

# Rollback Script - Reverts to previous deployment

set -e

APP_DIR="/var/www/aini-platform"
BACKUP_DIR="/var/backups/aini-platform"

echo "🔄 Rolling back deployment..."

# Get previous commit
if [ -f "/tmp/last_deploy.txt" ]; then
    PREVIOUS_COMMIT=$(cat /tmp/last_deploy.txt | cut -d' ' -f3)
    echo "Previous commit: $PREVIOUS_COMMIT"
else
    echo "❌ No previous deployment found"
    exit 1
fi

cd $APP_DIR

# Backup current state first
CURRENT_COMMIT=$(git rev-parse HEAD)
echo "Backing up current state: $CURRENT_COMMIT"
mkdir -p $BACKUP_DIR
echo $CURRENT_COMMIT > $BACKUP_DIR/before_rollback_$(date +%Y%m%d_%H%M%S).txt

# Reset to previous commit
echo "Resetting to: $PREVIOUS_COMMIT"
git reset --hard $PREVIOUS_COMMIT

# Reinstall dependencies (in case package.json changed)
echo "Reinstalling dependencies..."
npm ci --production

# Restart application
echo "Restarting application..."
pm2 restart aini-platform

# Wait and check health
sleep 5
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000/health)

if [ "$HEALTH_STATUS" = "200" ]; then
    echo "✅ Rollback successful! Health check passed."
    echo "Rolled back from $CURRENT_COMMIT to $PREVIOUS_COMMIT"
else
    echo "⚠️ Rollback completed but health check returned: $HEALTH_STATUS"
    exit 1
fi
