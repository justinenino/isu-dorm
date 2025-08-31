<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($room_id <= 0) {
    echo '<div class="alert alert-danger">Invalid room ID</div>';
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get room and building info
    $stmt = $pdo->prepare("
        SELECT r.*, b.building_name 
        FROM rooms r 
        JOIN buildings b ON r.building_id = b.building_id 
        WHERE r.room_id = ?
    ");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        echo '<div class="alert alert-danger">Room not found</div>';
        exit;
    }
    
    // Get bedspaces and student assignments
    $stmt = $pdo->prepare("
        SELECT bs.*, s.first_name, s.last_name, s.student_id, s.mobile_number, s.email,
               r.reservation_date, r.status as reservation_status
        FROM bedspaces bs
        LEFT JOIN reservations r ON bs.bedspace_id = r.bedspace_id AND r.status IN ('approved', 'occupied')
        LEFT JOIN students s ON r.student_id = s.student_id
        WHERE bs.room_id = ?
        ORDER BY bs.bedspace_label
    ");
    $stmt->execute([$room_id]);
    $bedspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching room data: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="row">
    <div class="col-12">
        <h6 class="mb-3">
            <i class="fas fa-building me-2"></i>
            <?php echo htmlspecialchars($room['building_name']); ?> - 
            <i class="fas fa-door-open me-2"></i>
            Room <?php echo htmlspecialchars($room['room_number']); ?>
        </h6>
        
        <div class="row mb-3">
            <div class="col-md-3">
                <small class="text-muted">Floor:</small><br>
                <strong><?php echo $room['floor']; ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Type:</small><br>
                <strong><?php echo htmlspecialchars($room['room_type']); ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Capacity:</small><br>
                <strong><?php echo $room['capacity']; ?> bedspaces</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Description:</small><br>
                <strong><?php echo $room['description'] ? htmlspecialchars($room['description']) : 'N/A'; ?></strong>
            </div>
        </div>
        
        <hr>
        
        <h6 class="mb-3">Bedspace Occupancy</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Bedspace</th>
                        <th>Status</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Contact</th>
                        <th>Date Assigned</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bedspaces as $bedspace): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($bedspace['bedspace_label']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch ($bedspace['status']) {
                                    case 'available':
                                        $statusClass = 'bg-success';
                                        $statusText = 'Available';
                                        break;
                                    case 'reserved':
                                        $statusClass = 'bg-warning';
                                        $statusText = 'Reserved';
                                        break;
                                    case 'occupied':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Occupied';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary';
                                        $statusText = 'Unknown';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <?php if ($bedspace['first_name'] && $bedspace['last_name']): ?>
                                    <?php echo htmlspecialchars($bedspace['first_name'] . ' ' . $bedspace['last_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bedspace['student_id']): ?>
                                    <?php echo htmlspecialchars($bedspace['student_id']); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bedspace['mobile_number']): ?>
                                    <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($bedspace['mobile_number']); ?></div>
                                <?php endif; ?>
                                <?php if ($bedspace['email']): ?>
                                    <div><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($bedspace['email']); ?></div>
                                <?php endif; ?>
                                <?php if (!$bedspace['mobile_number'] && !$bedspace['email']): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bedspace['reservation_date']): ?>
                                    <?php echo formatDate($bedspace['reservation_date'], 'M d, Y'); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bedspace['status'] === 'occupied'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="checkoutStudent(<?php echo $bedspace['bedspace_id']; ?>, '<?php echo htmlspecialchars($bedspace['first_name'] . ' ' . $bedspace['last_name']); ?>')">
                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                    </button>
                                <?php elseif ($bedspace['status'] === 'reserved'): ?>
                                    <button class="btn btn-sm btn-success" onclick="approveReservation(<?php echo $bedspace['bedspace_id']; ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectReservation(<?php echo $bedspace['bedspace_id']; ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No action needed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($room['description']): ?>
            <div class="mt-3">
                <small class="text-muted">Room Description:</small><br>
                <em><?php echo htmlspecialchars($room['description']); ?></em>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function checkoutStudent(bedspaceId, studentName) {
    if (confirm(`Are you sure you want to check out ${studentName} from this bedspace?`)) {
        // Implement checkout functionality
        alert('Checkout functionality will be implemented in the next phase');
    }
}

function approveReservation(bedspaceId) {
    if (confirm('Are you sure you want to approve this reservation?')) {
        // Implement approval functionality
        alert('Approval functionality will be implemented in the next phase');
    }
}

function rejectReservation(bedspaceId) {
    if (confirm('Are you sure you want to reject this reservation?')) {
        // Implement rejection functionality
        alert('Rejection functionality will be implemented in the next phase');
    }
}
</script>
