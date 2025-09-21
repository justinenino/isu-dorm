<?php
require_once '../config/database.php';

// No form processing needed - view only functionality

$page_title = 'Visitor Logs (View Only)';
include 'includes/header.php';

$pdo = getConnection();

// Build search query
$whereConditions = [];
$params = [];

// Search term filter
if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(vl.visitor_name LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.school_id LIKE ? OR vl.contact_number LIKE ?)";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Status filter
if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'inside') {
        $whereConditions[] = "vl.time_out IS NULL";
    } elseif ($_GET['status'] === 'checked_out') {
        $whereConditions[] = "vl.time_out IS NOT NULL";
    }
}

// Reason filter
if (!empty($_GET['reason'])) {
    $whereConditions[] = "vl.reason_of_visit = ?";
    $params[] = $_GET['reason'];
}

// Date range filters
if (!empty($_GET['date_from'])) {
    $whereConditions[] = "DATE(vl.time_in) >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $whereConditions[] = "DATE(vl.time_in) <= ?";
    $params[] = $_GET['date_to'];
}

// Build the complete query
$sql = "SELECT vl.*, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.school_id,
    s.email,
    s.mobile_number,
    r.room_number as student_room,
    b.name as building_name
    FROM visitor_logs vl
    JOIN students s ON vl.student_id = s.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY vl.time_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visitor_logs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-users"></i> Visitor Logs (View Only)</h2>
        <p class="text-muted mb-0">Students manage their own visitor registrations. Admins can view all logs and visitor details.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($visitor_logs, function($vl) { return $vl['time_out'] == null; })); ?></h3>
            <p>Currently Inside</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($visitor_logs, function($vl) { return $vl['time_out'] != null; })); ?></h3>
            <p>Checked Out</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($visitor_logs, function($vl) { return strtotime($vl['time_in']) > strtotime('today'); })); ?></h3>
            <p>Today's Visitors</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo count($visitor_logs); ?></h3>
            <p>Total Logs</p>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-search"></i> Search Visitor Logs</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search Term</label>
                <input type="text" name="search" class="form-control" placeholder="Visitor name, student name, or ID..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="inside" <?php echo (($_GET['status'] ?? '') === 'inside') ? 'selected' : ''; ?>>Inside</option>
                    <option value="checked_out" <?php echo (($_GET['status'] ?? '') === 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Reason</label>
                <select name="reason" class="form-control">
                    <option value="">All Reasons</option>
                    <option value="Project" <?php echo (($_GET['reason'] ?? '') === 'Project') ? 'selected' : ''; ?>>Project</option>
                    <option value="Activities" <?php echo (($_GET['reason'] ?? '') === 'Activities') ? 'selected' : ''; ?>>Activities</option>
                    <option value="Friends" <?php echo (($_GET['reason'] ?? '') === 'Friends') ? 'selected' : ''; ?>>Friends</option>
                    <option value="Family" <?php echo (($_GET['reason'] ?? '') === 'Family') ? 'selected' : ''; ?>>Family</option>
                    <option value="Study Group" <?php echo (($_GET['reason'] ?? '') === 'Study Group') ? 'selected' : ''; ?>>Study Group</option>
                    <option value="Meeting" <?php echo (($_GET['reason'] ?? '') === 'Meeting') ? 'selected' : ''; ?>>Meeting</option>
                    <option value="Personal" <?php echo (($_GET['reason'] ?? '') === 'Personal') ? 'selected' : ''; ?>>Personal</option>
                    <option value="Other" <?php echo (($_GET['reason'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['reason']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
        <div class="mt-3">
            <a href="visitor_logs.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Visitor Logs Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Visitor Logs</h5>
        <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['reason']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
        <span class="badge bg-info">
            <i class="fas fa-filter"></i> <?php echo count($visitor_logs); ?> result(s) found
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Age</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Reason</th>
                        <th>Student</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visitor_logs)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted">No visitor logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visitor_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['visitor_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['visitor_age']); ?></td>
                                <td><?php echo htmlspecialchars($log['visitor_address']); ?></td>
                                <td><?php echo htmlspecialchars($log['contact_number']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($log['reason_of_visit'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <?php echo htmlspecialchars($log['student_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['school_id']); ?></small>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($log['time_in'])); ?></td>
                                <td>
                                    <?php if ($log['time_out']): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($log['time_out'])); ?>
                                    <?php else: ?>
                                        <span class="text-warning">Still inside</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['time_out']): ?>
                                        <span class="badge bg-success">Checked Out</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Inside</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewVisitorDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Information Card -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Admin View Only</h6>
                <p class="text-muted">This page allows administrators to view all visitor logs across the dormitory. Students manage their own visitor registrations through their student portal.</p>
            </div>
            <div class="col-md-6">
                <h6>Available Actions</h6>
                <ul class="text-muted">
                    <li>View all visitor logs</li>
                    <li>View detailed visitor information</li>
                    <li>Monitor visitor statistics</li>
                    <li>Track student visitor patterns</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Visitor Details Modal -->
<div class="modal fade" id="visitorDetailsModal" tabindex="-1" aria-labelledby="visitorDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="visitorDetailsModalLabel">
                    <i class="fas fa-user"></i> Visitor Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Visitor Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-user-friends"></i> Visitor Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Name:</label>
                                    <p class="mb-0" id="visitorName">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Age:</label>
                                    <p class="mb-0" id="visitorAge">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contact Number:</label>
                                    <p class="mb-0" id="visitorContact">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reason of Visit:</label>
                                    <p class="mb-0"><span class="badge bg-info" id="visitorReason">-</span></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Address:</label>
                                    <p class="mb-0" id="visitorAddress">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-user-graduate"></i> Student Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Student Name:</label>
                                    <p class="mb-0" id="studentName">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Student ID:</label>
                                    <p class="mb-0" id="studentId">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Room Number:</label>
                                    <p class="mb-0" id="studentRoom">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Building:</label>
                                    <p class="mb-0" id="studentBuilding">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email:</label>
                                    <p class="mb-0" id="studentEmail">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mobile:</label>
                                    <p class="mb-0" id="studentMobile">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Visit Timeline -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-clock"></i> Visit Timeline</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Time In:</label>
                                            <p class="mb-0" id="timeIn">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Time Out:</label>
                                            <p class="mb-0" id="timeOut">-</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status:</label>
                                    <p class="mb-0"><span class="badge" id="visitStatus">-</span></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Duration:</label>
                                    <p class="mb-0" id="visitDuration">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewVisitorDetails(visitorData) {
    // Populate visitor information
    document.getElementById('visitorName').textContent = visitorData.visitor_name || '-';
    document.getElementById('visitorAge').textContent = visitorData.visitor_age || '-';
    document.getElementById('visitorContact').textContent = visitorData.contact_number || '-';
    document.getElementById('visitorReason').textContent = visitorData.reason_of_visit || 'N/A';
    document.getElementById('visitorAddress').textContent = visitorData.visitor_address || '-';
    
    // Populate student information
    document.getElementById('studentName').textContent = visitorData.student_name || '-';
    document.getElementById('studentId').textContent = visitorData.school_id || '-';
    document.getElementById('studentRoom').textContent = visitorData.student_room || 'Not assigned';
    document.getElementById('studentBuilding').textContent = visitorData.building_name || '-';
    document.getElementById('studentEmail').textContent = visitorData.email || '-';
    document.getElementById('studentMobile').textContent = visitorData.mobile_number || '-';
    
    // Populate timeline information
    const timeIn = new Date(visitorData.time_in);
    const timeOut = visitorData.time_out ? new Date(visitorData.time_out) : null;
    
    document.getElementById('timeIn').textContent = timeIn.toLocaleString();
    document.getElementById('timeOut').textContent = timeOut ? timeOut.toLocaleString() : 'Still inside';
    
    // Status badge
    const statusElement = document.getElementById('visitStatus');
    if (timeOut) {
        statusElement.textContent = 'Checked Out';
        statusElement.className = 'badge bg-success';
    } else {
        statusElement.textContent = 'Inside';
        statusElement.className = 'badge bg-warning';
    }
    
    // Calculate duration
    const durationElement = document.getElementById('visitDuration');
    if (timeOut) {
        const duration = timeOut - timeIn;
        const hours = Math.floor(duration / (1000 * 60 * 60));
        const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
        durationElement.textContent = `${hours}h ${minutes}m`;
    } else {
        const now = new Date();
        const duration = now - timeIn;
        const hours = Math.floor(duration / (1000 * 60 * 60));
        const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
        durationElement.textContent = `${hours}h ${minutes}m (ongoing)`;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('visitorDetailsModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?> 