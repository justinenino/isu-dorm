<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle student operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'approve_student') {
            $student_id = (int)$_POST['student_id'];
            $user_id = (int)$_POST['user_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Generate unique student ID
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id LIKE ?");
                $stmt->execute(['STU%']);
                $count = $stmt->fetchColumn();
                $new_student_id = 'STU' . str_pad(($count + 1), 6, '0', STR_PAD_LEFT);
                
                // Update student status and assign student ID
                $stmt = $pdo->prepare("UPDATE students SET status = 'approved', student_id = ?, approved_at = NOW(), approved_by = ? WHERE student_id = ?");
                $stmt->execute([$new_student_id, $_SESSION['user_id'], $student_id]);
                
                // Update user status
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $message = 'Student approved successfully! Student ID: ' . $new_student_id;
                logActivity($_SESSION['user_id'], 'Student approved: ' . $new_student_id);
                
            } catch (PDOException $e) {
                $error = 'Error approving student: ' . $e->getMessage();
            }
        } elseif ($action === 'reject_student') {
            $student_id = (int)$_POST['student_id'];
            $user_id = (int)$_POST['user_id'];
            $rejection_reason = sanitizeInput($_POST['rejection_reason']);
            
            try {
                $pdo = getDBConnection();
                
                // Update student status
                $stmt = $pdo->prepare("UPDATE students SET status = 'rejected', rejection_reason = ?, rejected_at = NOW(), rejected_by = ? WHERE student_id = ?");
                $stmt->execute([$rejection_reason, $_SESSION['user_id'], $student_id]);
                
                // Update user status
                $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $message = 'Student rejected successfully!';
                logActivity($_SESSION['user_id'], 'Student rejected: ID ' . $student_id);
                
            } catch (PDOException $e) {
                $error = 'Error rejecting student: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_student') {
            $student_id = (int)$_POST['student_id'];
            $user_id = (int)$_POST['user_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Check if student has active reservations
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE student_id = ? AND status IN ('pending', 'approved', 'occupied')");
                $stmt->execute([$student_id]);
                $reservationCount = $stmt->fetchColumn();
                
                if ($reservationCount > 0) {
                    $error = 'Cannot delete student with active reservations. Please cancel all reservations first.';
                } else {
                    // Delete student record
                    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                    $stmt->execute([$student_id]);
                    
                    // Delete user record
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    $message = 'Student deleted successfully!';
                    logActivity($_SESSION['user_id'], 'Student deleted: ID ' . $student_id);
                }
            } catch (PDOException $e) {
                $error = 'Error deleting student: ' . $e->getMessage();
            }
        }
    }
}

// Fetch students with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "s.status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.school_id_number LIKE ? OR s.lrn LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "
        SELECT s.*, u.username, u.email, u.status as user_status, u.created_at as registration_date,
               COUNT(r.reservation_id) as total_reservations,
               COUNT(CASE WHEN r.status = 'occupied' THEN 1 END) as active_reservations
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN reservations r ON s.student_id = r.student_id
        $where_clause
        GROUP BY s.student_id
        ORDER BY s.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for different statuses
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM students 
        GROUP BY status
    ");
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    $error = 'Error fetching students: ' . $e->getMessage();
    $students = [];
    $status_counts = [];
}

