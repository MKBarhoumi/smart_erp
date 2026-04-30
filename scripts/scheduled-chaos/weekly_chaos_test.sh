#!/bin/bash

# Weekly Chaos Testing Script
# Schedule: Every Sunday at 2 AM
# Cron: 0 2 * * 0 /path/to/scripts/scheduled-chaos/weekly_chaos_test.sh

echo "🔥 Starting Weekly Chaos Testing..."
echo "📅 Date: $(date)"
echo "⏰ Time: $(date +%H:%M:%S)"
echo ""

# Log file
LOG_FILE="/var/log/smart-erp/chaos-testing/weekly_$(date +%Y%m%d).log"
mkdir -p /var/log/smart-erp/chaos-testing

exec > >(tee -a "$LOG_FILE") 2>&1

# Test scenarios
SCENARIOS=(
    "queue_worker_failure"
    "stripe_api_failure"
    "database_slowdown"
    "cache_failure"
    "storage_failure"
)

echo "📋 Running chaos scenarios..."
echo ""

for scenario in "${SCENARIOS[@]}"; do
    echo "🎯 Running scenario: $scenario"
    echo "⏰ Started at: $(date)"
    
    case $scenario in
        queue_worker_failure)
            bash /path/to/scripts/chaos-testing/kill-queue.sh
            ;;
        stripe_api_failure)
            php /path/to/scripts/chaos-testing/simulate-stripe-failure.php
            ;;
        database_slowdown)
            php /path/to/scripts/scheduled-chaos/simulate_database_slowdown.php
            ;;
        cache_failure)
            php /path/to/scripts/scheduled-chaos/simulate_cache_failure.php
            ;;
        storage_failure)
            php /path/to/scripts/scheduled-chaos/simulate_storage_failure.php
            ;;
    esac
    
    echo "✅ Scenario completed: $scenario"
    echo "⏰ Completed at: $(date)"
    echo ""
    
    # Wait between scenarios
    sleep 60
done

echo "🎉 Weekly chaos testing completed!"
echo "📊 Results logged to: $LOG_FILE"
echo ""

# Send notification
if command -v curl &> /dev/null; then
    curl -X POST "$SLACK_WEBHOOK_URL" \
        -H 'Content-Type: application/json' \
        -d '{
            "text": "Weekly Chaos Testing Completed",
            "attachments": [{
                "color": "good",
                "title": "Chaos Testing Results",
                "text": "All scenarios completed successfully",
                "fields": [
                    {"title": "Date", "value": "'$(date)'", "short": true},
                    {"title": "Log File", "value": "'$LOG_FILE'", "short": true}
                ]
            }]
        }'
fi

echo "📊 Summary:"
echo "  - Total scenarios: ${#SCENARIOS[@]}"
echo "  - Log file: $LOG_FILE"
echo "  - Status: Completed"
echo ""