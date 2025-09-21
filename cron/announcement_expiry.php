<?php
/**
 * Announcement Expiry Cron Job
 * This script should be run every hour to automatically archive expired announcements
 * Add this to your crontab: 0 * * * * /usr/bin/php /path/to/your/project/cron/announcement_expiry.php
 */

require_once '../config/database.php';

try {
    $pdo = getConnection();
    
    // Archive expired announcements
    $stmt = $pdo->prepare("UPDATE announcements 
        SET is_archived = 1 
        WHERE expires_at IS NOT NULL 
        AND expires_at < NOW() 
        AND is_archived = 0");
    
    $result = $stmt->execute();
    $affected_rows = $stmt->rowCount();
    
    // Log the action
    $log_message = date('Y-m-d H:i:s') . " - Archived $affected_rows expired announcements\n";
    file_put_contents('../logs/announcement_expiry.log', $log_message, FILE_APPEND | LOCK_EX);
    
    echo "Successfully archived $affected_rows expired announcements\n";
    
} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    file_put_contents('../logs/announcement_expiry.log', $error_message, FILE_APPEND | LOCK_EX);
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
