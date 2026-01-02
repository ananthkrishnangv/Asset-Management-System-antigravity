#!/bin/bash

# Configuration
# CHANGE THIS to the correct user if not root
REMOTE_USER="root"
REMOTE_HOST="10.10.200.57"
REMOTE_DIR="/var/www/html/asset_management"
LOCAL_DIR="../"

# Colors
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}Starting deployment to ${REMOTE_HOST}...${NC}"

# 1. Create remote directory
echo -e "${GREEN}Creating remote directory...${NC}"
ssh ${REMOTE_USER}@${REMOTE_HOST} "mkdir -p ${REMOTE_DIR}"

# 2. Copy application files (excluding git, node_modules, etc.)
echo -e "${GREEN}Copying application files...${NC}"
rsync -avz --exclude '.git' \
    --exclude 'node_modules' \
    --exclude '.vscode' \
    --exclude 'deployment' \
    --exclude 'tests' \
    --exclude 'README.md' \
    --exclude 'Link to Asset Management - Antigravity.desktop' \
    --exclude 'Asset Management - Antigravity.code-workspace' \
    ${LOCAL_DIR}/ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}

# 3. Copy SSL Keys
echo -e "${GREEN}Copying SSL keys...${NC}"
ssh ${REMOTE_USER}@${REMOTE_HOST} "mkdir -p /etc/ssl/certs /etc/ssl/private"
scp "${LOCAL_DIR}/SSL Key/cert.crt" ${REMOTE_USER}@${REMOTE_HOST}:/etc/ssl/certs/ir.serc.res.in.crt
scp "${LOCAL_DIR}/SSL Key/cert.key" ${REMOTE_USER}@${REMOTE_HOST}:/etc/ssl/private/ir.serc.res.in.key

# 4. Copy Apache Config
echo -e "${GREEN}Copying Apache configuration...${NC}"
scp ams.conf ${REMOTE_USER}@${REMOTE_HOST}:/etc/apache2/sites-available/ir.serc.res.in.conf

# 5. Configure Remote Server
echo -e "${GREEN}Configuring remote server (enabling modules, site, restarting apache)...${NC}"
ssh ${REMOTE_USER}@${REMOTE_HOST} "bash -s" <<EOF
    # Enable Apache modules
    a2enmod rewrite
    a2enmod ssl

    # Set permissions
    chown -R www-data:www-data ${REMOTE_DIR}
    chmod -R 755 ${REMOTE_DIR}
    chmod -R 777 ${REMOTE_DIR}/storage
    chmod -R 777 ${REMOTE_DIR}/uploads
    chmod -R 777 ${REMOTE_DIR}/public/logs

    # Enable site
    a2ensite ir.serc.res.in.conf

    # Restart Apache
    systemctl restart apache2
EOF

echo -e "${GREEN}Deployment completed successfully!${NC}"
echo -e "${GREEN}Access the site at https://ir.serc.res.in${NC}"
