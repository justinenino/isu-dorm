<?php
// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/database.php';
    requireStudent();
    
    $pdo = getConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register_visitor':
                $visitor_name = $_POST['visitor_name'];
                $visitor_age = $_POST['visitor_age'];
                $visitor_address = $_POST['visitor_address'];
                $contact_number = $_POST['contact_number'];
                $reason_of_visit = $_POST['reason_of_visit'];
                
                $stmt = $pdo->prepare("INSERT INTO visitor_logs (student_id, visitor_name, visitor_age, visitor_address, contact_number, reason_of_visit) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $visitor_name, $visitor_age, $visitor_address, $contact_number, $reason_of_visit]);
                
                $_SESSION['success'] = "Visitor registered successfully.";
                header("Location: visitor_logs.php");
                exit;
                break;
                
            case 'checkout_visitor':
                $visitor_id = $_POST['visitor_id'];
                
                $stmt = $pdo->prepare("UPDATE visitor_logs SET time_out = ? WHERE id = ? AND student_id = ?");
                $stmt->execute([date('Y-m-d H:i:s'), $visitor_id, $_SESSION['user_id']]);
                
                $_SESSION['success'] = "Visitor checked out successfully.";
                header("Location: visitor_logs.php");
                exit;
                break;
        }
    }
}

$page_title = 'Visitor Management';
include 'includes/header.php';

$pdo = getConnection();

// Get student's room information
$stmt = $pdo->prepare("SELECT s.*, r.room_number, b.name as building_name
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get student's visitor logs
$stmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE student_id = ? ORDER BY time_in DESC");
$stmt->execute([$_SESSION['user_id']]);
$visitor_logs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-users"></i> Visitor Management</h2>
        <p class="text-muted mb-0">Manage your own visitor registrations. Admins can view all logs but cannot add visitors.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerVisitorModal">
        <i class="fas fa-plus"></i> Register New Visitor
    </button>
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
            <p>Total Visitors</p>
        </div>
    </div>
</div>

<!-- Current Room Information -->
<?php if ($student['room_id']): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bed"></i> Your Room Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Building:</strong> <?php echo htmlspecialchars($student['building_name']); ?></p>
                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($student['room_number']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Student:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> You don't have a room assigned yet. Please wait for admin approval before registering visitors.
</div>
<?php endif; ?>

<!-- Visitor Logs Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Your Visitor Logs</h5>
    </div>
    <div class="card-body">
        <?php if (empty($visitor_logs)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No visitors registered yet</h5>
                <p class="text-muted">Register your first visitor to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped" id="visitorTable">
                    <thead>
                        <tr>
                            <th>Visitor Name</th>
                            <th>Age</th>
                            <th>Contact Number</th>
                            <th>Reason</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitor_logs as $visitor): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($visitor['visitor_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($visitor['visitor_age']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['contact_number']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($visitor['reason_of_visit'] ?? 'N/A'); ?></span></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($visitor['time_in'])); ?></td>
                                <td>
                                    <?php if ($visitor['time_out']): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($visitor['time_out'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not checked out</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($visitor['time_out']): ?>
                                        <span class="badge bg-success">Checked Out</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Inside</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewVisitorModal" 
                                            data-visitor='<?php echo json_encode($visitor); ?>'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!$visitor['time_out']): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="checkoutVisitor(<?php echo $visitor['id']; ?>)">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Register Visitor Modal -->
<?php if ($student['room_id']): ?>
<div class="modal fade" id="registerVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Register New Visitor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="register_visitor">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visitor Name</label>
                            <input type="text" name="visitor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visitor Age</label>
                            <input type="number" name="visitor_age" class="form-control" min="1" max="120" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reason of Visit</label>
                            <select name="reason_of_visit" class="form-control" required>
                                <option value="">Select reason...</option>
                                <option value="Project">Project</option>
                                <option value="Activities">Activities</option>
                                <option value="Friends">Friends</option>
                                <option value="Family">Family</option>
                                <option value="Study Group">Study Group</option>
                                <option value="Meeting">Meeting</option>
                                <option value="Personal">Personal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visitor Address</label>
                        <textarea name="visitor_address" class="form-control" rows="3" required placeholder="Complete address of the visitor"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Register Visitor</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- View Visitor Modal -->
<div class="modal fade" id="viewVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visitor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitorDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Checkout Confirmation Form -->
<form id="checkoutForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="checkout_visitor">
    <input type="hidden" name="visitor_id" id="checkoutVisitorId">
</form>

<script>
$(document).ready(function() {
    $('#visitorTable').DataTable({
        order: [[3, 'desc']],
        pageLength: 10
    });
    
    // Handle view visitor modal
    $('#viewVisitorModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var visitor = button.data('visitor');
        var modal = $(this);
        
        var statusClass = visitor.time_out ? 'badge bg-success' : 'badge bg-warning';
        var statusText = visitor.time_out ? 'Checked Out' : 'Inside';
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Visitor Information</h6>
                    <p><strong>Name:</strong> ${visitor.visitor_name}</p>
                    <p><strong>Age:</strong> ${visitor.visitor_age}</p>
                    <p><strong>Contact:</strong> ${visitor.contact_number}</p>
                    <p><strong>Status:</strong> <span class="${statusClass}">${statusText}</span></p>
                </div>
                <div class="col-md-6">
                    <h6>Visit Details</h6>
                    <p><strong>Room:</strong> ${visitor.room_number}</p>
                    <p><strong>Time In:</strong> ${new Date(visitor.time_in).toLocaleString()}</p>
                    <p><strong>Time Out:</strong> ${visitor.time_out ? new Date(visitor.time_out).toLocaleString() : 'Not checked out yet'}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Visitor Address</h6>
                    <p>${visitor.visitor_address}</p>
                </div>
            </div>
        `;
        
        modal.find('#visitorDetails').html(content);
    });
});

function checkoutVisitor(id) {
    if (confirm('Are you sure you want to check out this visitor?')) {
        $('#checkoutVisitorId').val(id);
        $('#checkoutForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 