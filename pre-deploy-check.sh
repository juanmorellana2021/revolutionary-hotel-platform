#!/bin/bash

# Pre-Deployment Checklist Script
# Run this before deploying to catch issues early

set -e

echo "🔍 Pre-Deployment Validation"
echo "=============================="

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

checks_passed=0
checks_failed=0

# Function to run check
run_check() {
    local check_name=$1
    local check_command=$2
    
    echo -n "Checking $check_name... "
    
    if eval $check_command > /dev/null 2>&1; then
        echo -e "${GREEN}✅${NC}"
        checks_passed=$((checks_passed + 1))
        return 0
    else
        echo -e "${RED}❌${NC}"
        checks_failed=$((checks_failed + 1))
        return 1
    fi
}

# 1. Check Node.js version
run_check "Node.js version" "node --version | grep -E 'v1[68]\.'"

# 2. Check npm dependencies
run_check "npm dependencies" "npm ci"

# 3. Check for syntax errors
run_check "JavaScript syntax" "find . -name '*.js' -not -path './node_modules/*' -exec node --check {} \;"

# 4. Run tests
echo -n "Running tests... "
if npm test > /tmp/test_output.txt 2>&1; then
    PASSED=$(grep -o '[0-9]* passing' /tmp/test_output.txt | head -1)
    echo -e "${GREEN}✅ $PASSED${NC}"
    checks_passed=$((checks_passed + 1))
else
    echo -e "${RED}❌ Tests failed${NC}"
    cat /tmp/test_output.txt
    checks_failed=$((checks_failed + 1))
fi

# 5. Check for required environment variables
run_check "Environment variables" "[ -n \"$NODE_ENV\" ]"

# 6. Check critical files exist
echo -n "Checking critical files... "
critical_files=("package.json" "index.js")
all_exist=true

for file in "${critical_files[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ Missing: $file${NC}"
        all_exist=false
    fi
done

if [ "$all_exist" = true ]; then
    echo -e "${GREEN}✅${NC}"
    checks_passed=$((checks_passed + 1))
else
    checks_failed=$((checks_failed + 1))
fi

# 7. Check database connection
echo -n "Checking database connection... "
if ssh social-vps "sudo -u postgres psql -d aini_platform -c 'SELECT 1;'" > /dev/null 2>&1; then
    echo -e "${GREEN}✅${NC}"
    checks_passed=$((checks_passed + 1))
else
    echo -e "${RED}❌${NC}"
    checks_failed=$((checks_failed + 1))
fi

# 8. Check Redis connection
echo -n "Checking Redis connection... "
if ssh social-vps "redis-cli ping" > /dev/null 2>&1; then
    echo -e "${GREEN}✅${NC}"
    checks_passed=$((checks_passed + 1))
else
    echo -e "${RED}❌${NC}"
    checks_failed=$((checks_failed + 1))
fi

# 9. Check disk space on VPS
echo -n "Checking disk space... "
DISK_USAGE=$(ssh social-vps "df -h / | tail -1 | awk '{print \$5}' | sed 's/%//'")
if [ "$DISK_USAGE" -lt 90 ]; then
    echo -e "${GREEN}✅ ${DISK_USAGE}% used${NC}"
    checks_passed=$((checks_passed + 1))
else
    echo -e "${RED}❌ ${DISK_USAGE}% used (>90%)${NC}"
    checks_failed=$((checks_failed + 1))
fi

# 10. Check for uncommitted changes
echo -n "Checking for uncommitted changes... "
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${GREEN}✅${NC}"
    checks_passed=$((checks_passed + 1))
else
    echo -e "${YELLOW}⚠️  Uncommitted changes found${NC}"
    git status --short
fi

# Summary
echo ""
echo "=============================="
echo -e "${GREEN}✅ Passed: $checks_passed${NC}"
echo -e "${RED}❌ Failed: $checks_failed${NC}"
echo "=============================="

if [ $checks_failed -gt 0 ]; then
    echo -e "${RED}⚠️  Pre-deployment checks failed. Fix issues before deploying.${NC}"
    exit 1
else
    echo -e "${GREEN}✅ All checks passed! Ready to deploy.${NC}"
    exit 0
fi
