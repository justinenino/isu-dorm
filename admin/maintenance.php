<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle status updates
if ($_POST && isset($_POST['update_status'])) {
    $request_id = sanitizeInput($_POST['request_id']);
    $new_status = sanitizeInput($_POST['new_status']);
    $assigned_to = sanitizeInput($_POST['assigned_to']);
    $admin_notes = sanitizeInput($_POST['admin_notes']);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE maintenance_requests 
            SET status = ?, assigned_to = ?, admin_notes = ?, updated_at = NOW() 
            WHERE request_id = ?
        ");
        
        if ($stmt->execute([$new_status, $assigned_to, $admin_notes, $request_id])) {
            $message = 'Maintenance request status updated successfully.';
            logActivity($_SESSION['user_id'], "Updated maintenance request ID: $request_id to status: $new_status");
        } else {
            $error = 'Error updating maintenance request.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Handle request deletion
if ($_POST && isset($_POST['delete_request'])) {
    $request_id = sanitizeInput($_POST['request_id']);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE request_id = ?");
        
        if ($stmt->execute([$request_id])) {
            $message = 'Maintenance request deleted successfully.';
            logActivity($_SESSION['user_id'], "Deleted maintenance request ID: $request_id");
        } else {
            $error = 'Error deleting maintenance request.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch maintenance requests with filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(mr.title LIKE ? OR mr.description LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if ($status_filter) {
        $where_conditions[] = "mr.status = ?";
        $params[] = $status_filter;
    }
    
    if ($priority_filter) {
        $where_conditions[] = "mr.priority = ?";
        $params[] = $priority_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT mr.*, s.first_name, s.last_name, s.student_id as student_id_number,
               r.room_number, b.building_name
        FROM maintenance_requests mr
        JOIN students s ON mr.student_id = s.user_id
        LEFT JOIN rooms r ON mr.room_id = r.room_id
        LEFT JOIN buildings b ON r.building_id = b.building_id
        $where_clause
        ORDER BY 
            CASE mr.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            mr.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $maintenance_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as assigned,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority
        FROM maintenance_requests
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $maintenance_requests = [];
    $stats = ['total_requests' => 0, 'pending' => 0, 'assigned' => 0, 'in_progress' => 0, 'resolved' => 0, 'high_priority' => 0];
}

$page_title = "Maintenance Requests";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-tools me-2"></i>Maintenance Requests
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" onclick="exportMaintenance()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <button class="btn btn-primary" onclick="printMaintenance()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
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
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Requests
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_requests']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tools fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['pending']; ?>
                                    </div>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Assigned
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['assigned']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        In Progress
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['in_progress']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cogs fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Resolved
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['resolved']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        High Priority
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['high_priority']; ?>
                                    </div>
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
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Title, description, or student name...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="">All Priorities</option>
                                <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Maintenance Requests Table -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Maintenance Requests
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="maintenanceTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Location</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($maintenance_requests)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No maintenance requests found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($maintenance_requests as $request): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($request['description'], 0, 100)) . (strlen($request['description']) > 100 ? '...' : ''); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($request['student_id_number']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($request['room_number']): ?>
                                                    <strong><?php echo htmlspecialchars($request['room_number']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['building_name']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">General</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $priority_badges = [
                                                    'high' => 'bg-danger',
                                                    'medium' => 'bg-warning',
                                                    'low' => 'bg-success'
                                                ];
                                                $badge_class = $priority_badges[$request['priority']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($request['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_badges = [
                                                    'pending' => 'bg-warning',
                                                    'assigned' => 'bg-info',
                                                    'in_progress' => 'bg-secondary',
                                                    'resolved' => 'bg-success'
                                                ];
                                                $badge_class = $status_badges[$request['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo formatDate($request['created_at'], 'M d, Y h:i A'); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            onclick="updateStatus(<?php echo $request['request_id']; ?>, '<?php echo $request['status']; ?>', '<?php echo htmlspecialchars($request['title']); ?>')" 
                                                            title="Update Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Delete this maintenance request? This action cannot be undone.')">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                        <button type="submit" name="delete_request" class="btn btn-sm btn-danger" 
                                                                title="Delete Request">
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

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Update Maintenance Request Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="request_id" id="update_request_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Request Title</label>
                        <input type="text" class="form-control" id="update_request_title" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status *</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assigned To</label>
                        <input type="text" class="form-control" id="assigned_to" name="assigned_to" 
                               placeholder="Maintenance staff name or department...">
                        <small class="form-text text-muted">Leave blank if not assigning to specific person</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Additional notes, instructions, or updates..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tools me-2"></i>Maintenance Request Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(requestId, currentStatus, requestTitle) {
    document.getElementById('update_request_id').value = requestId;
    document.getElementById('update_request_title').value = requestTitle;
    document.getElementById('new_status').value = currentStatus;
    
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function viewRequestDetails(requestId) {
    // Load request details via AJAX
    fetch(`get_maintenance_details.php?request_id=${requestId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('requestDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('requestDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading request details');
        });
}

function exportMaintenance() {
    // Create export URL with current filters
    const search = document.getElementById('search').value;
    const status = document.getElementById('status').value;
    const priority = document.getElementById('priority').value;
    
    let exportUrl = 'export_maintenance.php?';
    if (search) exportUrl += `search=${encodeURIComponent(search)}&`;
    if (status) exportUrl += `status=${encodeURIComponent(status)}&`;
    if (priority) exportUrl += `priority=${encodeURIComponent(priority)}&`;
    
    window.open(exportUrl, '_blank');
}

function printMaintenance() {
    window.print();
}

// Initialize DataTable
$(document).ready(function() {
    $('#maintenanceTable').DataTable({
        pageLength: 25,
        order: [[4, 'asc'], [3, 'asc'], [5, 'desc']], // Sort by status, priority, then created date
        responsive: true,
        language: {
            search: "Search requests:",
            lengthMenu: "Show _MENU_ requests per page",
            info: "Showing _START_ to _END_ of _TOTAL_ requests"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
