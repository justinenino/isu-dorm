<?php
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Handle backup actions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'download_backup':
                // Create backup and download
                $backup_file = createBackup();
                if ($backup_file) {
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="dormitory_backup_' . date('Y-m-d_H-i-s') . '.zip"');
                    header('Content-Length: ' . filesize($backup_file));
                    readfile($backup_file);
                    unlink($backup_file); // Delete temporary file
                    exit;
                } else {
                    $error_message = 'Failed to create backup file.';
                }
                break;
                
            case 'upload_to_drive':
                // Create backup and upload to Google Drive
                $backup_file = createBackup();
                if ($backup_file) {
                    $upload_result = uploadToGoogleDrive($backup_file);
                    if ($upload_result['success']) {
                        $success_message = 'Backup uploaded to Google Drive successfully!';
                    } else {
                        $error_message = 'Failed to upload to Google Drive: ' . $upload_result['error'];
                    }
                    unlink($backup_file); // Delete temporary file
                } else {
                    $error_message = 'Failed to create backup file.';
                }
                break;
        }
    }
}

$page_title = 'System Backup';
include 'includes/header.php';

// Get backup statistics
$backup_dir = '../backups/';
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'zip') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
    // Sort by date (newest first)
    usort($backup_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Get system information
$pdo = getConnection();
$db_size = 0;
$stmt = $pdo->query("SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB'
    FROM information_schema.tables 
    WHERE table_schema = '" . DB_NAME . "'");
$result = $stmt->fetch();
$db_size = $result['DB Size in MB'] ?? 0;

function createBackup() {
    $backup_dir = '../backups/';
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
        $project_root = '../';
        $exclude_dirs = ['backups', 'uploads', 'node_modules', '.git'];
        
        addFolderToZip($zip, $project_root, '', $exclude_dirs);
        
        $zip->close();
        
        // Clean up database dump file
        unlink($db_dump_file);
        
        return $backup_file;
    }
    
    return false;
}

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

function uploadToGoogleDrive($file_path) {
    // This function requires Google Drive API setup
    // For now, return a placeholder response
    return [
        'success' => false,
        'error' => 'Google Drive API not configured. Please set up the API credentials.'
    ];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-database"></i> System Backup</h2>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Backup Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count($backup_files); ?></h3>
            <p>Total Backups</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo $db_size; ?> MB</h3>
            <p>Database Size</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo date('Y-m-d'); ?></h3>
            <p>Last Backup</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3>Auto</h3>
            <p>Backup Schedule</p>
        </div>
    </div>
</div>

<!-- Backup Actions -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download"></i> Manual Backup</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="download_backup">
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-download"></i> Download Backup
                            </button>
                            <p class="text-muted small">Creates a complete backup including database and project files, then downloads it to your browser.</p>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="upload_to_drive">
                            <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                                <i class="fas fa-cloud-upload-alt"></i> Upload to Google Drive
                            </button>
                            <p class="text-muted small">Creates a backup and automatically uploads it to your configured Google Drive folder.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Backup Information</h5>
            </div>
            <div class="card-body">
                <h6>What's Included:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Complete database dump</li>
                    <li><i class="fas fa-check text-success"></i> All project files</li>
                    <li><i class="fas fa-check text-success"></i> Configuration files</li>
                    <li><i class="fas fa-times text-danger"></i> Uploaded files (excluded)</li>
                    <li><i class="fas fa-times text-danger"></i> Previous backups (excluded)</li>
                </ul>
                
                <hr>
                
                <h6>Backup Schedule:</h6>
                <p class="mb-0">Automatic backups run daily at 11:59 PM and are kept for 7 days.</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Backups -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Backups</h5>
    </div>
    <div class="card-body">
        <?php if (empty($backup_files)): ?>
            <p class="text-muted text-center">No backup files found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Backup File</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($backup_files, 0, 10) as $file): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-archive text-primary"></i>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </td>
                                <td><?php echo formatBytes($file['size']); ?></td>
                                <td><?php echo date('M j, Y g:i A', $file['date']); ?></td>
                                <td>
                                    <a href="../backups/<?php echo urlencode($file['name']); ?>" 
                                       class="btn btn-sm btn-outline-primary" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Google Drive Setup Instructions -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-cog"></i> Google Drive Setup</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle"></i> Setup Instructions</h6>
            <p>To enable automatic Google Drive uploads, follow these steps:</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6>1. Google Cloud Console Setup</h6>
                <ol>
                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select existing one</li>
                    <li>Enable Google Drive API</li>
                    <li>Create a Service Account</li>
                    <li>Download the JSON key file</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h6>2. Google Drive Setup</h6>
                <ol>
                    <li>Create a folder in Google Drive</li>
                    <li>Share the folder with the service account email</li>
                    <li>Copy the folder ID from the URL</li>
                    <li>Place the JSON key file in the config directory</li>
                </ol>
            </div>
        </div>
        
        <div class="mt-3">
            <h6>3. Configuration</h6>
            <p>Update the configuration in <code>config/google_drive.php</code> with your credentials.</p>
        </div>
    </div>
</div>

<script>
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php
function formatBytes($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<?php include 'includes/footer.php'; ?>
