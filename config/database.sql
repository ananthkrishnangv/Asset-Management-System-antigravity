-- CSIR-SERC Asset Management System
-- Complete Database Schema
-- Version 2.0

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `asset_mgt` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asset_mgt`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";
SET FOREIGN_KEY_CHECKS = 0;

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
-- Table: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ams_id` VARCHAR(20) NOT NULL UNIQUE,
    `emp_name` VARCHAR(200) NOT NULL,
    `email_id` VARCHAR(100) DEFAULT NULL,
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
    INDEX `idx_role` (`role`),
    INDEX `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: inventory_items
-- --------------------------------------------------------
DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE `inventory_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `serial_number` VARCHAR(50) DEFAULT NULL,
    `item_description` VARCHAR(500) NOT NULL,
    `category_id` INT DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `current_holder_id` INT DEFAULT NULL,
    `unit_price` DECIMAL(15,2) DEFAULT 0.00,
    `quantity` INT DEFAULT 1,
    `purchase_date` DATE DEFAULT NULL,
    `supplier_name` VARCHAR(200) DEFAULT NULL,
    `invoice_number` VARCHAR(100) DEFAULT NULL,
    `location` VARCHAR(200) DEFAULT NULL,
    `condition_status` ENUM('new', 'good', 'fair', 'poor', 'non_serviceable', 'scrapped') DEFAULT 'good',
    `inventory_type` ENUM('dir', 'pir') NOT NULL DEFAULT 'dir',
    `is_active` TINYINT(1) DEFAULT 1,
    `remarks` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_serial` (`serial_number`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_holder` (`current_holder_id`),
    INDEX `idx_type` (`inventory_type`)
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
    `status` ENUM('pending_supervisor', 'pending_hod', 'approved', 'rejected', 'completed') DEFAULT 'pending_supervisor',
    `remarks` TEXT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: stores_returns
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stores_returns`;
CREATE TABLE `stores_returns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `returned_by` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `condition_on_return` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('pending_approval', 'approved', 'rejected', 'completed') DEFAULT 'pending_approval',
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action_type` ENUM('create', 'update', 'delete', 'login', 'logout', 'transfer', 'return') NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT DEFAULT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: whatsapp_logs
-- --------------------------------------------------------
DROP TABLE IF EXISTS `whatsapp_logs`;
CREATE TABLE `whatsapp_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('sent', 'error', 'pending') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Default Data
-- --------------------------------------------------------

-- Departments
INSERT INTO `departments` (`id`, `name`, `code`, `building`, `floor`) VALUES
(1, 'Administration', 'ADMIN', 'Main Building', 'Ground Floor'),
(2, 'Finance & Accounts', 'F&A', 'Main Building', '1st Floor'),
(3, 'Stores & Purchase', 'S&P', 'Main Building', 'Ground Floor'),
(4, 'ICT', 'ICT', 'Main Building', '2nd Floor'),
(5, 'ASTaR', 'ASTAR', 'Lab Block', '1st Floor'),
(6, 'AML', 'AML', 'Lab Block', 'Ground Floor'),
(7, 'FFL', 'FFL', 'Lab Block', '2nd Floor'),
(8, 'SMSL', 'SMSL', 'Lab Block', '3rd Floor'),
(9, 'WEL', 'WEL', 'Lab Block', '4th Floor'),
(10, 'SHML', 'SHML', 'Lab Block', '5th Floor');

-- Categories
INSERT INTO `categories` (`id`, `name`, `code`, `description`) VALUES
(1, 'Computer & IT Equipment', 'IT', 'Computers, laptops, servers, networking'),
(2, 'Furniture', 'FUR', 'Office furniture, chairs, tables'),
(3, 'Laboratory Equipment', 'LAB', 'Testing and lab equipment'),
(4, 'Office Equipment', 'OFF', 'Printers, projectors, scanners'),
(5, 'Vehicles', 'VEH', 'Cars, two-wheelers'),
(6, 'Scientific Instruments', 'SCI', 'Research instruments'),
(7, 'Electrical Equipment', 'ELE', 'Electrical appliances');

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('organization_name', 'CSIR-SERC', 'string', 'Organization name'),
('organization_address', 'Chennai, India', 'string', 'Organization address'),
('whatsapp_enabled', 'false', 'boolean', 'Enable WhatsApp notifications'),
('whatsapp_access_token', '', 'string', 'WhatsApp API token'),
('whatsapp_phone_number_id', '', 'string', 'WhatsApp phone number ID'),
('whatsapp_notify_add', 'true', 'boolean', 'Notify on asset add'),
('whatsapp_notify_delete', 'true', 'boolean', 'Notify on asset delete'),
('whatsapp_notify_transfer', 'true', 'boolean', 'Notify on transfer');

-- Default admin user (password: admin123)
INSERT INTO `users` (`ams_id`, `emp_name`, `email_id`, `password`, `role`, `department_id`, `designation`) VALUES
('1410145', 'Shri. G.V. Ananthakrishnan', 'ananth.serc@csir.res.in', '$2y$12$LN1c5/sVqxE6G8UqN0A1eO3M.qR7tHmCbKZwXjVpNlCaTnPfWZIZK', 'admin', 4, 'Senior Technical Officer (1)');

-- Update ICT department HoD
UPDATE `departments` SET `hod_user_id` = 1 WHERE `id` = 4;
