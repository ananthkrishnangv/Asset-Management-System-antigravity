-- Unified Database Initialization Schema
-- Combined from database.sql, users_import.sql, and migration_v4.sql
-- Default Password for all users: 'Welcom@123'
-- Hash: $2y$10$WKLF1dYc/6K7MQCFgS7W8eorJvDMuEMKWq8HBRD6/MmYBOInuXowK

-- =================================================================
-- PART 1: BASE SCHEMA (from config/database.sql)
-- =================================================================

CREATE DATABASE IF NOT EXISTS `asset_mgt` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asset_mgt`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";
SET FOREIGN_KEY_CHECKS = 0;

-- Drop obsolete table if it exists (from incorrect previous deployments)
DROP TABLE IF EXISTS `emp_details`;

-- Table: departments
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

-- Table: users
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

-- Table: categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: inventory_items
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

-- Table: transfer_requests
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

-- Table: stores_returns
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

-- Table: activity_logs
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

-- Table: settings
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

-- Table: whatsapp_logs
DROP TABLE IF EXISTS `whatsapp_logs`;
CREATE TABLE `whatsapp_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('sent', 'error', 'pending') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Default Data: Departments
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

-- Default Data: Categories
INSERT INTO `categories` (`id`, `name`, `code`, `description`) VALUES
(1, 'Computer & IT Equipment', 'IT', 'Computers, laptops, servers, networking'),
(2, 'Furniture', 'FUR', 'Office furniture, chairs, tables'),
(3, 'Laboratory Equipment', 'LAB', 'Testing and lab equipment'),
(4, 'Office Equipment', 'OFF', 'Printers, projectors, scanners'),
(5, 'Vehicles', 'VEH', 'Cars, two-wheelers'),
(6, 'Scientific Instruments', 'SCI', 'Research instruments'),
(7, 'Electrical Equipment', 'ELE', 'Electrical appliances');

-- Default Data: Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('organization_name', 'CSIR-SERC', 'string', 'Organization name'),
('organization_address', 'Chennai, India', 'string', 'Organization address'),
('whatsapp_enabled', 'false', 'boolean', 'Enable WhatsApp notifications'),
('whatsapp_access_token', '', 'string', 'WhatsApp API token'),
('whatsapp_phone_number_id', '', 'string', 'WhatsApp phone number ID'),
('whatsapp_notify_add', 'true', 'boolean', 'Notify on asset add'),
('whatsapp_notify_delete', 'true', 'boolean', 'Notify on asset delete'),
('whatsapp_notify_transfer', 'true', 'boolean', 'Notify on transfer');

-- =================================================================
-- PART 2: USER IMPORT (from config/users_import.sql)
-- =================================================================

-- Set verified default password hash for 'Welcom@123'
SET @default_password = '$2y$10$WKLF1dYc/6K7MQCFgS7W8eorJvDMuEMKWq8HBRD6/MmYBOInuXowK';

