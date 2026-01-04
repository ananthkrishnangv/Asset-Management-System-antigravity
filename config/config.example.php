<?php
/**
 * CSIR-SERC Asset Management System - Configuration
 * 
 * IMPORTANT: Copy this file to config.php and update the values
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ams_database');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'CSIR-SERC Asset Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/asset-management'); // Update this

// Session Settings
define('SESSION_NAME', 'AMS_SESSION');
define('SESSION_LIFETIME', 3600);

// Security
define('CSRF_TOKEN_NAME', 'ams_csrf_token');
define('PASSWORD_ALGO', PASSWORD_ARGON2ID);

// SMTP Settings (Update with your credentials)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'CSIR-SERC AMS');
define('SMTP_ENCRYPTION', 'tls');

// File Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Backup Settings
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
