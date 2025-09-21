<?php
require_once '../config/database.php';
// Include email configuration - try Hostinger version first, fallback to original
if (file_exists('../config/email_hostinger.php')) {
    require_once '../config/email_hostinger.php';
} else {
    require_once '../config/gmail_email.php';
}

$success_message = '';
$error_message = '';

// Handle approval/rejection BEFORE including header
if ($_POST) {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];
    $room_id = isset($_POST['room_id']) ? $_POST['room_id'] : null;
    $bed_space_id = isset($_POST['bed_space_id']) ? $_POST['bed_space_id'] : null;
    
    $pdo = getConnection();
    
    if ($action === 'approve') {
        if ($room_id && $bed_space_id) {
            try {
                $pdo->beginTransaction();
                
                // Get student details for email notification
                $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get room details for email notification
                $stmt = $pdo->prepare("
                    SELECT r.*, b.building_name, bs.bed_space_number 
                    FROM rooms r 
                    JOIN buildings b ON r.building_id = b.id 
                    JOIN bed_spaces bs ON bs.room_id = r.id 
                    WHERE r.id = ? AND bs.id = ?
                ");
                $stmt->execute([$room_id, $bed_space_id]);
                $room = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update student status and assign room
                $stmt = $pdo->prepare("UPDATE students SET application_status = 'approved', room_id = ?, bed_space_id = ? WHERE id = ?");
                $stmt->execute([$room_id, $bed_space_id, $student_id]);
                
                // Update bed space
                $stmt = $pdo->prepare("UPDATE bed_spaces SET is_occupied = TRUE, student_id = ? WHERE id = ?");
                $stmt->execute([$student_id, $bed_space_id]);
                
                // Update room occupancy
                $stmt = $pdo->prepare("UPDATE rooms SET occupied = occupied + 1, status = CASE WHEN occupied + 1 >= capacity THEN 'full' ELSE 'available' END WHERE id = ?");
                $stmt->execute([$room_id]);
                
                $pdo->commit();
                
                // Send approval email notification
                if ($student && $room) {
                    // Try Hostinger email function first, fallback to original
                    if (function_exists('sendStudentApprovalEmailHostinger')) {
                        $email_sent = sendStudentApprovalEmailHostinger($student, $room);
                    } else {
                        $email_sent = sendStudentApprovalEmail($student, $room);
                    }
                    
                    if ($email_sent) {
                        $success_message = 'Student application approved successfully! Approval email sent to student.';
                    } else {
                        $success_message = 'Student application approved successfully! However, email notification failed to send.';
                        error_log("Failed to send approval email to: " . $student['email']);
                    }
                } else {
                    $success_message = 'Student application approved successfully!';
                }
            } catch (Exception $e) {
                $pdo->rollback();
                $error_message = 'Error approving application: ' . $e->getMessage();
            }
        } else {
            $error_message = 'Please select a room and bed space for approval.';
        }
    } elseif ($action === 'reject') {
        // Get student details for email notification
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("UPDATE students SET application_status = 'rejected' WHERE id = ?");
        if ($stmt->execute([$student_id])) {
            // Send rejection email notification
            if ($student) {
                // Try Hostinger email function first, fallback to original
                if (function_exists('sendStudentRejectionEmailHostinger')) {
                    $email_sent = sendStudentRejectionEmailHostinger($student);
                } else {
                    $email_sent = sendStudentRejectionEmail($student);
                }
                
                if ($email_sent) {
                    $success_message = 'Student application rejected. Rejection email sent to student.';
                } else {
                    $success_message = 'Student application rejected. However, email notification failed to send.';
                    error_log("Failed to send rejection email to: " . $student['email']);
                }
            } else {
                $success_message = 'Student application rejected.';
            }
        } else {
            $error_message = 'Error rejecting application.';
        }
    }
}

$page_title = 'Online Reservation Management';
include 'includes/header.php';

// Get pending applications
$pdo = getConnection();
$stmt = $pdo->query("SELECT s.*, 
    CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) as full_name,
    r.room_number, r.building_id, b.name as building_name
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    ORDER BY s.application_status, s.created_at DESC");
$students = $stmt->fetchAll();

