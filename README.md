# CSIR-SERC Asset Management System

A modern, secure, and feature-rich Asset Management System built for CSIR-SERC.

![PHP](https://img.shields.io/badge/PHP-8.x-blue)
![MariaDB](https://img.shields.io/badge/MariaDB-10.x-orange)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-06B6D4)

## Features

### Core Functionality
- **DIR Management** - Divisional Inventory Register
- **PIR Management** - Personal Inventory Register
- **Transfer Workflow** - Employee → HoD → Supervisor → Approval
- **Stores Returns** - Repair, scrapping, non-serviceable items
- **QR Code Generation** - Print-ready labels with full item details

### User Management
- Role-based access (Admin, Supervisor, Employee)
- HoD and Supervisor mapping
- Password reset via email

### Advanced Features
- **Image Upload** - Item photos for easy identification
- **Global Search** - Search across inventory, users, transfers
- **Reports & Charts** - Analytics with Chart.js
- **Export** - CSV/Excel and PDF exports
- **Activity Logging** - Complete audit trail
- **Backup System** - Local + cloud storage options

### Security
- CSRF protection
- SQL injection prevention (PDO prepared statements)
- Argon2id password hashing
- Security headers
- Rate limiting

## Installation

### Requirements
- PHP 8.0+
- MariaDB 10.x / MySQL 8.x
- Apache/Nginx with mod_rewrite

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/Asset-Mgmt-antigravity.git
   cd Asset-Mgmt-antigravity
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < config/database.sql
   ```

3. **Configure the application**
   ```bash
   cp config/config.example.php config/config.php
   # Edit config/config.php with your database and SMTP credentials
   ```

4. **Create required directories**
   ```bash
   mkdir -p uploads/{items,po,documents}
   mkdir -p backups
   chmod 755 uploads backups
   ```

5. **Configure your web server**
   - Point document root to the project directory
   - Enable mod_rewrite for Apache

6. **Access the application**
   - URL: `http://your-domain/public/index.php`
   - Default login: `admin` / `admin123`

## Directory Structure

```
├── api/                 # API endpoints
├── config/              # Configuration files
├── includes/            # PHP classes
│   ├── ActivityLog.php
│   ├── Auth.php
│   ├── Backup.php
│   ├── Database.php
│   ├── Mailer.php
│   ├── Security.php
│   └── SerialNumber.php
├── public/              # Web accessible files
│   ├── admin/           # Admin pages
│   ├── inventory/       # DIR/PIR management
│   ├── logs/            # Activity logs
│   ├── qr/              # QR code generation
│   ├── reports/         # Reports & exports
│   ├── stores/          # Stores returns
│   └── transfers/       # Transfer management
├── templates/           # Layout templates
├── uploads/             # Uploaded files (gitignored)
├── backups/             # Backup files (gitignored)
└── Branding/            # Logo and branding assets
```

## Transfer Workflow

```
Employee → HoD Approval → Supervisor Approval → Update DIR/PIR → Notify New Holder + HoD
```

## Technology Stack

- **Backend**: PHP 8.x with PDO
- **Database**: MariaDB/MySQL
- **Frontend**: Tailwind CSS 3.x, Chart.js, Font Awesome 6
- **Email**: SMTP (Gmail compatible)

## Deployment
    
    ### Remote Deployment
    
    The application includes a script to deploy to the production server (`10.10.200.57`).
    
    1. **Navigate to the deployment directory:**
       ```bash
       cd deployment
       ```
    
    2. **Run the deployment script:**
       ```bash
       ./deploy.sh
       ```
       > **Note:** Establish SSH access to the remote server before running this script. The script assumes your local SSH keys are authorized on the server.
       > Ideally, update the `REMOTE_USER` variable in `deploy.sh` if the user is not `root`.
    
    ### Server Configuration
    
    - **Apache Config:** Located at `deployment/ams.conf`
    - **SSL Certificates:** Located in `SSL Key/` directory
    - **Domain:** `https://ir.serc.res.in`
    
    ## Contributing
    
    1. Fork the repository
    2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
    3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
    4. Push to the branch (`git push origin feature/AmazingFeature`)
    5. Open a Pull Request
    
    ## License
    
    This project is developed for CSIR-SERC internal use.
    
    ## Support
    
    For support, contact the ICT Division at CSIR-SERC.
