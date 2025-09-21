<?php
/**
 * Restore All Students Script
 * This script allows admins to restore all archived students at once
 */

require_once '../config/database.php';

// Check if admin is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Access denied. Admin login required.');
}

$success_message = '';
$error_message = '';
$stats = [];

try {
    $pdo = getConnection();
    
    // Get current statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) as active_students,
        SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as archived_students
        FROM students");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'restore_all') {
            $confirmation = $_POST['confirmation'] ?? '';
            $restore_reason = trim($_POST['restore_reason'] ?? '');
            
            // Validate confirmation
            if ($confirmation !== 'RESTORE ALL STUDENTS') {
                $error_message = 'Confirmation text does not match. Please type "RESTORE ALL STUDENTS" exactly.';
            } elseif (empty($restore_reason)) {
                $error_message = 'Please provide a reason for restoring all students.';
            } else {
                // Begin transaction
                $pdo->beginTransaction();
                
                try {
                    $admin_id = $_SESSION['user_id'];
                    $current_time = date('Y-m-d H:i:s');
                    
                    // Restore all archived students and grant access
                    $stmt = $pdo->prepare("UPDATE students 
                        SET is_deleted = 0, 
                            is_active = 1,
                            deleted_at = NULL, 
                            deleted_by = NULL,
                            updated_at = ?
                        WHERE is_deleted = 1");
                    
                    $result = $stmt->execute([$current_time]);
                    $restored_count = $stmt->rowCount();
                    
                    if ($result && $restored_count > 0) {
                        // Log the bulk restore action
                        $stmt = $pdo->prepare("INSERT INTO admin_actions_log (admin_id, action, description, created_at) 
                            VALUES (?, 'bulk_restore', ?, ?)");
                        $stmt->execute([$admin_id, "Bulk restored $restored_count students. Reason: $restore_reason", $current_time]);
                        
                        $pdo->commit();
                        $success_message = "Successfully restored $restored_count students. Reason: $restore_reason";
                        
                        // Refresh statistics
                        $stmt = $pdo->query("SELECT 
                            COUNT(*) as total_students,
                            SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) as active_students,
                            SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as archived_students
                            FROM students");
                        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $pdo->rollback();
                        $error_message = 'No students were restored. They may already be active.';
                    }
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error_message = 'Error restoring students: ' . $e->getMessage();
                }
            }
        }
    }
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}

$page_title = 'Restore All Students';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-box-open"></i> Restore All Students</h2>
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
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo $stats['total_students'] ?? 0; ?></h3>
                            <p class="mb-0">Total Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo $stats['active_students'] ?? 0; ?></h3>
                            <p class="mb-0">Active Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning"><?php echo $stats['archived_students'] ?? 0; ?></h3>
                            <p class="mb-0">Archived Students</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restore All Form -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-box-open"></i> Restore All Archived Students</h5>
                </div>
                <div class="card-body">
                    <?php if ($stats['archived_students'] > 0): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> This action will restore ALL archived students and grant them access!</h6>
                            <ul class="mb-0">
                                <li><strong><?php echo $stats['archived_students']; ?></strong> archived students will be restored to active status</li>
                                <li><strong>ALL students will regain login access</strong> and can log in again</li>
                                <li>All student data will remain intact</li>
                                <li>Students will appear in the active students list again</li>
                                <li>This action is logged for audit purposes</li>
                            </ul>
                        </div>

                        <form method="POST" id="restoreAllForm">
                            <input type="hidden" name="action" value="restore_all">
                            
                            <div class="mb-3">
                                <label for="restore_reason" class="form-label">
                                    <i class="fas fa-comment"></i> Reason for Restoring All Students
                                </label>
                                <textarea class="form-control" id="restore_reason" name="restore_reason" rows="3" 
                                    placeholder="Please provide a reason for restoring all students..." required></textarea>
                                <div class="form-text">This reason will be logged for audit purposes.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirmation" class="form-label">
                                    <i class="fas fa-shield-alt"></i> Confirmation
                                </label>
                                <input type="text" class="form-control" id="confirmation" name="confirmation" 
                                    placeholder="Type 'RESTORE ALL STUDENTS' to confirm" required>
                                <div class="form-text">Type exactly: <strong>RESTORE ALL STUDENTS</strong></div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="reservation_management.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success" id="restoreBtn" disabled>
                                    <i class="fas fa-box-open"></i> Restore All Students
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>No Archived Students to Restore</strong><br>
                            All students are already active or there are no students in the system.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmationInput = document.getElementById('confirmation');
    const restoreBtn = document.getElementById('restoreBtn');
    const restoreForm = document.getElementById('restoreAllForm');
    
    if (confirmationInput && restoreBtn && restoreForm) {
        // Enable/disable restore button based on confirmation
        confirmationInput.addEventListener('input', function() {
            if (this.value === 'RESTORE ALL STUDENTS') {
                restoreBtn.disabled = false;
                restoreBtn.classList.remove('btn-success');
                restoreBtn.classList.add('btn-primary');
            } else {
                restoreBtn.disabled = true;
                restoreBtn.classList.remove('btn-primary');
                restoreBtn.classList.add('btn-success');
            }
        });
        
        // Final confirmation before submit
        restoreForm.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to restore ALL archived students?')) {
                e.preventDefault();
            }
        });
    }
});
</script>

<style>
.card-header.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
    transform: translateY(-1px);
}

.alert-info {
    border-left: 4px solid #17a2b8;
}
</style>

<?php include 'includes/footer.php'; ?>
