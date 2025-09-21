<?php
/**
 * HOSTINGER CONNECTION TEST SCRIPT
 * Use this script to test your Hostinger database connection
 * Upload this file to your Hostinger server and run it
 */

// Include the Hostinger database configuration
require_once 'config/database_hostinger_updated.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostinger Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h1, h2 {
            color: #333;
        }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Hostinger Connection Test</h1>
        <p>This script tests your Hostinger database connection and system compatibility.</p>

        <?php
        // Test 1: Database Connection
        echo "<div class='test-section'>";
        echo "<h2>Test 1: Database Connection</h2>";
        
        try {
            $pdo = getConnection();
            echo "<div class='success'>✅ Database connection successful!</div>";
            
            // Get database version
            $stmt = $pdo->query("SELECT VERSION() as version");
            $version = $stmt->fetchColumn();
            echo "<div class='info'>📊 MySQL Version: " . htmlspecialchars($version) . "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database connection failed!</div>";
            echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>💡 Check your database credentials in config/database_hostinger_updated.php</div>";
        }
        echo "</div>";

        // Test 2: Database Tables
        echo "<div class='test-section'>";
        echo "<h2>Test 2: Database Tables</h2>";
        
        try {
            $pdo = getConnection();
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo "<div class='error'>❌ No tables found in database!</div>";
                echo "<div class='info'>💡 You need to import your database schema first.</div>";
            } else {
                echo "<div class='success'>✅ Found " . count($tables) . " tables in database</div>";
                echo "<div class='info'>📋 Tables: " . implode(', ', $tables) . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error checking tables: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        echo "</div>";

        // Test 3: Sample Data
        echo "<div class='test-section'>";
        echo "<h2>Test 3: Sample Data Check</h2>";
        
        try {
            $pdo = getConnection();
            
            // Check for admin users
            $adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
            echo "<div class='info'>👤 Admin users: " . $adminCount . "</div>";
            
            // Check for students
            $studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            echo "<div class='info'>🎓 Students: " . $studentCount . "</div>";
            
            // Check for rooms
            $roomCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
            echo "<div class='info'>🏠 Rooms: " . $roomCount . "</div>";
            
            if ($adminCount > 0) {
                echo "<div class='success'>✅ Sample data found - database appears to be properly imported</div>";
            } else {
                echo "<div class='error'>❌ No admin users found - you may need to import your data</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error checking sample data: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        echo "</div>";

        // Test 4: PHP Configuration
        echo "<div class='test-section'>";
        echo "<h2>Test 4: PHP Configuration</h2>";
        
        echo "<div class='info'>🐘 PHP Version: " . PHP_VERSION . "</div>";
        echo "<div class='info'>📁 Current Directory: " . getcwd() . "</div>";
        echo "<div class='info'>🌐 Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</div>";
        
        // Check required PHP extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'session', 'json'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        if (empty($missingExtensions)) {
            echo "<div class='success'>✅ All required PHP extensions are loaded</div>";
        } else {
            echo "<div class='error'>❌ Missing PHP extensions: " . implode(', ', $missingExtensions) . "</div>";
        }
        echo "</div>";

        // Test 5: File Permissions
        echo "<div class='test-section'>";
        echo "<h2>Test 5: File Permissions</h2>";
        
        $configFile = 'config/database_hostinger_updated.php';
        if (file_exists($configFile)) {
            if (is_readable($configFile)) {
                echo "<div class='success'>✅ Configuration file is readable</div>";
            } else {
                echo "<div class='error'>❌ Configuration file is not readable</div>";
            }
        } else {
            echo "<div class='error'>❌ Configuration file not found: " . $configFile . "</div>";
        }
        echo "</div>";

        // Test 6: Session Test
        echo "<div class='test-section'>";
        echo "<h2>Test 6: Session Test</h2>";
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "<div class='success'>✅ Sessions are working</div>";
        } else {
            echo "<div class='error'>❌ Sessions are not working</div>";
        }
        echo "</div>";
        ?>

        <div class="test-section">
            <h2>🎯 Next Steps</h2>
            <div class="info">
                <p><strong>If all tests pass:</strong></p>
                <ul>
                    <li>✅ Your Hostinger setup is working correctly</li>
                    <li>✅ You can proceed with the full deployment</li>
                    <li>✅ Update your main config file: <code>mv config/database_hostinger_updated.php config/database.php</code></li>
                </ul>
                
                <p><strong>If any tests fail:</strong></p>
                <ul>
                    <li>❌ Check your database credentials</li>
                    <li>❌ Ensure database is imported correctly</li>
                    <li>❌ Verify file permissions</li>
                    <li>❌ Contact Hostinger support if needed</li>
                </ul>
            </div>
        </div>

        <div class="test-section">
            <h2>🔒 Security Note</h2>
            <div class="info">
                <p><strong>Important:</strong> Delete this test file after completing your tests for security reasons.</p>
                <div class="code">rm test_hostinger_connection.php</div>
            </div>
        </div>
    </div>
</body>
</html>
