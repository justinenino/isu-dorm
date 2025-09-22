<?php
require_once '../config/database.php';

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_file':
                $upload_date = $_POST['upload_date'];
                
                // Check if file was uploaded
                if (isset($_FILES['biometric_file']) && $_FILES['biometric_file']['error'] == 0) {
                    $file = $_FILES['biometric_file'];
                    $original_name = $file['name'];
                    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                    
                    // Validate file type
                    $allowed_extensions = ['csv', 'xlsx', 'xls', 'txt'];
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $_SESSION['error'] = "Invalid file type. Please upload CSV, Excel, or TXT files only.";
                        header("Location: biometric_management.php");
                        exit;
                    }
                    
                    // Generate unique filename
                    $filename = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = '../uploads/biometric_files/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0755, true);
                    }
                    
                    $file_path = $upload_path . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $pdo = getConnection();
                        $stmt = $pdo->prepare("INSERT INTO biometric_files (filename, original_name, file_path, upload_date, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$filename, $original_name, $file_path, $upload_date, $_SESSION['admin_id']]);
                        
                        $_SESSION['success'] = "Biometric file uploaded successfully.";
                    } else {
                        $_SESSION['error'] = "Error uploading file. Please try again.";
                    }
                } else {
                    $_SESSION['error'] = "Please select a file to upload.";
                }
                
                header("Location: biometric_management.php");
                exit;
                break;
                
            case 'delete_file':
                $file_id = $_POST['file_id'];
                
                // Get file details
                $pdo = getConnection();
                $stmt = $pdo->prepare("SELECT file_path FROM biometric_files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch();
                
                if ($file && file_exists($file['file_path'])) {
                    unlink($file['file_path']); // Delete physical file
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM biometric_files WHERE id = ?");
                $stmt->execute([$file_id]);
                
                $_SESSION['success'] = "Biometric file deleted successfully.";
                header("Location: biometric_management.php");
                exit;
                break;
        }
    }
}

$page_title = 'Biometric Management';
include 'includes/header.php';

$pdo = getConnection();

// Get all biometric files
$stmt = $pdo->query("SELECT bf.*, 
    CONCAT(adm.username) as uploaded_by_name
    FROM biometric_files bf
    LEFT JOIN admins adm ON bf.uploaded_by = adm.id
    ORDER BY bf.upload_date DESC, bf.created_at DESC");
$biometric_files = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-fingerprint"></i> Biometric Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
        <i class="fas fa-upload"></i> Upload Biometric File
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count($biometric_files); ?></h3>
            <p>Total Files</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($biometric_files, function($bf) { return strtotime($bf['upload_date']) == strtotime(date('Y-m-d')); })); ?></h3>
            <p>Today's Files</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($biometric_files, function($bf) { return strtotime($bf['upload_date']) > strtotime('-7 days'); })); ?></h3>
            <p>This Week</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo count(array_filter($biometric_files, function($bf) { return strtotime($bf['upload_date']) > strtotime('-30 days'); })); ?></h3>
            <p>This Month</p>
        </div>
    </div>
</div>

<!-- Biometric Files Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Biometric Files</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="biometricTable">
                <thead>
                    <tr>
                        <th>Original Filename</th>
                        <th>File Type</th>
                        <th>Upload Date</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($biometric_files as $file): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($file['original_name']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                $extension_class = '';
                                switch ($extension) {
                                    case 'csv': $extension_class = 'badge bg-success'; break;
                                    case 'xlsx': 
                                    case 'xls': $extension_class = 'badge bg-primary'; break;
                                    case 'txt': $extension_class = 'badge bg-info'; break;
                                    default: $extension_class = 'badge bg-secondary'; break;
                                }
                                ?>
                                <span class="<?php echo $extension_class; ?>"><?php echo strtoupper($extension); ?></span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($file['upload_date'])); ?></td>
                            <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($file['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo $file['file_path']; ?>" class="btn btn-sm btn-outline-primary" download>
                                    <i class="fas fa-download"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(<?php echo $file['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upload File Modal -->
<div class="modal fade" id="uploadFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Biometric File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_file">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" name="biometric_file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                        <small class="form-text text-muted">Supported formats: CSV, Excel (XLSX, XLS), TXT</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Date</label>
                        <input type="date" name="upload_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        <small class="form-text text-muted">Date for which this biometric data applies</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_file">
    <input type="hidden" name="file_id" id="deleteFileId">
</form>

<script>
$(document).ready(function() {
    $('#biometricTable').DataTable({
        order: [[4, 'desc']],
        pageLength: 25
    });
});

function deleteFile(id) {
    if (confirm('Are you sure you want to delete this biometric file? This action cannot be undone.')) {
        $('#deleteFileId').val(id);
        $('#deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 