INSERT INTO `users` (`ams_id`, `emp_name`, `email_id`, `password`, `role`, `department_id`, `designation`) VALUES
('1410001', 'Dr. N. Anandavalli', 'anandavalli@csir.res.in', @default_password, 'admin', NULL, 'Director'),
('1410040', 'Dr. K. Sathish Kumar', 'sathishkumar@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410042', 'Dr. Pabbisetty Harikrishna', 'harikrishna@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410043', 'Dr. S. Parivallal', 'parivallal@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410044', 'Dr. J. Prabakar', 'prabakar@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410049', 'Dr. S. Bhaskar', 'bhaskar@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410053', 'Dr. Saptarshi Sasmal', 'sasmal@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410051', 'Dr. M.B. Anoop', 'anoop@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410052', 'Dr. A. Ramachandra Murthy', 'murthy@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410050', 'Dr. Voggu Srinivas', 'srinivas@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410056', 'Dr. P. Kamatchi', 'kamatchi@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410055', 'Smt. R. Sreekala', 'sreekala@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410054', 'Shri. Gajjala Remesh Babu', 'remeshbabu@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410057', 'Dr. S. Maheswaran', 'maheswaran@csir.res.in', @default_password, 'supervisor', NULL, 'Chief Scientist'),
('1410058', 'Dr. Rokade Rajendra Pitambar', 'rajendra@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410062', 'Dr. Amar Prakash', 'amarprakash@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410066', 'Dr.(Ms.) Smitha Gopinath', 'smitha@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410061', 'Dr. A. Abraham', 'abraham@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410070', 'Dr. S. Vishnuvardhan', 'vishnuvardhan@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410071', 'Dr. K. Lakshmi', 'lakshmi@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410069', 'Dr. V. Marimuthu', 'marimuthu@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410064', 'Shri. V. Srinivasan', 'vsrinivasan@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410072', 'Dr. R. Balagopal', 'balagopal@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410076', 'Dr. B. Arun Sundaram', 'arunsundaram@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410074', 'Dr. M. Saravanan', 'msaravanan@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410068', 'Dr. P.S. Ambily', 'ambily@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410065', 'Dr. R. Manisekar', 'manisekar@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410080', 'Dr. C. Bharathi Priya', 'bharathipriya@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410081', 'Dr. B.S. Sindu', 'sindu@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410082', 'Dr. Prabhat Ranjan Prem', 'prabhatranjan@csir.res.in', @default_password, 'employee', NULL, 'Senior Principal Scientist'),
('1410067', 'Dr. K. Sivasubramanian', 'sivasubramanian@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410073', 'Dr. P. Prabha', 'prabha@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410075', 'Dr. Srinivasa Babu Ramisetti', 'srinivasababu@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410077', 'Shri. A.K. Farvaze Ahmed', 'farvaze@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410078', 'Dr. A. Kanchana Devi', 'kanchanadevi@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410079', 'Dr. Venkata Rama Rao Guntuka', 'venkataramarao@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410083', 'Dr. A. Cinitha', 'cinitha@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410084', 'Dr. T. Hemalatha', 'hemalatha@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410085', 'Dr. S. Sundar Kumar', 'sundarkumar@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410088', 'Dr. Mohit Verma', 'mohitverma@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410093', 'Dr. M. Keerthana', 'keerthana@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410086', 'Shri. G. Ramesh', 'gramesh@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410087', 'Shri. V. Ramesh Kumar', 'rameshkumar@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410090', 'Dr. M. Saravanan', 'msaravanan2@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410091', 'Shri. Vimal Mohan', 'vimalmohan@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410092', 'Dr. K.N. Lakshmikandhan', 'lakshmikandhan@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410095', 'Dr. M. Surendran', 'surendran@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410089', 'Dr. S.R. Balasubramanian', 'balasubramanian@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410094', 'Dr. Bhashya Vankudothu', 'bhashya@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410097', 'Shri. G.S. Vijaya Bhaskara', 'vijayabhaskara@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410098', 'Dr. J. Prawin', 'prawin@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410099', 'Dr. A. Thirumalaiselvi', 'thirumalaiselvi@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410101', 'Shri. M. Kannusamy', 'kannusamy@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410096', 'Shri. K. Saravana Kumar', 'saravanakumar@csir.res.in', @default_password, 'employee', NULL, 'Principal Scientist'),
('1410100', 'Shri. Abhishek Kumar', 'abhishekkumar@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410102', 'Shri. E. Ashok Kumar', 'ashokkumar@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410107', 'Shri. Jonnalagadda Chintaiah Sunil', 'sunil@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410109', 'Dr. K. Senthil Kumar', 'senthilkumar@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410110', 'Dr. J. Venkatesan', 'venkatesan@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410108', 'Dr. A. Subbulakshmi', 'subbulakshmi@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410104', 'Dr. Nartu Manoj Kumar', 'manojkumar@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410105', 'Smt. N. Ramya', 'ramya@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410106', 'Shri. Nitin Khandelwal', 'nitinkhandelwal@csir.res.in', @default_password, 'employee', NULL, 'Senior Scientist'),
('1410112', 'Shri. Deepak Kumar', 'deepakkumar@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410113', 'Ms. Renuka Darshyamkar', 'renuka@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410114', 'Shri. Allamraju Manikantha Sarath', 'manikantha@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410115', 'Shri. S. Vinoth Kirshnan', 'vinothkirshnan@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410116', 'Ms. G. Nasima', 'nasima@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410117', 'Shri. M. Aravindan', 'aravindan@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410118', 'Dr. M.J. Mahesh', 'mahesh@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410119', 'Shri. Kanishka Bhattacharya', 'kanishka@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410120', 'Shri. Thondamon V', 'thondamon@csir.res.in', @default_password, 'employee', NULL, 'Scientist'),
('1410121', 'Shri. R.D. Sathish Kumar', 'rdsathishkumar@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410123', 'Shri. G. Muthuramalingam', 'muthuramalingam@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410125', 'Shri. G. Jayaraman', 'jayaraman@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410126', 'Shri. M. Kumarappan', 'kumarappan@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410129', 'Smt. Chitra Sankaran', 'chitra@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410150', 'Shri. S. Srinivasan', 'ssrinivasan@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410149', 'Dr. S. Vijayalakshmi', 'vijayalakshmi@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410131', 'Shri. S. Kanniah Sah', 'kanniahsah@csir.res.in', @default_password, 'employee', NULL, 'Principal Technical Officer'),
('1410134', 'Shri. J. Prakashvel', 'prakashvel@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (3)'),
('1410136', 'Shri. S. Harishkumaran', 'harishkumaran@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (3)'),
('1410137', 'Shri. S. Muraleeswaran', 'muraleeswaran@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (3)'),
('1410138', 'Smt. E. Kanmani', 'kanmani@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (2)'),
('1410139', 'Ms. R. Lakshmi Poorna', 'lakshmipoorna@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (2)'),
('1410140', 'Shri. P. Vasudevan', 'vasudevan@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (2)'),
('1410187', 'Shri. R.M. Manikandan', 'rmmanikandan@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (2)'),
('1410141', 'Smt. R. Soniya', 'soniya@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (1)'),
('1410142', 'Shri. M. Vinothkumar', 'mvinothkumar@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (1)'),
('1410143', 'Shri. P. Subbash', 'subbash@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (1)'),
('1410145', 'Shri. G.V. Ananthakrishnan', 'ananth.serc@csir.res.in', @default_password, 'admin', NULL, 'Senior Technical Officer (1)'),
('1410148', 'Shri. G. Lakshmikanth', 'lakshmikanth@csir.res.in', @default_password, 'employee', NULL, 'Senior Technical Officer (1)'),
('1410144', 'Shri. K. Kumaran', 'kumaran@csir.res.in', @default_password, 'employee', NULL, 'Assistant Executive Engineer (Elect.)'),
('1410146', 'Smt. E. Surya', 'surya.serc@csir.res.in', @default_password, 'employee', NULL, 'Technical Officer'),
('1410147', 'Shri. S. Viswanatha Manikandandan', 'viswanatha@csir.res.in', @default_password, 'employee', NULL, 'Technical Officer'),
('1410151', 'Shri. Sadhiq Shaik', 'sadhiq@csir.res.in', @default_password, 'employee', NULL, 'Technical Assistant'),
('1410152', 'Shri. M. Elamaran', 'elamaran@csir.res.in', @default_password, 'employee', NULL, 'Technical Assistant'),
('1410154', 'Shri. D. Deivaraj', 'deivaraj@csir.res.in', @default_password, 'employee', NULL, 'Senior Technician (3)'),
('1410162', 'Shri. G. Ponnan', 'ponnan@csir.res.in', @default_password, 'employee', NULL, 'Senior Technician (3)'),
('1410157', 'Shri. S. Srinivasan', 'ssrinivasan2@csir.res.in', @default_password, 'employee', NULL, 'Senior Technician (3)'),
('1410159', 'Smt. J. Rajalakshmi', 'rajalakshmi@csir.res.in', @default_password, 'employee', NULL, 'Senior Technician (2)'),
('1410160', 'Shri. N. Baskaran', 'baskaran@csir.res.in', @default_password, 'employee', NULL, 'Senior Technician (2)'),
('1410161', 'Shri. V. Krishnan', 'vkrishnan@csir.res.in', @default_password, 'employee', NULL, 'Senior Technician (2)'),
('1410163', 'Shri. R. Rajesh', 'rrajesh@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410164', 'Shri. G. Poovendan', 'poovendan@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410165', 'Shri. S. Bala Murugan', 'balamurugan@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410166', 'Shri. K. Srinivasan', 'ksrinivasan@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410167', 'Shri. T. Sathish Kumar', 'tsathishkumar@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410168', 'Smt. S. Vimala', 'vimala@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410169', 'Shri. A. Karunakaran', 'karunakaran@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410170', 'Shri. S. Muthuraj', 'muthuraj@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410171', 'Smt. K. Savitha', 'savitha@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410172', 'Shri. M. Karunamoorthi', 'karunamoorthi@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410173', 'Shri. N. Syed Ibrahim', 'syedibrahim@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410174', 'Shri. V. Mahendran', 'mahendran@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410175', 'Shri. S. Balakrishnan', 'sbalakrishnan@csir.res.in', @default_password, 'employee', NULL, 'Technician (2)'),
('1410177', 'Shri. S. Eswaran', 'eswaran@csir.res.in', @default_password, 'employee', NULL, 'Lab Assistant'),
('1410002', 'Shri. Lokanath Patnayak', 'lokanath@csir.res.in', @default_password, 'employee', NULL, 'Administrative Officer'),
('1410004', 'Ms. Sudha Nair', 'sudhanair@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (G)'),
('1410005', 'Dr. N. Sudhakar', 'sudhakar@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (G)'),
('1410008', 'Smt. S.P. Kalaivani', 'kalaivani@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (G)'),
('1410202', 'Smt. N. Geetha', 'geetha@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (G)'),
('1410009', 'Smt. M. Vanijayaleela', 'vanijayaleela@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (Gen)'),
('1410232', 'Smt. N. Hemamalini', 'hemamalini@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (Gen)'),
('1410012', 'Smt. M.A. Kamsar Chinnappan', 'kamsar@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (Gen)'),
('1410204', 'Ms. Megha', 'megha@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (Gen)'),
('1410205', 'Ms. Monu', 'monu@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (Gen)'),
('1410207', 'Shri. Jeyaram R', 'jeyaram@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (Gen)'),
('1410212', 'Ms. Paviya P', 'paviya@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410215', 'Smt. Gayathri Devi K', 'gayathridevi@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410219', 'Smt. Rajeshwari S', 'rajeshwari@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410220', 'Shri. Mohansurya S', 'mohansurya@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410234', 'Smt. V.D. Jhansirani', 'jhansirani@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410221', 'Ms. R. Sreeranjini', 'sreeranjini@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410227', 'Shri. Ganta Praveen', 'praveen@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410222', 'Shri. Pon Ramalinga Vel S', 'ramalingavel@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410229', 'Shri. Mukesh RA', 'mukesh@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410231', 'Shri. A.V. Rakesh', 'rakesh@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (Gen)'),
('1410233', 'Shri. T. Karthikai Kannan', 'karthikaikannan@csir.res.in', @default_password, 'employee', NULL, 'Controller of Finance & Accounts'),
('1410208', 'Ms. Sonu', 'sonu@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (F&A)'),
('1410209', 'Smt. Poonam Chahal', 'poonam@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (F&A)'),
('1410014', 'Smt. K. Uma Maheswari', 'umamaheswari@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (F&A)'),
('1410015', 'Smt. M. Thulasi', 'thulasi@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (F&A)'),
('1410206', 'Shri. Vishnu Prasath S', 'vishnuprasath@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (F&A)'),
('1410216', 'Smt. Manthra S', 'manthra@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (F&A)'),
('1410230', 'Ms. Sharon Nivedha J R', 'sharonnivedha@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (F&A)'),
('1410210', 'Shri. E. Mahesh Kumar', 'emaheshkumar@csir.res.in', @default_password, 'supervisor', NULL, 'Controller of Stores & Purchase'),
('1410186', 'Shri. N. Suresh', 'nsuresh@csir.res.in', @default_password, 'employee', NULL, 'Section Officer (S&P)'),
('1410019', 'Shri. M. Palanisamy', 'palanisamy@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (S&P)'),
('1410020', 'Shri. S. Kannappan', 'kannappan@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (S&P)'),
('1410022', 'Shri. C. Rajaji', 'rajaji@csir.res.in', @default_password, 'employee', NULL, 'Assistant Section Officer (S&P)'),
('1410023', 'Shri. Sai Sudheer Amba', 'saisudheer@csir.res.in', @default_password, 'employee', NULL, 'Senior Secretariat Assistant (S&P)'),
('1410213', 'Shri. M. Barani Dharan', 'baranidharan@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (S&P)'),
('1410214', 'Shri. Karthigeyan K', 'karthigeyan@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (S&P)'),
('1410223', 'Shri. Peddipaga Teja', 'teja@csir.res.in', @default_password, 'employee', NULL, 'Junior Secretariat Assistant (S&P)'),
('1410024', 'Shri. B. Ravikumar', 'ravikumar@csir.res.in', @default_password, 'employee', NULL, 'Principal Private Secretary'),
('1410025', 'Smt. K. Venkateswari', 'venkateswari@csir.res.in', @default_password, 'employee', NULL, 'Private Secretary'),
('1410026', 'Smt. S. Jagadhaprabha', 'jagadhaprabha@csir.res.in', @default_password, 'employee', NULL, 'Private Secretary'),
('1410028', 'Smt. M. Vijayalakshmi', 'mvijayalakshmi@csir.res.in', @default_password, 'employee', NULL, 'Private Secretary'),
('1410030', 'Smt. S. Shanthi', 'shanthi@csir.res.in', @default_password, 'employee', NULL, 'Senior Stenographer'),
('1410031', 'Shri. S.M. Yuvaraj', 'yuvaraj@csir.res.in', @default_password, 'employee', NULL, 'Senior Stenographer'),
('1410224', 'Ms. Akshaya V', 'akshaya@csir.res.in', @default_password, 'employee', NULL, 'Junior Stenographer'),
('1410228', 'Ms. Swetha G M', 'swetha@csir.res.in', @default_password, 'employee', NULL, 'Junior Stenographer'),
('1410203', 'Dr. Asha G. Nair', 'ashanair@csir.res.in', @default_password, 'employee', NULL, 'Hindi Officer'),
('1410034', 'Shri. K. Jagannathan', 'jagannathan@csir.res.in', @default_password, 'employee', NULL, 'Driver II (4)'),
('1410037', 'Shri. D. John', 'john@csir.res.in', @default_password, 'employee', NULL, 'Driver II (3)'),
('1410225', 'Shri. Bhukya Ramki Nayak', 'ramkinayak@csir.res.in', @default_password, 'employee', NULL, 'Driver'),
('1410226', 'Shri. Saravanan K', 'ksaravanan@csir.res.in', @default_password, 'employee', NULL, 'Driver'),
('1410178', 'Smt. B. Reeta Sangeetha', 'reetasangeetha@csir.res.in', @default_password, 'employee', NULL, 'MTS')
ON DUPLICATE KEY UPDATE emp_name = VALUES(emp_name), designation = VALUES(designation), password = VALUES(password);

