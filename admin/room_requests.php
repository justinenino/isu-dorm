<?php
require_once '../config/database.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Log incoming POST for debugging
    if (isset($_POST['action'])) {
        error_log('[room_requests] POST action=' . $_POST['action'] . ', data=' . json_encode($_POST));
    }
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'process_request':
                $request_id = $_POST['request_id'] ?? null;
                $status = $_POST['status'] ?? null;
                $admin_response = $_POST['admin_response'] ?? null;
                
                if (empty($request_id) || empty($status) || empty($admin_response)) {
                    $_SESSION['error'] = 'All fields are required.';
                    header('Location: room_requests.php');
                    exit;
                }
                
                $pdo = getConnection();
                
                if ($status == 'approved') {
                    // Get request details
                    $stmt = $pdo->prepare("SELECT * FROM room_change_requests WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $request = $stmt->fetch();
                    
                    if ($request) {
                        // Prevent re-approving already processed requests
                        if (in_array($request['status'], ['approved', 'rejected'], true)) {
                            $_SESSION['error'] = 'This request has already been processed.';
                            header('Location: room_requests.php');
                            exit;
                        }
                        if (empty($request['requested_room_id'])) {
                            $_SESSION['error'] = 'Requested room is missing for this request.';
                            header('Location: room_requests.php');
                            exit;
                        }
                        // Begin transaction
                        $pdo->beginTransaction();
                        
                        try {
                            // Update student's room assignment
                            if (isset($request['requested_bed_space_id']) && $request['requested_bed_space_id']) {
                                $stmt = $pdo->prepare("UPDATE students SET room_id = ?, bed_space_id = ? WHERE id = ?");
                                $stmt->execute([$request['requested_room_id'], $request['requested_bed_space_id'], $request['student_id']]);
                            } else {
                                $stmt = $pdo->prepare("UPDATE students SET room_id = ? WHERE id = ?");
                                $stmt->execute([$request['requested_room_id'], $request['student_id']]);
                            }
                            
                            // Update bed space occupancy if bed space ID exists
                            if (isset($request['requested_bed_space_id']) && $request['requested_bed_space_id']) {
                                $stmt = $pdo->prepare("UPDATE bed_spaces SET is_occupied = TRUE, student_id = ? WHERE id = ?");
                                $stmt->execute([$request['student_id'], $request['requested_bed_space_id']]);
                            }
                            
                            // Update room occupancy based on bed spaces
                            $stmt = $pdo->prepare("UPDATE rooms SET occupied = (SELECT COUNT(*) FROM bed_spaces WHERE room_id = ? AND is_occupied = TRUE) WHERE id = ?");
                            $stmt->execute([$request['requested_room_id'], $request['requested_room_id']]);
                            
                            if ($request['current_room_id'] && $request['current_room_id'] != $request['requested_room_id']) {
                                // Free up the old bed space
                                $stmt = $pdo->prepare("UPDATE bed_spaces SET is_occupied = FALSE, student_id = NULL WHERE student_id = ?");
                                $stmt->execute([$request['student_id']]);
                                
                                // Update old room occupancy based on bed spaces
                                $stmt = $pdo->prepare("UPDATE rooms SET occupied = (SELECT COUNT(*) FROM bed_spaces WHERE room_id = ? AND is_occupied = TRUE) WHERE id = ?");
                                $stmt->execute([$request['current_room_id'], $request['current_room_id']]);
                            }
                            
                            // Update request status
                            $stmt = $pdo->prepare("UPDATE room_change_requests SET status = ?, admin_response = ?, processed_at = ? WHERE id = ?");
                            $stmt->execute([$status, $admin_response, date('Y-m-d H:i:s'), $request_id]);
                            
                            $pdo->commit();
                            $_SESSION['success'] = "Room change request approved successfully.";
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $_SESSION['error'] = "Error processing room change request: " . $e->getMessage();
                            error_log('[room_requests] Approve failed: ' . $e->getMessage());
                        }
                    } else {
                        $_SESSION['error'] = 'Request not found.';
                    }
                } else {
                    // Just update the status for rejected requests
                    $stmt = $pdo->prepare("UPDATE room_change_requests SET status = ?, admin_response = ?, processed_at = ? WHERE id = ?");
                    if ($stmt->execute([$status, $admin_response, date('Y-m-d H:i:s'), $request_id])) {
                        $_SESSION['success'] = "Room change request " . $status . " successfully.";
                    } else {
                        $_SESSION['error'] = 'Failed to update room change request.';
                        $err = $stmt->errorInfo();
                        error_log('[room_requests] Reject update failed: ' . json_encode($err));
                    }
                }
                
                header("Location: room_requests.php");
                exit;
                break;
                
            case 'flush_room_requests':
                try {
                    $pdo = getConnection();
                    
                    // Delete all room change requests
                    $pdo->exec("DELETE FROM room_change_requests");
                    
                    $_SESSION['success'] = "All room change requests have been deleted successfully.";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                header("Location: room_requests.php");
                exit;
                break;
        }
    }
}

