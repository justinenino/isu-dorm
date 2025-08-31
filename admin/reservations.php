<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle reservation operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'approve_reservation') {
            $reservation_id = (int)$_POST['reservation_id'];
            $bedspace_id = (int)$_POST['bedspace_id'];
            $student_id = (int)$_POST['student_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Update reservation status
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE reservation_id = ?");
                $stmt->execute([$_SESSION['user_id'], $reservation_id]);
                
                // Update bedspace status to reserved
                $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'reserved' WHERE bedspace_id = ?");
                $stmt->execute([$bedspace_id]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Reservation approved: ID ' . $reservation_id);
                
                $pdo->commit();
                $message = 'Reservation approved successfully!';
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error approving reservation: ' . $e->getMessage();
            }
        } elseif ($action === 'reject_reservation') {
            $reservation_id = (int)$_POST['reservation_id'];
            $bedspace_id = (int)$_POST['bedspace_id'];
            $rejection_reason = sanitizeInput($_POST['rejection_reason']);
            
            try {
                $pdo = getDBConnection();
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Update reservation status
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'rejected', rejection_reason = ?, rejected_at = NOW(), rejected_by = ? WHERE reservation_id = ?");
                $stmt->execute([$rejection_reason, $_SESSION['user_id'], $reservation_id]);
                
                // Update bedspace status back to available
                $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'available' WHERE bedspace_id = ?");
                $stmt->execute([$bedspace_id]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Reservation rejected: ID ' . $reservation_id);
                
                $pdo->commit();
                $message = 'Reservation rejected successfully!';
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error rejecting reservation: ' . $e->getMessage();
            }
        } elseif ($action === 'cancel_reservation') {
            $reservation_id = (int)$_POST['reservation_id'];
            $bedspace_id = (int)$_POST['bedspace_id'];
            $cancellation_reason = sanitizeInput($_POST['cancellation_reason']);
            
            try {
                $pdo = getDBConnection();
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Update reservation status
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled', cancellation_reason = ?, cancelled_at = NOW(), cancelled_by = ? WHERE reservation_id = ?");
                $stmt->execute([$cancellation_reason, $_SESSION['user_id'], $reservation_id]);
                
                // Update bedspace status back to available
                $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'available' WHERE bedspace_id = ?");
                $stmt->execute([$bedspace_id]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Reservation cancelled: ID ' . $reservation_id);
                
                $pdo->commit();
                $message = 'Reservation cancelled successfully!';
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error cancelling reservation: ' . $e->getMessage();
            }
        } elseif ($action === 'check_in') {
            $reservation_id = (int)$_POST['reservation_id'];
            $bedspace_id = (int)$_POST['bedspace_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Update reservation status to occupied
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'occupied', check_in_date = NOW(), checked_in_by = ? WHERE reservation_id = ?");
                $stmt->execute([$_SESSION['user_id'], $reservation_id]);
                
                // Update bedspace status to occupied
                $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'occupied' WHERE bedspace_id = ?");
                $stmt->execute([$bedspace_id]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Student checked in: Reservation ID ' . $reservation_id);
                
                $pdo->commit();
                $message = 'Student checked in successfully!';
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error checking in student: ' . $e->getMessage();
            }
        } elseif ($action === 'check_out') {
            $reservation_id = (int)$_POST['reservation_id'];
            $bedspace_id = (int)$_POST['bedspace_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Update reservation status to checked_out
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_out', check_out_date = NOW(), checked_out_by = ? WHERE reservation_id = ?");
                $stmt->execute([$_SESSION['user_id'], $reservation_id]);
                
                // Update bedspace status back to available
                $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'available' WHERE bedspace_id = ?");
                $stmt->execute([$bedspace_id]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Student checked out: Reservation ID ' . $reservation_id);
                
                $pdo->commit();
                $message = 'Student checked out successfully!';
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error checking out student: ' . $e->getMessage();
            }
        }
    }
}

// Fetch reservations with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$building_filter = isset($_GET['building']) ? (int)$_GET['building'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "r.status = ?";
        $params[] = $status_filter;
    }
    
    if ($building_filter) {
        $where_conditions[] = "b.building_id = ?";
        $params[] = $building_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR r.reservation_id LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "
        SELECT r.*, s.first_name, s.last_name, s.student_id, s.mobile_number, s.email,
               bs.bedspace_label, rm.room_number, rm.room_type, rm.floor,
               b.building_name, b.building_address,
               u.username
        FROM reservations r
        JOIN students s ON r.student_id = s.student_id
        JOIN bedspaces bs ON r.bedspace_id = bs.bedspace_id
        JOIN rooms rm ON bs.room_id = rm.room_id
        JOIN buildings b ON rm.building_id = b.building_id
        JOIN users u ON s.user_id = u.user_id
        $where_clause
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for different statuses
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM reservations 
        GROUP BY status
    ");
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get buildings for filter
    $stmt = $pdo->query("SELECT building_id, building_name FROM buildings ORDER BY building_name");
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error fetching reservations: ' . $e->getMessage();
    $reservations = [];
    $status_counts = [];
    $buildings = [];
}

$page_title = "Reservations Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-calendar-check me-2"></i>Reservations Management
                </h1>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Status Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Reservations
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo array_sum($status_counts); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending Approval
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['pending'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Approved
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['approved'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Occupied
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['occupied'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-bed fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="occupied" <?php echo $status_filter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="building" class="form-label">Building</label>
                            <select class="form-control" id="building" name="building">
                                <option value="">All Buildings</option>
                                <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building['building_id']; ?>" <?php echo $building_filter == $building['building_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($building['building_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by student name, ID, or reservation ID" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-check me-2"></i>Reservations List
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="reservationsTable">
                            <thead>
                                <tr>
                                    <th>Reservation Info</th>
                                    <th>Student Details</th>
                                    <th>Room & Bedspace</th>
                                    <th>Status</th>
                                    <th>Dates</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>#<?php echo $reservation['reservation_id']; ?></strong>
                                                <br><small class="text-muted"><?php echo formatDate($reservation['created_at'], 'M d, Y h:i A'); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-3">
                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></strong>
                                                    <br><small class="text-muted">ID: <?php echo htmlspecialchars($reservation['student_id']); ?></small>
                                                    <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($reservation['mobile_number']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($reservation['building_name']); ?></strong>
                                                <br><small class="text-muted">Room <?php echo htmlspecialchars($reservation['room_number']); ?> (Floor <?php echo $reservation['floor']; ?>)</small>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($reservation['bedspace_label']); ?> • <?php echo htmlspecialchars($reservation['room_type']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($reservation['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    $statusText = 'Pending Approval';
                                                    break;
                                                case 'approved':
                                                    $statusClass = 'bg-success';
                                                    $statusText = 'Approved';
                                                    break;
                                                case 'occupied':
                                                    $statusClass = 'bg-info';
                                                    $statusText = 'Occupied';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-danger';
                                                    $statusText = 'Rejected';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'bg-secondary';
                                                    $statusText = 'Cancelled';
                                                    break;
                                                case 'checked_out':
                                                    $statusClass = 'bg-dark';
                                                    $statusText = 'Checked Out';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                                    $statusText = 'Unknown';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            <?php if ($reservation['rejection_reason']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($reservation['rejection_reason']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div><strong>Requested:</strong> <?php echo formatDate($reservation['reservation_date'], 'M d, Y'); ?></div>
                                                <?php if ($reservation['approved_at']): ?>
                                                    <div><strong>Approved:</strong> <?php echo formatDate($reservation['approved_at'], 'M d, Y'); ?></div>
                                                <?php endif; ?>
                                                <?php if ($reservation['check_in_date']): ?>
                                                    <div><strong>Check-in:</strong> <?php echo formatDate($reservation['check_in_date'], 'M d, Y'); ?></div>
                                                <?php endif; ?>
                                                <?php if ($reservation['check_out_date']): ?>
                                                    <div><strong>Check-out:</strong> <?php echo formatDate($reservation['check_out_date'], 'M d, Y'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <?php if ($reservation['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success mb-1" onclick="approveReservation(<?php echo $reservation['reservation_id']; ?>, <?php echo $reservation['bedspace_id']; ?>, <?php echo $reservation['student_id']; ?>)">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger mb-1" onclick="rejectReservation(<?php echo $reservation['reservation_id']; ?>, <?php echo $reservation['bedspace_id']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php elseif ($reservation['status'] === 'approved'): ?>
                                                    <button class="btn btn-sm btn-info mb-1" onclick="checkInStudent(<?php echo $reservation['reservation_id']; ?>, <?php echo $reservation['bedspace_id']; ?>)">
                                                        <i class="fas fa-sign-in-alt"></i> Check In
                                                    </button>
                                                    <button class="btn btn-sm btn-warning mb-1" onclick="cancelReservation(<?php echo $reservation['reservation_id']; ?>, <?php echo $reservation['bedspace_id']; ?>)">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </button>
                                                <?php elseif ($reservation['status'] === 'occupied'): ?>
                                                    <button class="btn btn-sm btn-warning mb-1" onclick="checkOutStudent(<?php echo $reservation['reservation_id']; ?>, <?php echo $reservation['bedspace_id']; ?>)">
                                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-info" onclick="viewReservationDetails(<?php echo $reservation['reservation_id']; ?>)">
                                                    <i class="fas fa-eye"></i> Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Reservation Modal -->
<div class="modal fade" id="rejectReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_reservation">
                    <input type="hidden" name="reservation_id" id="reject_reservation_id">
                    <input type="hidden" name="bedspace_id" id="reject_bedspace_id">
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required 
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> This will free up the bedspace for other reservations.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Reservation Modal -->
<div class="modal fade" id="cancelReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="cancel_reservation">
                    <input type="hidden" name="reservation_id" id="cancel_reservation_id">
                    <input type="hidden" name="bedspace_id" id="cancel_bedspace_id">
                    
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Cancellation Reason *</label>
                        <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="4" required 
                                  placeholder="Please provide a reason for cancellation..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> This will free up the bedspace for other reservations.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Cancel Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Forms -->
<form id="approveReservationForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve_reservation">
    <input type="hidden" name="reservation_id" id="approve_reservation_id">
    <input type="hidden" name="bedspace_id" id="approve_bedspace_id">
    <input type="hidden" name="student_id" id="approve_student_id">
</form>

<form id="checkInForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="check_in">
    <input type="hidden" name="reservation_id" id="check_in_reservation_id">
    <input type="hidden" name="bedspace_id" id="check_in_bedspace_id">
</form>

<form id="checkOutForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="check_out">
    <input type="hidden" name="reservation_id" id="check_out_reservation_id">
    <input type="hidden" name="bedspace_id" id="check_out_bedspace_id">
</form>

<?php include 'includes/footer.php'; ?>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#reservationsTable').DataTable({
        order: [[4, 'desc']],
        pageLength: 25,
        responsive: true
    });
});

// Approve Reservation
function approveReservation(reservationId, bedspaceId, studentId) {
    if (confirm('Are you sure you want to approve this reservation? This will reserve the bedspace for the student.')) {
        $('#approve_reservation_id').val(reservationId);
        $('#approve_bedspace_id').val(bedspaceId);
        $('#approve_student_id').val(studentId);
        $('#approveReservationForm').submit();
    }
}

// Reject Reservation
function rejectReservation(reservationId, bedspaceId) {
    $('#reject_reservation_id').val(reservationId);
    $('#reject_bedspace_id').val(bedspaceId);
    $('#rejectReservationModal').modal('show');
}

// Cancel Reservation
function cancelReservation(reservationId, bedspaceId) {
    $('#cancel_reservation_id').val(reservationId);
    $('#cancel_bedspace_id').val(bedspaceId);
    $('#cancelReservationModal').modal('show');
}

// Check In Student
function checkInStudent(reservationId, bedspaceId) {
    if (confirm('Are you sure you want to check in this student? This will mark the bedspace as occupied.')) {
        $('#check_in_reservation_id').val(reservationId);
        $('#check_in_bedspace_id').val(bedspaceId);
        $('#checkInForm').submit();
    }
}

// Check Out Student
function checkOutStudent(reservationId, bedspaceId) {
    if (confirm('Are you sure you want to check out this student? This will free up the bedspace.')) {
        $('#check_out_reservation_id').val(reservationId);
        $('#check_out_bedspace_id').val(bedspaceId);
        $('#checkOutForm').submit();
    }
}

// View Reservation Details
function viewReservationDetails(reservationId) {
    // Implement reservation details view functionality
    alert('Reservation details view functionality will be implemented in the next phase');
}
</script>