// Get available rooms and bed spaces
$stmt = $pdo->query("SELECT r.id as room_id, r.room_number, r.floor_number, r.capacity, r.occupied,
    b.name as building_name, b.id as building_id
    FROM rooms r
    JOIN buildings b ON r.building_id = b.id
    WHERE r.status = 'available' AND r.occupied < r.capacity
    ORDER BY b.name, r.room_number");
$available_rooms = $stmt->fetchAll();
?>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo count(array_filter($students, function($s) { return $s['application_status'] == 'pending'; })); ?></h3>
            <p class="mb-0">Pending Applications</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($students, function($s) { return $s['application_status'] == 'approved'; })); ?></h3>
            <p class="mb-0">Approved Students</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
            <h3><?php echo count(array_filter($students, function($s) { return $s['application_status'] == 'rejected'; })); ?></h3>
            <p class="mb-0">Rejected Applications</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count($available_rooms); ?></h3>
            <p class="mb-0">Available Rooms</p>
        </div>
    </div>
</div>

<!-- Student Applications Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-users"></i> Student Applications</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>Student Info</th>
                        <th>Contact Details</th>
                        <th>Guardian Info</th>
                        <th>Status</th>
                        <th>Room Assignment</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                <small class="text-muted">
                                    ID: <?php echo htmlspecialchars($student['school_id']); ?><br>
                                    LRN: <?php echo htmlspecialchars($student['learner_reference_number']); ?><br>
                                    <?php echo htmlspecialchars($student['gender']); ?>, 
                                    <?php echo date('M j, Y', strtotime($student['date_of_birth'])); ?>
                                </small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['mobile_number']); ?><br>
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?><br>
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($student['municipality'] . ', ' . $student['province']); ?>
                                </small>
                            </td>
                            <td>
                                <small>
                                    <strong><?php echo htmlspecialchars($student['guardian_name']); ?></strong><br>
                                    <?php echo htmlspecialchars($student['guardian_relationship']); ?><br>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['guardian_mobile']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($student['application_status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($student['application_status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['room_id']): ?>
                                    <small>
                                        <strong><?php echo htmlspecialchars($student['building_name']); ?></strong><br>
                                        Room <?php echo htmlspecialchars($student['room_number']); ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo date('M j, Y g:i A', strtotime($student['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm">
                                    <button class="btn btn-info btn-sm" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if ($student['application_status'] == 'pending'): ?>
                                        <button class="btn btn-success btn-sm" onclick="approveStudent(<?php echo $student['id']; ?>)">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="rejectStudent(<?php echo $student['id']; ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Student details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Student Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="approveStudentId">
                    <input type="hidden" name="action" value="approve">
                    
                    <div class="mb-3">
                        <label for="room_select" class="form-label">Select Room</label>
                        <select class="form-control" id="room_select" name="room_id" required onchange="loadBedSpaces()">
                            <option value="">Choose a room...</option>
                            <?php foreach ($available_rooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>" 
                                        data-building="<?php echo htmlspecialchars($room['building_name']); ?>"
                                        data-capacity="<?php echo $room['capacity']; ?>"
                                        data-occupied="<?php echo $room['occupied']; ?>">
                                    <?php echo htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']); ?>
                                    (<?php echo ($room['capacity'] - $room['occupied']); ?> beds available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bed_space_select" class="form-label">Select Bed Space</label>
                        <select class="form-control" id="bed_space_select" name="bed_space_id" required>
                            <option value="">First select a room...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Student Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="rejectStudentId">
                    <input type="hidden" name="action" value="reject">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Are you sure you want to reject this student's application? This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewStudent(studentId) {
    // Load student details via AJAX
    fetch(`get_student_details.php?id=${studentId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('studentDetails').innerHTML = data;
            new bootstrap.Modal(document.getElementById('studentModal')).show();
        });
}

function approveStudent(studentId) {
    document.getElementById('approveStudentId').value = studentId;
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function rejectStudent(studentId) {
    document.getElementById('rejectStudentId').value = studentId;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

function loadBedSpaces() {
    const roomSelect = document.getElementById('room_select');
    const bedSpaceSelect = document.getElementById('bed_space_select');
    const roomId = roomSelect.value;
    
    if (roomId) {
        fetch(`get_bed_spaces.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                bedSpaceSelect.innerHTML = '<option value="">Select bed space...</option>';
                data.forEach(bedSpace => {
                    bedSpaceSelect.innerHTML += `<option value="${bedSpace.id}">Bed ${bedSpace.bed_number}</option>`;
                });
            });
    } else {
        bedSpaceSelect.innerHTML = '<option value="">First select a room...</option>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>