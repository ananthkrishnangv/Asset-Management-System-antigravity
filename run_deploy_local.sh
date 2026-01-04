#!/bin/bash
# CSIR-SERC Asset Management System - Local Deployment Script
# Deploys to server using rsync over SSH
# Usage: bash run_deploy_local.sh

set -e

# Configuration
SERVER_IP="10.10.200.57"
REMOTE_USER="root"
REMOTE_DIR="/var/www/html"
LOCAL_DIR="$(dirname "$0")"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}=============================================="
echo "CSIR-SERC Asset Management - Deployment"
echo -e "==============================================${NC}"
echo ""
echo "Server: $SERVER_IP"
echo "Remote Dir: $REMOTE_DIR"
echo ""

# Check SSH connectivity
echo -e "${YELLOW}[1/5] Checking SSH connectivity...${NC}"
if ssh -o ConnectTimeout=10 -o BatchMode=yes $REMOTE_USER@$SERVER_IP exit 2>/dev/null; then
    echo -e "${GREEN}SSH connection successful${NC}"
else
    echo -e "${RED}Cannot connect to $SERVER_IP. Please check:${NC}"
    echo "1. Server is accessible"
    echo "2. SSH key is configured or use password"
    echo ""
    echo "Try: ssh $REMOTE_USER@$SERVER_IP"
    exit 1
fi

# Sync files
echo -e "${YELLOW}[2/5] Syncing files to server...${NC}"
rsync -avz --delete \
    --exclude '.git' \
    --exclude '.gitignore' \
    --exclude '*.sql' \
    --exclude 'deploy.sh' \
    --exclude 'run_deploy_local.sh' \
    --exclude 'composer.json' \
    --exclude 'composer.lock' \
    --exclude 'composer.phar' \
    --exclude '*.txt' \
    --exclude '*.xlsx' \
    --exclude 'backups/*' \
    --exclude 'uploads/*' \
    "$LOCAL_DIR/" "$REMOTE_USER@$SERVER_IP:$REMOTE_DIR/"

echo -e "${GREEN}Files synced successfully${NC}"

# Run database migrations
echo -e "${YELLOW}[3/5] Running database migrations...${NC}"
ssh $REMOTE_USER@$SERVER_IP << 'REMOTE_SCRIPT'
cd /var/www/html

# Run migrations if they exist
if [ -f config/migration_v2.sql ]; then
    echo "Running Migration V2..."
    mariadb -u ams_user -p'serc@123' asset_mgt < config/migration_v2.sql 2>/dev/null || echo "V2 already applied or skipped"
fi

if [ -f config/migration_v3_users.sql ]; then
    echo "Running Migration V3..."
    mariadb -u ams_user -p'serc@123' asset_mgt < config/migration_v3_users.sql 2>/dev/null || echo "V3 already applied or skipped"
fi

if [ -f config/migration_v4_comprehensive.sql ]; then
    echo "Running Migration V4..."
    mariadb -u ams_user -p'serc@123' asset_mgt < config/migration_v4_comprehensive.sql 2>/dev/null || echo "V4 already applied or skipped"
fi

echo "Migrations complete"
REMOTE_SCRIPT

# Fix permissions
echo -e "${YELLOW}[4/5] Setting permissions...${NC}"
ssh $REMOTE_USER@$SERVER_IP << 'REMOTE_SCRIPT'
cd /var/www/html

# Set ownership
chown -R apache:apache /var/www/html
chmod -R 755 /var/www/html

# Create required directories
mkdir -p uploads backups logs uploads/qr uploads/profiles
chown apache:apache uploads backups logs uploads/qr uploads/profiles
chmod 775 uploads backups logs uploads/qr uploads/profiles

# Fix SELinux contexts
chcon -Rt httpd_sys_content_t /var/www/html 2>/dev/null || true
chcon -Rt httpd_sys_rw_content_t /var/www/html/uploads 2>/dev/null || true
chcon -Rt httpd_sys_rw_content_t /var/www/html/backups 2>/dev/null || true
chcon -Rt httpd_sys_rw_content_t /var/www/html/logs 2>/dev/null || true

echo "Permissions set"
REMOTE_SCRIPT

# Restart Apache
echo -e "${YELLOW}[5/5] Restarting web server...${NC}"
ssh $REMOTE_USER@$SERVER_IP "systemctl restart httpd && systemctl restart php-fpm"

echo ""
echo -e "${GREEN}=============================================="
echo "DEPLOYMENT COMPLETE!"
echo "=============================================="
echo ""
echo "Portal URL: https://ir.serc.res.in"
echo "Login: https://ir.serc.res.in/public/"
echo ""
echo "Default Admin: AMS ID 1410145"
echo -e "==============================================${NC}"
