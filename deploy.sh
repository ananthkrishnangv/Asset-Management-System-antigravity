#!/bin/bash
# CSIR-SERC Asset Management System - Complete Deployment Script
# Includes MariaDB root password reset
# For server: 10.10.200.57 | Domain: ir.serc.res.in
# Run as root on the server

set -e
echo "=============================================="
echo "CSIR-SERC Asset Management - Full Deployment"
echo "=============================================="

# Definition of variables
SERVER_IP="10.10.200.57"
REMOTE_DIR="/var/www/html"
DB_NAME="asset_mgt"
DB_USER="ams_user"
DB_PASS="serc@123"

# Colors
GREEN='\033[0;32m'
NC='\033[0m'

# ================================================
# 1. RESET MARIADB ROOT PASSWORD
# ================================================
echo "[1/7] Resetting MariaDB root password..."
systemctl stop mariadb
# Start MariaDB in safe mode
mysqld_safe --skip-grant-tables --skip-networking &
sleep 5
# Reset root password
mariadb -u root << 'RESET_PWD'
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'serc@123';
FLUSH PRIVILEGES;
RESET_PWD
# Kill safe mode and restart normally
pkill -f mysqld_safe 2>/dev/null || true
pkill -f mariadbd 2>/dev/null || true
sleep 2
systemctl start mariadb
sleep 2

# ================================================
# 2. CREATE DATABASE & USER
# ================================================
echo "[2/7] Setting up database..."
mariadb -u root -p'serc@123' << 'MYSQL_SCRIPT'
CREATE DATABASE IF NOT EXISTS `asset_mgt` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS 'ams_user'@'localhost';
CREATE USER 'ams_user'@'localhost' IDENTIFIED BY 'serc@123';
GRANT ALL PRIVILEGES ON `asset_mgt`.* TO 'ams_user'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# ================================================
# 3. COPY APPLICATION FILES
# ================================================
echo "[3/7] Copying application files..."
mkdir -p $REMOTE_DIR
# Copy all project files including hidden ones
cp -r . $REMOTE_DIR/
# Remove git files from production
rm -rf $REMOTE_DIR/.git $REMOTE_DIR/.gitignore $REMOTE_DIR/deploy.sh $REMOTE_DIR/composer.json $REMOTE_DIR/composer.lock

# ================================================
# 4. DATABASE IMPORT
# ================================================
echo "[4/7] Importing database schema..."
if [ -f $REMOTE_DIR/config/database.sql ]; then
    mariadb -u root -p'serc@123' asset_mgt < $REMOTE_DIR/config/database.sql
    echo "   Schema imported!"
fi
if [ -f $REMOTE_DIR/config/users_import.sql ]; then
    mariadb -u root -p'serc@123' asset_mgt < $REMOTE_DIR/config/users_import.sql
    echo "   Users imported!"
fi

# ================================================
# 5. CONFIGURATION
# ================================================
echo "[5/7] Creating application configuration..."
cat > $REMOTE_DIR/config/config.php << 'PHPCONFIG'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
define('DB_HOST', 'localhost');
define('DB_NAME', 'asset_mgt');
define('DB_USER', 'ams_user');
define('DB_PASS', 'serc@123');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'CSIR-SERC Asset Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://ir.serc.res.in');
define('SESSION_NAME', 'AMS_SESSION');
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_NAME', 'ams_csrf_token');
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM_EMAIL', 'noreply@serc.res.in');
define('SMTP_FROM_NAME', 'CSIR-SERC AMS');
define('SMTP_ENCRYPTION', 'tls');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
PHPCONFIG

# Run Migration V2
if [ -f /var/www/html/config/migration_v2.sql ]; then
    echo "Running Schema Migration V2..."
    mariadb -u root -p'serc@123' asset_mgt < /var/www/html/config/migration_v2.sql
fi

# Run Migration V3 (User Management)
if [ -f /var/www/html/config/migration_v3_users.sql ]; then
    echo "Running Schema Migration V3 (User Management)..."
    mariadb -u root -p'serc@123' asset_mgt < /var/www/html/config/migration_v3_users.sql
fi

# Run Migration V4 (Comprehensive Update)
if [ -f /var/www/html/config/migration_v4_comprehensive.sql ]; then
    echo "Running Schema Migration V4 (Comprehensive)..."
    mariadb -u root -p'serc@123' asset_mgt < /var/www/html/config/migration_v4_comprehensive.sql
fi

# ================================================
# 6. APACHE CONFIGURATION
# ================================================
echo "[6/7] Configuring Apache with SSL..."
rm -f /etc/httpd/conf.d/ir.serc.res.in.conf 2>/dev/null
cat > /etc/httpd/conf.d/ir.serc.res.in.conf << 'APACHECONF'
<VirtualHost *:80>
    ServerName ir.serc.res.in
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName ir.serc.res.in
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    SSLEngine on
    SSLCertificateFile "/var/www/html/SSL Key/cert.crt"
    SSLCertificateKeyFile "/var/www/html/SSL Key/cert.key"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
</VirtualHost>
APACHECONF

# ================================================
# 7. PERMISSIONS & SERVICES
# ================================================
echo "[7/7] Finalizing permissions and restarting..."
chown -R apache:apache $REMOTE_DIR
chmod -R 755 $REMOTE_DIR
mkdir -p $REMOTE_DIR/uploads $REMOTE_DIR/backups $REMOTE_DIR/logs
chown apache:apache $REMOTE_DIR/uploads $REMOTE_DIR/backups $REMOTE_DIR/logs
chmod 775 $REMOTE_DIR/uploads $REMOTE_DIR/backups $REMOTE_DIR/logs
chmod 600 "$REMOTE_DIR/SSL Key/cert.key" 2>/dev/null || true

restorecon -Rv $REMOTE_DIR || echo "SELinux restorecon skipped"
httpd -t && systemctl restart httpd && systemctl enable httpd

echo -e "${GREEN}DEPLOYMENT COMPLETE!${NC}"
