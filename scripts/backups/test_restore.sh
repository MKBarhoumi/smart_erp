#!/bin/bash

# Backup Restore Test Script
# Schedule: Weekly on Wednesday at 4 AM
# Cron: 0 4 * * 3 /path/to/scripts/backups/test_restore.sh

# Configuration
BACKUP_DIR="/var/backups/smart-erp/daily"
TEST_DIR="/tmp/smart-erp-restore-test"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

# Create test directory
mkdir -p "$TEST_DIR"
mkdir -p /var/log/smart-erp/backups

# Log file
LOG_FILE="/var/log/smart-erp/backups/restore_test_$(date +%Y%m%d).log"

exec > >(tee -a "$LOG_FILE") 2>&1

echo "🧪 Starting Backup Restore Test..."
echo "📅 Date: $(date)"
echo "⏰ Time: $(date +%H:%M:%S)"
echo ""

# Get latest backup
LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/central_*.sql.gz 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "❌ No backup found to test"
    exit 1
fi

echo "📦 Testing backup: $LATEST_BACKUP"
echo "📊 Backup size: $(du -h "$LATEST_BACKUP" | cut -f1)"
echo ""

# Create test database
TEST_DB="smart_erp_restore_test_$(date +%Y%m%d_%H%M%S)"
echo "🔧 Creating test database: $TEST_DB"

mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
    -e "CREATE DATABASE IF NOT EXISTS $TEST_DB"

if [ $? -ne 0 ]; then
    echo "❌ Failed to create test database"
    exit 1
fi

echo "✅ Test database created"

# Restore backup
echo "🔄 Restoring backup to test database..."
START_TIME=$(date +%s)

gunzip -c "$LATEST_BACKUP" | mysql -h "$DB_HOST" -P "$DB_PORT" \
    -u "$DB_USER" -p"$DB_PASSWORD" "$TEST_DB"

RESTORE_RESULT=$?
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

if [ $RESTORE_RESULT -eq 0 ]; then
    echo "✅ Backup restored successfully"
    echo "⏱️  Restore time: ${DURATION}s"
else
    echo "❌ Backup restore failed"
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        -e "DROP DATABASE IF EXISTS $TEST_DB"
    exit 1
fi

# Verify restored data
echo "🔍 Verifying restored data..."

# Check tables
TABLE_COUNT=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
    -e "USE $TEST_DB; SHOW TABLES;" | wc -l)

echo "📊 Tables restored: $TABLE_COUNT"

# Check specific tables
CRITICAL_TABLES=("tenants" "domains" "plans" "users")
ALL_TABLES_OK=true

for TABLE in "${CRITICAL_TABLES[@]}"; do
    TABLE_EXISTS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        -e "USE $TEST_DB; SHOW TABLES LIKE '$TABLE';" | grep -c "$TABLE" || true)
    
    if [ "$TABLE_EXISTS" -gt 0 ]; then
        ROW_COUNT=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
            -e "USE $TEST_DB; SELECT COUNT(*) FROM $TABLE;" | tail -1)
        echo "✅ Table '$TABLE': $ROW_COUNT rows"
    else
        echo "❌ Table '$TABLE': NOT FOUND"
        ALL_TABLES_OK=false
    fi
done

# Clean up test database
echo "🧹 Cleaning up test database..."
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
    -e "DROP DATABASE IF EXISTS $TEST_DB"

echo "✅ Test database cleaned up"

# Test summary
echo ""
echo "📊 Restore Test Summary:"
echo "  - Backup file: $LATEST_BACKUP"
echo "  - Restore time: ${DURATION}s"
echo "  - Tables restored: $TABLE_COUNT"
echo "  - Critical tables: $([ "$ALL_TABLES_OK" = true ] && echo "✅ OK" || echo "❌ FAILED")"
echo "  - Log file: $LOG_FILE"
echo ""

if [ "$ALL_TABLES_OK" = true ]; then
    echo "🎉 Restore test PASSED"
    
    # Send notification
    if command -v curl &> /dev/null; then
        curl -X POST "$SLACK_WEBHOOK_URL" \
            -H 'Content-Type: application/json' \
            -d "{
                \"text\": \"Backup Restore Test Passed\",
                \"attachments\": [{
                    \"color\": \"good\",
                    \"title\": \"Restore Test Results\",
                    \"fields\": [
                        {\"title\": \"Date\", \"value\": \"$(date)\", \"short\": true},
                        {\"title\": \"Restore Time\", \"value\": \"${DURATION}s\", \"short\": true},
                        {\"title\": \"Tables\", \"value\": \"$TABLE_COUNT\", \"short\": true},
                        {\"title\": \"Status\", \"value\": \"✅ PASSED\", \"short\": true}
                    ]
                }]
            }"
    fi
    exit 0
else
    echo "❌ Restore test FAILED"
    
    # Send notification
    if command -v curl &> /dev/null; then
        curl -X POST "$SLACK_WEBHOOK_URL" \
            -H 'Content-Type: application/json' \
            -d "{
                \"text\": \"Backup Restore Test Failed\",
                \"attachments\": [{
                    \"color\": \"danger\",
                    \"title\": \"Restore Test Results\",
                    \"fields\": [
                        {\"title\": \"Date\", \"value\": \"$(date)\", \"short\": true},
                        {\"title\": \"Restore Time\", \"value\": \"${DURATION}s\", \"short\": true},
                        {\"title\": \"Tables\", \"value\": \"$TABLE_COUNT\", \"short\": true},
                        {\"title\": \"Status\", \"value\": \"❌ FAILED\", \"short\": true}
                    ]
                }]
            }"
    fi
    exit 1
fi