<?php
require_once '../config/database.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_offense':
                $student_id = $_POST['student_id'];
                $offense_type = $_POST['offense_type'];
                $description = $_POST['description'];
                $severity = $_POST['severity'];
                $action_taken = $_POST['action_taken'];
                $reported_by = $_POST['reported_by'];
                $room_id = $_POST['room_id'] ?? null;
                
                $pdo = getConnection();
                
                // If room_id is provided, log offense for all students in that room
                if ($room_id && $room_id != '') {
                    // Get all students in the room
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE room_id = ? AND application_status = 'approved'");
                    $stmt->execute([$room_id]);
                    $room_students = $stmt->fetchAll();
                    
                    if (empty($room_students)) {
                        $_SESSION['error'] = "No students found in the selected room.";
                        header("Location: offense_logs.php");
                        exit;
                    }
                    
                    // Log offense for each student in the room
                    $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($room_students as $student) {
                        $stmt->execute([$student['id'], $offense_type, $description, $severity, $action_taken, $reported_by]);
                    }
                    
                    $_SESSION['success'] = "Offense logged for " . count($room_students) . " student(s) in the room.";
                } else {
                    // Log offense for specific student
                    $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$student_id, $offense_type, $description, $severity, $action_taken, $reported_by]);
                    
                    $_SESSION['success'] = "Offense logged successfully.";
                }
                
                header("Location: offense_logs.php");
                exit;
                break;
                
            case 'update_status':
                $offense_id = $_POST['offense_id'];
                $status = $_POST['status'];
                $admin_response = $_POST['admin_response'];
                
                // Validate inputs
                if (empty($offense_id) || empty($status) || empty($admin_response)) {
                    $_SESSION['error'] = "All fields are required.";
                    header("Location: offense_logs.php");
                    exit;
                }
                
                $pdo = getConnection();
                
                // First, check if the offense exists
                $check_stmt = $pdo->prepare("SELECT id FROM offense_logs WHERE id = ?");
                $check_stmt->execute([$offense_id]);
                
                if (!$check_stmt->fetch()) {
                    $_SESSION['error'] = "Offense not found.";
                    header("Location: offense_logs.php");
                    exit;
                }
                
                // Update the offense
                $stmt = $pdo->prepare("UPDATE offense_logs SET status = ?, action_taken = CONCAT(IFNULL(action_taken, ''), '\n\nAdmin Response: ', ?), resolved_at = ? WHERE id = ?");
                $resolved_at = ($status == 'resolved') ? date('Y-m-d H:i:s') : null;
                
                $result = $stmt->execute([$status, $admin_response, $resolved_at, $offense_id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $_SESSION['success'] = "Offense status updated successfully from " . $status . " to " . $status . ".";
                } else {
                    $_SESSION['error'] = "Failed to update offense status. No rows were affected.";
                }
                
                header("Location: offense_logs.php");
                exit;
                break;
        }
    }
}

$page_title = 'Offense Logs Management';
include 'includes/header.php';

$pdo = getConnection();

// Get all students for dropdown
$stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name, ' (', school_id, ')') as name FROM students WHERE application_status = 'approved' ORDER BY first_name");
$students = $stmt->fetchAll();

