#!/bin/bash
# Run this on the server after git pull
# Usage: bash deploy/setup-worker.sh

set -e

echo "=== Installing supervisor if needed ==="
if ! command -v supervisord &> /dev/null; then
    apt-get update && apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor
fi

echo "=== Copying worker config ==="
cp /var/www/credit-api/deploy/credit-worker.conf /etc/supervisor/conf.d/credit-worker.conf

echo "=== Creating log file ==="
touch /var/www/credit-api/storage/logs/worker.log
chown www-data:www-data /var/www/credit-api/storage/logs/worker.log

echo "=== Reloading supervisor ==="
supervisorctl reread
supervisorctl update
supervisorctl start credit-worker:*

echo "=== Done! Checking status ==="
supervisorctl status credit-worker:*

echo ""
echo "Worker is running! Commands you may need later:"
echo "  supervisorctl status credit-worker:*    # Check status"
echo "  supervisorctl restart credit-worker:*   # Restart worker"
echo "  supervisorctl stop credit-worker:*      # Stop worker"
echo "  tail -f /var/www/credit-api/storage/logs/worker.log  # Watch logs"
