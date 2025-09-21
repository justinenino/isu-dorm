<?php
// Hostinger Deployment Test Script
// Run this on Hostinger to check if everything is working

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Hostinger Deployment Test</h1>";
echo "<p>Testing your Hostinger environment...</p>";

// Test 1: PHP Version
echo "<h2>1. PHP Version Test</h2>";
$phpVersion = phpversion();
echo "<p>PHP Version: <strong>$phpVersion</strong></p>";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<p style='color: green;'>✅ PHP version is compatible</p>";
} else {
    echo "<p style='color: red;'>❌ PHP version is too old. Please upgrade to PHP 7.4 or higher</p>";
}

// Test 2: Required Extensions
echo "<h2>2. PHP Extensions Test</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'session', 'json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ $ext extension loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ $ext extension not loaded</p>";
    }
}

// Test 3: File System
echo "<h2>3. File System Test</h2>";
$requiredFiles = [
    'config/database.php',
    'config/database_hostinger.php',
    'admin/dashboard.php',
    'student/dashboard.php',
    'login.php',
    'index.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>";
    }
}

// Test 4: Database Configuration
echo "<h2>4. Database Configuration Test</h2>";

// Test localhost config
if (file_exists('config/database.php')) {
    include 'config/database.php';
    echo "<p>Testing localhost database configuration...</p>";
    $result = testDatabaseConnection();
    if ($result['status'] === 'success') {
        echo "<p style='color: green;'>✅ Localhost database connected</p>";
        echo "<p>MySQL Version: " . $result['version'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Localhost database failed: " . $result['message'] . "</p>";
    }
}

// Test Hostinger config
if (file_exists('config/database_hostinger.php')) {
    echo "<p>Testing Hostinger database configuration...</p>";
    include 'config/database_hostinger.php';
    $result = testDatabaseConnection();
    if ($result['status'] === 'success') {
        echo "<p style='color: green;'>✅ Hostinger database connected</p>";
        echo "<p>MySQL Version: " . $result['version'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Hostinger database failed: " . $result['message'] . "</p>";
    }
}

// Test 5: Database Tables
echo "<h2>5. Database Tables Test</h2>";
try {
    $pdo = getConnection();
    $tables = ['admins', 'students', 'rooms', 'buildings', 'offenses', 'complaints', 'maintenance_requests', 'announcements'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p style='color: green;'>✅ Table '$table' exists with $count records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table' error: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 6: Session Test
echo "<h2>6. Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Sessions are working</p>";
} else {
    echo "<p style='color: red;'>❌ Sessions are not working</p>";
}

// Test 7: Memory and Limits
echo "<h2>7. Server Limits Test</h2>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . " seconds</p>";
echo "<p>Upload Max Filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>Post Max Size: " . ini_get('post_max_size') . "</p>";

// Test 8: Directory Permissions
echo "<h2>8. Directory Permissions Test</h2>";
$directories = ['admin', 'student', 'config'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "<p style='color: green;'>✅ $dir is readable</p>";
        } else {
            echo "<p style='color: red;'>❌ $dir is not readable</p>";
        }
        
        if (is_writable($dir)) {
            echo "<p style='color: green;'>✅ $dir is writable</p>";
        } else {
            echo "<p style='color: red;'>❌ $dir is not writable</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $dir directory not found</p>";
    }
}

// Test 9: URL Test
echo "<h2>9. URL Access Test</h2>";
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
echo "<p>Current URL: $currentUrl</p>";

// Test 10: Error Log Location
echo "<h2>10. Error Log Test</h2>";
$errorLog = ini_get('error_log');
if ($errorLog) {
    echo "<p>Error log location: $errorLog</p>";
    if (file_exists($errorLog)) {
        echo "<p style='color: green;'>✅ Error log file exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Error log file doesn't exist yet</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Error log not configured</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If you see any red ❌ errors above, please fix them before proceeding.</p>";
echo "<p>If all tests pass, your Hostinger environment is ready!</p>";

// Show current directory structure
echo "<h2>Current Directory Structure</h2>";
echo "<pre>";
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    echo $file->getPathname() . "\n";
}
echo "</pre>";
?>
