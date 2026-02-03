#!/bin/bash
set -e

# Initialize MariaDB Data Directory if it is empty
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

# Start MariaDB in the background
echo "Starting MariaDB..."
/usr/bin/mysqld_safe --datadir=/var/lib/mysql &

# Wait for MariaDB to be ready
echo "Waiting for MariaDB to start..."
until mysqladmin ping -h localhost --silent; do
    echo "Waiting for MariaDB..."
    sleep 2
done

# Perform Database Initialization
# Check if we can connect as root with the known password
if mysql -u root -pserc@123 -e "SELECT 1" &>/dev/null; then
    echo "Root password already set."
else
    echo "Setting root password..."
    # Try connecting without password (fresh install default)
    if mysql -u root -e "SELECT 1" &>/dev/null; then
        mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('serc@123');"
        mysql -u root -e "FLUSH PRIVILEGES;"
        echo "Root password set successfully."
    else
        echo "WARNING: Could not connect as root without password. Assuming password is already set or something is wrong."
    fi
fi

# Check if database exists
if ! mysql -u root -pserc@123 -e "USE asset_mgt" &>/dev/null; then
    echo "Database 'asset_mgt' not found. Creating..."
    
    mysql -u root -pserc@123 -e "CREATE DATABASE IF NOT EXISTS asset_mgt;"
    mysql -u root -pserc@123 -e "CREATE USER IF NOT EXISTS 'ams_user'@'localhost' IDENTIFIED BY 'serc@123';"
    mysql -u root -pserc@123 -e "GRANT ALL PRIVILEGES ON asset_mgt.* TO 'ams_user'@'localhost';"
    mysql -u root -pserc@123 -e "FLUSH PRIVILEGES;"

    SCRIPT_DIR="/docker-entrypoint-initdb.d"
    
    # Function to run script with error checking
    run_sql() {
        echo "Running $2..."
        # Using root to run the imports to avoid permission issues, but piping to asset_mgt database
        if mysql -u root -pserc@123 asset_mgt < "$1"; then
            echo "$2 completed."
        else
            echo "ERROR running $2"
            exit 1
        fi
    }

    if [ -f "$SCRIPT_DIR/01_init_full_schema.sql" ]; then
        run_sql "$SCRIPT_DIR/01_init_full_schema.sql" "01_init_full_schema.sql"
    fi
    
    echo "Database initialization completed."
else
    echo "Database 'asset_mgt' already exists. Skipping initialization."
fi

# Start PHP-FPM
echo "Starting PHP-FPM..."
mkdir -p /run/php-fpm
/usr/sbin/php-fpm -D

# Start Apache in the foreground
echo "Starting Apache..."
rm -f /run/httpd/httpd.pid
exec /usr/sbin/httpd -D FOREGROUND
