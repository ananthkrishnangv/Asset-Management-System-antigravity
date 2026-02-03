-- Fix transfer_requests table
ALTER TABLE `transfer_requests`
ADD COLUMN IF NOT EXISTS `transfer_reason` TEXT DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `hod_id` INT DEFAULT NULL AFTER `to_department_id`,
ADD COLUMN IF NOT EXISTS `supervisor_id` INT DEFAULT NULL AFTER `hod_id`,
ADD COLUMN IF NOT EXISTS `requested_by` INT DEFAULT NULL AFTER `supervisor_id`,
ADD COLUMN IF NOT EXISTS `transfer_slip_number` VARCHAR(50) DEFAULT NULL AFTER `requested_by`,
ADD COLUMN IF NOT EXISTS `hod_action` ENUM('approved', 'rejected', 'pending') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS `hod_comments` TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `hod_action_date` DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `supervisor_action` ENUM('approved', 'rejected', 'pending') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS `supervisor_comments` TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `supervisor_action_date` DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `completed_at` DATETIME DEFAULT NULL;

-- Create transfer_history table
CREATE TABLE IF NOT EXISTS `transfer_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `transfer_request_id` INT DEFAULT NULL,
    `from_user_id` INT DEFAULT NULL,
    `to_user_id` INT DEFAULT NULL,
    `from_department_id` INT DEFAULT NULL,
    `to_department_id` INT DEFAULT NULL,
    `from_user_name` VARCHAR(255) DEFAULT NULL,
    `to_user_name` VARCHAR(255) DEFAULT NULL,
    `from_department_name` VARCHAR(255) DEFAULT NULL,
    `to_department_name` VARCHAR(255) DEFAULT NULL,
    `transfer_type` VARCHAR(50) DEFAULT 'internal',
    `transfer_slip_number` VARCHAR(50) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
