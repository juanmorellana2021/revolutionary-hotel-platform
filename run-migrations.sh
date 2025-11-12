#!/bin/bash

# Database Migration Runner
# Runs SQL migrations in order and tracks them

set -e  # Exit on error

DB_NAME="aini_platform"
MIGRATIONS_DIR="./migrations"
LOG_FILE="./logs/migrations.log"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create logs directory
mkdir -p logs

echo -e "${YELLOW}🗄️  Database Migration Runner${NC}"
echo "=================================="

# Function to check if migration was applied
migration_applied() {
    local migration_name=$1
    result=$(sudo -u postgres psql -d $DB_NAME -t -c \
        "SELECT COUNT(*) FROM migrations_log WHERE migration_name='$migration_name';" 2>/dev/null || echo "0")
    echo $result | tr -d ' '
}

# Function to log migration
log_migration() {
    local migration_name=$1
    local status=$2
    local error_msg=$3
    
    sudo -u postgres psql -d $DB_NAME -c \
        "INSERT INTO migrations_log (migration_name, status, error_message) 
         VALUES ('$migration_name', '$status', '$error_msg');" 2>/dev/null || true
}

# Create migrations_log table if it doesn't exist
echo "Checking migrations_log table..."
sudo -u postgres psql -d $DB_NAME -f migrations/001_create_migrations_table.sql 2>/dev/null || {
    echo -e "${YELLOW}⚠️  migrations_log table creation skipped (may already exist)${NC}"
}

# Run migrations in order
migration_count=0
skipped_count=0
failed_count=0

for migration in $(ls $MIGRATIONS_DIR/*.sql | sort); do
    migration_name=$(basename $migration)
    
    # Skip the migrations table creation itself
    if [ "$migration_name" = "001_create_migrations_table.sql" ]; then
        continue
    fi
    
    # Check if already applied
    if [ "$(migration_applied $migration_name)" -gt 0 ]; then
        echo -e "${YELLOW}⏭️  Skipped: $migration_name (already applied)${NC}"
        skipped_count=$((skipped_count + 1))
        continue
    fi
    
    # Run migration
    echo -e "${YELLOW}🔄 Running: $migration_name${NC}"
    
    if sudo -u postgres psql -d $DB_NAME -f $migration 2>&1 | tee -a $LOG_FILE; then
        log_migration "$migration_name" "success" ""
        echo -e "${GREEN}✅ Success: $migration_name${NC}"
        migration_count=$((migration_count + 1))
    else
        log_migration "$migration_name" "failed" "See logs/migrations.log"
        echo -e "${RED}❌ Failed: $migration_name${NC}"
        failed_count=$((failed_count + 1))
    fi
    
    echo ""
done

# Summary
echo "=================================="
echo -e "${GREEN}✅ Applied: $migration_count${NC}"
echo -e "${YELLOW}⏭️  Skipped: $skipped_count${NC}"
if [ $failed_count -gt 0 ]; then
    echo -e "${RED}❌ Failed: $failed_count${NC}"
fi
echo "=================================="

# Exit with error if any migrations failed
if [ $failed_count -gt 0 ]; then
    exit 1
fi