-- =================================================================
-- PART 3: SCHEMA UPDATES (from config/migration_v4_comprehensive.sql)
-- =================================================================

-- Add extra columns to users
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `mobile` VARCHAR(20) NULL AFTER `phone`,
ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `mobile`,
ADD COLUMN IF NOT EXISTS `profile_pic` VARCHAR(255) NULL AFTER `address`;

-- Add extra columns to inventory_items
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

-- Create backup_logs table
CREATE TABLE IF NOT EXISTS `backup_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `file_size` BIGINT DEFAULT 0,
    `backup_type` ENUM('auto', 'manual') DEFAULT 'manual',
    `status` ENUM('success', 'failed') DEFAULT 'success',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create email_logs table
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT,
    `status` ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add updated settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('smtp_enabled', 'false', 'boolean', 'Enable SMTP email notifications'),
('auto_backup_enabled', 'true', 'boolean', 'Enable automatic daily backups'),
('auto_backup_time', '00:00', 'string', 'Time for automatic backup (HH:MM)'),
('qr_code_size', '200', 'number', 'QR code size in pixels'),
('items_per_page', '25', 'number', 'Default pagination size'),
('warranty_alert_days', '30', 'number', 'Days before warranty expiry to alert'),
('amc_alert_days', '30', 'number', 'Days before AMC expiry to alert');

SELECT 'Database Initialization Complete' as Status;