// Get all rooms for dropdown
$stmt = $pdo->query("SELECT r.id, CONCAT(b.name, ' - Room ', r.room_number) as room_name, 
    COUNT(s.id) as student_count
    FROM rooms r 
    LEFT JOIN buildings b ON r.building_id = b.id 
    LEFT JOIN students s ON r.id = s.room_id AND s.application_status = 'approved'
    GROUP BY r.id, b.name, r.room_number
    ORDER BY b.name, r.room_number");
$rooms = $stmt->fetchAll();

// Check if complaint_id column exists in offense_logs table
$check_column = $pdo->query("SHOW COLUMNS FROM offense_logs LIKE 'complaint_id'");
$has_complaint_id = $check_column->rowCount() > 0;

// Get offense logs with student details and complaint info (if column exists)
if ($has_complaint_id) {
    $stmt = $pdo->query("SELECT ol.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.school_id,
        r.room_number,
        b.name as building_name,
        c.subject as complaint_subject,
        c.id as complaint_id
        FROM offense_logs ol
        JOIN students s ON ol.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN complaints c ON ol.complaint_id = c.id
        ORDER BY ol.reported_at DESC");
} else {
    $stmt = $pdo->query("SELECT ol.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.school_id,
        r.room_number,
        b.name as building_name,
        NULL as complaint_subject,
        NULL as complaint_id
        FROM offense_logs ol
        JOIN students s ON ol.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY ol.reported_at DESC");
}
$offenses = $stmt->fetchAll();
?>

<?php if (!$has_complaint_id): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Database Update Required:</strong> To enable complaint tracking in offense logs, please run the database update script. 
        <a href="../update_database.php" class="alert-link" target="_blank">Run Update Script</a>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-exclamation-triangle"></i> Offense Logs Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOffenseModal">
        <i class="fas fa-plus"></i> Log New Offense
    </button>
</div>

<!-- Search and Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-search"></i> Search & Filter</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Search Student</label>
                <input type="text" id="studentSearch" class="form-control" placeholder="Search by name or school ID">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Search Room</label>
                <input type="text" id="roomSearch" class="form-control" placeholder="Search by room number">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Filter by Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="resolved">Resolved</option>
                    <option value="escalated">Escalated</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Filter by Severity</label>
                <select id="severityFilter" class="form-select">
                    <option value="">All Severity</option>
                    <option value="minor">Minor</option>
                    <option value="major">Major</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Filter by Offense Type</label>
                <select id="offenseTypeFilter" class="form-select">
                    <option value="">All Types</option>
                    <option value="Curfew Violation">Curfew Violation</option>
                    <option value="Noise Violation">Noise Violation</option>
                    <option value="Property Damage">Property Damage</option>
                    <option value="Unauthorized Visitors">Unauthorized Visitors</option>
                    <option value="Smoking/Vaping">Smoking/Vaping</option>
                    <option value="Alcohol/Drugs">Alcohol/Drugs</option>
                    <option value="Fighting">Fighting</option>
                    <option value="Theft">Theft</option>
                    <option value="Disruptive Behavior">Disruptive Behavior</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($offenses, function($o) { return $o['status'] == 'pending'; })); ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($offenses, function($o) { return $o['status'] == 'resolved'; })); ?></h3>
            <p>Resolved</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
            <h3><?php echo count(array_filter($offenses, function($o) { return $o['severity'] == 'critical'; })); ?></h3>
            <p>Critical</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo count($offenses); ?></h3>
            <p>Total</p>
        </div>
    </div>
</div>

<!-- Offense Logs Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Offense Logs</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="offenseTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Offense Type</th>
                        <th>Description</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Reported By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offenses as $offense): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($offense['student_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($offense['school_id']); ?></small>
                            </td>
                            <td>
                                <?php if ($offense['room_number']): ?>
                                    <?php echo htmlspecialchars($offense['building_name'] . ' - ' . $offense['room_number']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($offense['offense_type']); ?></td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($offense['description']); ?>">
                                    <?php echo htmlspecialchars($offense['description']); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $severity_class = '';
                                switch ($offense['severity']) {
                                    case 'minor': $severity_class = 'badge bg-info'; break;
                                    case 'major': $severity_class = 'badge bg-warning'; break;
                                    case 'critical': $severity_class = 'badge bg-danger'; break;
                                }
                                ?>
                                <span class="<?php echo $severity_class; ?>"><?php echo ucfirst($offense['severity']); ?></span>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($offense['status']) {
                                    case 'pending': $status_class = 'badge bg-warning'; break;
                                    case 'resolved': $status_class = 'badge bg-success'; break;
                                    case 'escalated': $status_class = 'badge bg-danger'; break;
                                }
                                ?>
                                <span class="<?php echo $status_class; ?>"><?php echo ucfirst($offense['status']); ?></span>
                            </td>
                            <td>
                                <?php if ($offense['complaint_id']): ?>
                                    <span class="badge bg-info" title="Converted from complaint: <?php echo htmlspecialchars($offense['complaint_subject']); ?>">
                                        <i class="fas fa-comment-alt"></i> Complaint
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-plus"></i> Direct
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($offense['reported_by']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($offense['reported_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewOffenseModal" 
                                        data-offense='<?php echo json_encode($offense); ?>'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($offense['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal" 
                                            data-offense-id="<?php echo $offense['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Offense Modal -->
<div class="modal fade" id="addOffenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log New Offense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_offense">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> You can log an offense for a specific student or for all students in a room.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Target Type</label>
                            <select id="targetType" class="form-select" required>
                                <option value="">Select Target Type</option>
                                <option value="student">Specific Student</option>
                                <option value="room">All Students in Room</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Offense Type</label>
                            <select name="offense_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Curfew Violation">Curfew Violation</option>
                                <option value="Noise Violation">Noise Violation</option>
                                <option value="Property Damage">Property Damage</option>
                                <option value="Unauthorized Visitors">Unauthorized Visitors</option>
                                <option value="Smoking/Vaping">Smoking/Vaping</option>
                                <option value="Alcohol/Drugs">Alcohol/Drugs</option>
                                <option value="Fighting">Fighting</option>
                                <option value="Theft">Theft</option>
                                <option value="Disruptive Behavior">Disruptive Behavior</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row" id="studentSelection" style="display: none;">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Student</label>
                            <select name="student_id" class="form-select">
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row" id="roomSelection" style="display: none;">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Room</label>
                            <select name="room_id" class="form-select">
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_name']); ?> (<?php echo $room['student_count']; ?> students)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select" required>
                                <option value="minor">Minor</option>
                                <option value="major">Major</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reported By</label>
                            <input type="text" name="reported_by" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action Taken</label>
                        <textarea name="action_taken" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Offense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Offense Modal -->
<div class="modal fade" id="viewOffenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Offense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="offenseDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Offense Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="offense_id" id="updateOffenseId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                            <option value="escalated">Escalated</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Response</label>
                        <textarea name="admin_response" class="form-control" rows="4" required></textarea>
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

<script>
// Run after all assets (including jQuery/Bootstrap in footer) are loaded
window.addEventListener('load', function() {
    
    // Handle view offense modal
    $('#viewOffenseModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var offense = button.data('offense');
        var modal = $(this);
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Student Information</h6>
                    <p><strong>Name:</strong> ${offense.student_name}</p>
                    <p><strong>School ID:</strong> ${offense.school_id}</p>
                    <p><strong>Room:</strong> ${offense.building_name} - ${offense.room_number}</p>
                </div>
                <div class="col-md-6">
                    <h6>Offense Details</h6>
                    <p><strong>Type:</strong> ${offense.offense_type}</p>
                    <p><strong>Severity:</strong> <span class="badge bg-${offense.severity === 'critical' ? 'danger' : (offense.severity === 'major' ? 'warning' : 'info')}">${offense.severity}</span></p>
                    <p><strong>Status:</strong> <span class="badge bg-${offense.status === 'resolved' ? 'success' : (offense.status === 'escalated' ? 'danger' : 'warning')}">${offense.status}</span></p>
                    <p><strong>Reported By:</strong> ${offense.reported_by}</p>
                    <p><strong>Date:</strong> ${new Date(offense.reported_at).toLocaleString()}</p>
                    <p><strong>Source:</strong> ${offense.complaint_id ? '<span class="badge bg-info"><i class="fas fa-comment-alt"></i> Converted from Complaint</span>' : '<span class="badge bg-secondary"><i class="fas fa-plus"></i> Direct Entry</span>'}</p>
                </div>
            </div>
            ${offense.complaint_id ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Original Complaint</h6>
                    <p><strong>Subject:</strong> ${offense.complaint_subject}</p>
                    <p><strong>Complaint ID:</strong> #${offense.complaint_id}</p>
                </div>
            </div>
            ` : ''}
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Description</h6>
                    <p>${offense.description}</p>
                </div>
            </div>
            ${offense.action_taken ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Action Taken</h6>
                    <p>${offense.action_taken}</p>
                </div>
            </div>
            ` : ''}
        `;
        
        modal.find('#offenseDetails').html(content);
    });
    
    // Handle update status modal
    $('#updateStatusModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var offenseId = button.data('offense-id');
        $('#updateOffenseId').val(offenseId);
        console.log('Setting offense ID:', offenseId);
    });
    
    // Add form submission logging
    $('#updateStatusModal form').on('submit', function(e) {
        var formData = $(this).serialize();
        console.log('Form being submitted:', formData);
        
        // Check if all required fields are filled
        var status = $('select[name="status"]').val();
        var adminResponse = $('textarea[name="admin_response"]').val();
        var offenseId = $('input[name="offense_id"]').val();
        
        if (!status || !adminResponse || !offenseId) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });
    
    // Handle target type selection
    $('#targetType').on('change', function() {
        var targetType = $(this).val();
        $('#studentSelection').hide();
        $('#roomSelection').hide();
        $('select[name="student_id"]').prop('required', false);
        $('select[name="room_id"]').prop('required', false);
        
        if (targetType === 'student') {
            $('#studentSelection').show();
            $('select[name="student_id"]').prop('required', true);
        } else if (targetType === 'room') {
            $('#roomSelection').show();
            $('select[name="room_id"]').prop('required', true);
        }
    });
    
    // Search and filter functionality
    var table = $('#offenseTable').DataTable({
        order: [],
        pageLength: 25
    });
    
    // Student search
    $('#studentSearch').on('keyup', function() {
        table.column(0).search(this.value).draw();
    });
    
    // Room search
    $('#roomSearch').on('keyup', function() {
        table.column(1).search(this.value).draw();
    });
    
    // Status filter
    $('#statusFilter').on('change', function() {
        table.column(5).search(this.value).draw();
    });
    
    // Severity filter
    $('#severityFilter').on('change', function() {
        table.column(4).search(this.value).draw();
    });
    
    // Offense type filter
    $('#offenseTypeFilter').on('change', function() {
        table.column(2).search(this.value).draw();
    });
    
    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#studentSearch').val('');
        $('#roomSearch').val('');
        $('#statusFilter').val('');
        $('#severityFilter').val('');
        $('#offenseTypeFilter').val('');
        table.search('').columns().search('').draw();
    });

});
</script>

<?php include 'includes/footer.php'; ?> 