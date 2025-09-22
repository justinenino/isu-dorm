<?php
/**
 * Dormitory Management System - Automatic Backup Script
 * 
 * This script is designed to be run by cron for automatic daily backups.
 * It creates a complete backup of the system and uploads it to Google Drive.
 * 
 * Usage: php backup_cron.php
 * Cron: 59 23 * * * /usr/bin/php /path/to/project/backup_cron.php >> /var/log/dormitory_backup.log 2>&1
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Start execution time tracking
$start_time = microtime(true);

// Log function
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Write to log file
    $log_file = '/var/log/dormitory_backup.log';
    if (is_writable(dirname($log_file))) {
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    // Also output to stderr for cron logging
    fwrite(STDERR, $log_entry);
}

// Check if script is running from command line
if (php_sapi_name() !== 'cli') {
    logMessage('This script must be run from command line', 'ERROR');
    exit(1);
}

// Set the working directory to the project root
$script_dir = dirname(__FILE__);
chdir($script_dir);

logMessage('Starting automatic backup process');

// Check if required files exist
$required_files = [
    'config/database.php',
    'config/google_drive.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        logMessage("Required file not found: $file", 'ERROR');
        exit(1);
    }
}

// Include required files
try {
    require_once 'config/database.php';
    require_once 'config/google_drive.php';
} catch (Exception $e) {
    logMessage('Failed to include required files: ' . $e->getMessage(), 'ERROR');
    exit(1);
}

// Check database connection
try {
    $pdo = getConnection();
    logMessage('Database connection successful');
} catch (Exception $e) {
    logMessage('Database connection failed: ' . $e->getMessage(), 'ERROR');
    exit(1);
}

// Check if Google Drive backup is enabled
if (!GOOGLE_DRIVE_ENABLED) {
    logMessage('Google Drive backup is disabled, creating local backup only', 'WARNING');
}

// Create backup directory if it doesn't exist
$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    if (!mkdir($backup_dir, 0755, true)) {
        logMessage('Failed to create backup directory', 'ERROR');
        exit(1);
    }
    logMessage('Created backup directory');
}

// Generate backup filename
$timestamp = date('Y-m-d_H-i-s');
$backup_filename = "dormitory_backup_{$timestamp}.zip";
$backup_path = $backup_dir . $backup_filename;

logMessage("Creating backup: $backup_filename");

// Create database dump
$db_dump_filename = "database_dump_{$timestamp}.sql";
$db_dump_path = $backup_dir . $db_dump_filename;

$mysqldump_command = sprintf(
    'mysqldump -h %s -u %s -p%s %s > %s',
    DB_HOST,
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME,
    $db_dump_path
);

logMessage('Creating database dump...');
$output = [];
$return_var = 0;
exec($mysqldump_command, $output, $return_var);

if ($return_var !== 0) {
    logMessage('Database dump failed: ' . implode("\n", $output), 'ERROR');
    exit(1);
}

logMessage('Database dump created successfully');

// Create ZIP archive
try {
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE) !== TRUE) {
        logMessage('Failed to create ZIP archive', 'ERROR');
        exit(1);
    }

    // Add database dump
    $zip->addFile($db_dump_path, 'database/dormitory_db.sql');
    logMessage('Added database dump to archive');

    // Add project files (excluding sensitive directories)
    $exclude_dirs = ['backups', 'uploads', 'node_modules', '.git', 'vendor'];
    $project_root = '.';
    
    function addFolderToZip($zip, $folder, $relative_path, $exclude_dirs) {
        $files = scandir($folder);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $file_path = $folder . '/' . $file;
            $zip_path = $relative_path . $file;
            
            if (is_dir($file_path)) {
                if (!in_array($file, $exclude_dirs)) {
                    addFolderToZip($zip, $file_path, $zip_path . '/', $exclude_dirs);
                }
            } else {
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
    
    addFolderToZip($zip, $project_root, '', $exclude_dirs);
    logMessage('Added project files to archive');
    
    $zip->close();
    logMessage('ZIP archive created successfully');
    
} catch (Exception $e) {
    logMessage('Failed to create ZIP archive: ' . $e->getMessage(), 'ERROR');
    exit(1);
}

// Clean up database dump file
if (file_exists($db_dump_path)) {
    unlink($db_dump_path);
    logMessage('Cleaned up temporary database dump file');
}

// Check backup file size
$backup_size = filesize($backup_path);
logMessage("Backup file size: " . formatBytes($backup_size));

// Upload to Google Drive if enabled
if (GOOGLE_DRIVE_ENABLED) {
    logMessage('Uploading backup to Google Drive...');
    
    try {
        $upload_result = uploadToGoogleDrive($backup_path);
        
        if ($upload_result['success']) {
            logMessage('Backup uploaded to Google Drive successfully');
        } else {
            logMessage('Google Drive upload failed: ' . $upload_result['error'], 'ERROR');
            
            // Send email notification for upload failure
            if (BACKUP_EMAIL_ENABLED) {
                sendBackupEmail('Backup Upload Failed', $upload_result['error']);
            }
        }
    } catch (Exception $e) {
        logMessage('Google Drive upload exception: ' . $e->getMessage(), 'ERROR');
    }
}

// Clean up old local backups (keep last 7 days)
logMessage('Cleaning up old local backups...');
$cutoff_time = time() - (7 * 24 * 60 * 60); // 7 days ago

$old_backups = glob($backup_dir . 'dormitory_backup_*.zip');
foreach ($old_backups as $old_backup) {
    if (filemtime($old_backup) < $cutoff_time) {
        if (unlink($old_backup)) {
            logMessage('Deleted old backup: ' . basename($old_backup));
        } else {
            logMessage('Failed to delete old backup: ' . basename($old_backup), 'WARNING');
        }
    }
}

// Calculate execution time
$execution_time = microtime(true) - $start_time;
logMessage("Backup process completed in " . round($execution_time, 2) . " seconds");

// Send success email notification
if (BACKUP_EMAIL_ENABLED && GOOGLE_DRIVE_ENABLED) {
    $success_message = "Automatic backup completed successfully.\n";
    $success_message .= "Backup file: $backup_filename\n";
    $success_message .= "File size: " . formatBytes($backup_size) . "\n";
    $success_message .= "Execution time: " . round($execution_time, 2) . " seconds";
    
    sendBackupEmail('Backup Completed Successfully', $success_message);
}

logMessage('Automatic backup process finished successfully');
exit(0);

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
