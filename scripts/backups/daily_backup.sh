#!/bin/bash

# Daily Database Backup Script
# Schedule: Daily at 2 AM
# Cron: 0 2 * * * /path/to/scripts/backups/daily_backup.sh

# Configuration
BACKUP_DIR="/var/backups/smart-erp/daily"
RETENTION_DAYS=30
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
CENTRAL_DB="${CENTRAL_DB:-smart_erp}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Log file
LOG_FILE="/var/log/smart-erp/backups/daily_$(date +%Y%m%d).log"
mkdir -p /var/log/smart-erp/backups

exec > >(tee -a "$LOG_FILE") 2>&1

echo "🗄️  Starting Daily Database Backup..."
echo "📅 Date: $(date)"
echo "⏰ Time: $(date +%H:%M:%S)"
echo ""

# Backup central database
echo "📦 Backing up central database: $CENTRAL_DB"
BACKUP_FILE="$BACKUP_DIR/central_$(date +%Y%m%d_%H%M%S).sql.gz"

mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
    --single-transaction --quick --lock-tables=false \
    "$CENTRAL_DB" | gzip > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Central database backup completed: $BACKUP_FILE"
    SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "📊 Backup size: $SIZE"
else
    echo "❌ Central database backup failed"
    exit 1
fi

# Backup tenant databases
echo "📦 Backing up tenant databases..."
TENANT_BACKUP_DIR="$BACKUP_DIR/tenants_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$TENANT_BACKUP_DIR"

# Get list of tenant databases
TENANT_DBS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
    -e "SHOW DATABASES LIKE 'tenant_%'" | grep -v Database)

TENANT_COUNT=0
for DB in $TENANT_DBS; do
    echo "📦 Backing up tenant database: $DB"
    TENANT_BACKUP_FILE="$TENANT_BACKUP_DIR/${DB}.sql.gz"
    
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction --quick --lock-tables=false \
        "$DB" | gzip > "$TENANT_BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "✅ Tenant database backup completed: $DB"
        TENANT_COUNT=$((TENANT_COUNT + 1))
    else
        echo "❌ Tenant database backup failed: $DB"
    fi
done

echo "📊 Total tenant databases backed up: $TENANT_COUNT"

# Backup application files
echo "📦 Backing up application files..."
APP_BACKUP_DIR="$BACKUP_DIR/app_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$APP_BACKUP_DIR"

# Backup important directories
for DIR in storage/app storage/logs public/uploads; do
    if [ -d "/var/www/html/your-project/$DIR" ]; then
        echo "📦 Backing up directory: $DIR"
        tar -czf "$APP_BACKUP_DIR/${DIR//\//_}.tar.gz" \
            -C /var/www/html/your-project "$DIR"
    fi
done

# Clean up old backups
echo "🧹 Cleaning up old backups (retention: $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "central_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "tenants_*" -type d -mtime +$RETENTION_DAYS -exec rm -rf {} +
find "$BACKUP_DIR" -name "app_*" -type d -mtime +$RETENTION_DAYS -exec rm -rf {} +

echo "✅ Old backups cleaned up"

# Backup summary
echo ""
echo "📊 Backup Summary:"
echo "  - Central database: ✅"
echo "  - Tenant databases: $TENANT_COUNT"
echo "  - Application files: ✅"
echo "  - Retention: $RETENTION_DAYS days"
echo "  - Log file: $LOG_FILE"
echo ""

# Send notification
if command -v curl &> /dev/null; then
    curl -X POST "$SLACK_WEBHOOK_URL" \
        -H 'Content-Type: application/json' \
        -d "{
            \"text\": \"Daily Database Backup Completed\",
            \"attachments\": [{
                \"color\": \"good\",
                \"title\": \"Backup Results\",
                \"fields\": [
                    {\"title\": \"Date\", \"value\": \"$(date)\", \"short\": true},
                    {\"title\": \"Central DB\", \"value\": \"✅\", \"short\": true},
                    {\"title\": \"Tenant DBs\", \"value\": \"$TENANT_COUNT\", \"short\": true},
                    {\"title\": \"Retention\", \"value\": \"$RETENTION_DAYS days\", \"short\": true}
                ]
            }]
        }"
fi

echo "🎉 Daily backup completed successfully!"