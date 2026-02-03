<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'asset_mgt');
define('DB_USER', 'ams_user');
define('DB_PASS', 'serc@123');
define('DB_CHARSET', 'utf8mb4');

// App Configuration
define('APP_NAME', 'CSIR-SERC Asset Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://10.30.0.26');

// Session & Security
define('SESSION_NAME', 'AMS_SESSION');
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_NAME', 'ams_csrf_token');
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum login attempts before lockout
define('LOGIN_LOCKOUT_TIME', 900); // Lockout time in seconds (15 minutes)

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
