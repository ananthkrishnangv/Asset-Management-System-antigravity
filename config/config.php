<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'asset_mgt');
define('DB_USER', 'ams_user');
define('DB_PASS', 'serc@123');
define('DB_CHARSET', 'utf8mb4');

// App Configuration
define('APP_NAME', 'CSIR-SERC Asset Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://ir.serc.res.in');

// Session & Security
define('SESSION_NAME', 'AMS_SESSION');
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_NAME', 'ams_csrf_token');
define('PASSWORD_ALGO', PASSWORD_BCRYPT);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM_EMAIL', 'noreply@serc.res.in');
define('SMTP_FROM_NAME', 'CSIR-SERC AMS');
define('SMTP_ENCRYPTION', 'tls');

// File Uploads
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Backup
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
