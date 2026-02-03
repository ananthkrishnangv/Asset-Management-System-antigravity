#!/bin/bash
set -e

# Configuration
POD_NAME="ams_pod"
DB_CONTAINER="ams_db"
WEB_CONTAINER="ams_web"
IMAGE_NAME="ams_app:latest"
NETWORK="mcvlan1"
IP_ADDRESS="10.30.0.26"  # Picked from range 10.30.0.26 - 10.30.0.45

echo "Preparing for deployment..."

# 1. Prepare Database Init Files
echo "Organizing database initialization files..."
rm -rf db_init
mkdir -p db_init

# Function to copy if exists
copy_safe() {
    if [ -f "$1" ]; then
        cp "$1" "db_init/$2"
        echo "Included $1 as $2"
    else
        echo "Warning: $1 not found, skipping."
    fi
}

copy_safe "config/database.sql" "01_database.sql"
copy_safe "config/users_import.sql" "02_users_import.sql"
# Skip v2 and v3 as they are incompatible with current database.sql
copy_safe "config/migration_v4_comprehensive.sql" "05_migration_v4_comprehensive.sql"

# 2. Build the Application Image
echo "Building application image..."
podman build -t $IMAGE_NAME -f Containerfile .

# 3. Clean up old deployment
echo "Cleaning up old pod and containers..."
podman pod stop $POD_NAME 2>/dev/null || true
podman pod rm $POD_NAME 2>/dev/null || true
# Also ensure containers are gone if not in pod
podman rm -f $DB_CONTAINER $WEB_CONTAINER 2>/dev/null || true

# 4. Create Pod
echo "Creating Pod..."
# Note: Macvlan network must exist. 
podman pod create --name $POD_NAME --network $NETWORK --ip $IP_ADDRESS

# 5. Run Database Container
echo "Starting Database Container..."
podman run -d --name $DB_CONTAINER --pod $POD_NAME \
    -e MYSQL_ROOT_PASSWORD=serc@123 \
    -e MYSQL_DATABASE=asset_mgt \
    -e MYSQL_USER=ams_user \
    -e MYSQL_PASSWORD=serc@123 \
    -v ./db_init:/docker-entrypoint-initdb.d:z \
    -v ams_db_data:/var/lib/mysql \
    docker.io/library/mariadb:10.11

# Wait for DB to initialize (optional, but good practice before app starts if app tries to connect immediately)
# However, php connection is on request, so usually okay.

# 6. Run Application Container
echo "Starting Application Container..."
podman run -d --name $WEB_CONTAINER --pod $POD_NAME \
    $IMAGE_NAME

echo "Deployment success!"
echo "App should be accessible at https://$IP_ADDRESS (if DNS is set to ir.serc.res.in, otherwise use IP with host header)"
echo "Note: Ensure 'SSL Key' directory contains valid certificates."