$page_title = 'Room Request Management';
include 'includes/header.php';

$pdo = getConnection();

// Get room change requests with student and room details including bed spaces
$stmt = $pdo->query("SELECT rcr.*, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.school_id,
    current_r.room_number as current_room,
    current_b.name as current_building,
    current_bs.bed_number as current_bed_number,
    requested_r.room_number as requested_room,
    requested_b.name as requested_building,
    requested_bs.bed_number as requested_bed_number
    FROM room_change_requests rcr
    JOIN students s ON rcr.student_id = s.id
    LEFT JOIN rooms current_r ON rcr.current_room_id = current_r.id
    LEFT JOIN buildings current_b ON current_r.building_id = current_b.id
    LEFT JOIN bed_spaces current_bs ON rcr.current_bed_space_id = current_bs.id
    LEFT JOIN rooms requested_r ON rcr.requested_room_id = requested_r.id
    LEFT JOIN buildings requested_b ON requested_r.building_id = requested_b.id
    LEFT JOIN bed_spaces requested_bs ON rcr.requested_bed_space_id = requested_bs.id
    ORDER BY rcr.requested_at DESC");
$room_requests = $stmt->fetchAll();
?>

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
    <h2><i class="fas fa-exchange-alt"></i> Room Request Management</h2>
    <div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#flushRoomRequestsModal">
            <i class="fas fa-trash-alt"></i> Flush All Data
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($room_requests, function($rr) { return $rr['status'] == 'pending'; })); ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($room_requests, function($rr) { return $rr['status'] == 'approved'; })); ?></h3>
            <p>Approved</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
            <h3><?php echo count(array_filter($room_requests, function($rr) { return $rr['status'] == 'rejected'; })); ?></h3>
            <p>Rejected</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count($room_requests); ?></h3>
            <p>Total</p>
        </div>
    </div>
</div>

