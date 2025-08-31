<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

$biometric_id = isset($_GET['biometric_id']) ? sanitizeInput($_GET['biometric_id']) : '';

if (!$biometric_id) {
    echo '<div class="alert alert-danger">Invalid biometric file ID.</div>';
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT b.*, u.username as uploaded_by_name, u.email as uploaded_by_email
        FROM biometrics b
        LEFT JOIN users u ON b.uploaded_by = u.user_id
        WHERE b.biometric_id = ?
    ");
    
    $stmt->execute([$biometric_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        echo '<div class="alert alert-danger">Biometric file not found.</div>';
        exit;
    }
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching file details: ' . $e->getMessage() . '</div>';
    exit;
}

// Check if physical file exists
$file_path = '../uploads/biometrics/' . $file['filename'];
$file_exists = file_exists($file_path);
$file_size_formatted = formatFileSize($file['file_size']);
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-file-alt me-2"></i>File Information
        </h6>
        
        <table class="table table-borderless">
            <tr>
                <td><strong>Original Name:</strong></td>
                <td><?php echo htmlspecialchars($file['original_name']); ?></td>
            </tr>
            <tr>
                <td><strong>System Filename:</strong></td>
                <td>
                    <code class="text-muted"><?php echo htmlspecialchars($file['filename']); ?></code>
                </td>
            </tr>
            <tr>
                <td><strong>File Size:</strong></td>
                <td>
                    <span class="badge bg-info"><?php echo $file_size_formatted; ?></span>
                </td>
            </tr>
            <tr>
                <td><strong>File Type:</strong></td>
                <td>
                    <?php 
                    $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                    $type_badges = [
                        'csv' => 'bg-success',
                        'xlsx' => 'bg-primary',
                        'xls' => 'bg-warning'
                    ];
                    $badge_class = $type_badges[$extension] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>">
                        <?php echo strtoupper($extension); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Physical File:</strong></td>
                <td>
                    <?php if ($file_exists): ?>
                        <span class="badge bg-success">Exists</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Missing</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-calendar me-2"></i>Upload Information
        </h6>
        
        <table class="table table-borderless">
            <tr>
                <td><strong>Upload Date:</strong></td>
                <td>
                    <span class="badge bg-primary">
                        <?php echo formatDate($file['upload_date'], 'M d, Y'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Upload Time:</strong></td>
                <td>
                    <?php echo formatDate($file['created_at'], 'M d, Y h:i A'); ?>
                </td>
            </tr>
            <tr>
                <td><strong>Uploaded By:</strong></td>
                <td>
                    <strong><?php echo htmlspecialchars($file['uploaded_by_name']); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo htmlspecialchars($file['uploaded_by_email']); ?></small>
                </td>
            </tr>
            <tr>
                <td><strong>File Age:</strong></td>
                <td>
                    <?php 
                    $upload_time = strtotime($file['created_at']);
                    $age_seconds = time() - $upload_time;
                    $age_days = floor($age_seconds / 86400);
                    $age_hours = floor(($age_seconds % 86400) / 3600);
                    
                    if ($age_days > 0) {
                        echo $age_days . ' day' . ($age_days > 1 ? 's' : '') . ' ago';
                    } else {
                        echo $age_hours . ' hour' . ($age_hours > 1 ? 's' : '') . ' ago';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if ($file['description']): ?>
    <hr>
    <div class="row">
        <div class="col-12">
            <h6 class="text-primary mb-3">
                <i class="fas fa-sticky-note me-2"></i>Description
            </h6>
            <div class="alert alert-info">
                <?php echo nl2br(htmlspecialchars($file['description'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-download me-2"></i>Download Options
        </h6>
        
        <?php if ($file_exists): ?>
            <div class="d-grid gap-2">
                <a href="../uploads/biometrics/<?php echo $file['filename']; ?>" 
                   class="btn btn-success"
                   download="<?php echo $file['original_name']; ?>">
                    <i class="fas fa-download me-2"></i>Download Original File
                </a>
                
                <button class="btn btn-info" onclick="previewFile('<?php echo $file['filename']; ?>', '<?php echo $extension; ?>')">
                    <i class="fas fa-eye me-2"></i>Preview File Content
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Physical file not found. The file may have been moved or deleted.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-chart-bar me-2"></i>File Statistics
        </h6>
        
        <div class="row text-center">
            <div class="col-6">
                <div class="mb-3">
                    <i class="fas fa-hdd fa-2x text-primary mb-2"></i>
                    <h6>File Size</h6>
                    <p class="small text-muted"><?php echo $file_size_formatted; ?></p>
                </div>
            </div>
            <div class="col-6">
                <div class="mb-3">
                    <i class="fas fa-calendar fa-2x text-success mb-2"></i>
                    <h6>Upload Date</h6>
                    <p class="small text-muted"><?php echo formatDate($file['upload_date'], 'M d'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-between">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                File uploaded: <?php echo formatDate($file['created_at'], 'M d, Y h:i A'); ?>
            </small>
            
            <?php if ($file['updated_at'] && $file['updated_at'] !== $file['created_at']): ?>
                <small class="text-muted">
                    <i class="fas fa-edit me-1"></i>
                    Last updated: <?php echo formatDate($file['updated_at'], 'M d, Y h:i A'); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function previewFile(filename, extension) {
    if (extension === 'csv') {
        // For CSV files, show content in modal
        fetch(`../uploads/biometrics/${filename}`)
            .then(response => response.text())
            .then(content => {
                // Show first 1000 characters as preview
                const preview = content.substring(0, 1000);
                const truncated = content.length > 1000;
                
                let previewContent = `<pre class="bg-light p-3 rounded">${preview}</pre>`;
                if (truncated) {
                    previewContent += '<p class="text-muted mt-2">File truncated for preview. Download to see full content.</p>';
                }
                
                // Create preview modal
                const modal = new bootstrap.Modal(document.createElement('div'));
                modal.element.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">File Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                ${previewContent}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                `;
                modal.show();
            })
            .catch(error => {
                alert('Error previewing file: ' + error.message);
            });
    } else {
        // For Excel files, just show info
        alert('Excel file preview is not available. Please download the file to view its contents.');
    }
}
</script>
