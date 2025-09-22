<?php
/**
 * Database Connection Test for Hostinger
 * 
 * This file tests the database connection after deployment
 * Delete this file after successful testing
 */

require_once 'config/database_hostinger_updated.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Connection Test</title></head><body>";
echo "<h2>🔧 Database Connection Test</h2>";

$result = testDatabaseConnection();

if ($result['status'] === 'success') {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p><strong>Status:</strong> " . $result['status'] . "</p>";
    echo "<p><strong>Message:</strong> " . $result['message'] . "</p>";
    echo "<p><strong>MySQL Version:</strong> " . $result['version'] . "</p>";
    echo "<p>Your database connection is working perfectly!</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ FAILED!</h3>";
    echo "<p><strong>Status:</strong> " . $result['status'] . "</p>";
    echo "<p><strong>Message:</strong> " . $result['message'] . "</p>";
    echo "<p>Please check your database configuration in config/database_hostinger_updated.php</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Database Health Check</h3>";
if (checkDatabaseHealth()) {
    echo "<p style='color: green;'>✅ Database is healthy and responding</p>";
} else {
    echo "<p style='color: red;'>❌ Database health check failed</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after testing for security!</p>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
echo "</body></html>";
?>
