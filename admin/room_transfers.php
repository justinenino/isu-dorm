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
                case 'approve_transfer':
                    $transfer_id = sanitizeInput($_POST['transfer_id']);
                    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
                    
                    $pdo->beginTransaction();
                    
                    // Get transfer details
                    $stmt = $pdo->prepare("SELECT * FROM room_transfers WHERE id = ?");
                    $stmt->execute([$transfer_id]);
                    $transfer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$transfer) {
                        throw new Exception('Transfer request not found');
                    }
                    
                    // Check if target bedspace is still available
                    $stmt = $pdo->prepare("SELECT status FROM bedspaces WHERE id = ?");
                    $stmt->execute([$transfer['target_bedspace_id']]);
                    $bedspace = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$bedspace || $bedspace['status'] !== 'available') {
                        throw new Exception('Target bedspace is no longer available');
                    }
                    
                    // Update current bedspace to available
                    $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'available', student_id = NULL WHERE id = ?");
                    $stmt->execute([$transfer['current_bedspace_id']]);
                    
                    // Update target bedspace to occupied
                    $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'occupied', student_id = ? WHERE id = ?");
                    $stmt->execute([$transfer['student_id'], $transfer['target_bedspace_id']]);
                    
                    // Update transfer status
                    $stmt = $pdo->prepare("
                        UPDATE room_transfers 
                        SET status = 'approved', admin_notes = ?, processed_by = ?, processed_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin_notes, $_SESSION['user_id'], $transfer_id]);
                    
                    $pdo->commit();
                    
                    logActivity($_SESSION['user_id'], "Approved room transfer request ID: $transfer_id");
                    echo json_encode(['success' => true, 'message' => 'Transfer request approved successfully']);
                    break;
                    
                case 'reject_transfer':
                    $transfer_id = sanitizeInput($_POST['transfer_id']);
                    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
                    
                    $stmt = $pdo->prepare("
                        UPDATE room_transfers 
                        SET status = 'rejected', admin_notes = ?, processed_by = ?, processed_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin_notes, $_SESSION['user_id'], $transfer_id]);
                    
                    logActivity($_SESSION['user_id'], "Rejected room transfer request ID: $transfer_id");
                    echo json_encode(['success' => true, 'message' => 'Transfer request rejected']);
                    break;
                    
                case 'delete_transfer':
                    $transfer_id = sanitizeInput($_POST['transfer_id']);
                    
                    $stmt = $pdo->prepare("DELETE FROM room_transfers WHERE id = ?");
                    $stmt->execute([$transfer_id]);
                    
                    logActivity($_SESSION['user_id'], "Deleted room transfer request ID: $transfer_id");
                    echo json_encode(['success' => true, 'message' => 'Transfer request deleted']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in room_transfers.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');
$building_filter = sanitizeInput($_GET['building'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR rt.reason LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "rt.status = ?";
    $params[] = $status_filter;
}

if (!empty($building_filter)) {
    $where_conditions[] = "(cb.id = ? OR tb.id = ?)";
    $params[] = $building_filter;
    $params[] = $building_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $pdo = getDBConnection();
    
    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM room_transfers
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get transfers with student and room details
    $stmt = $pdo->prepare("
        SELECT 
            rt.*,
            s.first_name, s.last_name, s.middle_name, s.student_id, s.mobile_number, s.email,
            cr.room_number as current_room, cb.building_name as current_building,
            tr.room_number as target_room, tb.building_name as target_building,
            cbs.bedspace_number as current_bedspace, tbs.bedspace_number as target_bedspace,
            u.username as processed_by_name
        FROM room_transfers rt
        LEFT JOIN students s ON rt.student_id = s.id
        LEFT JOIN bedspaces cbs ON rt.current_bedspace_id = cbs.id
        LEFT JOIN rooms cr ON cbs.room_id = cr.id
        LEFT JOIN buildings cb ON cr.building_id = cb.id
        LEFT JOIN bedspaces tbs ON rt.target_bedspace_id = tbs.id
        LEFT JOIN rooms tr ON tbs.room_id = tr.id
        LEFT JOIN buildings tb ON tr.building_id = tb.id
        LEFT JOIN users u ON rt.processed_by = u.id
        $where_clause
        ORDER BY rt.created_at DESC
    ");
    $stmt->execute($params);
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get buildings for filter
    $buildings_stmt = $pdo->prepare("SELECT * FROM buildings ORDER BY building_name");
    $buildings_stmt->execute();
    $buildings = $buildings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error in room_transfers.php: " . $e->getMessage());
    $transfers = [];
    $buildings = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Room Transfer Requests</h1>
                <p class="text-muted">Manage student room transfer requests</p>
            </div>
            <div>
                <button class="btn btn-outline-primary" onclick="exportTransfers()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
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
                                <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                                <p class="mb-0">Total Requests</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exchange-alt fa-2x"></i>
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
                                <h4 class="mb-0"><?php echo $stats['pending']; ?></h4>
                                <p class="mb-0">Pending</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
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
                                <h4 class="mb-0"><?php echo $stats['approved']; ?></h4>
                                <p class="mb-0">Approved</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['rejected']; ?></h4>
                                <p class="mb-0">Rejected</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-times fa-2x"></i>
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
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Student name, ID, or reason...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                        <a href="room_transfers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transfers Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transfer Requests (<?php echo count($transfers); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($transfers)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No transfer requests found</h5>
                        <p class="text-muted">Transfer requests will appear here when students submit them.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="transfersTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Current Room</th>
                                    <th>Target Room</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transfers as $transfer): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-white fw-bold">
                                                        <?php echo strtoupper(substr($transfer['first_name'], 0, 1) . substr($transfer['last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($transfer['first_name'] . ' ' . $transfer['last_name']); ?>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($transfer['student_id']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($transfer['current_building'] . ' - ' . $transfer['current_room']); ?></strong>
                                                <br>
                                                <small class="text-muted">Bed <?php echo $transfer['current_bedspace']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($transfer['target_building'] . ' - ' . $transfer['target_room']); ?></strong>
                                                <br>
                                                <small class="text-muted">Bed <?php echo $transfer['target_bedspace']; ?></small>
                                            </div>
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
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewTransferDetails(<?php echo $transfer['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($transfer['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="approveTransfer(<?php echo $transfer['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="rejectTransfer(<?php echo $transfer['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteTransfer(<?php echo $transfer['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
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

<!-- Approve Transfer Modal -->
<div class="modal fade" id="approveTransferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Transfer Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveTransferForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        This will move the student to their requested room and bedspace.
                    </div>
                    <div class="mb-3">
                        <label for="approveNotes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="approveNotes" name="admin_notes" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    <input type="hidden" id="approveTransferId" name="transfer_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Transfer Modal -->
<div class="modal fade" id="rejectTransferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Transfer Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectTransferForm">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Please provide a reason for rejecting this transfer request.
                    </div>
                    <div class="mb-3">
                        <label for="rejectNotes" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" id="rejectNotes" name="admin_notes" rows="3" 
                                  placeholder="Explain why this transfer request is being rejected..." required></textarea>
                    </div>
                    <input type="hidden" id="rejectTransferId" name="transfer_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#transfersTable').DataTable({
        pageLength: 25,
        order: [[5, 'desc']], // Sort by date
        columnDefs: [
            { orderable: false, targets: [6] } // Disable sorting for actions column
        ]
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

// Approve transfer
function approveTransfer(transferId) {
    $('#approveTransferId').val(transferId);
    $('#approveTransferModal').modal('show');
}

// Reject transfer
function rejectTransfer(transferId) {
    $('#rejectTransferId').val(transferId);
    $('#rejectTransferModal').modal('show');
}

// Delete transfer
function deleteTransfer(transferId) {
    if (confirm('Are you sure you want to delete this transfer request? This action cannot be undone.')) {
        $.post('room_transfers.php', {
            action: 'delete_transfer',
            transfer_id: transferId
        }, function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        }, 'json').fail(function() {
            showAlert('Error deleting transfer request', 'danger');
        });
    }
}

// Handle approve form submission
$('#approveTransferForm').on('submit', function(e) {
    e.preventDefault();
    
    $.post('room_transfers.php', {
        action: 'approve_transfer',
        transfer_id: $('#approveTransferId').val(),
        admin_notes: $('#approveNotes').val()
    }, function(response) {
        if (response.success) {
            $('#approveTransferModal').modal('hide');
            showAlert(response.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function() {
        showAlert('Error approving transfer request', 'danger');
    });
});

// Handle reject form submission
$('#rejectTransferForm').on('submit', function(e) {
    e.preventDefault();
    
    $.post('room_transfers.php', {
        action: 'reject_transfer',
        transfer_id: $('#rejectTransferId').val(),
        admin_notes: $('#rejectNotes').val()
    }, function(response) {
        if (response.success) {
            $('#rejectTransferModal').modal('hide');
            showAlert(response.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function() {
        showAlert('Error rejecting transfer request', 'danger');
    });
});

// Export transfers
function exportTransfers() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = 'export_room_transfers.php?' + params.toString();
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
