<?php
define('AMS_LOADED', true);
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Seed Department
    $pdo->exec("INSERT IGNORE INTO departments (id, name, code) VALUES (1, 'Information Technology', 'IT')");
    
    // Seed User
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (ams_id, password, role, is_active, emp_name, email_id, department_id, designation) VALUES (?, ?, 'admin', 1, 'System Admin', 'admin@serc.res.in', 1, 'Administrator') ON DUPLICATE KEY UPDATE password = ?, is_active = 1");
    $stmt->execute(['1410145', $hash, $hash]);
    
    echo "User 1410145 seeded successfully with password admin123";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
