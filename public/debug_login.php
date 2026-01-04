<?php
define('AMS_LOADED', true);
require_once __DIR__ . '/../config/config.php';

echo "<h1>Debug Login</h1>";
echo "APP_URL: " . APP_URL . "<br>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_USER: " . DB_USER . "<br>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database Connected Successfully.<br>";
} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

$amsId = '1410145';
$password = 'admin123';

$stmt = $pdo->prepare("SELECT * FROM users WHERE ams_id = ?");
$stmt->execute([$amsId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User Found: " . htmlspecialchars($user['ams_id']) . "<br>";
    echo "Role: " . htmlspecialchars($user['role']) . "<br>";
    echo "Active: " . htmlspecialchars($user['is_active']) . "<br>";
    echo "Stored Hash: " . htmlspecialchars($user['password']) . "<br>";
    
    if (password_verify($password, $user['password'])) {
        echo "<h2 style='color:green'>Password Verification SUCCESS</h2>";
    } else {
        echo "<h2 style='color:red'>Password Verification FAILED</h2>";
        // Attempt to generate new hash
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "Valid Hash for 'admin123' should look like: " . $newHash . "<br>";
        
        // Update functionality
        if (isset($_GET['fix']) && $_GET['fix'] == '1') {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$newHash, $user['id']]);
            echo "<b>Password Updated to 'admin123'</b>";
        } else {
             echo "<a href='?fix=1'>Click here to reset password to admin123</a>";
        }
    }
} else {
    echo "<h2 style='color:red'>User '1410145' NOT FOUND</h2>";
    // Check if table empty
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total users in table: $count<br>";
    
    // Check if we should insert
    echo "<br>To insert default admin, run seed script.";
}
?>
