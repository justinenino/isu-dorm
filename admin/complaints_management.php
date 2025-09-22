<?php
require_once '../config/database.php';

/**
 * Parse complaint subject to determine target type and value
 */
function parseComplaintSubject($subject) {
    // Check if subject contains "(Regarding Room X)" pattern
    if (preg_match('/\(Regarding Room (\d+)\)/', $subject, $matches)) {
        return [
            'type' => 'room',
            'value' => $matches[1]
        ];
    }
    
    // Check if subject contains "(Regarding Person Name)" pattern
    if (preg_match('/\(Regarding ([^)]+)\)/', $subject, $matches)) {
        return [
            'type' => 'person',
            'value' => trim($matches[1])
        ];
    }
    
    // Default to general complaint
    return [
        'type' => 'general',
        'value' => null
    ];
}

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $complaint_id = $_POST['complaint_id'];
                $status = $_POST['status'];
                $admin_response = $_POST['admin_response'];
                
                $pdo = getConnection();
                $stmt = $pdo->prepare("UPDATE complaints SET status = ?, admin_response = ?, resolved_at = ? WHERE id = ?");
                $resolved_at = ($status == 'resolved' || $status == 'closed') ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$status, $admin_response, $resolved_at, $complaint_id]);
                
                $_SESSION['success'] = "Complaint status updated successfully.";
                header("Location: complaints_management.php");
                exit;
                break;
                
            case 'flush_complaints':
                try {
                    $pdo = getConnection();
                    
                    // Delete all complaints and related data
                    $pdo->exec("DELETE FROM offense_logs WHERE complaint_id IS NOT NULL");
                    $pdo->exec("DELETE FROM complaints");
                    
                    $_SESSION['success'] = "All complaints and related data have been deleted successfully.";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                header("Location: complaints_management.php");
                exit;
                break;
                
            case 'convert_to_offense':
                $complaint_id = $_POST['complaint_id'];
                $student_id = $_POST['student_id'];
                $offense_type = $_POST['offense_type'];
                $severity = $_POST['severity'];
                $action_taken = $_POST['action_taken'];
                $reported_by = $_POST['reported_by'];
                
                
                $pdo = getConnection();
                
                // Get complaint details
                $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ?");
                $stmt->execute([$complaint_id]);
                $complaint = $stmt->fetch();
                
                if ($complaint) {
                    // Get target information from form
                    $target_type = $_POST['target_type'] ?? 'general';
                    $target_value = $_POST['target_value'] ?? '';
                    
                    $offense_count = 0;
                    $offense_message = "";
                    
                    if ($target_type === 'room') {
                        // Create offense for all students in the target room
                        $stmt = $pdo->prepare("SELECT s.id, s.first_name, s.last_name 
                            FROM students s 
                            JOIN rooms r ON s.room_id = r.id 
                            WHERE r.room_number = ? AND s.application_status = 'approved'");
                        $stmt->execute([$target_value]);
                        $room_students = $stmt->fetchAll();
                        
                        if (!empty($room_students)) {
                            // Check if complaint_id column exists
                            $check_column = $pdo->query("SHOW COLUMNS FROM offense_logs LIKE 'complaint_id'");
                            $has_complaint_id = $check_column->rowCount() > 0;
                            
                            foreach ($room_students as $student) {
                                if ($has_complaint_id) {
                                    $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by, complaint_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([$student['id'], $offense_type, $complaint['description'], $severity, $action_taken, $reported_by, $complaint_id]);
                                } else {
                                    $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([$student['id'], $offense_type, $complaint['description'], $severity, $action_taken, $reported_by]);
                                }
                                $offense_count++;
                            }
                            $offense_message = "Offense created for " . $offense_count . " student(s) in Room " . $target_value;
                        } else {
                            $_SESSION['error'] = "No students found in Room " . $target_value;
                            header("Location: complaints_management.php");
                            exit;
                        }
                        
                    } elseif ($target_type === 'person') {
                        // Create offense for specific person
                        $stmt = $pdo->prepare("SELECT s.id, s.first_name, s.last_name 
                            FROM students s 
                            WHERE CONCAT(s.first_name, ' ', s.last_name) = ? AND s.application_status = 'approved'");
                        $stmt->execute([$target_value]);
                        $target_student = $stmt->fetch();
                        
                        if ($target_student) {
                            // Check if complaint_id column exists
                            $check_column = $pdo->query("SHOW COLUMNS FROM offense_logs LIKE 'complaint_id'");
                            $has_complaint_id = $check_column->rowCount() > 0;
                            
                            if ($has_complaint_id) {
                                $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by, complaint_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$target_student['id'], $offense_type, $complaint['description'], $severity, $action_taken, $reported_by, $complaint_id]);
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$target_student['id'], $offense_type, $complaint['description'], $severity, $action_taken, $reported_by]);
                            }
                            $offense_count = 1;
                            $offense_message = "Offense created for " . $target_value;
                        } else {
                            $_SESSION['error'] = "Student '" . $target_value . "' not found.";
                            header("Location: complaints_management.php");
                            exit;
                        }
                        
                    } else {
                        // General complaint - create offense for complaining student only
                        $check_column = $pdo->query("SHOW COLUMNS FROM offense_logs LIKE 'complaint_id'");
                        $has_complaint_id = $check_column->rowCount() > 0;
                        
                        if ($has_complaint_id) {
                            $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by, complaint_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$student_id, $offense_type, $complaint['description'], $severity, $action_taken, $reported_by, $complaint_id]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$student_id, $offense_type, $complaint['description'], $severity, $action_taken, $reported_by]);
                        }
                        $offense_count = 1;
                        $offense_message = "Offense created for complaining student";
                    }
                    
                    // Update complaint status to indicate it was converted
                    $stmt = $pdo->prepare("UPDATE complaints SET status = 'closed', admin_response = CONCAT(IFNULL(admin_response, ''), '\n\nConverted to offense log. ', ?, '. Action taken: ', ?), resolved_at = ? WHERE id = ?");
                    $stmt->execute([$offense_message, $action_taken, date('Y-m-d H:i:s'), $complaint_id]);
                    
                    $_SESSION['success'] = "Complaint converted to offense log successfully. " . $offense_message . ".";
                } else {
                    $_SESSION['error'] = "Complaint not found.";
                }
                
                header("Location: complaints_management.php");
                exit;
                break;
        }
    }
}

