<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'flush_logs':
                    // Delete location logs older than 7 days
                    $stmt = $pdo->prepare("DELETE FROM student_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $stmt->execute();
                    $deleted_count = $stmt->rowCount();
                    
                    logActivity($_SESSION['user_id'], "Flushed student location logs ($deleted_count records deleted)");
                    echo json_encode(['success' => true, 'message' => "Successfully deleted $deleted_count old location records"]);
                    break;
                    
                case 'update_location':
                    $student_id = sanitizeInput($_POST['student_id']);
                    $location = sanitizeInput($_POST['location']);
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    // Insert new location entry
                    $stmt = $pdo->prepare("
                        INSERT INTO student_locations (student_id, location, notes, created_by, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$student_id, $location, $notes, $_SESSION['user_id']]);
                    
                    logActivity($_SESSION['user_id'], "Updated location for student ID: $student_id to $location");
                    echo json_encode(['success' => true, 'message' => 'Student location updated successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        }
    } catch (Exception $e) {
        error_log("Error in student_locator.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$location_filter = sanitizeInput($_GET['location'] ?? '');
$building_filter = sanitizeInput($_GET['building'] ?? '');

try {
    $pdo = getDBConnection();
    
    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT s.id) as total_students,
            SUM(CASE WHEN sl.location = 'inside_dorm' THEN 1 ELSE 0 END) as inside_dorm,
            SUM(CASE WHEN sl.location = 'outside_campus' THEN 1 ELSE 0 END) as outside_campus,
            SUM(CASE WHEN sl.location = 'in_class' THEN 1 ELSE 0 END) as in_class
        FROM students s
        LEFT JOIN (
            SELECT student_id, location, 
                   ROW_NUMBER() OVER (PARTITION BY student_id ORDER BY created_at DESC) as rn
            FROM student_locations 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) sl ON s.id = sl.student_id AND sl.rn = 1
        WHERE s.status = 'approved'
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Build query for student locations
    $where_conditions = ["s.status = 'approved'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }
    
    if (!empty($location_filter)) {
        $where_conditions[] = "latest_location.location = ?";
        $params[] = $location_filter;
    }
    
    if (!empty($building_filter)) {
        $where_conditions[] = "b.id = ?";
        $params[] = $building_filter;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get students with their latest location
    $stmt = $pdo->prepare("
        SELECT 
            s.id, s.first_name, s.last_name, s.middle_name, s.student_id, s.mobile_number,
            r.room_number, b.building_name, bs.bedspace_number,
            latest_location.location, latest_location.notes, latest_location.created_at as last_update,
            u.username as updated_by
        FROM students s
        LEFT JOIN bedspaces bs ON s.id = bs.student_id
        LEFT JOIN rooms r ON bs.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN (
            SELECT sl.*, u.username,
                   ROW_NUMBER() OVER (PARTITION BY student_id ORDER BY created_at DESC) as rn
            FROM student_locations sl
            LEFT JOIN users u ON sl.created_by = u.id
            WHERE sl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) latest_location ON s.id = latest_location.student_id AND latest_location.rn = 1
        LEFT JOIN users u ON latest_location.created_by = u.id
        $where_clause
        ORDER BY latest_location.created_at DESC, s.last_name, s.first_name
    ");
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get buildings for filter
    $buildings_stmt = $pdo->prepare("SELECT * FROM buildings ORDER BY building_name");
    $buildings_stmt->execute();
    $buildings = $buildings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all students for location update dropdown
    $all_students_stmt = $pdo->prepare("
        SELECT id, first_name, last_name, student_id 
        FROM students 
        WHERE status = 'approved' 
        ORDER BY last_name, first_name
    ");
    $all_students_stmt->execute();
    $all_students = $all_students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error in student_locator.php: " . $e->getMessage());
    $students = [];
    $buildings = [];
    $all_students = [];
    $stats = ['total_students' => 0, 'inside_dorm' => 0, 'outside_campus' => 0, 'in_class' => 0];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Student Locator & Logs</h1>
                <p class="text-muted">Track student locations and manage location logs</p>
            </div>
            <div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#updateLocationModal">
                    <i class="fas fa-map-marker-alt"></i> Update Location
                </button>
                <button class="btn btn-warning" onclick="flushLogs()">
                    <i class="fas fa-broom"></i> Flush Old Logs
                </button>
                <button class="btn btn-outline-primary" onclick="exportLocations()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['total_students']; ?></h4>
                                <p class="mb-0">Total Students</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['inside_dorm']; ?></h4>
                                <p class="mb-0">Inside Dorm</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-home fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['in_class']; ?></h4>
                                <p class="mb-0">In Class</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chalkboard-teacher fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['outside_campus']; ?></h4>
                                <p class="mb-0">Outside Campus</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-map-marker-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Student</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name or Student ID...">
                    </div>
                    <div class="col-md-3">
                        <label for="location" class="form-label">Location</label>
                        <select class="form-select" id="location" name="location">
                            <option value="">All Locations</option>
                            <option value="inside_dorm" <?php echo $location_filter === 'inside_dorm' ? 'selected' : ''; ?>>Inside Dorm</option>
                            <option value="outside_campus" <?php echo $location_filter === 'outside_campus' ? 'selected' : ''; ?>>Outside Campus</option>
                            <option value="in_class" <?php echo $location_filter === 'in_class' ? 'selected' : ''; ?>>In Class</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="building" class="form-label">Building</label>
                        <select class="form-select" id="building" name="building">
                            <option value="">All Buildings</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?php echo $building['id']; ?>" 
                                        <?php echo $building_filter == $building['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($building['building_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="student_locator.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students Location Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Student Locations (<?php echo count($students); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No students found</h5>
                        <p class="text-muted">Student location data will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="locationsTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Room Assignment</th>
                                    <th>Current Location</th>
                                    <th>Last Update</th>
                                    <th>Updated By</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-white fw-bold">
                                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($student['room_number']): ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['building_name'] . ' - ' . $student['room_number']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Bed <?php echo $student['bedspace_number']; ?></small>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No assignment</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['location']): ?>
                                                <?php
                                                $location_class = '';
                                                $location_icon = '';
                                                switch ($student['location']) {
                                                    case 'inside_dorm':
                                                        $location_class = 'bg-success';
                                                        $location_icon = 'fas fa-home';
                                                        break;
                                                    case 'outside_campus':
                                                        $location_class = 'bg-warning';
                                                        $location_icon = 'fas fa-map-marker-alt';
                                                        break;
                                                    case 'in_class':
                                                        $location_class = 'bg-info';
                                                        $location_icon = 'fas fa-chalkboard-teacher';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $location_class; ?>">
                                                    <i class="<?php echo $location_icon; ?>"></i>
                                                    <?php echo ucwords(str_replace('_', ' ', $student['location'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-question"></i> Unknown
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['last_update']): ?>
                                                <?php echo formatDate($student['last_update']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['updated_by']): ?>
                                                <small><?php echo htmlspecialchars($student['updated_by']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['notes']): ?>
                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                      title="<?php echo htmlspecialchars($student['notes']); ?>">
                                                    <?php echo htmlspecialchars($student['notes']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewLocationHistory(<?php echo $student['id']; ?>)">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="updateStudentLocation(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Update Location Modal -->
<div class="modal fade" id="updateLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Student Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateLocationForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="studentSelect" class="form-label">Student *</label>
                        <select class="form-select" id="studentSelect" name="student_id" required>
                            <option value="">Select student...</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="locationSelect" class="form-label">Location *</label>
                        <select class="form-select" id="locationSelect" name="location" required>
                            <option value="">Select location...</option>
                            <option value="inside_dorm">Inside Dorm</option>
                            <option value="outside_campus">Outside Campus</option>
                            <option value="in_class">In Class</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="locationNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="locationNotes" name="notes" rows="3" 
                                  placeholder="Additional notes about the location update..."></textarea>
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

<!-- Location History Modal -->
<div class="modal fade" id="locationHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Location History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="locationHistoryContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#locationsTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc']], // Sort by last update
        columnDefs: [
            { orderable: false, targets: [6] } // Disable sorting for actions column
        ]
    });
});

// Update student location (with pre-filled data)
function updateStudentLocation(studentId, studentName) {
    $('#studentSelect').val(studentId);
    $('#updateLocationModal .modal-title').text('Update Location - ' + studentName);
    $('#updateLocationModal').modal('show');
}

// View location history
function viewLocationHistory(studentId) {
    $.post('get_location_history.php', {
        student_id: studentId
    }, function(response) {
        $('#locationHistoryContent').html(response);
        $('#locationHistoryModal').modal('show');
    }).fail(function() {
        showAlert('Error loading location history', 'danger');
    });
}

// Flush old logs
function flushLogs() {
    if (confirm('Are you sure you want to flush location logs older than 7 days? This action cannot be undone.')) {
        $.post('student_locator.php', {
            action: 'flush_logs'
        }, function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(response.message, 'danger');
            }
        }, 'json').fail(function() {
            showAlert('Error flushing logs', 'danger');
        });
    }
}

// Handle location update form submission
$('#updateLocationForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
    
    $.post('student_locator.php', {
        action: 'update_location',
        student_id: $('#studentSelect').val(),
        location: $('#locationSelect').val(),
        notes: $('#locationNotes').val()
    }, function(response) {
        if (response.success) {
            $('#updateLocationModal').modal('hide');
            showAlert(response.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function() {
        showAlert('Error updating location', 'danger');
    }).always(function() {
        submitBtn.html(originalText).prop('disabled', false);
    });
});

// Export locations
function exportLocations() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = 'export_student_locations.php?' + params.toString();
}

// Alert function
function showAlert(message, type) {
    const alertDiv = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('.main-content .container-fluid').prepend(alertDiv);
    
    setTimeout(() => {
        alertDiv.alert('close');
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>
