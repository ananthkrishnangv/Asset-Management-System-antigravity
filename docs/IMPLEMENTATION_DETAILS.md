# CSIR-SERC Asset Management System - Implementation Details

## Overview

A comprehensive web-based Asset Management System for CSIR-SERC to track, manage, and report on all organizational assets including DIR (Departmental Inventory Register) and PIR (Personal Issue Register) items.

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| **Backend** | PHP 8.x |
| **Database** | MariaDB 10.x / MySQL 8.x |
| **Frontend** | HTML5, CSS3, JavaScript |
| **CSS Framework** | Bootstrap 5.3 |
| **Charts** | Chart.js |
| **Icons** | Font Awesome 6 |
| **Typography** | Noto Sans (Google Fonts) |
| **DataTables** | DataTables 1.13 |
| **Select Dropdowns** | Select2 |

---

## Directory Structure

```
Asset Management - Antigravity/
├── bootstrap.php              # Application bootstrap and autoloader
├── config/
│   ├── config.php             # Main configuration
│   ├── database.sql           # Base schema
│   ├── migration_v2.sql       # V2 schema updates
│   ├── migration_v3_users.sql # User management fields
│   └── migration_v4_comprehensive.sql  # File uploads, warranty, AMC
├── includes/
│   ├── Auth.php               # Authentication and authorization
│   ├── Database.php           # PDO database wrapper
│   ├── Security.php           # CSRF, XSS, password hashing
│   ├── ActivityLog.php        # Audit logging
│   ├── Backup.php             # Database backup utilities
│   ├── Mailer.php             # SMTP email sending
│   ├── EmailNotification.php  # Email templates
│   ├── WhatsApp.php           # WhatsApp Business API
│   └── User.php               # User model
├── public/
│   ├── index.php              # Login page
│   ├── dashboard.php          # Main dashboard
│   ├── profile.php            # User profile
│   ├── scanner.php            # QR/Barcode scanner
│   ├── admin/
│   │   ├── settings.php       # System settings
│   │   ├── users.php          # User management
│   │   ├── departments.php    # Department management
│   │   └── backup.php         # Backup management
│   ├── inventory/
│   │   ├── dir.php            # DIR listing
│   │   ├── pir.php            # PIR listing
│   │   ├── add.php            # Add/Edit asset
│   │   ├── view.php           # Asset details
│   │   └── item-details.php   # Detailed item view
│   ├── reports/
│   │   ├── index.php          # Reports dashboard
│   │   └── export.php         # CSV/PDF export
│   ├── transfers/
│   │   └── index.php          # Transfer requests
│   ├── stores/
│   │   └── returns.php        # Stores returns
│   ├── logs/
│   │   └── activity.php       # Activity log viewer
│   └── qr/
│       └── index.php          # QR code generation
├── templates/
│   └── layout.php             # Page layout template
├── uploads/                   # Uploaded files
├── backups/                   # Database backups
└── deploy.sh                  # Deployment script
```

---

## Database Schema

### Core Tables

#### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| ams_id | VARCHAR(20) | Unique AMS ID |
| emp_name | VARCHAR(200) | Employee name |
| email_id | VARCHAR(100) | Email address |
| password | VARCHAR(255) | Bcrypt hash |
| role | ENUM | admin, supervisor, employee |
| department_id | INT | FK to departments |
| is_active | TINYINT | Active status |
| profile_pic | VARCHAR(255) | Profile image path |

#### `inventory_items`
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| serial_number | VARCHAR(50) | Unique serial/asset ID |
| item_description | VARCHAR(500) | Description |
| category_id | INT | FK to categories |
| department_id | INT | FK to departments |
| current_holder_id | INT | FK to users |
| quantity | INT | Item quantity |
| unit_price | DECIMAL(15,2) | Price per unit |
| amount | DECIMAL(15,2) | Total amount |
| inventory_type | ENUM | dir, pir |
| condition_status | ENUM | new, good, fair, poor, etc. |
| scanned_copy | VARCHAR(255) | Document scan path |
| asset_image | VARCHAR(255) | Asset photo path |
| warranty_expiry | DATE | Warranty end date |
| amc_expiry | DATE | AMC end date |
| qr_code_data | VARCHAR(255) | QR code content |

#### `transfer_requests`
Tracks asset transfer between users/departments with multi-level approval.

#### `stores_returns`
Tracks assets returned to stores for disposal or redistribution.

#### `activity_logs`
Complete audit trail of all system actions.

#### `settings`
Key-value store for system configuration.

---

## Authentication Flow

1. User submits AMS ID + Password on login page
2. `Auth::attempt()` validates credentials against `users` table
3. Password verified using `password_verify()` (bcrypt)
4. Session created with user ID and role
5. CSRF token generated for form protection
6. Activity logged to `activity_logs`

### Role-Based Access Control

| Role | Permissions |
|------|-------------|
| Admin | Full system access, settings, user management |
| Supervisor | Add/edit inventory, approve transfers, reports |
| Employee | View own items, initiate transfers/returns |

---

## Key Features Implementation

### File Uploads
- Files stored in `uploads/` directory
- Scanned copies: PDF, JPG, PNG
- Asset images: JPG, PNG, GIF, WebP
- Max size: 10MB (configurable)
- Unique filename with timestamp

### Warranty/AMC Tracking
- Date fields for `warranty_expiry` and `amc_expiry`
- Dashboard alerts for items expiring in 30/60 days
- Reports page lists all expiring items

### QR Code Generation
- Auto-generated QR data on asset creation
- Format: `AMS-{TYPE}-{DATE}-{UNIQUE_ID}`
- Batch generation available in settings

### Activity Logging
- All CRUD operations logged
- Login/logout events tracked
- IP address and user agent captured
- Filterable log viewer for admins

---

## Security Measures

1. **Password Hashing**: bcrypt with cost factor 12
2. **CSRF Protection**: Token in all forms, verified on POST
3. **XSS Prevention**: `htmlspecialchars()` on all output
4. **SQL Injection**: PDO prepared statements
5. **Session Security**: Regenerated on login, HTTP-only cookies
6. **Rate Limiting**: Failed login attempts tracked

---

## Deployment

### Requirements
- PHP 8.0+
- MariaDB 10.5+ or MySQL 8.0+
- Apache with mod_rewrite
- PHP extensions: pdo_mysql, gd, mbstring, openssl

### Steps
1. Copy files to `/var/www/html`
2. Import `config/database.sql`
3. Run migrations V2, V3, V4
4. Configure `config/config.php`
5. Set directory permissions
6. Configure Apache virtual host
7. Set SELinux contexts (if applicable)

---

## API Endpoints (Future)

The system is designed to support REST API endpoints:
- `GET /api/inventory` - List items
- `GET /api/inventory/{id}` - Get item details
- `POST /api/inventory` - Create item
- `PUT /api/inventory/{id}` - Update item
- `DELETE /api/inventory/{id}` - Delete item

---

## Contact

**Developer**: CSIR-SERC ICT Division  
**Domain**: ir.serc.res.in  
**Support**: ict@serc.res.in
