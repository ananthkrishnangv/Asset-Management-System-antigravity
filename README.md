# CSIR-SERC Asset Management System

A comprehensive web-based Asset Management System for CSIR-SERC to track, manage, and report on organizational assets.

![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![MariaDB](https://img.shields.io/badge/MariaDB-10.5+-green)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)
![License](https://img.shields.io/badge/License-Internal-red)

## Features

- 📦 **Inventory Management** - DIR and PIR asset tracking
- 📊 **Analytics Dashboard** - Real-time charts and statistics
- 🔄 **Transfer Workflow** - Multi-level approval system
- 📁 **File Uploads** - Scanned documents and asset photos
- 🔔 **Warranty Alerts** - Track expiring warranties and AMCs
- 📱 **QR Code Generation** - Asset identification
- 📧 **Notifications** - Email and WhatsApp integration
- 🔐 **Security** - Role-based access, audit logging

## Quick Start

```bash
# Clone repository
git clone https://github.com/your-org/asset-management.git

# Copy to web server
cp -r asset-management/* /var/www/html/

# Import database
mysql -u root -p asset_mgt < config/database.sql

# Run migrations
mysql -u root -p asset_mgt < config/migration_v2.sql
mysql -u root -p asset_mgt < config/migration_v3_users.sql
mysql -u root -p asset_mgt < config/migration_v4_comprehensive.sql

# Set permissions
chown -R apache:apache /var/www/html
chmod -R 755 /var/www/html
chmod 775 /var/www/html/uploads /var/www/html/backups
```

## Documentation

- [Implementation Details](docs/IMPLEMENTATION_DETAILS.md)
- [Application Details](docs/APPLICATION_DETAILS.md)
- [User Walkthrough](docs/WALKTHROUGH.md)

## Requirements

- PHP 8.0+
- MariaDB 10.5+ or MySQL 8.0+
- Apache with mod_rewrite
- SSL certificate (for HTTPS)

## Default Login

| Field | Value |
|-------|-------|
| URL | https://ir.serc.res.in/public/ |
| AMS ID | 1410145 |
| Password | Contact Admin |

## Directory Structure

```
├── bootstrap.php        # Application bootstrap
├── config/              # Configuration files
├── includes/            # PHP classes
├── public/              # Web-accessible files
│   ├── admin/           # Admin pages
│   ├── inventory/       # Inventory pages
│   ├── reports/         # Report generation
│   └── logs/            # Activity logs
├── templates/           # Layout templates
├── uploads/             # Uploaded files
├── backups/             # Database backups
└── docs/                # Documentation
```

## Deployment

Use the provided deployment script:

```bash
./run_deploy_local.sh
```

This will:
1. Sync files to server via rsync
2. Run database migrations
3. Set proper permissions
4. Restart Apache

## License

Internal use only. CSIR-SERC © 2026

## Support

CSIR-SERC ICT Division - ict@serc.res.in
