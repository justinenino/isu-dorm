<?php
require_once '../config/database.php';

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'flush_logs':
                // Flush all logs older than 7 days
                $pdo = getConnection();
                $stmt = $pdo->prepare("DELETE FROM student_location_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $stmt->execute();
                
                $_SESSION['success'] = "Location logs older than 7 days have been flushed.";
                header("Location: student_location_logs.php");
                exit;
                break;
        }
    }
}

$page_title = 'Student Location Logs';
include 'includes/header.php';

$pdo = getConnection();

// Get search parameters
$search = $_GET['search'] ?? '';
$location_filter = $_GET['location'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.school_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($location_filter)) {
    $where_conditions[] = "sll.location_status = ?";
    $params[] = $location_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(sll.timestamp) = ?";
    $params[] = $date_filter;
}

// Always include active student filter
$where_conditions[] = "s.is_deleted = 0 AND s.is_active = 1";

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get student location logs with student details (only active students)
$query = "SELECT sll.*, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.school_id,
    r.room_number,
    b.name as building_name
    FROM student_location_logs sll
    JOIN students s ON sll.student_id = s.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    $where_clause
    ORDER BY sll.timestamp DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$location_logs = $stmt->fetchAll();

// Get current student locations (latest entry for each active student)
$current_locations_query = "SELECT sll.*, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.school_id,
    r.room_number,
    b.name as building_name
    FROM student_location_logs sll
    JOIN students s ON sll.student_id = s.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.is_deleted = 0 AND s.is_active = 1
    AND sll.timestamp = (
        SELECT MAX(timestamp) 
        FROM student_location_logs 
        WHERE student_id = sll.student_id
    )
    ORDER BY sll.timestamp DESC";

$current_locations = $pdo->query($current_locations_query)->fetchAll();

