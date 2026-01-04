<?php
/**
 * Server Requirements Checker
 * Run this to verify if the server has all necessary extensions.
 */
echo "<h1>Server Diagnostics</h1>";

// Check PHP Version
echo "PHP Version: " . phpversion() . "<br>";

// Check Extensions
$extensions = ['pdo_mysql', 'curl', 'mbstring', 'openssl', 'gd'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span style='color:green'>[OK] Extension $ext is loaded.</span><br>";
    } else {
        echo "<span style='color:red'>[FAIL] Extension $ext is MISSING!</span><br>";
    }
}

// Check Writable Directories
$dirs = ['uploads', 'backups', 'logs'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    if (!is_dir($path)) {
         echo "<span style='color:orange'>[WARN] Directory $dir does not exist. Creating...</span><br>";
         @mkdir($path, 0755, true);
    }
    
    if (is_writable($path)) {
        echo "<span style='color:green'>[OK] $dir is writable.</span><br>";
    } else {
        echo "<span style='color:red'>[FAIL] $dir is NOT writable!</span> (Path: $path)<br>";
    }
}

// Check Config
if (file_exists(__DIR__ . '/config/config.php')) {
    echo "<span style='color:green'>[OK] config.php exists.</span><br>";
} else {
    echo "<span style='color:red'>[FAIL] config.php is MISSING!</span><br>";
}

// Check Vendor
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<span style='color:green'>[OK] Composer vendor directory exists.</span><br>";
} else {
    echo "<span style='color:red'>[FAIL] Composer vendor directory is MISSING! Run 'composer install'.</span><br>";
}
