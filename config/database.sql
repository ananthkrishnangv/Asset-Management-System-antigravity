-- CSIR-SERC Asset Management System
-- Complete Database Schema
-- Version 2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- --------------------------------------------------------
-- Database: `asset_mgt`
-- --------------------------------------------------------

-- --------------------------------------------------------
-- Table: departments
-- --------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `building` VARCHAR(100) DEFAULT NULL,
    `floor` VARCHAR(50) DEFAULT NULL,
    `hod_user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dept_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users (Enhanced from emp_details)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ams_id` VARCHAR(20) NOT NULL UNIQUE,
    `emp_name` VARCHAR(200) NOT NULL,
    `email_id` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'supervisor', 'employee') NOT NULL DEFAULT 'employee',
    `department_id` INT DEFAULT NULL,
    `hod_id` INT DEFAULT NULL,
    `supervisor_id` INT DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `designation` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `password_reset_token` VARCHAR(100) DEFAULT NULL,
    `password_reset_expires` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ams_id` (`ams_id`),
    INDEX `idx_email` (`email_id`),
    INDEX `idx_role` (`role`),
    INDEX `idx_department` (`department_id`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`hod_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories (Asset categories)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: inventory_items (Main inventory)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE `inventory_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `serial_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Auto-generated: YYYY-MMDD-HHMMSS-XXXXX',
    `item_description` TEXT NOT NULL,
    `detailed_description` TEXT DEFAULT NULL,
    `category_id` INT DEFAULT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `quantity_unit` VARCHAR(50) DEFAULT 'Nos',
    `amount` DECIMAL(15,2) DEFAULT 0.00,
    `purchase_date` DATE DEFAULT NULL,
    `po_number` VARCHAR(200) DEFAULT NULL,
    `po_date` DATE DEFAULT NULL,
    `po_file_path` VARCHAR(500) DEFAULT NULL,
    `budget_head` VARCHAR(100) DEFAULT NULL,
    `stock_reference` VARCHAR(200) DEFAULT NULL,
    `issue_number` INT DEFAULT NULL,
    `issue_date` DATE DEFAULT NULL,
    `building_location` VARCHAR(255) DEFAULT NULL,
    `floor_location` VARCHAR(255) DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `room_location` VARCHAR(255) DEFAULT NULL,
    `current_holder_id` INT DEFAULT NULL,
    `nodal_officer_id` INT DEFAULT NULL,
    `condition_status` ENUM('new', 'good', 'fair', 'poor', 'non_serviceable', 'scrapped') DEFAULT 'good',
    `inventory_type` ENUM('dir', 'pir') NOT NULL DEFAULT 'dir',
    `qr_code` VARCHAR(255) DEFAULT NULL,
    `image_path` VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded item image',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_serial` (`serial_number`),
    INDEX `idx_inventory_type` (`inventory_type`),
    INDEX `idx_department` (`department_id`),
    INDEX `idx_holder` (`current_holder_id`),
    INDEX `idx_condition` (`condition_status`),
    INDEX `idx_purchase_date` (`purchase_date`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`current_holder_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`nodal_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: transfer_requests
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transfer_requests`;
CREATE TABLE `transfer_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `from_user_id` INT NOT NULL,
    `to_user_id` INT NOT NULL,
    `from_department_id` INT DEFAULT NULL,
    `to_department_id` INT DEFAULT NULL,
    `transfer_reason` TEXT DEFAULT NULL,
    `status` ENUM('pending_supervisor', 'pending_hod', 'approved', 'rejected', 'completed') DEFAULT 'pending_supervisor',
    `supervisor_id` INT DEFAULT NULL,
    `supervisor_action` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `supervisor_comments` TEXT DEFAULT NULL,
    `supervisor_action_date` DATETIME DEFAULT NULL,
    `hod_id` INT DEFAULT NULL,
    `hod_action` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `hod_comments` TEXT DEFAULT NULL,
    `hod_action_date` DATETIME DEFAULT NULL,
    `transfer_slip_number` VARCHAR(100) DEFAULT NULL,
    `requested_by` INT NOT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_item` (`item_id`),
    INDEX `idx_from_user` (`from_user_id`),
    INDEX `idx_to_user` (`to_user_id`),
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`hod_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: transfer_history
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transfer_history`;
CREATE TABLE `transfer_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `transfer_request_id` INT DEFAULT NULL,
    `from_user_id` INT DEFAULT NULL,
    `to_user_id` INT DEFAULT NULL,
    `from_department_id` INT DEFAULT NULL,
    `to_department_id` INT DEFAULT NULL,
    `from_user_name` VARCHAR(200) DEFAULT NULL,
    `to_user_name` VARCHAR(200) DEFAULT NULL,
    `from_department_name` VARCHAR(100) DEFAULT NULL,
    `to_department_name` VARCHAR(100) DEFAULT NULL,
    `transfer_type` ENUM('internal', 'inter_department', 'stores_return', 'new_issue') DEFAULT 'internal',
    `transfer_slip_number` VARCHAR(100) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `transferred_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_item_history` (`item_id`),
    INDEX `idx_transfer_date` (`transferred_at`),
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`transfer_request_id`) REFERENCES `transfer_requests`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: stores_returns
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stores_returns`;
CREATE TABLE `stores_returns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `returned_by` INT NOT NULL,
    `return_type` ENUM('repair', 'non_serviceable', 'scrapping', 'obsolete', 'other') NOT NULL,
    `return_reason` TEXT DEFAULT NULL,
    `condition_at_return` TEXT DEFAULT NULL,
    `status` ENUM('pending_approval', 'approved', 'rejected', 'received', 'processed') DEFAULT 'pending_approval',
    `approved_by` INT DEFAULT NULL,
    `approval_date` DATETIME DEFAULT NULL,
    `approval_comments` TEXT DEFAULT NULL,
    `received_by` INT DEFAULT NULL,
    `received_date` DATETIME DEFAULT NULL,
    `final_action` ENUM('repaired', 'scrapped', 'disposed', 'returned_to_stock', 'pending') DEFAULT 'pending',
    `final_action_date` DATETIME DEFAULT NULL,
    `final_action_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_return_status` (`status`),
    INDEX `idx_return_type` (`return_type`),
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`returned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: purchase_orders
-- --------------------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(200) NOT NULL UNIQUE,
    `po_date` DATE NOT NULL,
    `vendor_name` VARCHAR(255) DEFAULT NULL,
    `total_amount` DECIMAL(15,2) DEFAULT 0.00,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_type` VARCHAR(10) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_po_number` (`po_number`),
    INDEX `idx_po_date` (`po_date`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `user_name` VARCHAR(200) DEFAULT NULL,
    `action_type` ENUM('login', 'logout', 'create', 'update', 'delete', 'transfer', 'approve', 'reject', 'export', 'backup', 'other') NOT NULL,
    `module` VARCHAR(50) DEFAULT NULL,
    `record_id` INT DEFAULT NULL,
    `record_type` VARCHAR(50) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_activity` (`user_id`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `email_sent` TINYINT(1) DEFAULT 0,
    `email_sent_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_notifications` (`user_id`, `is_read`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: backups
-- --------------------------------------------------------
DROP TABLE IF EXISTS `backups`;
CREATE TABLE `backups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `backup_type` ENUM('database', 'files', 'full') NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT DEFAULT 0,
    `storage_location` ENUM('local', 'google_drive', 's3', 'onedrive', 'ftp') DEFAULT 'local',
    `cloud_file_id` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_backup_status` (`status`),
    INDEX `idx_backup_date` (`created_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default data
-- --------------------------------------------------------

-- Default departments
INSERT INTO `departments` (`name`, `code`, `building`, `floor`) VALUES
('PURCHASE', 'PUR', 'Main Building', '2nd Floor'),
('ASTAR', 'ASTAR', 'Main Building', '1st Floor'),
('STORES', 'STORES', 'Main Building', 'Ground Floor'),
('ADMINISTRATION', 'ADMIN', 'Main Building', '2nd Floor'),
('IT DIVISION', 'IT', 'Main Building', '3rd Floor');

-- Default categories
INSERT INTO `categories` (`name`, `code`, `description`) VALUES
('Furniture', 'FUR', 'Tables, Chairs, Cupboards, etc.'),
('Computer Equipment', 'COMP', 'Computers, Laptops, Monitors, etc.'),
('Lab Equipment', 'LAB', 'Scientific and Laboratory Equipment'),
('Office Equipment', 'OFF', 'Printers, Scanners, etc.'),
('Electrical Equipment', 'ELEC', 'Electrical items and appliances'),
('Miscellaneous', 'MISC', 'Other items');

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('app_name', 'CSIR-SERC Asset Management System', 'string', 'Application name'),
('app_version', '2.0.0', 'string', 'Application version'),
('smtp_host', '', 'string', 'SMTP server hostname'),
('smtp_port', '587', 'number', 'SMTP server port'),
('smtp_username', '', 'string', 'SMTP username'),
('smtp_password', '', 'string', 'SMTP password (encrypted)'),
('smtp_encryption', 'tls', 'string', 'SMTP encryption type'),
('smtp_from_email', 'noreply@csir.res.in', 'string', 'From email address'),
('smtp_from_name', 'CSIR-SERC AMS', 'string', 'From name'),
('backup_enabled', 'true', 'boolean', 'Enable automatic backups'),
('backup_frequency', 'daily', 'string', 'Backup frequency'),
('backup_retention_days', '30', 'number', 'Days to keep backups'),
('organization_name', 'CSIR-SERC', 'string', 'Organization name'),
('organization_address', 'Chennai, India', 'string', 'Organization address');

-- Default admin user (password: Admin@123)
INSERT INTO `users` (`ams_id`, `emp_name`, `email_id`, `password`, `role`, `department_id`) VALUES
('1410146', 'SURYA E', 'surya.serc@csir.res.in', '$2y$12$eoY3.R6C4Vt1BifT/W0sO.P1tCxIyKBsW1SKpZ2h9GTHPkHAF7xBa', 'admin', 4);

-- Update department HoD
UPDATE `departments` SET `hod_user_id` = 1 WHERE `id` = 4;
