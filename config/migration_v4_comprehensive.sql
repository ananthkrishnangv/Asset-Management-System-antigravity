-- Migration V4: Comprehensive Schema Update
-- CSIR-SERC Asset Management System
-- Run this AFTER V2 and V3 migrations

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- --------------------------------------------------------
-- Update inventory_items table with all required columns
-- --------------------------------------------------------

-- Add columns if they don't exist (safe migration)
ALTER TABLE `inventory_items`
ADD COLUMN IF NOT EXISTS `scanned_copy` VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `asset_image` VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `amc_details` TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `warranty_details` TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `warranty_expiry` DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `amc_expiry` DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `qr_code_data` VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `qr_code_path` VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `po_number` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `po_date` DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `budget_head` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `stock_reference` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `building_location` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `floor_location` VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `room_location` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `quantity_unit` VARCHAR(50) DEFAULT 'No.',
ADD COLUMN IF NOT EXISTS `amount` DECIMAL(15,2) DEFAULT 0.00;

-- --------------------------------------------------------
-- Update users table with additional fields
-- --------------------------------------------------------

ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `mobile` VARCHAR(20) NULL AFTER `phone`,
ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `mobile`,
ADD COLUMN IF NOT EXISTS `profile_pic` VARCHAR(255) NULL AFTER `address`;

-- --------------------------------------------------------
-- Add indexes for better performance
-- --------------------------------------------------------

-- Only create if not exists (MariaDB 10.5+)
CREATE INDEX IF NOT EXISTS `idx_warranty_expiry` ON `inventory_items` (`warranty_expiry`);
CREATE INDEX IF NOT EXISTS `idx_amc_expiry` ON `inventory_items` (`amc_expiry`);
CREATE INDEX IF NOT EXISTS `idx_qr_code` ON `inventory_items` (`qr_code_data`);
CREATE INDEX IF NOT EXISTS `idx_building` ON `inventory_items` (`building_location`);

-- --------------------------------------------------------
-- Create backup_logs table if not exists
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `backup_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `file_size` BIGINT DEFAULT 0,
    `backup_type` ENUM('auto', 'manual') DEFAULT 'manual',
    `status` ENUM('success', 'failed') DEFAULT 'success',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Create email_logs table if not exists
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT,
    `status` ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add more default settings
-- --------------------------------------------------------

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('smtp_enabled', 'false', 'boolean', 'Enable SMTP email notifications'),
('auto_backup_enabled', 'true', 'boolean', 'Enable automatic daily backups'),
('auto_backup_time', '00:00', 'string', 'Time for automatic backup (HH:MM)'),
('qr_code_size', '200', 'number', 'QR code size in pixels'),
('items_per_page', '25', 'number', 'Default pagination size'),
('warranty_alert_days', '30', 'number', 'Days before warranty expiry to alert'),
('amc_alert_days', '30', 'number', 'Days before AMC expiry to alert');

-- --------------------------------------------------------
-- Update default admin password to 'admin123' if needed
-- Password hash for 'admin123' using bcrypt
-- --------------------------------------------------------

UPDATE `users` SET `password` = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4mdYbXzZwXXXXXXX' 
WHERE `ams_id` = '1000' AND `password` = '$2y$10$YourHashedPasswordHere';

SELECT 'Migration V4 completed successfully!' as Status;