<!-- Room Requests Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Room Change Requests</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="roomRequestsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Current Room</th>
                        <th>Current Bed</th>
                        <th>Requested Room</th>
                        <th>Requested Bed</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($room_requests as $request): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($request['student_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($request['school_id']); ?></small>
                            </td>
                            <td>
                                <?php if ($request['current_room']): ?>
                                    <?php echo htmlspecialchars($request['current_building'] . ' - ' . $request['current_room']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($request['current_bed_number']): ?>
                                    <span class="badge bg-info">Bed <?php echo htmlspecialchars($request['current_bed_number']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($request['requested_room']): ?>
                                    <?php echo htmlspecialchars($request['requested_building'] . ' - ' . $request['requested_room']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($request['requested_bed_number']): ?>
                                    <span class="badge bg-primary">Bed <?php echo htmlspecialchars($request['requested_bed_number']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($request['reason']); ?>">
                                    <?php echo htmlspecialchars($request['reason']); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($request['status']) {
                                    case 'pending': $status_class = 'badge bg-warning'; break;
                                    case 'approved': $status_class = 'badge bg-success'; break;
                                    case 'rejected': $status_class = 'badge bg-danger'; break;
                                }
                                ?>
                                <span class="<?php echo $status_class; ?>"><?php echo ucfirst($request['status']); ?></span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal" 
                                        data-request='<?php echo json_encode($request); ?>'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#processRequestModal" 
                                            data-request-id="<?php echo $request['id']; ?>">
                                        <i class="fas fa-check"></i>
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

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Change Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Process Request Modal -->
<div class="modal fade" id="processRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Room Change Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="process_request">
                <input type="hidden" name="request_id" id="processRequestId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Decision</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Decision</option>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Response</label>
                        <textarea name="admin_response" class="form-control" rows="4" placeholder="Provide reason for approval or rejection" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ensure page scripts run after jQuery/Bootstrap are loaded (loaded in footer)
window.addEventListener('load', function() {
    $('#roomRequestsTable').DataTable({
        order: [[5, 'desc']],
        pageLength: 25
    });

    // Handle view request modal
    $('#viewRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var request = button.data('request');
        var modal = $(this);

        var statusClass = '';
        switch (request.status) {
            case 'pending': statusClass = 'badge bg-warning'; break;
            case 'approved': statusClass = 'badge bg-success'; break;
            case 'rejected': statusClass = 'badge bg-danger'; break;
        }

        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Student Information</h6>
                    <p><strong>Name:</strong> ${request.student_name}</p>
                    <p><strong>School ID:</strong> ${request.school_id}</p>
                    <p><strong>Status:</strong> <span class="${statusClass}">${request.status}</span></p>
                </div>
                <div class="col-md-6">
                    <h6>Request Details</h6>
                    <p><strong>Current Room:</strong> ${request.current_room ? (request.current_building + ' - ' + request.current_room) : 'Not assigned'}</p>
                    <p><strong>Current Bed:</strong> ${request.current_bed_number ? ('Bed ' + request.current_bed_number) : 'Not assigned'}</p>
                    <p><strong>Requested Room:</strong> ${request.requested_room ? (request.requested_building + ' - ' + request.requested_room) : 'Not specified'}</p>
                    <p><strong>Requested Bed:</strong> ${request.requested_bed_number ? ('Bed ' + request.requested_bed_number) : 'Not specified'}</p>
                    <p><strong>Requested:</strong> ${new Date(request.requested_at).toLocaleString()}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Room & Bed Space Change Summary</h6>
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-5">
                                    <h6 class="text-muted">FROM</h6>
                                    <div class="p-2 border rounded">
                                        <strong>${request.current_room ? (request.current_building + ' - ' + request.current_room) : 'Not assigned'}</strong><br>
                                        <span class="badge bg-info">${request.current_bed_number ? ('Bed ' + request.current_bed_number) : 'Not assigned'}</span>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-arrow-right fa-2x text-primary"></i>
                                </div>
                                <div class="col-md-5">
                                    <h6 class="text-muted">TO</h6>
                                    <div class="p-2 border rounded">
                                        <strong>${request.requested_room ? (request.requested_building + ' - ' + request.requested_room) : 'Not specified'}</strong><br>
                                        <span class="badge bg-primary">${request.requested_bed_number ? ('Bed ' + request.requested_bed_number) : 'Not specified'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Reason for Change</h6>
                    <p>${request.reason}</p>
                </div>
            </div>
            ${request.admin_response ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Admin Response</h6>
                    <p>${request.admin_response}</p>
                </div>
            </div>
            ` : ''}
            ${request.processed_at ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Processing</h6>
                    <p><strong>Processed:</strong> ${new Date(request.processed_at).toLocaleString()}</p>
                </div>
            </div>
            ` : ''}
        `;

        modal.find('#requestDetails').html(content);
    });

    // Handle process request modal
    $('#processRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var requestId = button.data('request-id');
        $('#processRequestId').val(requestId);
    });
});
</script>

<!-- Flush Room Requests Data Confirmation Modal -->
<div class="modal fade" id="flushRoomRequestsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Flush All Room Requests Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-warning"></i> WARNING: This action cannot be undone!</h6>
                    <p class="mb-0">This will permanently delete:</p>
                    <ul class="mb-0 mt-2">
                        <li>All room change requests</li>
                        <li>All admin responses and processing data</li>
                        <li>All request history and timestamps</li>
                    </ul>
                </div>
                <p class="mb-0">Are you absolutely sure you want to flush all room requests data?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="flush_room_requests">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Flush All Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 