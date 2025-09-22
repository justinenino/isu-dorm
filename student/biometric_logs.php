<?php
require_once '../config/database.php';
requireStudent();

$pdo = getConnection();

// Handle location update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_location') {
        $location_status = $_POST['location_status'];
        
        // Basic validation
        if (empty($location_status)) {
            $_SESSION['error'] = "Please select a location status.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO student_location_logs (student_id, location_status) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $location_status]);
                
                $_SESSION['success'] = "Your location has been updated successfully.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to update location. Please try again.";
            }
        }
        
        header("Location: biometric_logs.php");
        exit;
    }
}

$page_title = 'Biometric Logs';
include 'includes/header.php';

// Get biometric files
$stmt = $pdo->query("SELECT * FROM biometric_files ORDER BY upload_date DESC");
$biometric_files = $stmt->fetchAll();

// Get student's current location
$stmt = $pdo->prepare("SELECT location_status FROM student_location_logs WHERE student_id = ? ORDER BY timestamp DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$current_location = $stmt->fetchColumn();

// Get student's location history
$stmt = $pdo->prepare("SELECT * FROM student_location_logs WHERE student_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$location_history = $stmt->fetchAll();
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-fingerprint"></i> Biometric Logs</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateLocationModal">
        <i class="fas fa-map-marker-alt"></i> Update My Location
    </button>
</div>

<!-- Current Location Status -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Current Location Status</h5>
            </div>
            <div class="card-body">
                <?php if ($current_location): ?>
                    <?php
                    $status_class = '';
                    $status_icon = '';
                    $status_text = '';
                    switch ($current_location) {
                        case 'inside_dormitory':
                            $status_class = 'badge bg-success fs-6';
                            $status_icon = 'fas fa-home';
                            $status_text = 'Inside Dormitory';
                            break;
                        case 'in_class':
                            $status_class = 'badge bg-info fs-6';
                            $status_icon = 'fas fa-graduation-cap';
                            $status_text = 'In Class';
                            break;
                        case 'outside_campus':
                            $status_class = 'badge bg-danger fs-6';
                            $status_icon = 'fas fa-external-link-alt';
                            $status_text = 'Outside Campus';
                            break;
                    }
                    ?>
                    <div class="text-center">
                        <span class="<?php echo $status_class; ?>">
                            <i class="<?php echo $status_icon; ?>"></i>
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <span class="badge bg-secondary fs-6">
                            <i class="fas fa-question-circle"></i>
                            No Location Data
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i>
                        Your location status helps administrators monitor student safety and attendance.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Location History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($location_history)): ?>
                    <p class="text-muted">No location history available.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($location_history as $location): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch ($location['location_status']) {
                                        case 'inside_dormitory':
                                            $status_class = 'badge bg-success';
                                            $status_icon = 'fas fa-home';
                                            break;
                                        case 'in_class':
                                            $status_class = 'badge bg-info';
                                            $status_icon = 'fas fa-graduation-cap';
                                            break;
                                        case 'outside_campus':
                                            $status_class = 'badge bg-danger';
                                            $status_icon = 'fas fa-external-link-alt';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <i class="<?php echo $status_icon; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $location['location_status'])); ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, g:i A', strtotime($location['timestamp'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Biometric Files -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-download"></i> Available Biometric Files</h5>
    </div>
    <div class="card-body">
        <?php if (empty($biometric_files)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No biometric files are currently available for download.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped" id="biometricTable">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($biometric_files as $file): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-alt text-primary"></i>
                                    <?php echo htmlspecialchars($file['original_name']); ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($file['upload_date'])); ?></td>
                                <td>
                                    <a href="../<?php echo $file['file_path']; ?>" 
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

<!-- Update Location Modal -->
<div class="modal fade" id="updateLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update My Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_location">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Important:</strong> Please update your location status to help administrators monitor student safety and attendance.
                    </div>
                    
                    <div class="mb-3">
                        <label for="location_status" class="form-label">Current Location Status</label>
                        <select class="form-select" id="location_status" name="location_status" required>
                            <option value="">Select your current location...</option>
                            <option value="inside_dormitory">
                                <i class="fas fa-home"></i> Inside Dormitory
                            </option>
                            <option value="in_class">
                                <i class="fas fa-graduation-cap"></i> In Class
                            </option>
                            <option value="outside_campus">
                                <i class="fas fa-external-link-alt"></i> Outside Campus
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Location Guidelines:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-home text-success"></i> <strong>Inside Dormitory:</strong> When you're in your dorm room or common areas</li>
                            <li><i class="fas fa-graduation-cap text-info"></i> <strong>In Class:</strong> When you're attending classes or in academic buildings</li>
                            <li><i class="fas fa-external-link-alt text-danger"></i> <strong>Outside Campus:</strong> When you're off-campus (shopping, appointments, etc.)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#biometricTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 10
    });
});
</script>

<?php include 'includes/footer.php'; ?> 