$page_title = 'Complaints Management';
include 'includes/header.php';

$pdo = getConnection();

// Check if complaint_id column exists in offense_logs table
$check_column = $pdo->query("SHOW COLUMNS FROM offense_logs LIKE 'complaint_id'");
$has_complaint_id = $check_column->rowCount() > 0;

// Get complaints with student details
$stmt = $pdo->query("SELECT c.*, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.school_id,
    r.room_number,
    b.name as building_name
    FROM complaints c
    JOIN students s ON c.student_id = s.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    ORDER BY c.created_at DESC");
$complaints = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-comment-alt"></i> Complaints Management</h2>
    <div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#flushComplaintsModal">
            <i class="fas fa-trash-alt"></i> Flush All Data
        </button>
    </div>
</div>

<?php if (!$has_complaint_id): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Database Update Required:</strong> To enable complaint to offense conversion, please run the database update script. 
        <a href="../update_database.php" class="alert-link" target="_blank">Run Update Script</a>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'pending'; })); ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'investigating'; })); ?></h3>
            <p>Investigating</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'resolved'; })); ?></h3>
            <p>Resolved</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
            <h3><?php echo count(array_filter($complaints, function($c) { return $c['status'] == 'closed'; })); ?></h3>
            <p>Closed</p>
        </div>
    </div>
</div>

