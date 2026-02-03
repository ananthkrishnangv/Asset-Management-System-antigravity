#!/bin/bash
set -e

# Configuration
REMOTE_USER="root"
REMOTE_HOST="10.10.200.53"
REMOTE_DIR="/opt/ams_deployment"

echo "=============================================="
echo "Deploying to $REMOTE_HOST..."
echo "=============================================="

# 1. Sync Files
echo "[1/3] Syncing files to remote host..."
# Using rsync to exclude unnecessary files
rsync -avz --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='_backup_old' \
    --exclude='backups' \
    . $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR

# 2. Fix permissions remotely
echo "[2/3] Preparing remote environment..."
ssh $REMOTE_USER@$REMOTE_HOST "mkdir -p $REMOTE_DIR && chmod +x $REMOTE_DIR/deploy_single.sh $REMOTE_DIR/start_services.sh"

# 3. Execute Deployment
echo "[3/3] Executing deployment on remote host..."
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && ./deploy_single.sh"

echo "=============================================="
echo "Remote Deployment Triggered Successfully!"
echo "=============================================="
