#!/bin/bash
set -e

APP_NAME="ams_single"
IMAGE_NAME="localhost/ams_single:latest"
CONTAINER_NAME="ams_app_single"
NET_NAME="mcvlan1"
IP_ADDRESS="10.30.0.26"
DB_VOLUME="ams_db_single_data"

# 1. Prepare Database Init Files context for build (copying them so COPY in Containerfile works if we chose to COPY, 
# but we are using a mounting strategy or copying in start_services. 
# Actually, the start_services.sh looks for /docker-entrypoint-initdb.d files.
# We should probably copy them into the image during build OR mount them.
# Let's copy them into a temporary folder 'db_init' and copy that in Containerfile or mount it.
# To make it self contained, let's COPY them in the Containerfile. 
# wait, my Containerfile.single didn't COPY them specifically to /docker-entrypoint-initdb.d
# Let's adjust this script to prepare them and I'll update Containerfile.single to copy them.

echo "Preparing DB init scripts..."
mkdir -p db_init_single
rm -f db_init_single/*.sql

# Copy the unified schema file
if [ -f "config/init_full_schema.sql" ]; then
    cp "config/init_full_schema.sql" "db_init_single/01_init_full_schema.sql"
    echo "  > Copied config/init_full_schema.sql"
else
    echo "  > ERROR: config/init_full_schema.sql not found!"
    exit 1
fi
# Exclude incompatible ones

# 2. Build Image
echo "Building Image..."
# We need to ensure db_init_single files are inside the image for the entrypoint to use them.
# I will append a COPY instruction to the build context or just COPY them in the Containerfile.
# Simpler: rely on the 'COPY . /var/www/html/' and then move them in the Containerfile?
# No, let's just mount them? No, user wants a single container deployment, often implies self-sufficient image if possible, 
# but persistence is needed. 
# I will Add a COPY to the Containerfile via a dynamic modification or just rely on 'COPY . /var/www/html' 
# and update start_services.sh to look in /var/www/html/config/ ...
# Actually, start_services.sh looks in /docker-entrypoint-initdb.d. 
# I will just mount the local db_init_single directory to /docker-entrypoint-initdb.d in run command? 
# BUT, we are building on the remote host? NO, deploy_podman.sh runs ON the remote host.
# So if I copy the files there, I can mount them.

# 3. Cleanup Old
echo "Cleaning up old containers..."
podman rm -f $CONTAINER_NAME || true
# Also remove the old pod if it exists
podman pod rm -f ams_pod || true
podman rm -f ams_app || true # old container name
podman rm -f ams_db || true  # old container name

# 4. Run Container
echo "Running Container..."
# Note: We need to make sure the volume exists for DB persistence
podman volume create $DB_VOLUME || true

podman build -f Containerfile.single -t $IMAGE_NAME .

# Run with privileged might be needed for systemd or complex setups, but for simple bash entrypoint causing httpd+mysqld it should be fine.
# We need to map the database init scripts if we didn't COPY them.
# The 'COPY . /var/www/html' puts config/database.sql at /var/www/html/config/database.sql
# So I should update start_services.sh to look there, OR mount.
# Let's trust the current COPY . and update start_services.sh to use the /var/www/html/config path? 
# OR just mount the prepared dir. 
# Mounting is safer for ensuring we use the exact files we prepared.

podman run -d --name $CONTAINER_NAME \
    --network $NET_NAME --ip $IP_ADDRESS \
    -v $DB_VOLUME:/var/lib/mysql \
    -v ./uploads:/var/www/html/uploads \
    -v ./db_init_single:/docker-entrypoint-initdb.d:Z \
    $IMAGE_NAME

echo "Deployment of Single Container Complete. IP: $IP_ADDRESS"
