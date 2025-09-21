<?php
/**
 * Google Drive Backup Configuration
 * 
 * This file handles automatic backup uploads to Google Drive
 * Requires Google Drive API setup and service account credentials
 */

// Google Drive API Configuration
define('GOOGLE_DRIVE_ENABLED', false); // Set to true after setup
define('GOOGLE_DRIVE_FOLDER_ID', ''); // Your Google Drive folder ID
define('GOOGLE_DRIVE_SERVICE_ACCOUNT_FILE', __DIR__ . '/service-account-key.json');

// Email notification settings
define('BACKUP_EMAIL_ENABLED', true);
define('BACKUP_EMAIL_TO', 'admin@example.com');
define('BACKUP_EMAIL_FROM', 'noreply@dormitory.com');

// Backup retention settings
define('BACKUP_RETENTION_DAYS', 7); // Keep backups for 7 days

/**
 * Upload backup to Google Drive
 * 
 * @param string $file_path Path to the backup file
 * @return array ['success' => bool, 'error' => string]
 */
function uploadToGoogleDrive($file_path) {
    if (!GOOGLE_DRIVE_ENABLED) {
        return [
            'success' => false,
            'error' => 'Google Drive backup is not enabled. Please configure the settings.'
        ];
    }
    
    if (!file_exists(GOOGLE_DRIVE_SERVICE_ACCOUNT_FILE)) {
        return [
            'success' => false,
            'error' => 'Service account key file not found. Please place your JSON key file in the config directory.'
        ];
    }
    
    if (empty(GOOGLE_DRIVE_FOLDER_ID)) {
        return [
            'success' => false,
            'error' => 'Google Drive folder ID not configured.'
        ];
    }
    
    try {
        // Load Google API Client Library
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Create Google Client
        $client = new Google_Client();
        $client->setAuthConfig(GOOGLE_DRIVE_SERVICE_ACCOUNT_FILE);
        $client->addScope(Google_Service_Drive::DRIVE);
        
        // Create Drive Service
        $service = new Google_Service_Drive($client);
        
        // File metadata
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => basename($file_path),
            'parents' => [GOOGLE_DRIVE_FOLDER_ID]
        ]);
        
        // Upload file
        $content = file_get_contents($file_path);
        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/zip',
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);
        
        // Clean up old backups
        cleanupOldBackups($service);
        
        // Log success
        logBackupActivity('SUCCESS', 'Backup uploaded to Google Drive: ' . $file->getId());
        
        return [
            'success' => true,
            'file_id' => $file->getId()
        ];
        
    } catch (Exception $e) {
        $error_message = 'Google Drive upload failed: ' . $e->getMessage();
        logBackupActivity('ERROR', $error_message);
        
        // Send email notification
        if (BACKUP_EMAIL_ENABLED) {
            sendBackupEmail('Backup Upload Failed', $error_message);
        }
        
        return [
            'success' => false,
            'error' => $error_message
        ];
    }
}

/**
 * Clean up old backup files from Google Drive
 * 
 * @param Google_Service_Drive $service
 */
function cleanupOldBackups($service) {
    try {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . BACKUP_RETENTION_DAYS . ' days'));
        
        // Get files in the backup folder
        $results = $service->files->listFiles([
            'q' => "'" . GOOGLE_DRIVE_FOLDER_ID . "' in parents and name contains 'dormitory_backup'",
            'fields' => 'files(id, name, createdTime)'
        ]);
        
        foreach ($results->getFiles() as $file) {
            $file_date = new DateTime($file->getCreatedTime());
            $cutoff = new DateTime($cutoff_date);
            
            if ($file_date < $cutoff) {
                $service->files->delete($file->getId());
                logBackupActivity('CLEANUP', 'Deleted old backup: ' . $file->getName());
            }
        }
        
    } catch (Exception $e) {
        logBackupActivity('ERROR', 'Failed to cleanup old backups: ' . $e->getMessage());
    }
}

/**
 * Log backup activities
 * 
 * @param string $level
 * @param string $message
 */
