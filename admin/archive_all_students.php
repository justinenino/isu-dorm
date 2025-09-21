<?php
/**
 * Archive All Students Script
 * This script allows admins to archive all students at once with proper safety measures
 */

require_once '../config/database.php';

// Check if admin is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Access denied. Admin login required.');
}

$page_title = 'Archive All Students';
include 'includes/header.php';

$success_message = '';
$error_message = '';
$stats = [];

try {
    $pdo = getConnection();
    
    // Get current statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) as active_students,
        SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as archived_students,
        SUM(CASE WHEN application_status = 'pending' AND is_deleted = 0 THEN 1 ELSE 0 END) as pending_students,
        SUM(CASE WHEN application_status = 'approved' AND is_deleted = 0 THEN 1 ELSE 0 END) as approved_students,
        SUM(CASE WHEN application_status = 'rejected' AND is_deleted = 0 THEN 1 ELSE 0 END) as rejected_students
        FROM students");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'archive_all') {
            $confirmation = $_POST['confirmation'] ?? '';
            $archive_reason = trim($_POST['archive_reason'] ?? '');
            
            // Validate confirmation
            if ($confirmation !== 'ARCHIVE ALL STUDENTS') {
                $error_message = 'Confirmation text does not match. Please type "ARCHIVE ALL STUDENTS" exactly.';
            } elseif (empty($archive_reason)) {
                $error_message = 'Please provide a reason for archiving all students.';
            } else {
                // Begin transaction
                $pdo->beginTransaction();
                
                try {
                    $admin_id = $_SESSION['user_id'];
                    $current_time = date('Y-m-d H:i:s');
                    
                    // Archive all active students and revoke access
                    $stmt = $pdo->prepare("UPDATE students 
                        SET is_deleted = 1, 
                            is_active = 0,
                            deleted_at = ?, 
                            deleted_by = ?,
                            updated_at = ?
                        WHERE is_deleted = 0");
                    
                    $result = $stmt->execute([$current_time, $admin_id, $current_time]);
                    $archived_count = $stmt->rowCount();
                    
                    if ($result && $archived_count > 0) {
                        // Log the bulk archive action
                        $stmt = $pdo->prepare("INSERT INTO admin_actions_log (admin_id, action, description, created_at) 
                            VALUES (?, 'bulk_archive', ?, ?)");
                        $stmt->execute([$admin_id, "Bulk archived $archived_count students. Reason: $archive_reason", $current_time]);
                        
                        $pdo->commit();
                        $success_message = "Successfully archived $archived_count students. Reason: $archive_reason";
                        
                        // Refresh statistics
                        $stmt = $pdo->query("SELECT 
                            COUNT(*) as total_students,
                            SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) as active_students,
                            SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as archived_students,
                            SUM(CASE WHEN application_status = 'pending' AND is_deleted = 0 THEN 1 ELSE 0 END) as pending_students,
                            SUM(CASE WHEN application_status = 'approved' AND is_deleted = 0 THEN 1 ELSE 0 END) as approved_students,
                            SUM(CASE WHEN application_status = 'rejected' AND is_deleted = 0 THEN 1 ELSE 0 END) as rejected_students
                            FROM students");
                        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $pdo->rollback();
                        $error_message = 'No students were archived. They may already be archived.';
                    }
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error_message = 'Error archiving students: ' . $e->getMessage();
                }
            }
        }
    }
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-archive"></i> Archive All Students</h2>
                <a href="reservation_management.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Reservations
                </a>
            </div>

            <!-- Success/Error Messages -->
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

            <!-- Current Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo $stats['total_students'] ?? 0; ?></h3>
                            <p class="mb-0">Total Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo $stats['active_students'] ?? 0; ?></h3>
                            <p class="mb-0">Active Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning"><?php echo $stats['archived_students'] ?? 0; ?></h3>
                            <p class="mb-0">Archived Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?php echo $stats['pending_students'] ?? 0; ?></h3>
                            <p class="mb-0">Pending Applications</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive All Form -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Archive All Active Students</h5>
                </div>
                <div class="card-body">
                    <?php if ($stats['active_students'] > 0): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-warning"></i> Warning: This action will archive ALL active students and revoke their access!</h6>
                            <ul class="mb-0">
                                <li><strong><?php echo $stats['active_students']; ?></strong> active students will be moved to archive</li>
                                <li><strong>ALL students will lose login access</strong> and cannot log in until restored</li>
                                <li>This includes <strong><?php echo $stats['pending_students']; ?></strong> pending applications</li>
                                <li>This includes <strong><?php echo $stats['approved_students']; ?></strong> approved students</li>
                                <li>This includes <strong><?php echo $stats['rejected_students']; ?></strong> rejected applications</li>
                                <li>Students can be restored individually from the reservation management page</li>
                                <li>This action is logged and cannot be undone automatically</li>
                            </ul>
                        </div>

                        <form method="POST" id="archiveAllForm">
                            <input type="hidden" name="action" value="archive_all">
                            
                            <div class="mb-3">
                                <label for="archive_reason" class="form-label">
                                    <i class="fas fa-comment"></i> Reason for Archiving All Students
                                </label>
                                <textarea class="form-control" id="archive_reason" name="archive_reason" rows="3" 
                                    placeholder="Please provide a reason for archiving all students..." required></textarea>
                                <div class="form-text">This reason will be logged for audit purposes.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirmation" class="form-label">
                                    <i class="fas fa-shield-alt"></i> Confirmation
                                </label>
                                <input type="text" class="form-control" id="confirmation" name="confirmation" 
                                    placeholder="Type 'ARCHIVE ALL STUDENTS' to confirm" required>
                                <div class="form-text">Type exactly: <strong>ARCHIVE ALL STUDENTS</strong></div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="reservation_management.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-warning" id="archiveBtn" disabled>
                                    <i class="fas fa-archive"></i> Archive All Students
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>No Active Students to Archive</strong><br>
                            All students are already archived or there are no students in the system.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Archive Log -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Archive Actions</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT aal.*, a.username as admin_name 
                            FROM admin_actions_log aal 
                            LEFT JOIN admins a ON aal.admin_id = a.id 
                            WHERE aal.action = 'bulk_archive' 
                            ORDER BY aal.created_at DESC 
                            LIMIT 10");
                        $recent_actions = $stmt->fetchAll();
                        
                        if ($recent_actions):
                    ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Admin</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_actions as $action): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y g:i A', strtotime($action['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($action['admin_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($action['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent archive actions found.</p>
                    <?php endif; ?>
                    <?php } catch (Exception $e) { ?>
                        <p class="text-muted">Unable to load recent actions.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmationInput = document.getElementById('confirmation');
    const archiveBtn = document.getElementById('archiveBtn');
    const archiveForm = document.getElementById('archiveAllForm');
    
    // Enable/disable archive button based on confirmation
    confirmationInput.addEventListener('input', function() {
        if (this.value === 'ARCHIVE ALL STUDENTS') {
            archiveBtn.disabled = false;
            archiveBtn.classList.remove('btn-warning');
            archiveBtn.classList.add('btn-danger');
        } else {
            archiveBtn.disabled = true;
            archiveBtn.classList.remove('btn-danger');
            archiveBtn.classList.add('btn-warning');
        }
    });
    
    // Final confirmation before submit
    archiveForm.addEventListener('submit', function(e) {
        if (!confirm('Are you absolutely sure you want to archive ALL active students?\n\nThis action cannot be undone automatically!')) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.card-header.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%) !important;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border: none;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-1px);
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}
</style>

<?php include 'includes/footer.php'; ?>
