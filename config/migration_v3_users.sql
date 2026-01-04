-- Migration V3: User Management Updates

ALTER TABLE `emp_details` 
ADD COLUMN `mobile` VARCHAR(20) NULL AFTER `email_id`,
ADD COLUMN `address` TEXT NULL AFTER `mobile`,
ADD COLUMN `department` VARCHAR(100) NULL AFTER `address`,
ADD COLUMN `profile_pic` VARCHAR(255) NULL AFTER `department`,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Create default admin if not exists (This is handled by users_import generally, but good for safety)
-- INSERT IGNORE INTO `emp_details` (`AMS_id`, `emp_name`, `email_id`, `password`, `user_priv`) VALUES ('1000', 'Administrator', 'admin@serc.res.in', '$2y$10$YourHashedPasswordHere', 'admin');
