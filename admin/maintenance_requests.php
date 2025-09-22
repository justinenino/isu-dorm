<?php
require_once '../config/database.php';

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $request_id = $_POST['request_id'];
                $status = $_POST['status'];
                $assigned_to = $_POST['assigned_to'];
                
                try {
                    $pdo = getConnection();
                    
                    // Prevent admin from marking requests as completed
                    if ($status == 'completed') {
                        $_SESSION['error'] = "Only students can mark maintenance requests as completed.";
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ?, assigned_to = ?, completed_at = ? WHERE id = ?");
                    $completed_at = ($status == 'completed') ? date('Y-m-d H:i:s') : null;
                    $result = $stmt->execute([$status, $assigned_to, $completed_at, $request_id]);
                    
                    if ($result) {
                        $_SESSION['success'] = "Maintenance request updated successfully.";
                    } else {
                        $_SESSION['error'] = "Failed to update maintenance request.";
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                header("Location: maintenance_requests.php");
                exit;
                break;
                
            case 'flush_maintenance_requests':
                try {
                    $pdo = getConnection();
                    
                    // Delete all maintenance-related data
                    $pdo->exec("DELETE FROM maintenance_notifications");
                    $pdo->exec("DELETE FROM maintenance_status_history");
                    $pdo->exec("DELETE FROM maintenance_requests");
                    
                    $_SESSION['success'] = "All maintenance requests and related data have been deleted successfully.";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                header("Location: maintenance_requests.php");
                exit;
                break;
                
        }
    }
}

$page_title = 'Maintenance Request Management';
include 'includes/header.php';

try {
    $pdo = getConnection();
    
    // Pagination parameters
    $items_per_page = 10;
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get total count for pagination
    $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM maintenance_requests mr
        JOIN students s ON mr.student_id = s.id
        LEFT JOIN rooms r ON mr.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id");
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / $items_per_page);
    
    // Get maintenance requests with student and room details (paginated)
    $stmt = $pdo->prepare("SELECT mr.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.school_id,
        s.email,
        s.mobile_number as phone,
        r.room_number,
        b.name as building_name
        FROM maintenance_requests mr
        JOIN students s ON mr.student_id = s.id
        LEFT JOIN rooms r ON mr.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY mr.created_at DESC
        LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', intval($items_per_page), PDO::PARAM_INT);
    $stmt->bindValue(':offset', intval($offset), PDO::PARAM_INT);
    $stmt->execute();
    $maintenance_requests = $stmt->fetchAll();
    
    // Get all maintenance requests for statistics (not paginated)
    $all_requests_stmt = $pdo->query("SELECT mr.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.school_id,
        s.email,
        s.mobile_number as phone,
        r.room_number,
        b.name as building_name
        FROM maintenance_requests mr
        JOIN students s ON mr.student_id = s.id
        LEFT JOIN rooms r ON mr.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY mr.created_at DESC");
    $all_maintenance_requests = $all_requests_stmt->fetchAll();
    
    // Get buildings for filter
    $buildings_stmt = $pdo->query("SELECT DISTINCT b.name FROM buildings b 
        JOIN rooms r ON b.id = r.building_id 
        JOIN maintenance_requests mr ON r.id = mr.room_id 
        ORDER BY b.name");
    $buildings = $buildings_stmt->fetchAll();
    
} catch (Exception $e) {
    $maintenance_requests = [];
    $all_maintenance_requests = [];
    $buildings = [];
    $total_items = 0;
    $total_pages = 0;
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tools"></i> Maintenance Request Management</h2>
    <div>
        <button class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#flushMaintenanceModal">
            <i class="fas fa-trash-alt"></i> Flush All Data
        </button>
        <button class="btn btn-primary" onclick="refreshPage()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($all_maintenance_requests, function($mr) { return $mr['status'] == 'pending'; })); ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($all_maintenance_requests, function($mr) { return $mr['status'] == 'in_progress'; })); ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($all_maintenance_requests, function($mr) { return $mr['status'] == 'completed'; })); ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
            <h3><?php echo count(array_filter($all_maintenance_requests, function($mr) { return $mr['priority'] == 'urgent'; })); ?></h3>
            <p>Urgent</p>
        </div>
    </div>
</div>

<!-- Advanced Filter & Search Controls -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-filter"></i> Advanced Filters & Search</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Priority</label>
                <select id="priorityFilter" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Building</label>
                <select id="buildingFilter" class="form-select">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings as $building): ?>
                        <option value="<?php echo htmlspecialchars($building['name']); ?>"><?php echo htmlspecialchars($building['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" id="dateFromFilter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" id="dateToFilter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Actions</label>
                <button id="resetFilters" class="btn reset-btn w-100">Reset Filters</button>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search within current page...">
                    <button class="btn btn-outline-primary" type="button" id="searchBtn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Quick Actions</label>
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-outline-success" id="exportBtn">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button type="button" class="btn btn-outline-info" id="refreshBtn">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Requests Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            Maintenance Requests 
            <span class="badge bg-primary"><?php echo $total_items; ?> total</span>
            <?php if ($total_pages > 1): ?>
                <span class="badge bg-secondary">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
            <?php endif; ?>
        </h5>
        <div class="text-muted">
            Showing <?php echo count($maintenance_requests); ?> of <?php echo $total_items; ?> requests
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="maintenanceTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Submitted</th>
                        <th>Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($maintenance_requests)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                No maintenance requests found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($maintenance_requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['student_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['school_id']); ?></small>
                                </td>
                                <td>
                                    <?php if ($request['room_number']): ?>
                                        <?php echo htmlspecialchars($request['building_name'] . ' - ' . $request['room_number']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['title']); ?></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($request['description']); ?>">
                                        <?php echo htmlspecialchars($request['description']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $priority_class = '';
                                    switch ($request['priority']) {
                                        case 'low': $priority_class = 'badge bg-info'; break;
                                        case 'medium': $priority_class = 'badge bg-warning'; break;
                                        case 'high': $priority_class = 'badge bg-danger'; break;
                                        case 'urgent': $priority_class = 'badge bg-dark'; break;
                                    }
                                    ?>
                                    <span class="<?php echo $priority_class; ?>"><?php echo ucfirst($request['priority']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($request['status']) {
                                        case 'pending': $status_class = 'badge bg-warning'; break;
                                        case 'in_progress': $status_class = 'badge bg-info'; break;
                                        case 'completed': $status_class = 'badge bg-success'; break;
                                        case 'cancelled': $status_class = 'badge bg-secondary'; break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?></span>
                                </td>
                                <td>
                                    <?php echo $request['assigned_to'] ? htmlspecialchars($request['assigned_to']) : '<span class="text-muted">Not assigned</span>'; ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php if ($request['completed_at']): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($request['completed_at'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal" 
                                                data-request='<?php echo htmlspecialchars(json_encode($request)); ?>' title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($request['status'] != 'completed' && $request['status'] != 'cancelled'): ?>
                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateRequestModal" 
                                                    data-request-id="<?php echo $request['id']; ?>" title="Update Status">
                                                <i class="fas fa-tasks"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing <?php echo (($current_page - 1) * $items_per_page) + 1; ?> to 
                <?php echo min($current_page * $items_per_page, $total_items); ?> of <?php echo $total_items; ?> entries
            </div>
            
            <nav aria-label="Maintenance requests pagination">
                <ul class="pagination mb-0">
                    <!-- Previous Page -->
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </span>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Show first page if not in range
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page numbers in range -->
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Show last page if not in range -->
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Next Page -->
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Maintenance Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>


<!-- Update Request Modal -->
<div class="modal fade" id="updateRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Maintenance Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="updateRequestForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="request_id" id="updateRequestId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="updateStatus" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <input type="text" name="assigned_to" id="updateAssignedTo" class="form-control" placeholder="Enter staff name or department">
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

<style>
.reset-btn {
    background: linear-gradient(90deg, #28a745, #ffc107);
    color: #fff;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    transition: 0.3s ease-in-out;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
.reset-btn:hover {
    background: linear-gradient(90deg, #218838, #e0a800);
    transform: scale(1.05);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card h3 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stats-card p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.9;
}

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    border-color: #e9ecef;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
}

.modal-header .btn-close {
    filter: invert(1);
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    border-radius: 20px;
}

@media (max-width: 768px) {
    .stats-card h3 {
        font-size: 2rem;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}

@media (max-width: 576px) {
    .col-md-2, .col-md-3, .col-md-4 {
        margin-bottom: 0.5rem;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
// Function to refresh page while preserving current page
function refreshPage() {
    const currentPage = new URLSearchParams(window.location.search).get('page') || '1';
    window.location.href = '?page=' + currentPage;
}

$(document).ready(function() {
    // Note: Using custom pagination instead of DataTables
    // Table is already sorted by submitted date in PHP query

    // Simple filtering function (redirects to filtered page)
    function filterTable() {
        // Prevent multiple simultaneous searches
        if (isSearching) return;
        isSearching = true;
        
        var status = $('#statusFilter').val();
        var priority = $('#priorityFilter').val();
        var building = $('#buildingFilter').val();
        var dateFrom = $('#dateFromFilter').val();
        var dateTo = $('#dateToFilter').val();
        var search = $('#searchInput').val().trim();
        
        // Show loading indicator
        $('#searchBtn').html('<i class="fas fa-spinner fa-spin"></i> Searching...');
        $('#searchBtn').prop('disabled', true);
        
        // Build query parameters
        var params = new URLSearchParams();
        if (status) params.append('status', status);
        if (priority) params.append('priority', priority);
        if (building) params.append('building', building);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        if (search) params.append('search', search);
        
        // Redirect to filtered page
        var queryString = params.toString();
        window.location.href = '?' + (queryString ? queryString + '&' : '') + 'page=1';
    }

    // Event listeners for filters
    $('#statusFilter, #priorityFilter, #buildingFilter, #dateFromFilter, #dateToFilter').on('change', function() {
        filterTable();
    });
    
    // Client-side search functionality (no page redirect)
    var searchTimeout;
    
    $('#searchInput').on('input', function(e) {
        clearTimeout(searchTimeout);
        var searchValue = $(this).val().trim();
        
        // Debounce the search
        searchTimeout = setTimeout(function() {
            performClientSideSearch(searchValue);
        }, 500); // Wait 500ms after user stops typing
    });
    
    // Handle Enter key
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            clearTimeout(searchTimeout);
            var searchValue = $(this).val().trim();
            performClientSideSearch(searchValue);
        }
    });
    
    // Client-side search function
    function performClientSideSearch(searchTerm) {
        var rows = $('#maintenanceTable tbody tr');
        var visibleCount = 0;
        
        rows.each(function() {
            var row = $(this);
            var text = row.text().toLowerCase();
            var searchLower = searchTerm.toLowerCase();
            
            if (searchTerm === '' || text.includes(searchLower)) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        
        // Update the "Showing X of Y entries" text
        var totalRows = rows.length;
        var currentPage = <?php echo $current_page; ?>;
        var itemsPerPage = <?php echo $items_per_page; ?>;
        var startItem = ((currentPage - 1) * itemsPerPage) + 1;
        var endItem = Math.min(currentPage * itemsPerPage, totalRows);
        
        if (searchTerm !== '') {
            $('#entriesInfo').text('Showing ' + visibleCount + ' filtered entries');
        } else {
            $('#entriesInfo').text('Showing ' + startItem + ' to ' + endItem + ' of ' + totalRows + ' entries');
        }
    }

    // Search button
    $('#searchBtn').on('click', function() {
        var searchValue = $('#searchInput').val().trim();
        performClientSideSearch(searchValue);
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#statusFilter').val('');
        $('#priorityFilter').val('');
        $('#buildingFilter').val('');
        $('#dateFromFilter').val('');
        $('#dateToFilter').val('');
        $('#searchInput').val('');
        
        // Clear client-side search
        performClientSideSearch('');
        
        // Redirect to page 1 without filters
        window.location.href = '?page=1';
    });

    // Refresh button
    $('#refreshBtn').on('click', function() {
        refreshPage();
    });

    // Export functionality (simplified)
    $('#exportBtn').on('click', function() {
        alert('Export functionality will be implemented in a future update.');
    });

    // Handle view request modal
    $('#viewRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var request = button.data('request');
        var modal = $(this);
        
        var priorityClass = '';
        switch (request.priority) {
            case 'low': priorityClass = 'badge bg-info'; break;
            case 'medium': priorityClass = 'badge bg-warning'; break;
            case 'high': priorityClass = 'badge bg-danger'; break;
            case 'urgent': priorityClass = 'badge bg-dark'; break;
        }
        
        var statusClass = '';
        switch (request.status) {
            case 'pending': statusClass = 'badge bg-warning'; break;
            case 'in_progress': statusClass = 'badge bg-info'; break;
            case 'completed': statusClass = 'badge bg-success'; break;
            case 'cancelled': statusClass = 'badge bg-secondary'; break;
        }
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-user"></i> Student Information</h6>
                    <p><strong>Name:</strong> ${request.student_name}</p>
                    <p><strong>School ID:</strong> ${request.school_id}</p>
                    <p><strong>Email:</strong> ${request.email || 'Not provided'}</p>
                    <p><strong>Phone:</strong> ${request.phone || 'Not provided'}</p>
                    <p><strong>Room:</strong> ${request.building_name} - ${request.room_number || 'Not specified'}</p>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-tools"></i> Request Details</h6>
                    <p><strong>Title:</strong> ${request.title}</p>
                    <p><strong>Priority:</strong> <span class="${priorityClass}">${request.priority.charAt(0).toUpperCase() + request.priority.slice(1)}</span></p>
                    <p><strong>Status:</strong> <span class="${statusClass}">${request.status.replace('_', ' ').charAt(0).toUpperCase() + request.status.replace('_', ' ').slice(1)}</span></p>
                    <p><strong>Submitted:</strong> ${new Date(request.created_at).toLocaleString()}</p>
                    ${request.completed_at ? `<p><strong>Completed:</strong> ${new Date(request.completed_at).toLocaleString()}</p>` : ''}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-align-left"></i> Description</h6>
                    <div class="border p-3 rounded bg-light">
                        <p class="mb-0">${request.description}</p>
                    </div>
                </div>
            </div>
            ${request.assigned_to ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-user-tie"></i> Assignment</h6>
                    <p><strong>Assigned To:</strong> ${request.assigned_to}</p>
                </div>
            </div>
            ` : ''}
        `;
        
        modal.find('#requestDetails').html(content);
    });


    // Handle update request modal
    $('#updateRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var requestId = button.data('request-id');
        $('#updateRequestId').val(requestId);
    });

    // Form validation
    $('#updateRequestForm').on('submit', function(e) {
        var form = $(this);
        var isValid = true;
        
        form.find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });

    // Auto-refresh every 5 minutes (preserve current page)
    setInterval(function() {
        if (!$('.modal').hasClass('show')) {
            const currentPage = new URLSearchParams(window.location.search).get('page') || '1';
            window.location.href = '?page=' + currentPage;
        }
    }, 300000);
});
</script>

<!-- Flush Maintenance Data Confirmation Modal -->
<div class="modal fade" id="flushMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Flush All Maintenance Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-warning"></i> WARNING: This action cannot be undone!</h6>
                    <p class="mb-0">This will permanently delete:</p>
                    <ul class="mb-0 mt-2">
                        <li>All maintenance requests</li>
                        <li>All maintenance notifications</li>
                        <li>All maintenance status history</li>
                        <li>All student feedback and completion records</li>
                    </ul>
                </div>
                <p class="mb-0">Are you absolutely sure you want to flush all maintenance data?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="flush_maintenance_requests">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Flush All Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>