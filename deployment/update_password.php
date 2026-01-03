<?php
require_once __DIR__ . '/bootstrap.php';

$amsId = 'admin';
$newPassword = 'Dda5a3d52a#4815';

$hashedPassword = Security::hashPassword($newPassword);
$db = Database::getInstance();

// Update password
$update = $db->update(
    'users',
    ['password' => $hashedPassword],
    'ams_id = :ams_id',
    ['ams_id' => $amsId]
);

if ($update) {
    echo "Password updated successfully for user: $amsId\n";
} else {
    echo "Failed to update password for user: $amsId\n";
}
