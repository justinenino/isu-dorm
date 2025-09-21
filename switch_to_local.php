<?php
/**
 * SWITCH TO LOCAL CONFIGURATION
 * This script switches your application from Hostinger back to local configuration
 */

echo "<h2>🔄 Switching to Local Configuration</h2>";

// Check if local backup exists
if (!file_exists('config/database_local_backup.php')) {
    die("<p style='color: red;'>❌ Local configuration backup not found!</p>");
}

// Switch back to local config
if (copy('config/database_local_backup.php', 'config/database.php')) {
    echo "<p style='color: green;'>✅ Successfully switched back to local configuration</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to switch back to local configuration</p>";
}

echo "<hr>";
echo "<h3>You are now back to local development mode!</h3>";
echo "<p>Your application will now use the local XAMPP database.</p>";
?>
