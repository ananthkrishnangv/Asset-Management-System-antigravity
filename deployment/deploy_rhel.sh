#!/bin/bash

# Configuration
REMOTE_USER="root"
REMOTE_HOST="10.10.200.57"
REMOTE_DIR="/var/www/html/asset_management"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
LOCAL_DIR="$(dirname "$SCRIPT_DIR")"

# Colors
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}Starting deployment to ${REMOTE_HOST} (Rocky Linux/RHEL)...${NC}"

# 1. Create remote directory
echo -e "${GREEN}Creating remote directory...${NC}"
ssh ${REMOTE_USER}@${REMOTE_HOST} "mkdir -p ${REMOTE_DIR}"

# 2. Copy application files
echo -e "${GREEN}Copying application files...${NC}"
rsync -avz --exclude '.git' \
    --exclude 'node_modules' \
    --exclude '.vscode' \
    --exclude 'deployment' \
    --exclude 'tests' \
    --exclude 'README.md' \
    --exclude 'Link to Asset Management - Antigravity.desktop' \
    --exclude 'Asset Management - Antigravity.code-workspace' \
    "${LOCAL_DIR}/" ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}

# 3. Copy SSL Keys (RHEL paths: /etc/pki/tls/...)
echo -e "${GREEN}Copying SSL keys...${NC}"
ssh ${REMOTE_USER}@${REMOTE_HOST} "mkdir -p /etc/pki/tls/certs /etc/pki/tls/private"
scp "${LOCAL_DIR}/SSL Key/cert.crt" ${REMOTE_USER}@${REMOTE_HOST}:/etc/pki/tls/certs/ir.serc.res.in.crt
scp "${LOCAL_DIR}/SSL Key/cert.key" ${REMOTE_USER}@${REMOTE_HOST}:/etc/pki/tls/private/ir.serc.res.in.key

# 4. Copy Apache Config
echo -e "${GREEN}Copying Apache configuration...${NC}"
scp "${SCRIPT_DIR}/ams.conf" ${REMOTE_USER}@${REMOTE_HOST}:/etc/httpd/conf.d/ir.serc.res.in.conf

# 5. Configure Remote Server
echo -e "${GREEN}Configuring remote server...${NC}"
ssh ${REMOTE_USER}@${REMOTE_HOST} "bash -s" <<EOF
    # Install mod_ssl if needed
    if ! rpm -qa | grep -qw mod_ssl; then
        echo "Installing mod_ssl..."
        dnf install -y mod_ssl
    fi

    # Set permissions (user is apache, not www-data)
    chown -R apache:apache ${REMOTE_DIR}
    chmod -R 755 ${REMOTE_DIR}

    # Update APP_URL in config.php
    echo "Configuring APP_URL..."
    sed -i "s|define('APP_URL', .*);|define('APP_URL', 'https://ir.serc.res.in');|g" ${REMOTE_DIR}/config/config.php
    
    # Ensure writable directories exist and set permissions
    mkdir -p ${REMOTE_DIR}/storage ${REMOTE_DIR}/uploads ${REMOTE_DIR}/public/logs
    chown -R apache:apache ${REMOTE_DIR}/storage ${REMOTE_DIR}/uploads ${REMOTE_DIR}/public/logs
    chmod -R 775 ${REMOTE_DIR}/storage ${REMOTE_DIR}/uploads ${REMOTE_DIR}/public/logs

    # SELinux Contexts
    echo "Applying SELinux contexts..."
    if command -v chcon &> /dev/null; then
        chcon -R -t httpd_sys_content_t ${REMOTE_DIR}
        chcon -R -t httpd_sys_rw_content_t ${REMOTE_DIR}/storage
        chcon -R -t httpd_sys_rw_content_t ${REMOTE_DIR}/uploads
        chcon -R -t httpd_sys_rw_content_t ${REMOTE_DIR}/public/logs
        # Allow httpd to connect to network (if needed for db)
        setsebool -P httpd_can_network_connect 1
        setsebool -P httpd_can_sendmail 1
    fi

    # Firewall
    if command -v firewall-cmd &> /dev/null; then
        echo "Configuring Firewall..."
        firewall-cmd --permanent --add-service=http
        firewall-cmd --permanent --add-service=https
        firewall-cmd --reload
    fi

    # Restart Apache (httpd)
    echo "Restarting httpd..."
    systemctl restart httpd
EOF

echo -e "${GREEN}Deployment completed successfully!${NC}"
echo -e "${GREEN}Access the site at https://ir.serc.res.in${NC}"
