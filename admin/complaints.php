<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$page_title = "Complaints Management";
require_once 'includes/header.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $complaint_id = sanitizeInput($_POST['complaint_id']);
    
    if ($_POST['action'] === 'update_status') {
        $status = sanitizeInput($_POST['status']);
        $admin_response = sanitizeInput($_POST['admin_response']);
        
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE complaints 
                SET status = ?, admin_response = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$status, $admin_response, $complaint_id])) {
                logActivity($_SESSION['user_id'], "Updated complaint #$complaint_id status to $status");
                $success_message = "Complaint status updated successfully!";
            } else {
                $error_message = "Failed to update complaint status.";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = "Database error occurred.";
        }
    } elseif ($_POST['action'] === 'delete') {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
            
            if ($stmt->execute([$complaint_id])) {
                logActivity($_SESSION['user_id'], "Deleted complaint #$complaint_id");
                $success_message = "Complaint deleted successfully!";
            } else {
                $error_message = "Failed to delete complaint.";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = "Failed to delete complaint.";
        }
    }
}

// Get complaints with filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(c.title LIKE ? OR c.description LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "c.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($priority_filter)) {
        $where_conditions[] = "c.priority = ?";
        $params[] = $priority_filter;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get complaints
    $sql = "
        SELECT 
            c.*,
            s.first_name,
            s.last_name,
            s.middle_name,
            s.student_id,
            s.mobile_number,
            s.email
        FROM complaints c
        LEFT JOIN students s ON c.student_id = s.id
        $where_clause
        ORDER BY c.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll();
    
    // Get statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority
        FROM complaints
    ";
    
    $stats_stmt = $pdo->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database error occurred.";
    $complaints = [];
    $stats = [];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-white">Complaints Management</h1>
                <div>
                    <a href="export_complaints.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success me-2">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Complaints</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">In Progress</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['in_progress'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Resolved</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['resolved'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Closed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['closed'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">High Priority</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['high_priority'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search complaints..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="priority">
                                <option value="">All Priorities</option>
                                <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Complaints Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Complaints List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="complaintsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo $complaint['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($complaint['title']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo substr(htmlspecialchars($complaint['description']), 0, 100); ?>...</small>
                                        </td>
                                        <td>
                                            <?php 
                                            $student_name = $complaint['first_name'] . ' ' . $complaint['last_name'];
                                            if ($complaint['middle_name']) {
                                                $student_name = $complaint['first_name'] . ' ' . $complaint['middle_name'] . ' ' . $complaint['last_name'];
                                            }
                                            echo htmlspecialchars($student_name);
                                            ?>
                                            <br>
                                            <small class="text-muted"><?php echo $complaint['student_id']; ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $priority_class = '';
                                            switch ($complaint['priority']) {
                                                case 'low':
                                                    $priority_class = 'success';
                                                    break;
                                                case 'medium':
                                                    $priority_class = 'warning';
                                                    break;
                                                case 'high':
                                                    $priority_class = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $priority_class; ?>">
                                                <?php echo ucfirst($complaint['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($complaint['status']) {
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'in_progress':
                                                    $status_class = 'info';
                                                    break;
                                                case 'resolved':
                                                    $status_class = 'success';
                                                    break;
                                                case 'closed':
                                                    $status_class = 'secondary';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $complaint['id']; ?>, '<?php echo $complaint['status']; ?>')">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteComplaint(<?php echo $complaint['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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

<!-- View Complaint Modal -->
<div class="modal fade" id="viewComplaintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complaint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Complaint Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="complaint_id" id="updateComplaintId">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="updateStatus" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_response" class="form-label">Admin Response</label>
                        <textarea class="form-control" name="admin_response" id="adminResponse" rows="4" placeholder="Enter your response or notes..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this complaint? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="complaint_id" id="deleteComplaintId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#complaintsTable').DataTable({
        order: [[5, 'desc']], // Sort by created date descending
        pageLength: 25
    });
});

function viewComplaint(complaintId) {
    // Load complaint details via AJAX
    $.post('get_complaint_details.php', {complaint_id: complaintId}, function(response) {
        if (response.success) {
            $('#complaintDetails').html(response.data);
            $('#viewComplaintModal').modal('show');
        } else {
            alert('Error loading complaint details: ' + response.message);
        }
    }, 'json');
}

function updateStatus(complaintId, currentStatus) {
    $('#updateComplaintId').val(complaintId);
    $('#updateStatus').val(currentStatus);
    $('#adminResponse').val('');
    $('#updateStatusModal').modal('show');
}

function deleteComplaint(complaintId) {
    $('#deleteComplaintId').val(complaintId);
    $('#deleteComplaintModal').modal('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>