<!-- Complaints Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Student Complaints</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="complaintsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($complaint['student_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($complaint['school_id']); ?></small>
                            </td>
                            <td>
                                <?php if ($complaint['room_number']): ?>
                                    <?php echo htmlspecialchars($complaint['building_name'] . ' - ' . $complaint['room_number']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($complaint['description']); ?>">
                                    <?php echo htmlspecialchars($complaint['description']); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($complaint['status']) {
                                    case 'pending': $status_class = 'badge bg-warning'; break;
                                    case 'investigating': $status_class = 'badge bg-info'; break;
                                    case 'resolved': $status_class = 'badge bg-success'; break;
                                    case 'closed': $status_class = 'badge bg-secondary'; break;
                                }
                                ?>
                                <span class="<?php echo $status_class; ?>"><?php echo ucfirst($complaint['status']); ?></span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($complaint['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewComplaintModal" 
                                        data-complaint='<?php echo json_encode($complaint); ?>'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($complaint['status'] != 'resolved' && $complaint['status'] != 'closed'): ?>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateComplaintModal" 
                                            data-complaint-id="<?php echo $complaint['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#convertToOffenseModal" 
                                            data-complaint='<?php echo json_encode($complaint); ?>'>
                                        <i class="fas fa-exclamation-triangle"></i>
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

<!-- View Complaint Modal -->
<div class="modal fade" id="viewComplaintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complaint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Update Complaint Modal -->
<div class="modal fade" id="updateComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Complaint Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="complaint_id" id="updateComplaintId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="investigating">Investigating</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Response</label>
                        <textarea name="admin_response" class="form-control" rows="4" placeholder="Provide response or action taken" required></textarea>
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

<!-- Convert to Offense Modal -->
<div class="modal fade" id="convertToOffenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Convert Complaint to Offense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="convert_to_offense">
                <input type="hidden" name="complaint_id" id="convertComplaintId">
                <input type="hidden" name="student_id" id="convertStudentId">
                <input type="hidden" name="target_type" id="hiddenTargetType">
                <input type="hidden" name="target_value" id="hiddenTargetValue">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This will convert the complaint into an offense log entry based on the complaint subject. The complaint will be marked as closed.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Complaint Subject</label>
                        <input type="text" id="convertComplaintSubject" class="form-control" readonly>
                    </div>
                    
                    <div class="row">
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select" required>
                                <option value="minor">Minor</option>
                                <option value="major">Major</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reported By</label>
                        <input type="text" name="reported_by" class="form-control" required placeholder="Admin name or identifier">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Action Taken</label>
                        <textarea name="action_taken" class="form-control" rows="4" required placeholder="Describe the action taken or disciplinary measure"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Convert to Offense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Flush Complaints Data Confirmation Modal -->
<div class="modal fade" id="flushComplaintsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Flush All Complaints Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-warning"></i> WARNING: This action cannot be undone!</h6>
                    <p class="mb-0">This will permanently delete:</p>
                    <ul class="mb-0 mt-2">
                        <li>All complaints</li>
                        <li>All related offense logs</li>
                        <li>All admin responses and resolution data</li>
                    </ul>
                </div>
                <p class="mb-0">Are you absolutely sure you want to flush all complaints data?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="flush_complaints">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Flush All Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#complaintsTable').DataTable({
        order: [[5, 'desc']],
        pageLength: 25
    });
    
    // Handle view complaint modal
    $('#viewComplaintModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var complaint = button.data('complaint');
        var modal = $(this);
        
        var content = '<div class="row">' +
            '<div class="col-md-6"><strong>Student:</strong> ' + complaint.student_name + ' (' + complaint.school_id + ')</div>' +
            '<div class="col-md-6"><strong>Room:</strong> ' + (complaint.room_number ? (complaint.building_name + ' - ' + complaint.room_number) : 'Not assigned') + '</div>' +
            '</div>' +
            '<div class="row mt-3">' +
            '<div class="col-12"><strong>Subject:</strong> ' + complaint.subject + '</div>' +
            '</div>' +
            '<div class="row mt-3">' +
            '<div class="col-12"><strong>Description:</strong><br>' + complaint.description + '</div>' +
            '</div>' +
            '<div class="row mt-3">' +
            '<div class="col-md-6"><strong>Status:</strong> <span class="badge bg-warning">' + complaint.status + '</span></div>' +
            '<div class="col-md-6"><strong>Submitted:</strong> ' + new Date(complaint.created_at).toLocaleString() + '</div>' +
            '</div>';
        
        if (complaint.admin_response) {
            content += '<div class="row mt-3">' +
                '<div class="col-12"><strong>Admin Response:</strong><br>' + complaint.admin_response + '</div>' +
                '</div>';
        }
        
        modal.find('#complaintDetails').html(content);
    });
    
    // Handle update complaint modal
    $('#updateComplaintModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var complaintId = button.data('complaint-id');
        $('#updateComplaintId').val(complaintId);
    });
    
    // Handle convert to offense modal
    $('#convertToOffenseModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var complaint = button.data('complaint');
        var modal = $(this);
        
        $('#convertComplaintId').val(complaint.id);
        $('#convertStudentId').val(complaint.student_id);
        $('#convertComplaintSubject').val(complaint.subject);
        
        // Analyze complaint subject and set hidden target information
        var subject = complaint.subject;
        var targetInfo = analyzeComplaintSubject(subject);
        
        // Set hidden target information based on complaint subject
        $('#hiddenTargetType').val(targetInfo.type);
        $('#hiddenTargetValue').val(targetInfo.value || '');
        
    });
    
    // Function to analyze complaint subject
    function analyzeComplaintSubject(subject) {
        // Check if subject contains "(Regarding Room X)" pattern
        var roomMatch = subject.match(/\(Regarding Room (\d+)\)/);
        if (roomMatch) {
            return {
                type: 'room',
                value: roomMatch[1]
            };
        }
        
        // Check if subject contains "(Regarding Person Name)" pattern
        var personMatch = subject.match(/\(Regarding ([^)]+)\)/);
        if (personMatch) {
            return {
                type: 'person',
                value: personMatch[1].trim()
            };
        }
        
        // Default to general complaint
        return {
            type: 'general',
            value: null
        };
    }
});
</script> 