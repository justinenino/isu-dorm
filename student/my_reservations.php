<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
        $reservation_id = (int)$_POST['reservation_id'];
        
        try {
            $pdo = getDBConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Get reservation details
            $stmt = $pdo->prepare("
                SELECT r.*, bs.bedspace_id 
                FROM reservations r 
                JOIN bedspaces bs ON r.bedspace_id = bs.bedspace_id 
                WHERE r.reservation_id = ? AND r.student_id = ? AND r.status = 'pending'
            ");
            $stmt->execute([$reservation_id, $_SESSION['user_id']]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                $error = 'Reservation not found or cannot be cancelled.';
            } else {
                // Update reservation status
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled', cancelled_at = NOW() WHERE reservation_id = ?");
                $stmt->execute([$reservation_id]);
                
                // Update bedspace status back to available
                $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'available' WHERE bedspace_id = ?");
                $stmt->execute([$reservation['bedspace_id']]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Reservation cancelled: ID ' . $reservation_id);
                
                $pdo->commit();
                $message = 'Reservation cancelled successfully!';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error cancelling reservation: ' . $e->getMessage();
        }
    }
}

// Fetch student's reservations
try {
    $pdo = getDBConnection();
    
    // Get all reservations for the student
    $stmt = $pdo->prepare("
        SELECT r.*, bs.bedspace_label, rm.room_number, rm.room_type, rm.floor,
               b.building_name, b.building_address,
               CASE 
                   WHEN r.status = 'pending' THEN 1
                   WHEN r.status = 'approved' THEN 2
                   WHEN r.status = 'occupied' THEN 3
                   WHEN r.status = 'checked_out' THEN 4
                   WHEN r.status = 'rejected' THEN 5
                   WHEN r.status = 'cancelled' THEN 6
                   ELSE 7
               END as sort_order
        FROM reservations r
        JOIN bedspaces bs ON r.bedspace_id = bs.bedspace_id
        JOIN rooms rm ON bs.room_id = rm.room_id
        JOIN buildings b ON rm.building_id = b.building_id
        WHERE r.student_id = ?
        ORDER BY sort_order, r.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for different statuses
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM reservations 
        WHERE student_id = ?
        GROUP BY status
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    $error = 'Error fetching reservations: ' . $e->getMessage();
    $reservations = [];
    $status_counts = [];
}

$page_title = "My Reservations";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-calendar-check me-2"></i>My Reservations
                </h1>
                <a href="reserve_room.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Reservation
                </a>
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
                                        Pending
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
                                        Active
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo ($status_counts['approved'] ?? 0) + ($status_counts['occupied'] ?? 0); ?>
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
                                        Completed
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $status_counts['checked_out'] ?? 0; ?>
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

            <!-- Reservations List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Reservation History
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <h5>No Reservations Yet</h5>
                            <p>You haven't made any reservations yet. Start by making your first room reservation!</p>
                            <a href="reserve_room.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Make First Reservation
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="reservationsTable">
                                <thead>
                                    <tr>
                                        <th>Reservation Info</th>
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
                                                    <?php if ($reservation['special_requests']): ?>
                                                        <br><small class="text-muted"><strong>Requests:</strong> <?php echo htmlspecialchars($reservation['special_requests']); ?></small>
                                                    <?php endif; ?>
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
                                                $statusIcon = '';
                                                switch ($reservation['status']) {
                                                    case 'pending':
                                                        $statusClass = 'bg-warning';
                                                        $statusText = 'Pending Approval';
                                                        $statusIcon = 'fas fa-clock';
                                                        break;
                                                    case 'approved':
                                                        $statusClass = 'bg-success';
                                                        $statusText = 'Approved';
                                                        $statusIcon = 'fas fa-check-circle';
                                                        break;
                                                    case 'occupied':
                                                        $statusClass = 'bg-info';
                                                        $statusText = 'Occupied';
                                                        $statusIcon = 'fas fa-bed';
                                                        break;
                                                    case 'rejected':
                                                        $statusClass = 'bg-danger';
                                                        $statusText = 'Rejected';
                                                        $statusIcon = 'fas fa-times-circle';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = 'Cancelled';
                                                        $statusIcon = 'fas fa-ban';
                                                        break;
                                                    case 'checked_out':
                                                        $statusClass = 'bg-dark';
                                                        $statusText = 'Checked Out';
                                                        $statusIcon = 'fas fa-sign-out-alt';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = 'Unknown';
                                                        $statusIcon = 'fas fa-question-circle';
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?> fs-6">
                                                    <i class="<?php echo $statusIcon; ?> me-1"></i><?php echo $statusText; ?>
                                                </span>
                                                <?php if ($reservation['rejection_reason']): ?>
                                                    <br><small class="text-muted mt-1"><?php echo htmlspecialchars($reservation['rejection_reason']); ?></small>
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
                                                    <?php if ($reservation['cancelled_at']): ?>
                                                        <div><strong>Cancelled:</strong> <?php echo formatDate($reservation['cancelled_at'], 'M d, Y'); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical" role="group">
                                                    <?php if ($reservation['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-danger mb-1" onclick="cancelReservation(<?php echo $reservation['reservation_id']; ?>)">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    <?php elseif ($reservation['status'] === 'approved'): ?>
                                                        <span class="badge bg-success mb-1">Ready for Check-in</span>
                                                    <?php elseif ($reservation['status'] === 'occupied'): ?>
                                                        <span class="badge bg-info mb-1">Currently Occupying</span>
                                                    <?php elseif ($reservation['status'] === 'rejected'): ?>
                                                        <button class="btn btn-sm btn-primary mb-1" onclick="makeNewReservation()">
                                                            <i class="fas fa-plus"></i> New Reservation
                                                        </button>
                                                    <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                                        <button class="btn btn-sm btn-primary mb-1" onclick="makeNewReservation()">
                                                            <i class="fas fa-plus"></i> New Reservation
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Reservation Form -->
<form id="cancelReservationForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="cancel_reservation">
    <input type="hidden" name="reservation_id" id="cancel_reservation_id">
</form>

<?php include 'includes/footer.php'; ?>

<script>
// Initialize DataTable
$(document).ready(function() {
    if (document.getElementById('reservationsTable')) {
        $('#reservationsTable').DataTable({
            order: [[3, 'desc']],
            pageLength: 10,
            responsive: true
        });
    }
});

// Cancel Reservation
function cancelReservation(reservationId) {
    if (confirm('Are you sure you want to cancel this reservation? This action cannot be undone.')) {
        document.getElementById('cancel_reservation_id').value = reservationId;
        document.getElementById('cancelReservationForm').submit();
    }
}

// Make New Reservation
function makeNewReservation() {
    window.location.href = 'reserve_room.php';
}

// View Reservation Details
function viewReservationDetails(reservationId) {
    // Implement reservation details view functionality
    alert('Reservation details view functionality will be implemented in the next phase');
}
</script>