$page_title = "Students Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-users me-2"></i>Students Management
                </h1>
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

            <!-- Status Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Students
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo array_sum($status_counts); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                        Pending Approval
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['pending'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                        Approved Students
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['approved'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Rejected Students
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['rejected'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name, school ID, or LRN" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-2"></i>Students List
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Student Info</th>
                                    <th>Academic Details</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Reservations</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-3">
                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                    <?php if ($student['student_id'] && $student['student_id'] !== 'pending'): ?>
                                                        <br><small class="text-muted">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($student['gender']); ?> • <?php echo formatDate($student['date_of_birth'], 'M d, Y'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><strong>School ID:</strong> <?php echo htmlspecialchars($student['school_id_number']); ?></div>
                                            <div><strong>LRN:</strong> <?php echo htmlspecialchars($student['lrn']); ?></div>
                                            <?php if ($student['course']): ?>
                                                <div><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($student['mobile_number']); ?></div>
                                            <div><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($student['email']); ?></div>
                                            <?php if ($student['facebook_link']): ?>
                                                <div><i class="fab fa-facebook me-1"></i><a href="<?php echo htmlspecialchars($student['facebook_link']); ?>" target="_blank">Facebook</a></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($student['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    $statusText = 'Pending Approval';
                                                    break;
                                                case 'approved':
                                                    $statusClass = 'bg-success';
                                                    $statusText = 'Approved';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-danger';
                                                    $statusText = 'Rejected';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                                    $statusText = 'Unknown';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            <?php if ($student['status'] === 'rejected' && $student['rejection_reason']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($student['rejection_reason']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <div class="badge bg-info mb-1"><?php echo $student['total_reservations']; ?> Total</div>
                                                <?php if ($student['active_reservations'] > 0): ?>
                                                    <div class="badge bg-success"><?php echo $student['active_reservations']; ?> Active</div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($student['registration_date'], 'M d, Y'); ?></small>
                                            <br><small class="text-muted"><?php echo formatDate($student['registration_date'], 'h:i A'); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <button class="btn btn-sm btn-info mb-1" onclick="viewStudentProfile(<?php echo $student['student_id']; ?>)">
                                                    <i class="fas fa-eye"></i> View Profile
                                                </button>
                                                
                                                <?php if ($student['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success mb-1" onclick="approveStudent(<?php echo $student['student_id']; ?>, <?php echo $student['user_id']; ?>)">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger mb-1" onclick="rejectStudent(<?php echo $student['student_id']; ?>, <?php echo $student['user_id']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php elseif ($student['status'] === 'approved'): ?>
                                                    <button class="btn btn-sm btn-warning mb-1" onclick="viewDocuments(<?php echo $student['student_id']; ?>)">
                                                        <i class="fas fa-file-alt"></i> Documents
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['student_id']; ?>, <?php echo $student['user_id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Student Modal -->
<div class="modal fade" id="rejectStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Student Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_student">
                    <input type="hidden" name="student_id" id="reject_student_id">
                    <input type="hidden" name="user_id" id="reject_user_id">
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required 
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> This action will reject the student's application and they will not be able to access the system.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Forms -->
<form id="approveStudentForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve_student">
    <input type="hidden" name="student_id" id="approve_student_id">
    <input type="hidden" name="user_id" id="approve_user_id">
</form>

<form id="deleteStudentForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_student">
    <input type="hidden" name="student_id" id="delete_student_id">
    <input type="hidden" name="user_id" id="delete_user_id">
</form>

<?php include 'includes/footer.php'; ?>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#studentsTable').DataTable({
        order: [[5, 'desc']],
        pageLength: 25,
        responsive: true
    });
});

// Approve Student
function approveStudent(studentId, userId) {
    if (confirm('Are you sure you want to approve this student? This will generate a student ID and activate their account.')) {
        $('#approve_student_id').val(studentId);
        $('#approve_user_id').val(userId);
        $('#approveStudentForm').submit();
    }
}

// Reject Student
function rejectStudent(studentId, userId) {
    $('#reject_student_id').val(studentId);
    $('#reject_user_id').val(userId);
    $('#rejectStudentModal').modal('show');
}

// Delete Student
function deleteStudent(studentId, userId, studentName) {
    if (confirm(`Are you sure you want to delete "${studentName}"? This action cannot be undone and will remove all associated data.`)) {
        $('#delete_student_id').val(studentId);
        $('#delete_user_id').val(userId);
        $('#deleteStudentForm').submit();
    }
}

// View Student Profile
function viewStudentProfile(studentId) {
    // Implement profile view functionality
    alert('Profile view functionality will be implemented in the next phase');
}

// View Documents
function viewDocuments(studentId) {
    // Implement document view functionality
    alert('Document view functionality will be implemented in the next phase');
}
</script>
