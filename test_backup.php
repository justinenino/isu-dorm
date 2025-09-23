<?php
/**
 * Test Backup Script
 * Use this to test backup functionality and diagnose issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Backup System Test</h2>";

// Test 1: Check required files
echo "<h3>1. Checking Required Files</h3>";
$required_files = [
    'config/database.php',
    'admin/system_backup.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 2: Check database connection
echo "<h3>2. Testing Database Connection</h3>";
try {
    require_once 'config/database.php';
    $pdo = getConnection();
    echo "✅ Database connection successful<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $result = $stmt->fetch();
    echo "✅ Database has " . $result['table_count'] . " tables<br>";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check PHP extensions
echo "<h3>3. Checking PHP Extensions</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'zip'];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension missing<br>";
    }
}

// Test 4: Check directory permissions
echo "<h3>4. Checking Directory Permissions</h3>";
$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    if (mkdir($backup_dir, 0755, true)) {
        echo "✅ Created backup directory<br>";
    } else {
        echo "❌ Failed to create backup directory<br>";
    }
} else {
    echo "✅ Backup directory exists<br>";
}

if (is_writable($backup_dir)) {
    echo "✅ Backup directory is writable<br>";
} else {
    echo "❌ Backup directory is not writable<br>";
}

// Test 5: Test backup creation
echo "<h3>5. Testing Backup Creation</h3>";
try {
    // Include the backup functions
    require_once 'admin/system_backup.php';
    
    $backup_file = createBackup();
    if ($backup_file && file_exists($backup_file)) {
        echo "✅ Backup created successfully: " . basename($backup_file) . "<br>";
        echo "✅ Backup file size: " . number_format(filesize($backup_file) / 1024, 2) . " KB<br>";
        
        // Clean up test file
        unlink($backup_file);
        echo "✅ Test backup file cleaned up<br>";
    } else {
        echo "❌ Backup creation failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Backup test failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Complete</h3>";
echo "<p>If all tests pass, the backup system should work. If any tests fail, those issues need to be resolved.</p>";
?>
