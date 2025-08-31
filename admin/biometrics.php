<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle file upload
if ($_POST && isset($_POST['upload_biometrics'])) {
    $upload_date = sanitizeInput($_POST['upload_date']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($upload_date)) {
        $error = 'Upload date is required.';
    } elseif (!isset($_FILES['biometric_file']) || $_FILES['biometric_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid file to upload.';
    } else {
        $file = $_FILES['biometric_file'];
        $allowed_types = ['csv', 'xlsx', 'xls'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $error = 'Only CSV and Excel files are allowed.';
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            $error = 'File size must be less than 10MB.';
        } else {
            try {
                $pdo = getDBConnection();
                
                // Generate unique filename
                $filename = 'biometrics_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
                $upload_path = '../uploads/biometrics/' . $filename;
                
                // Create directory if it doesn't exist
                if (!is_dir('../uploads/biometrics/')) {
                    mkdir('../uploads/biometrics/', 0755, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO biometrics (filename, original_name, upload_date, description, file_size, uploaded_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $filename,
                        $file['name'],
                        $upload_date,
                        $description,
                        $file['size'],
                        $_SESSION['user_id']
                    ]);
                    
                    $message = 'Biometric file uploaded successfully!';
                    logActivity($_SESSION['user_id'], "Uploaded biometric file: " . $file['name']);
                } else {
                    $error = 'Error uploading file. Please try again.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle file deletion
if ($_POST && isset($_POST['delete_file'])) {
    $biometric_id = sanitizeInput($_POST['biometric_id']);
    
    try {
        $pdo = getDBConnection();
        
        // Get file info before deletion
        $stmt = $pdo->prepare("SELECT filename FROM biometrics WHERE biometric_id = ?");
        $stmt->execute([$biometric_id]);
        $file_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file_info) {
            // Delete physical file
            $file_path = '../uploads/biometrics/' . $file_info['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete database record
            $stmt = $pdo->prepare("DELETE FROM biometrics WHERE biometric_id = ?");
            if ($stmt->execute([$biometric_id])) {
                $message = 'Biometric file deleted successfully.';
                logActivity($_SESSION['user_id'], "Deleted biometric file ID: $biometric_id");
            } else {
                $error = 'Error deleting file record.';
            }
        } else {
            $error = 'File not found.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch biometric files
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT b.*, u.username as uploaded_by_name
        FROM biometrics b
        LEFT JOIN users u ON b.uploaded_by = u.user_id
        ORDER BY b.upload_date DESC, b.created_at DESC
    ");
    $biometric_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_files,
            SUM(file_size) as total_size,
            COUNT(DISTINCT DATE(upload_date)) as unique_dates
        FROM biometrics
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $biometric_files = [];
    $stats = ['total_files' => 0, 'total_size' => 0, 'unique_dates' => 0];
}

$page_title = "Biometrics Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-fingerprint me-2"></i>Biometrics Management
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-2"></i>Upload Biometric File
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Files
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_files']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Size
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo formatFileSize($stats['total_size']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-hdd fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Unique Dates
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['unique_dates']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Today's Uploads
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $today_stmt = $pdo->query("SELECT COUNT(*) as count FROM biometrics WHERE DATE(created_at) = CURDATE()");
                                        $today_count = $today_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                        echo $today_count;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biometric Files Table -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Biometric Files
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="biometricsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>File Name</th>
                                    <th>Upload Date</th>
                                    <th>Description</th>
                                    <th>File Size</th>
                                    <th>Uploaded By</th>
                                    <th>Upload Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($biometric_files)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No biometric files uploaded yet</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($biometric_files as $file): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($file['original_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($file['filename']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo formatDate($file['upload_date'], 'M d, Y'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($file['description']): ?>
                                                    <?php echo htmlspecialchars($file['description']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatFileSize($file['file_size']); ?></td>
                                            <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                                            <td><?php echo formatDate($file['created_at'], 'M d, Y h:i A'); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../uploads/biometrics/<?php echo $file['filename']; ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       download="<?php echo $file['original_name']; ?>"
                                                       title="Download File">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewFileDetails(<?php echo $file['biometric_id']; ?>)" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Delete this biometric file? This action cannot be undone.')">
                                                        <input type="hidden" name="biometric_id" value="<?php echo $file['biometric_id']; ?>">
                                                        <button type="submit" name="delete_file" class="btn btn-sm btn-danger" 
                                                                title="Delete File">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>Upload Biometric File
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="upload_biometrics" value="1">
                    
                    <div class="mb-3">
                        <label for="upload_date" class="form-label">Upload Date *</label>
                        <input type="date" class="form-control" id="upload_date" name="upload_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                        <small class="form-text text-muted">The date this biometric data represents</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="biometric_file" class="form-label">Biometric File *</label>
                        <input type="file" class="form-control" id="biometric_file" name="biometric_file" 
                               accept=".csv,.xlsx,.xls" required>
                        <small class="form-text text-muted">Only CSV and Excel files are allowed. Max size: 10MB</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Optional description of the biometric data..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Uploaded files will be accessible to students for download. 
                        Make sure to upload clean, organized data files.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- File Details Modal -->
<div class="modal fade" id="fileDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2"></i>File Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fileDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewFileDetails(biometricId) {
    // Load file details via AJAX
    fetch(`get_biometric_details.php?biometric_id=${biometricId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('fileDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('fileDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading file details');
        });
}

// Initialize DataTable
$(document).ready(function() {
    $('#biometricsTable').DataTable({
        pageLength: 25,
        order: [[1, 'desc'], [5, 'desc']], // Sort by upload_date, then created_at
        responsive: true,
        language: {
            search: "Search files:",
            lengthMenu: "Show _MENU_ files per page",
            info: "Showing _START_ to _END_ of _TOTAL_ files"
        }
    });
});

// File upload preview
document.getElementById('biometric_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = (file.size / (1024 * 1024)).toFixed(2);
        const fileName = file.name;
        
        // Show file info
        const fileInfo = document.createElement('div');
        fileInfo.className = 'alert alert-info mt-2';
        fileInfo.innerHTML = `
            <i class="fas fa-file me-2"></i>
            <strong>Selected File:</strong> ${fileName}<br>
            <strong>Size:</strong> ${fileSize} MB
        `;
        
        // Remove previous file info if exists
        const existingInfo = document.querySelector('.alert-info');
        if (existingInfo) {
            existingInfo.remove();
        }
        
        document.getElementById('biometric_file').parentNode.appendChild(fileInfo);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
