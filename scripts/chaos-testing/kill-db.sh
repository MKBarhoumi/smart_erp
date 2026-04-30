#!/bin/bash

echo "🔥 Simulating Database Connection Failure..."
echo "⚠️  This will stop MySQL/MariaDB service"
echo "⚠️  Make sure you're in a development environment!"
echo ""

read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Aborted"
    exit 1
fi

echo "🛑 Stopping MySQL/MariaDB service..."
sudo systemctl stop mysql || sudo systemctl stop mariadb || sudo service mysql stop

echo "✅ Database stopped"
echo "📊 Check your application logs for error handling"
echo ""
echo "To restart the database:"
echo "  sudo systemctl start mysql"
echo "  or"
echo "  sudo systemctl start mariadb"