<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$page_title = "Biometrics";
require_once 'includes/header.php';

// Get student info
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    // Get available biometric files
    $biometric_stmt = $pdo->prepare("
        SELECT 
            b.*,
            u.username as uploaded_by_name
        FROM biometrics b
        LEFT JOIN users u ON b.uploaded_by = u.id
        ORDER BY b.uploaded_at DESC
    ");
    $biometric_stmt->execute();
    $biometric_files = $biometric_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database error occurred.";
    $student = null;
    $biometric_files = [];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-white">Biometrics</h1>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Student Information -->
                <div class="col-lg-4">
                    <?php if ($student): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-white">Your Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                <p><strong>Student ID:</strong> <?php echo $student['student_id']; ?></p>
                                <p><strong>LRN:</strong> <?php echo $student['lrn']; ?></p>
                                <p><strong>School ID:</strong> <?php echo $student['school_id_number']; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Information Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">About Biometric Data</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                Biometric data is used for secure access control and attendance tracking within the dormitory.
                            </p>
                            
                            <h6>Data Includes:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-fingerprint text-primary"></i> Fingerprint templates</li>
                                <li><i class="fas fa-clock text-info"></i> Access timestamps</li>
                                <li><i class="fas fa-map-marker-alt text-success"></i> Entry/exit locations</li>
                                <li><i class="fas fa-shield-alt text-warning"></i> Security events</li>
                            </ul>
                            
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-lock"></i> 
                                Your biometric data is encrypted and securely stored.
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Available Downloads -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Available Biometric Files</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($biometric_files)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-fingerprint fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No biometric files available for download yet.</p>
                                    <small class="text-muted">Files will appear here once uploaded by the administrator.</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="biometricsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>File Size</th>
                                                <th>Upload Date</th>
                                                <th>Uploaded By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($biometric_files as $file): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-file-csv text-success me-2"></i>
                                                        <strong><?php echo htmlspecialchars($file['original_filename']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($file['description'] ?: 'No description'); ?></small>
                                                    </td>
                                                    <td><?php echo formatFileSize($file['file_size']); ?></td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($file['uploaded_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                                                    <td>
                                                        <a href="../uploads/biometrics/<?php echo htmlspecialchars($file['filename']); ?>" 
                                                           class="btn btn-sm btn-primary" download>
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                        <button class="btn btn-sm btn-info" onclick="viewFile(<?php echo $file['id']; ?>)">
                                                            <i class="fas fa-eye"></i> Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Usage Instructions -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">How to Use Your Biometric Data</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-download text-primary"></i> Downloading Files</h6>
                                    <ul class="list-unstyled">
                                        <li>• Click "Download" to save the file</li>
                                        <li>• Files are in CSV format</li>
                                        <li>• Can be opened in Excel or text editor</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-shield-alt text-success"></i> Data Security</h6>
                                    <ul class="list-unstyled">
                                        <li>• Data is anonymized for privacy</li>
                                        <li>• Only your records are included</li>
                                        <li>• Files are regularly updated</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> If you need specific date ranges or have questions about your biometric data, 
                                please contact the dormitory administration.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View File Details Modal -->
<div class="modal fade" id="viewFileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Biometric File Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fileDetails">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#biometricsTable').DataTable({
        order: [[2, 'desc']], // Sort by upload date descending
        pageLength: 10
    });
});

function viewFile(fileId) {
    // Load file details via AJAX
    $.post('get_biometric_details.php', {biometric_id: fileId}, function(response) {
        if (response.success) {
            $('#fileDetails').html(response.data);
            $('#viewFileModal').modal('show');
        } else {
            alert('Error loading file details: ' + response.message);
        }
    }, 'json');
}
</script>

<?php require_once 'includes/footer.php'; ?>
