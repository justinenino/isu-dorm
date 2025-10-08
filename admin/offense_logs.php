<?php
require_once '../config/database.php';
requireAdmin();

$page_title = 'Offense Logs (View Only)';
include 'includes/header.php';

$pdo = getConnection();

// Get all active students for dropdown
$stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name, ' (', school_id, ')') as name FROM students WHERE application_status = 'approved' AND is_deleted = 0 AND is_active = 1 ORDER BY first_name");
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

// Check if offenses table exists, if not create it
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'offenses'");
    $table_exists = $check_table->rowCount() > 0;
    
    if (!$table_exists) {
        // Create offenses table if it doesn't exist
        $create_table_sql = "
        CREATE TABLE IF NOT EXISTS `offenses` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `student_id` int(11) NOT NULL,
          `offense_type` varchar(100) NOT NULL,
          `description` text NOT NULL,
          `severity` enum('minor','major','critical') NOT NULL,
          `status` enum('pending','resolved','escalated') DEFAULT 'pending',
          `admin_notes` text DEFAULT NULL,
          `reported_by` varchar(100) NOT NULL,
          `complaint_id` int(11) DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `resolved_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_student_id` (`student_id`),
          KEY `idx_offense_type` (`offense_type`),
          KEY `idx_severity` (`severity`),
          KEY `idx_status` (`status`),
          KEY `idx_complaint_id` (`complaint_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_table_sql);
    }
    
    // Check if complaint_id column exists in offenses table
    $check_column = $pdo->query("SHOW COLUMNS FROM offenses LIKE 'complaint_id'");
    $has_complaint_id = $check_column->rowCount() > 0;
    
} catch (PDOException $e) {
    // If there's an error, assume table doesn't exist and set defaults
    $has_complaint_id = false;
    error_log("Error checking offenses table: " . $e->getMessage());
}

// Get offense logs with student details and complaint info (if column exists)
if ($has_complaint_id) {
    $stmt = $pdo->query("SELECT ol.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.school_id,
        r.room_number,
        b.name as building_name,
        c.subject as complaint_subject,
        c.id as complaint_id
        FROM offenses ol
        JOIN students s ON ol.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN complaints c ON ol.complaint_id = c.id
        ORDER BY ol.created_at DESC");
} else {
    $stmt = $pdo->query("SELECT ol.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.school_id,
        r.room_number,
        b.name as building_name,
        NULL as complaint_subject,
        NULL as complaint_id
        FROM offenses ol
        JOIN students s ON ol.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY ol.created_at DESC");
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
    <h2><i class="fas fa-exclamation-triangle"></i> Offense Logs</h2>
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
                            <td><?php echo date('M j, Y g:i A', strtotime($offense['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewOffenseModal" 
                                        data-offense='<?php echo json_encode($offense); ?>'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

<!-- (Update Status Modal removed for view-only mode) -->

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
                    <p><strong>Date:</strong> ${new Date(offense.created_at).toLocaleString()}</p>
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
            ${offense.admin_notes ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Action Taken</h6>
                    <p>${offense.admin_notes}</p>
                </div>
            </div>
            ` : ''}
        `;
        
        modal.find('#offenseDetails').html(content);
    });
    
    // (Update and add handlers removed for view-only mode)
    
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