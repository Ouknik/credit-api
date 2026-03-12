#!/bin/bash
# Setup queue worker for shared hosting (cron-based)
# Usage: bash deploy/setup-worker.sh
#
# This adds a cron job that runs queue:work every minute.

set -e

# Auto-detect the project directory
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN=$(which php)

echo "=== Project: $PROJECT_DIR ==="
echo "=== PHP: $PHP_BIN ==="

# Create log file
touch "$PROJECT_DIR/storage/logs/worker.log"

# The cron line: run queue worker every minute, process jobs and stop
CRON_CMD="* * * * * cd $PROJECT_DIR && $PHP_BIN artisan queue:work --stop-when-empty --max-time=55 >> storage/logs/worker.log 2>&1"

# Also keep Laravel scheduler
SCHEDULER_CMD="* * * * * cd $PROJECT_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"

# Check if cron already has our worker
if crontab -l 2>/dev/null | grep -q "queue:work"; then
    echo "=== Queue worker cron already exists, updating... ==="
    # Remove old entry and add new one
    (crontab -l 2>/dev/null | grep -v "queue:work" | grep -v "schedule:run"; echo "$CRON_CMD"; echo "$SCHEDULER_CMD") | crontab -
else
    echo "=== Adding queue worker cron ==="
    (crontab -l 2>/dev/null; echo "$CRON_CMD"; echo "$SCHEDULER_CMD") | crontab -
fi

echo ""
echo "=== Current crontab ==="
crontab -l

echo ""
echo "=== Done! Queue worker will run every minute ==="
echo "Commands:"
echo "  tail -f $PROJECT_DIR/storage/logs/worker.log  # Watch worker logs"
echo "  crontab -e  # Edit cron manually"
echo "  $PHP_BIN $PROJECT_DIR/artisan queue:work --stop-when-empty  # Run manually once"
