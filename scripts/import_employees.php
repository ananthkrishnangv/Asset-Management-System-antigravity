<?php
/**
 * Employee Import Script
 * Imports employees from CSV, removes old users except specified ones
 */

require_once __DIR__ . '/../bootstrap.php';

if (php_sapi_name() !== 'cli' && (!Auth::check() || !Auth::isAdmin())) {
    die('Access denied. Admin only.');
}

$db = Database::getInstance();

// Default password for new users
$defaultPassword = password_hash('serc@12345', PASSWORD_BCRYPT);

// Users to keep (by AMS ID)
$usersToKeep = ['1410145', '1410210']; // GV Ananthakrishnan, E Mahesh Kumar

// Find Sathish Kumar K  
$sathishKumar = $db->fetchValue("SELECT ams_id FROM users WHERE emp_name LIKE '%Sathish%Kumar%' AND ams_id IS NOT NULL LIMIT 1");
if ($sathishKumar) {
    $usersToKeep[] = $sathishKumar;
    echo "Found Sathish Kumar: $sathishKumar\n";
}

echo "Users to keep: " . implode(', ', $usersToKeep) . "\n\n";

// Delete users except those to keep
$placeholders = implode(',', array_fill(0, count($usersToKeep), '?'));
$deleted = $db->query("DELETE FROM users WHERE ams_id NOT IN ($placeholders)", $usersToKeep);
echo "Deleted old users (except specified).\n";

// Read CSV file
$csvFile = '/tmp/Employees_Email_ID_CSIR_res_in_02_12_25.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile\n");
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Cannot open CSV file.\n");
}

// Skip header
fgetcsv($handle);

$imported = 0;
$skipped = 0;

while (($data = fgetcsv($handle)) !== false) {
    if (count($data) < 5)
        continue;

    $slNo = trim($data[0]);
    $firstName = trim($data[1]);
    $lastName = trim($data[2]);
    $designation = trim($data[3]);
    $emailPrefix = trim($data[4]);

    // Full name
    $empName = $firstName . ' ' . $lastName;
    $empName = preg_replace('/\s+/', ' ', $empName);

    // Email with domain
    $email = $emailPrefix . '@csir.res.in';

    // Check if email already exists
    $existing = $db->fetchValue("SELECT id FROM users WHERE email_id = ?", [$email]);
    if ($existing) {
        echo "Skipped (exists): $empName - $email\n";
        $skipped++;
        continue;
    }

    // Check if name already exists (from kept users)
    $nameExists = $db->fetchValue("SELECT id FROM users WHERE LOWER(emp_name) LIKE LOWER(?)", ["%$firstName%$lastName%"]);
    if ($nameExists) {
        echo "Skipped (name match): $empName - $email\n";
        $skipped++;
        continue;
    }

    // Insert new user without AMS ID
    try {
        $db->query(
            "INSERT INTO users (ams_id, emp_name, email_id, password, role, designation, is_active, created_at) 
             VALUES (NULL, ?, ?, ?, 'employee', ?, 1, NOW())",
            [$empName, $email, $defaultPassword, $designation]
        );
        echo "Imported: $empName - $email ($designation)\n";
        $imported++;
    } catch (Exception $e) {
        echo "Error importing $empName: " . $e->getMessage() . "\n";
        $skipped++;
    }
}

fclose($handle);

echo "\n=================================\n";
echo "Import Complete!\n";
echo "Imported: $imported\n";
echo "Skipped: $skipped\n";
echo "=================================\n";

// Show current user count
$userCount = $db->fetchValue("SELECT COUNT(*) FROM users");
echo "Total users in database: $userCount\n";
