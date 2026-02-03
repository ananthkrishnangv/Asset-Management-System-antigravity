# Podman Deployment Guide

This directory contains the files necessary to deploy the Asset Management System on a Podman host.

## Files Created
1. `Containerfile`: The definition for the Application container image.
2. `apache-config.conf`: Apache configuration with SSL and Rewrite rules.
3. `deploy_podman.sh`: Automated deployment script.

## Prerequisites
- Target Host: `10.10.200.53` (or similar)
- Podman installed and running.
- Network `mcvlan1` must exist on the host.
- SSL Certificates in `SSL Key/` directory (`cert.crt`, `cert.key`).

## Deployment Steps
1. Transfer this directory to the Podman host.
   ```bash
   scp -r . root@10.10.200.53:/path/to/deploy/
   ```
2. SSH into the host.
   ```bash
   ssh root@10.10.200.53
   cd /path/to/deploy/
   ```
3. Run the deployment script.
   ```bash
   ./deploy_podman.sh
   ```

## Configuration Details
- **IP Address**: The script uses `10.30.0.26`. You can change `IP_ADDRESS` variable in `deploy_podman.sh` if needed (Range: 10.30.0.26 - 10.30.0.45).
- **Database**: A MariaDB container is created within the same Pod (`ams_pod`). The application connects to `localhost`.
- **Data Persistence**: 
    - Database data is stored in volume `ams_db_data`.
    - Uploads are inside the container at `/var/www/html/uploads`. *Note: For production, you might want to mount a volume for uploads.*

## Modifying for Persistence (Optional but Recommended)
To persist user uploads, edit `deploy_podman.sh` to add a volume mapping for uploads:
```bash
# In the App run command:
-v ams_uploads:/var/www/html/uploads \
```
And creating the volume: `podman volume create ams_uploads`.