// Get statistics (only for active students)
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_logs,
    SUM(CASE WHEN location_status = 'inside_dormitory' THEN 1 ELSE 0 END) as inside_dormitory,
    SUM(CASE WHEN location_status = 'outside_campus' THEN 1 ELSE 0 END) as outside_campus,
    SUM(CASE WHEN location_status = 'in_class' THEN 1 ELSE 0 END) as in_class
    FROM student_location_logs sll
    JOIN students s ON sll.student_id = s.id
    WHERE s.is_deleted = 0 AND s.is_active = 1
    AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$today_stats = $stmt->fetch();

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-map-marker-alt"></i> Student Location Logs</h2>
    <div>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#flushLogsModal">
            <i class="fas fa-trash"></i> Flush Old Logs
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center position-relative overflow-hidden">
            <div class="stats-icon position-absolute top-0 end-0 me-3 mt-2 opacity-25">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="mb-2 fw-bold"><?php echo $today_stats['total_logs']; ?></h3>
            <p class="mb-0 text-white-50">Today's Logs</p>
            <div class="stats-trend position-absolute bottom-0 start-0 w-100">
                <div class="progress" style="height: 3px;">
                    <div class="progress-bar bg-white" style="width: 85%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <div class="stats-icon position-absolute top-0 end-0 me-3 mt-2 opacity-25">
                <i class="fas fa-home"></i>
            </div>
            <h3 class="mb-2 fw-bold"><?php echo $today_stats['inside_dormitory']; ?></h3>
            <p class="mb-0 text-white-50">Inside Dormitory</p>
            <div class="stats-trend position-absolute bottom-0 start-0 w-100">
                <div class="progress" style="height: 3px;">
                    <div class="progress-bar bg-white" style="width: <?php echo $today_stats['total_logs'] > 0 ? ($today_stats['inside_dormitory'] / $today_stats['total_logs']) * 100 : 0; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <div class="stats-icon position-absolute top-0 end-0 me-3 mt-2 opacity-25">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h3 class="mb-2 fw-bold"><?php echo $today_stats['in_class']; ?></h3>
            <p class="mb-0 text-white-50">In Class</p>
            <div class="stats-trend position-absolute bottom-0 start-0 w-100">
                <div class="progress" style="height: 3px;">
                    <div class="progress-bar bg-white" style="width: <?php echo $today_stats['total_logs'] > 0 ? ($today_stats['in_class'] / $today_stats['total_logs']) * 100 : 0; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
            <div class="stats-icon position-absolute top-0 end-0 me-3 mt-2 opacity-25">
                <i class="fas fa-external-link-alt"></i>
            </div>
            <h3 class="mb-2 fw-bold"><?php echo $today_stats['outside_campus']; ?></h3>
            <p class="mb-0 text-white-50">Outside Campus</p>
            <div class="stats-trend position-absolute bottom-0 start-0 w-100">
                <div class="progress" style="height: 3px;">
                    <div class="progress-bar bg-white" style="width: <?php echo $today_stats['total_logs'] > 0 ? ($today_stats['outside_campus'] / $today_stats['total_logs']) * 100 : 0; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0"><i class="fas fa-search me-2"></i> Search & Filter</h5>
    </div>
    <div class="card-body bg-light">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label fw-semibold">
                        <i class="fas fa-user me-1 text-primary"></i>Search Student
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name or School ID">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="location" class="form-label fw-semibold">
                        <i class="fas fa-map-marker-alt me-1 text-primary"></i>Location Status
                    </label>
                    <select class="form-select" id="location" name="location">
                        <option value="">All Locations</option>
                        <option value="inside_dormitory" <?php echo $location_filter == 'inside_dormitory' ? 'selected' : ''; ?>>
                            <i class="fas fa-home"></i> Inside Dormitory
                        </option>
                        <option value="in_class" <?php echo $location_filter == 'in_class' ? 'selected' : ''; ?>>
                            <i class="fas fa-graduation-cap"></i> In Class
                        </option>
                        <option value="outside_campus" <?php echo $location_filter == 'outside_campus' ? 'selected' : ''; ?>>
                            <i class="fas fa-external-link-alt"></i> Outside Campus
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label fw-semibold">
                        <i class="fas fa-calendar-alt me-1 text-primary"></i>Date
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-calendar text-muted"></i>
                        </span>
                        <input type="date" class="form-control border-start-0" id="date" name="date" 
                               value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Current Student Locations -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-gradient-info text-white">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i> Current Student Locations</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="currentLocationsTable">
                <thead class="table-light">
                    <tr>
                        <th class="border-0 ps-4">
                            <i class="fas fa-user me-2 text-primary"></i>Student
                        </th>
                        <th class="border-0">
                            <i class="fas fa-id-card me-2 text-primary"></i>School ID
                        </th>
                        <th class="border-0">
                            <i class="fas fa-door-open me-2 text-primary"></i>Room
                        </th>
                        <th class="border-0">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>Current Location
                        </th>
                        <th class="border-0">
                            <i class="fas fa-clock me-2 text-primary"></i>Last Updated
                        </th>
                        <th class="border-0 pe-4">
                            <i class="fas fa-cogs me-2 text-primary"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_locations as $location): ?>
                        <tr class="align-middle">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark"><?php echo htmlspecialchars($location['student_name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($location['school_id']); ?></span>
                            </td>
                            <td>
                                <?php if ($location['room_number']): ?>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-home text-success me-2"></i>
                                        <span class="text-dark"><?php echo htmlspecialchars($location['building_name'] . ' - ' . $location['room_number']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-times-circle me-1"></i>Not assigned
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                $status_bg = '';
                                switch ($location['location_status']) {
                                    case 'inside_dormitory':
                                        $status_class = 'badge bg-success';
                                        $status_icon = 'fas fa-home';
                                        $status_bg = 'bg-success bg-opacity-10';
                                        break;
                                    case 'in_class':
                                        $status_class = 'badge bg-info';
                                        $status_icon = 'fas fa-graduation-cap';
                                        $status_bg = 'bg-info bg-opacity-10';
                                        break;
                                    case 'outside_campus':
                                        $status_class = 'badge bg-danger';
                                        $status_icon = 'fas fa-external-link-alt';
                                        $status_bg = 'bg-danger bg-opacity-10';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $status_class; ?> px-3 py-2 rounded-pill">
                                    <i class="<?php echo $status_icon; ?> me-1"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $location['location_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($location['timestamp'])); ?>
                                </div>
                            </td>
                            <td class="pe-4">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                        onclick="viewStudentLogs(<?php echo $location['student_id']; ?>)">
                                    <i class="fas fa-history me-1"></i> View Logs
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Location Logs History -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-gradient-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i> Location Logs History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="locationLogsTable">
                <thead class="table-light">
                    <tr>
                        <th class="border-0 ps-4">
                            <i class="fas fa-user me-2 text-primary"></i>Student
                        </th>
                        <th class="border-0">
                            <i class="fas fa-id-card me-2 text-primary"></i>School ID
                        </th>
                        <th class="border-0">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>Location Status
                        </th>
                        <th class="border-0 pe-4">
                            <i class="fas fa-clock me-2 text-primary"></i>Timestamp
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($location_logs as $log): ?>
                        <tr class="align-middle">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark"><?php echo htmlspecialchars($log['student_name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['school_id']); ?></span>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                switch ($log['location_status']) {
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
                                <span class="<?php echo $status_class; ?> px-3 py-2 rounded-pill">
                                    <i class="<?php echo $status_icon; ?> me-1"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $log['location_status'])); ?>
                                </span>
                            </td>
                            <td class="pe-4">
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($log['timestamp'])); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Flush Logs Modal -->
<div class="modal fade" id="flushLogsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-warning text-dark border-0">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>Flush Old Location Logs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                        <div>
                            <strong>Warning:</strong> This action will permanently delete all location logs older than 7 days.
                        </div>
                    </div>
                </div>
                <div class="bg-light p-3 rounded">
                    <p class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        This will help maintain system performance and provide a fresh start for weekly monitoring.
                    </p>
                    <p class="mb-0">
                        <strong>Logs to be deleted:</strong> All entries older than 
                        <span class="badge bg-warning text-dark"><?php echo date('M j, Y', strtotime('-7 days')); ?></span>
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="flush_logs">
                    <button type="submit" class="btn btn-warning px-4" onclick="return confirm('Are you sure you want to delete old logs? This action cannot be undone.')">
                        <i class="fas fa-trash me-1"></i> Flush Old Logs
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Student Logs Modal -->
<div class="modal fade" id="studentLogsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-info text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>Student Location History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="studentLogsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#currentLocationsTable').DataTable({
        order: [[4, 'desc']],
        pageLength: 25
    });
    
    $('#locationLogsTable').DataTable({
        order: [[3, 'desc']],
        pageLength: 50
    });
});

function viewStudentLogs(studentId) {
    // Load student logs via AJAX
    $.get('get_student_location_logs.php', {student_id: studentId}, function(data) {
        $('#studentLogsContent').html(data);
        $('#studentLogsModal').modal('show');
    });
}
</script>

<?php include 'includes/footer.php'; ?> 