function logBackupActivity($level, $message) {
    $log_file = '/var/log/drive_backup.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Try to write to log file, fallback to error log if not writable
    if (is_writable(dirname($log_file))) {
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    } else {
        error_log("Drive Backup: $message");
    }
}

/**
 * Send email notification
 * 
 * @param string $subject
 * @param string $message
 */
function sendBackupEmail($subject, $message) {
    if (!BACKUP_EMAIL_ENABLED) {
        return;
    }
    
    $headers = [
        'From: ' . BACKUP_EMAIL_FROM,
        'Reply-To: ' . BACKUP_EMAIL_FROM,
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $html_message = "
    <html>
    <head>
        <title>$subject</title>
    </head>
    <body>
        <h2>Dormitory Management System - Backup Notification</h2>
        <p><strong>Subject:</strong> $subject</p>
        <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Message:</strong></p>
        <p>$message</p>
        <hr>
        <p><small>This is an automated message from the Dormitory Management System.</small></p>
    </body>
    </html>
    ";
    
    mail(BACKUP_EMAIL_TO, $subject, $html_message, implode("\r\n", $headers));
}

/**
 * Create automatic backup (for cron job)
 * 
 * @return bool
 */
function createAutomaticBackup() {
    try {
        // Create backup file
        $backup_file = createBackup();
        
        if (!$backup_file) {
            logBackupActivity('ERROR', 'Failed to create backup file');
            return false;
        }
        
        // Upload to Google Drive
        $result = uploadToGoogleDrive($backup_file);
        
        // Clean up local file
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }
        
        if ($result['success']) {
            logBackupActivity('SUCCESS', 'Automatic backup completed successfully');
            return true;
        } else {
            logBackupActivity('ERROR', 'Automatic backup failed: ' . $result['error']);
            return false;
        }
        
    } catch (Exception $e) {
        logBackupActivity('ERROR', 'Automatic backup exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create backup file
 * 
 * @return string|false Path to backup file or false on failure
 */
function createBackup() {
    $backup_dir = __DIR__ . '/../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . 'dormitory_backup_' . $timestamp . '.zip';
    
    // Create database dump
    $db_dump_file = $backup_dir . 'database_dump_' . $timestamp . '.sql';
    $command = "mysqldump -h " . DB_HOST . " -u " . DB_USERNAME . " -p" . DB_PASSWORD . " " . DB_NAME . " > " . $db_dump_file;
    exec($command, $output, $return_var);
    
    if ($return_var !== 0) {
        return false;
    }
    
    // Create ZIP file
    $zip = new ZipArchive();
    if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
        // Add database dump
        $zip->addFile($db_dump_file, 'database/dormitory_db.sql');
        
        // Add project files (excluding backups and uploads)
        $project_root = __DIR__ . '/../';
        $exclude_dirs = ['backups', 'uploads', 'node_modules', '.git', 'vendor'];
        
        addFolderToZip($zip, $project_root, '', $exclude_dirs);
        
        $zip->close();
        
        // Clean up database dump file
        unlink($db_dump_file);
        
        return $backup_file;
    }
    
    return false;
}

/**
 * Add folder to ZIP recursively
 * 
 * @param ZipArchive $zip
 * @param string $folder
 * @param string $relative_path
 * @param array $exclude_dirs
 */
function addFolderToZip($zip, $folder, $relative_path, $exclude_dirs) {
    $files = scandir($folder);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $file_path = $folder . $file;
        $zip_path = $relative_path . $file;
        
        if (is_dir($file_path)) {
            if (!in_array($file, $exclude_dirs)) {
                addFolderToZip($zip, $file_path . '/', $zip_path . '/', $exclude_dirs);
            }
        } else {
            $zip->addFile($file_path, $zip_path);
        }
    }
}

// If this file is called directly (for cron job)
if (basename($_SERVER['SCRIPT_NAME']) == 'google_drive.php') {
    require_once __DIR__ . '/database.php';
    createAutomaticBackup();
}
?>
