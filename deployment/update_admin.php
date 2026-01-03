<?php
require_once __DIR__ . '/bootstrap.php';

$currentAmsId = '1410146'; // The ID found in the database
$newAmsId = 'admin';
$newPassword = 'Dda5a3d52a#4815';

$hashedPassword = Security::hashPassword($newPassword);
$db = Database::getInstance();

// 1. Update AMS ID to 'admin' first
$updateId = $db->query(
    "UPDATE users SET ams_id = ? WHERE ams_id = ?",
    [$newAmsId, $currentAmsId]
);

// 2. Update password for 'admin'
$updatePass = $db->update(
    'users',
    ['password' => $hashedPassword],
    'ams_id = :ams_id',
    ['ams_id' => $newAmsId]
);

if ($updatePass) {
    echo "Successfully updated admin credentials.\n";
    echo "Username: $newAmsId\n";
    echo "Password: [HIDDEN]\n";
} else {
    echo "Failed to update credentials.\n";
}
