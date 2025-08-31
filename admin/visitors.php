<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle time-out marking
if ($_POST && isset($_POST['mark_timeout'])) {
    $visitor_id = sanitizeInput($_POST['visitor_id']);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE visitors 
            SET time_out = NOW(), 
                updated_at = NOW() 
            WHERE visitor_id = ? AND time_out IS NULL
        ");
        
        if ($stmt->execute([$visitor_id])) {
            if ($stmt->rowCount() > 0) {
                $message = 'Visitor time-out marked successfully.';
                logActivity($_SESSION['user_id'], "Marked visitor time-out for visitor ID: $visitor_id");
            } else {
                $error = 'Visitor already checked out or not found.';
            }
        } else {
            $error = 'Error marking time-out.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Handle visitor deletion
if ($_POST && isset($_POST['delete_visitor'])) {
    $visitor_id = sanitizeInput($_POST['visitor_id']);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM visitors WHERE visitor_id = ?");
        
        if ($stmt->execute([$visitor_id])) {
            $message = 'Visitor record deleted successfully.';
            logActivity($_SESSION['user_id'], "Deleted visitor record ID: $visitor_id");
        } else {
            $error = 'Error deleting visitor record.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch visitors with filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$building_filter = isset($_GET['building']) ? sanitizeInput($_GET['building']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(v.visitor_name LIKE ? OR v.contact_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if ($status_filter) {
        if ($status_filter === 'checked_in') {
            $where_conditions[] = "v.time_out IS NULL";
        } elseif ($status_filter === 'checked_out') {
            $where_conditions[] = "v.time_out IS NOT NULL";
        }
    }
    
    if ($building_filter) {
        $where_conditions[] = "b.building_id = ?";
        $params[] = $building_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT v.*, s.first_name, s.last_name, s.student_id as host_student_id,
               r.room_number, b.building_name
        FROM visitors v
        JOIN students s ON v.student_id = s.user_id
        JOIN rooms r ON v.room_id = r.room_id
        JOIN buildings b ON r.building_id = b.building_id
        $where_clause
        ORDER BY v.time_in DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch buildings for filter
    $buildings_stmt = $pdo->query("SELECT building_id, building_name FROM buildings ORDER BY building_name");
    $buildings = $buildings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_visitors,
            COUNT(CASE WHEN time_out IS NULL THEN 1 END) as currently_inside,
            COUNT(CASE WHEN time_out IS NOT NULL THEN 1 END) as checked_out
        FROM visitors
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $visitors = [];
    $buildings = [];
    $stats = ['total_visitors' => 0, 'currently_inside' => 0, 'checked_out' => 0];
}

$page_title = "Visitors Logs";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-user-friends me-2"></i>Visitors Logs
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" onclick="exportVisitors()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <button class="btn btn-primary" onclick="printVisitors()">
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
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Visitors
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_visitors']; ?>
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
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Currently Inside
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['currently_inside']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Checked Out
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['checked_out']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-sign-out-alt fa-2x text-gray-300"></i>
                                    </div>
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
                                        Today's Visitors
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $today_stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE DATE(time_in) = CURDATE()");
                                        $today_count = $today_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                        echo $today_count;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                                   placeholder="Visitor name, contact, or host student...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Currently Inside</option>
                                <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="building" class="form-label">Building</label>
                            <select class="form-select" id="building" name="building">
                                <option value="">All Buildings</option>
                                <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building['building_id']; ?>" 
                                            <?php echo $building_filter == $building['building_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($building['building_name']); ?>
                                    </option>
                                <?php endforeach; ?>
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

            <!-- Visitors Table -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Visitors List
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="visitorsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Visitor Name</th>
                                    <th>Age</th>
                                    <th>Contact</th>
                                    <th>Host Student</th>
                                    <th>Room</th>
                                    <th>Building</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($visitors)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No visitors found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($visitors as $visitor): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($visitor['visitor_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($visitor['address']); ?></small>
                                            </td>
                                            <td><?php echo $visitor['age']; ?></td>
                                            <td><?php echo htmlspecialchars($visitor['contact_number']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($visitor['host_student_id']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($visitor['room_number']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['building_name']); ?></td>
                                            <td>
                                                <?php echo formatDate($visitor['time_in'], 'M d, Y h:i A'); ?>
                                            </td>
                                            <td>
                                                <?php if ($visitor['time_out']): ?>
                                                    <?php echo formatDate($visitor['time_out'], 'M d, Y h:i A'); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($visitor['time_out']): ?>
                                                    <span class="badge bg-success">Checked Out</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Currently Inside</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (!$visitor['time_out']): ?>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Mark this visitor as checked out?')">
                                                            <input type="hidden" name="visitor_id" value="<?php echo $visitor['visitor_id']; ?>">
                                                            <button type="submit" name="mark_timeout" class="btn btn-sm btn-success" 
                                                                    title="Mark Time Out">
                                                                <i class="fas fa-sign-out-alt"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewVisitorDetails(<?php echo $visitor['visitor_id']; ?>)" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Delete this visitor record? This action cannot be undone.')">
                                                        <input type="hidden" name="visitor_id" value="<?php echo $visitor['visitor_id']; ?>">
                                                        <button type="submit" name="delete_visitor" class="btn btn-sm btn-danger" 
                                                                title="Delete Record">
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

<!-- Visitor Details Modal -->
<div class="modal fade" id="visitorDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-friends me-2"></i>Visitor Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitorDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewVisitorDetails(visitorId) {
    // Load visitor details via AJAX
    fetch(`get_visitor_details.php?visitor_id=${visitorId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('visitorDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('visitorDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading visitor details');
        });
}

function exportVisitors() {
    // Create export URL with current filters
    const search = document.getElementById('search').value;
    const status = document.getElementById('status').value;
    const building = document.getElementById('building').value;
    
    let exportUrl = 'export_visitors.php?';
    if (search) exportUrl += `search=${encodeURIComponent(search)}&`;
    if (status) exportUrl += `status=${encodeURIComponent(status)}&`;
    if (building) exportUrl += `building=${encodeURIComponent(building)}&`;
    
    window.open(exportUrl, '_blank');
}

function printVisitors() {
    window.print();
}

// Initialize DataTable
$(document).ready(function() {
    $('#visitorsTable').DataTable({
        pageLength: 25,
        order: [[6, 'desc']], // Sort by time_in descending
        responsive: true,
        language: {
            search: "Search visitors:",
            lengthMenu: "Show _MENU_ visitors per page",
            info: "Showing _START_ to _END_ of _TOTAL_ visitors"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
