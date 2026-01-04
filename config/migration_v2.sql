-- Migration V2: Add columns for file uploads and details
-- Run this script to update the database schema

-- Update DIR Details
ALTER TABLE `dir_details`
ADD COLUMN `scanned_copy` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `asset_image` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `amc_details` TEXT DEFAULT NULL,
ADD COLUMN `warranty_details` TEXT DEFAULT NULL,
ADD COLUMN `qr_code_data` VARCHAR(255) DEFAULT NULL;

-- Update PIR Details
ALTER TABLE `pir_details`
ADD COLUMN `scanned_copy` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `asset_image` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `amc_details` TEXT DEFAULT NULL,
ADD COLUMN `warranty_details` TEXT DEFAULT NULL,
ADD COLUMN `qr_code_data` VARCHAR(255) DEFAULT NULL;
