FLUSH PRIVILEGES;

-- 1. Create/Fix root user (optional but good for administrative access)
ALTER USER 'root'@'localhost' IDENTIFIED BY 'S#erc@123' ACCOUNT UNLOCK;

-- 2. Create specific user for the app
CREATE USER IF NOT EXISTS 'ams_user'@'localhost' IDENTIFIED BY 'S#erc@123';
ALTER USER 'ams_user'@'localhost' IDENTIFIED BY 'S#erc@123'; -- Ensure password is set

-- 3. Create Database
CREATE DATABASE IF NOT EXISTS asset_mgt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 4. Grant permissions
GRANT ALL PRIVILEGES ON asset_mgt.* TO 'ams_user'@'localhost';
GRANT ALL PRIVILEGES ON asset_mgt.* TO 'root'@'localhost';

FLUSH PRIVILEGES;
