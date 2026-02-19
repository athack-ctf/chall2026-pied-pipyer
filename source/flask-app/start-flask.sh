#!/bin/bash
# Start Flask app in background on container startup

set -e

echo "Starting Flask internal service..."

# Wait for filesystem to be ready
sleep 2

# Start Flask app in background
cd /var/www/html/flask-app
/usr/bin/python3 app.py > /var/log/flask-internal.log 2>&1 &

echo "Flask service started on 0.0.0.0:5000"
echo "Logs: /var/log/flask-internal.log"
