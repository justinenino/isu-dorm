<?php
/**
 * SWITCH TO HOSTINGER CONFIGURATION
 * This script switches your application from local to Hostinger configuration
 */

echo "<h2>🔄 Switching to Hostinger Configuration</h2>";

// Check if Hostinger config exists
if (!file_exists('config/database_hostinger_updated.php')) {
    die("<p style='color: red;'>❌ Hostinger configuration file not found!</p>");
}

// Backup current local config
if (file_exists('config/database.php')) {
    if (copy('config/database.php', 'config/database_local_backup.php')) {
        echo "<p style='color: green;'>✅ Local configuration backed up to database_local_backup.php</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not backup local configuration</p>";
    }
}

// Switch to Hostinger config
if (copy('config/database_hostinger_updated.php', 'config/database.php')) {
    echo "<p style='color: green;'>✅ Successfully switched to Hostinger configuration</p>";
    echo "<p style='color: blue;'>📝 Remember to update your database credentials in config/database.php</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to switch to Hostinger configuration</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Update database credentials in config/database.php</li>";
echo "<li>Upload all files to Hostinger</li>";
echo "<li>Import your database to Hostinger</li>";
echo "<li>Test the connection using test_hostinger_connection.php</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>To switch back to local:</h3>";
echo "<p>Run: <code>cp config/database_local_backup.php config/database.php</code></p>";
?>
