<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || isAdmin()) {
    redirect('../index.php');
}

// Check if student is approved
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT status FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student || $student['status'] !== 'approved') {
    redirect('dashboard.php?error=not_approved');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $target_bedspace_id = sanitizeInput($_POST['target_bedspace_id']);
        $reason = sanitizeInput($_POST['reason']);
        $contact_preference = sanitizeInput($_POST['contact_preference']);
        
        // Get student details
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_data) {
            throw new Exception('Student record not found');
        }
        
        // Get current bedspace
        $stmt = $pdo->prepare("SELECT id FROM bedspaces WHERE student_id = ?");
        $stmt->execute([$student_data['id']]);
        $current_bedspace = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_bedspace) {
            throw new Exception('You must have a current room assignment to request a transfer');
        }
        
        // Check if target bedspace is available
        $stmt = $pdo->prepare("SELECT status FROM bedspaces WHERE id = ?");
        $stmt->execute([$target_bedspace_id]);
        $target_bedspace = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_bedspace || $target_bedspace['status'] !== 'available') {
            throw new Exception('Selected bedspace is not available');
        }
        
        // Check if student already has a pending transfer request
        $stmt = $pdo->prepare("SELECT id FROM room_transfers WHERE student_id = ? AND status = 'pending'");
        $stmt->execute([$student_data['id']]);
        if ($stmt->fetch()) {
            throw new Exception('You already have a pending transfer request');
        }
        
        // Insert transfer request
        $stmt = $pdo->prepare("
            INSERT INTO room_transfers (
                student_id, current_bedspace_id, target_bedspace_id, reason, 
                contact_preference, status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $student_data['id'],
            $current_bedspace['id'],
            $target_bedspace_id,
            $reason,
            $contact_preference
        ]);
        
        logActivity($_SESSION['user_id'], "Submitted room transfer request");
        echo json_encode(['success' => true, 'message' => 'Transfer request submitted successfully']);
        
    } catch (Exception $e) {
        error_log("Error in room_transfer.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get student's current room assignment
try {
    $stmt = $pdo->prepare("
        SELECT 
            s.id as student_id,
            bs.id as bedspace_id, bs.bedspace_number,
            r.room_number, r.floor, r.capacity,
            b.building_name
        FROM students s
        LEFT JOIN bedspaces bs ON s.id = bs.student_id
        LEFT JOIN rooms r ON bs.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get transfer history
    $stmt = $pdo->prepare("
        SELECT 
            rt.*,
            cr.room_number as current_room, cb.building_name as current_building,
            tr.room_number as target_room, tb.building_name as target_building,
            cbs.bedspace_number as current_bedspace, tbs.bedspace_number as target_bedspace
        FROM room_transfers rt
        LEFT JOIN bedspaces cbs ON rt.current_bedspace_id = cbs.id
        LEFT JOIN rooms cr ON cbs.room_id = cr.id
        LEFT JOIN buildings cb ON cr.building_id = cb.id
        LEFT JOIN bedspaces tbs ON rt.target_bedspace_id = tbs.id
        LEFT JOIN rooms tr ON tbs.room_id = tr.id
        LEFT JOIN buildings tb ON tr.building_id = tb.id
        WHERE rt.student_id = ?
        ORDER BY rt.created_at DESC
    ");
    $stmt->execute([$current_assignment['student_id']]);
    $transfer_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available bedspaces
    $stmt = $pdo->prepare("
        SELECT 
            bs.id, bs.bedspace_number,
            r.room_number, r.floor, r.capacity,
            b.building_name, b.id as building_id
        FROM bedspaces bs
        JOIN rooms r ON bs.room_id = r.id
        JOIN buildings b ON r.building_id = b.id
        WHERE bs.status = 'available'
        ORDER BY b.building_name, r.room_number, bs.bedspace_number
    ");
    $stmt->execute();
    $available_bedspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM room_transfers 
        WHERE student_id = ?
    ");
    $stmt->execute([$current_assignment['student_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error in room_transfer.php: " . $e->getMessage());
    $current_assignment = null;
    $transfer_history = [];
    $available_bedspaces = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Room Transfer Request</h1>
                <p class="text-muted">Request to transfer to a different room</p>
            </div>
        </div>

        <?php if (!$current_assignment || !$current_assignment['bedspace_id']): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>No Current Room Assignment</strong><br>
                You must have a current room assignment before you can request a transfer.
                Please contact the dormitory administration.
            </div>
        <?php else: ?>
            <!-- Current Assignment Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bed"></i> Current Room Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Building:</strong><br>
                            <?php echo htmlspecialchars($current_assignment['building_name']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Room:</strong><br>
                            <?php echo htmlspecialchars($current_assignment['room_number']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Bedspace:</strong><br>
                            <?php echo htmlspecialchars($current_assignment['bedspace_number']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Floor:</strong><br>
                            <?php echo htmlspecialchars($current_assignment['floor']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                            <p class="mb-0">Total Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><?php echo $stats['pending']; ?></h4>
                            <p class="mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><?php echo $stats['approved']; ?></h4>
                            <p class="mb-0">Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><?php echo $stats['rejected']; ?></h4>
                            <p class="mb-0">Rejected</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer Request Form -->
            <?php if ($stats['pending'] == 0): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Submit Transfer Request</h5>
                    </div>
                    <div class="card-body">
                        <form id="transferRequestForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="targetBedspace" class="form-label">Target Room & Bedspace *</label>
                                        <select class="form-select" id="targetBedspace" name="target_bedspace_id" required>
                                            <option value="">Select available bedspace...</option>
                                            <?php 
                                            $current_building = '';
                                            foreach ($available_bedspaces as $bedspace): 
                                                if ($current_building !== $bedspace['building_name']) {
                                                    if ($current_building !== '') echo '</optgroup>';
                                                    echo '<optgroup label="' . htmlspecialchars($bedspace['building_name']) . '">';
                                                    $current_building = $bedspace['building_name'];
                                                }
                                            ?>
                                                <option value="<?php echo $bedspace['id']; ?>">
                                                    Room <?php echo $bedspace['room_number']; ?> - Bed <?php echo $bedspace['bedspace_number']; ?> 
                                                    (Floor <?php echo $bedspace['floor']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if ($current_building !== '') echo '</optgroup>'; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contactPreference" class="form-label">Contact Preference *</label>
                                        <select class="form-select" id="contactPreference" name="contact_preference" required>
                                            <option value="">Select contact method...</option>
                                            <option value="email">Email</option>
                                            <option value="phone">Phone/SMS</option>
                                            <option value="facebook">Facebook</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Transfer *</label>
                                <textarea class="form-control" id="reason" name="reason" rows="4" required
                                          placeholder="Please explain why you want to transfer to a different room..."></textarea>
                                <div class="form-text">
                                    Be specific about your reasons. Common reasons include roommate conflicts, 
                                    medical needs, academic requirements, or personal preferences.
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Important Notes:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Transfer requests are subject to approval by dormitory administration</li>
                                    <li>You can only have one pending transfer request at a time</li>
                                    <li>Approved transfers will be processed within 1-3 business days</li>
                                    <li>You will be notified of the decision via your preferred contact method</li>
                                </ul>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Transfer Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Pending Request</strong><br>
                    You already have a pending transfer request. Please wait for it to be processed before submitting a new one.
                </div>
            <?php endif; ?>

            <!-- Transfer History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Transfer Request History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($transfer_history)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No transfer requests yet</h5>
                            <p class="text-muted">Your transfer request history will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transfer_history as $transfer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($transfer['current_building'] . ' - ' . $transfer['current_room']); ?></strong>
                                                <br>
                                                <small class="text-muted">Bed <?php echo $transfer['current_bedspace']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($transfer['target_building'] . ' - ' . $transfer['target_room']); ?></strong>
                                                <br>
                                                <small class="text-muted">Bed <?php echo $transfer['target_bedspace']; ?></small>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                      title="<?php echo htmlspecialchars($transfer['reason']); ?>">
                                                    <?php echo htmlspecialchars($transfer['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($transfer['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'rejected':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($transfer['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <small class="text-muted">Requested:</small><br>
                                                    <?php echo formatDate($transfer['created_at']); ?>
                                                    <?php if ($transfer['processed_at']): ?>
                                                        <br><small class="text-muted">Processed:</small><br>
                                                        <?php echo formatDate($transfer['processed_at']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewTransferDetails(<?php echo $transfer['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transfer Details Modal -->
<div class="modal fade" id="transferDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transfer Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transferDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Handle form submission
$('#transferRequestForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);
    
    $.post('room_transfer.php', $(this).serialize(), function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            $('#transferRequestForm')[0].reset();
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function() {
        showAlert('Error submitting transfer request', 'danger');
    }).always(function() {
        submitBtn.html(originalText).prop('disabled', false);
    });
});

// View transfer details
function viewTransferDetails(transferId) {
    $.post('get_transfer_details.php', {
        transfer_id: transferId
    }, function(response) {
        $('#transferDetailsContent').html(response);
        $('#transferDetailsModal').modal('show');
    }).fail(function() {
        showAlert('Error loading transfer details', 'danger');
    });
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
