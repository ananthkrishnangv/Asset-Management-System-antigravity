# CSIR-SERC Asset Management System

## Application Overview

A modern, web-based Asset Management System designed for CSIR-SERC (Central Building Research Institute - Structural Engineering Research Centre) to digitize and streamline the management of organizational assets.

---

## Key Features

### 🏢 Inventory Management
- **DIR (Departmental Inventory Register)**: Track department-level assets
- **PIR (Personal Issue Register)**: Track personal-issue items to employees
- Add, edit, view, and delete assets with full audit trail
- File uploads for scanned documents and asset photos
- QR code generation for asset tracking

### 📊 Analytics & Reports
- Interactive dashboard with real-time statistics
- Department-wise asset distribution charts
- Category-wise breakdown
- Condition status visualization
- Monthly trend analysis
- Export to CSV/Excel and PDF formats

### 🔄 Transfer Management
- Request asset transfers between users/departments
- Multi-level approval workflow (Supervisor → HoD)
- Complete transfer history
- Email/WhatsApp notifications

### 🏪 Stores Returns
- Return non-serviceable items to stores
- Approval workflow for returns
- Track disposal and redistribution

### ⚙️ Administration
- User management with role-based access
- Department and category configuration
- System settings with database persistence
- SMTP email configuration
- WhatsApp Business API integration
- Automated backup scheduling
- Activity log viewer

### 🔐 Security
- Secure login with bcrypt password hashing
- Role-based access control (Admin, Supervisor, Employee)
- CSRF protection on all forms
- XSS prevention
- Session management with timeout
- Complete audit trail

---

## User Roles

| Role | Access Level |
|------|--------------|
| **Administrator** | Full system access, settings, user management |
| **Supervisor/Purchase Officer** | Add/edit inventory, approve transfers, generate reports |
| **Employee** | View assigned items, request transfers and returns |

---

## Default Login

| Field | Value |
|-------|-------|
| URL | https://ir.serc.res.in/public/ |
| AMS ID | 1410145 |
| Role | Administrator |

---

## Screenshots

### Login Page
Premium glassmorphism design with animated orbs and gradient effects.

### Dashboard
Statistics cards, charts for category distribution, monthly trends, and recent activity.

### Inventory List
DataTables-powered list with search, sort, pagination, and export buttons.

### Add Asset Form
Multi-section form with file uploads, Select2 dropdowns, and image preview.

### Reports
Chart.js visualizations with export options and expiring warranty/AMC alerts.

### Settings
Functional settings panel with SMTP, WhatsApp, backup, and system info.

---

## Technical Requirements

- **Server**: Linux (Rocky Linux/AlmaLinux/CentOS recommended)
- **Web Server**: Apache 2.4+ with mod_rewrite
- **PHP**: 8.0 or higher
- **Database**: MariaDB 10.5+ or MySQL 8.0+
- **SSL**: Required for HTTPS

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.0.0 | Jan 2026 | Major rewrite with enhanced UI, file uploads, warranty tracking |
| 1.0.0 | Dec 2025 | Initial release with basic inventory management |

---

## Support

- **Email**: ict@serc.res.in
- **Phone**: +91-44-22549200
- **Address**: CSIR-SERC, Taramani, Chennai - 600113, India

---

## License

Internal use only. CSIR-SERC © 2026
