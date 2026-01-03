# Deployment Details - CSIR-SERC Asset Management System

> [!CAUTION]
> **SENSITIVE INFORMATION**: This document contains production credentials. Do not share publicly.

## Server Information
- **IP Address**: `10.10.200.57`
- **Domain**: `https://ir.serc.res.in`
- **OS**: Rocky Linux 9.7 (Blue Onyx)
- **Web Server**: Apache 2.4.62
- **Database**: MariaDB 11.4.9
- **PHP Version**: 8.0.30

## Credentials

### Application Login (Administrator)
- **URL**: [https://ir.serc.res.in](https://ir.serc.res.in)
- **Username (AMS ID)**: `admin`
- **Password**: `Dda5a3d52a#4815`

### Database Credentials
- **Host**: `localhost`
- **Database Name**: `asset_mgt`
- **Root Password**: `S#erc@123`
- **Application User**: `ams_user`
- **Application Password**: `S#erc@123`

### SSH Access
- **User**: `root`
- **Auth**: Key-based authentication (Authorized keys configured on server)

## Deployment Process

The project includes an automated deployment script tailored for the Rocky Linux environment.

### 1. Prerequisites
- SSH access to `10.10.200.57` as `root` (or sudoer).
- Local machine with `rsync` and `git`.

### 2. Deployment Steps
To deploy the latest code from your local machine:

1.  Navigate to the project root.
2.  Make the script executable (if not already):
    ```bash
    chmod +x deployment/deploy_rhel.sh
    ```
3.  Run the deployment script:
    ```bash
    ./deployment/deploy_rhel.sh
    ```

### 3. Manual Configuration (Reference)

#### Apache Configuration
The configuration file is located at `/etc/httpd/conf.d/ir.serc.res.in.conf`.
- **Document Root**: `/var/www/html/asset_management`
- **Logs**: `/var/log/httpd/ams_error.log`

#### SSL Certificates
- **Certificate**: `/etc/pki/tls/certs/ir.serc.res.in.crt`
- **Private Key**: `/etc/pki/tls/private/ir.serc.res.in.key`

#### Application Config
- **File**: `/var/www/html/asset_management/config/config.php`
- **Important Settings**: `APP_URL` must be set to `https://ir.serc.res.in`. This is handled automatically by the deployment script.

## Troubleshooting

### Database Issues
If the database connection fails, you can reset access using the recovery script:
```bash
# Upload and run recovery script
scp deployment/db_recovery.sql root@10.10.200.57:/tmp/
ssh root@10.10.200.57 "mysql -u root -p'S#erc@123' < /tmp/db_recovery.sql"
```

### Permission Issues
If files are not writable/readable:
```bash
# Fix ownership and SELinux contexts (Automatic in deploy script)
ssh root@10.10.200.57 "chown -R apache:apache /var/www/html/asset_management"
```
