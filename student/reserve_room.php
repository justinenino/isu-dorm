<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Check if student is approved
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT status FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_status = $stmt->fetchColumn();
    
    if ($student_status !== 'approved') {
        $error = 'Your account is not yet approved. Please wait for admin approval.';
    }
} catch (PDOException $e) {
    $error = 'Error checking student status: ' . $e->getMessage();
}

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student_status === 'approved') {
    if (isset($_POST['action']) && $_POST['action'] === 'reserve_bedspace') {
        $bedspace_id = (int)$_POST['bedspace_id'];
        $reservation_date = sanitizeInput($_POST['reservation_date']);
        $special_requests = sanitizeInput($_POST['special_requests']);
        
        try {
            $pdo = getDBConnection();
            
            // Check if student already has a pending or approved reservation
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE student_id = ? AND status IN ('pending', 'approved', 'occupied')");
            $stmt->execute([$_SESSION['user_id']]);
            $existing_reservation = $stmt->fetchColumn();
            
            if ($existing_reservation > 0) {
                $error = 'You already have an active reservation. Please cancel your existing reservation first.';
            } else {
                // Check if bedspace is still available
                $stmt = $pdo->prepare("SELECT status FROM bedspaces WHERE bedspace_id = ?");
                $stmt->execute([$bedspace_id]);
                $bedspace_status = $stmt->fetchColumn();
                
                if ($bedspace_status !== 'available') {
                    $error = 'This bedspace is no longer available. Please select another one.';
                } else {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Create reservation
                    $stmt = $pdo->prepare("
                        INSERT INTO reservations (student_id, bedspace_id, reservation_date, special_requests, status, created_at) 
                        VALUES (?, ?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([$_SESSION['user_id'], $bedspace_id, $reservation_date, $special_requests]);
                    
                    // Update bedspace status to reserved
                    $stmt = $pdo->prepare("UPDATE bedspaces SET status = 'reserved' WHERE bedspace_id = ?");
                    $stmt->execute([$bedspace_id]);
                    
                    // Log activity
                    logActivity($_SESSION['user_id'], 'Room reservation submitted: Bedspace ID ' . $bedspace_id);
                    
                    $pdo->commit();
                    $message = 'Reservation submitted successfully! Please wait for admin approval.';
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error submitting reservation: ' . $e->getMessage();
        }
    }
}

// Fetch buildings and rooms for browsing
try {
    $pdo = getDBConnection();
    
    // Get buildings with room counts
    $stmt = $pdo->query("
        SELECT b.*, 
               COUNT(r.room_id) as room_count,
               SUM(CASE WHEN r.room_id IS NOT NULL THEN r.capacity ELSE 0 END) as total_capacity
        FROM buildings b 
        LEFT JOIN rooms r ON b.building_id = r.building_id 
        GROUP BY b.building_id 
        ORDER BY b.building_name
    ");
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get rooms with availability info
    $stmt = $pdo->query("
        SELECT r.*, b.building_name,
               COUNT(bs.bedspace_id) as total_bedspaces,
               SUM(CASE WHEN bs.status = 'available' THEN 1 ELSE 0 END) as available_bedspaces,
               SUM(CASE WHEN bs.status = 'reserved' THEN 1 ELSE 0 END) as reserved_bedspaces,
               SUM(CASE WHEN bs.status = 'occupied' THEN 1 ELSE 0 END) as occupied_bedspaces
        FROM rooms r 
        JOIN buildings b ON r.building_id = b.building_id
        LEFT JOIN bedspaces bs ON r.room_id = bs.room_id
        GROUP BY r.room_id 
        ORDER BY b.building_name, r.room_number
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student's current reservation if any
    $stmt = $pdo->prepare("
        SELECT r.*, bs.bedspace_label, rm.room_number, rm.room_type, rm.floor,
               b.building_name, b.building_address
        FROM reservations r
        JOIN bedspaces bs ON r.bedspace_id = bs.bedspace_id
        JOIN rooms rm ON bs.room_id = rm.room_id
        JOIN buildings b ON rm.building_id = b.building_id
        WHERE r.student_id = ? AND r.status IN ('pending', 'approved', 'occupied')
        ORDER BY r.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error fetching data: ' . $e->getMessage();
    $buildings = [];
    $rooms = [];
    $current_reservation = null;
}

$page_title = "Reserve Room";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-bed me-2"></i>Reserve Room
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

            <?php if ($student_status !== 'approved'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Account Not Approved:</strong> You cannot make reservations until your account is approved by an administrator.
                </div>
            <?php else: ?>
                <!-- Current Reservation Status -->
                <?php if ($current_reservation): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Current Reservation Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6><?php echo htmlspecialchars($current_reservation['building_name']); ?> - Room <?php echo htmlspecialchars($current_reservation['room_number']); ?></h6>
                                    <p class="mb-2">
                                        <strong>Bedspace:</strong> <?php echo htmlspecialchars($current_reservation['bedspace_label']); ?><br>
                                        <strong>Room Type:</strong> <?php echo htmlspecialchars($current_reservation['room_type']); ?><br>
                                        <strong>Floor:</strong> <?php echo $current_reservation['floor']; ?><br>
                                        <strong>Reservation Date:</strong> <?php echo formatDate($current_reservation['reservation_date'], 'M d, Y'); ?>
                                    </p>
                                    <?php if ($current_reservation['special_requests']): ?>
                                        <p class="mb-2">
                                            <strong>Special Requests:</strong><br>
                                            <em><?php echo htmlspecialchars($current_reservation['special_requests']); ?></em>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($current_reservation['status']) {
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
                                        default:
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Unknown';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> fs-6 mb-3"><?php echo $statusText; ?></span>
                                    <br>
                                    <?php if ($current_reservation['status'] === 'pending'): ?>
                                        <button class="btn btn-danger btn-sm" onclick="cancelReservation(<?php echo $current_reservation['reservation_id']; ?>)">
                                            <i class="fas fa-times me-2"></i>Cancel Reservation
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Buildings and Rooms Browser -->
                <div class="row">
                    <!-- Buildings Section -->
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-building me-2"></i>Buildings
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group" id="buildingsList">
                                    <?php foreach ($buildings as $building): ?>
                                        <a href="#" class="list-group-item list-group-item-action" 
                                           data-building-id="<?php echo $building['building_id']; ?>"
                                           onclick="selectBuilding(<?php echo $building['building_id']; ?>)">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($building['building_name']); ?></h6>
                                                <small class="text-muted"><?php echo $building['room_count']; ?> rooms</small>
                                            </div>
                                            <p class="mb-1 small text-muted"><?php echo htmlspecialchars($building['building_address']); ?></p>
                                            <small class="text-muted">Capacity: <?php echo $building['total_capacity']; ?> beds</small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rooms Section -->
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-door-open me-2"></i>Rooms
                                    <span id="selectedBuildingName" class="text-muted ms-2"></span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="roomsContainer">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-building fa-3x mb-3"></i>
                                        <p>Select a building to view available rooms</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reservation Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reserve Bedspace</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reserve_bedspace">
                    <input type="hidden" name="bedspace_id" id="reserve_bedspace_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Building & Room</label>
                        <input type="text" class="form-control" id="reserve_building_room" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bedspace</label>
                        <input type="text" class="form-control" id="reserve_bedspace_label" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reservation_date" class="form-label">Reservation Date *</label>
                        <input type="date" class="form-control" id="reservation_date" name="reservation_date" 
                               value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="special_requests" class="form-label">Special Requests</label>
                        <textarea class="form-control" id="special_requests" name="special_requests" rows="3" 
                                  placeholder="Any special requests or requirements..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Your reservation will be reviewed by an administrator. You will be notified once it's approved or rejected.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Reservation</button>
                </div>
            </form>
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
// Store rooms data globally
const roomsData = <?php echo json_encode($rooms); ?>;
const buildingsData = <?php echo json_encode($buildings); ?>;

// Select Building
function selectBuilding(buildingId) {
    // Update active state
    document.querySelectorAll('#buildingsList a').forEach(link => {
        link.classList.remove('active');
    });
    event.target.closest('a').classList.add('active');
    
    // Get building name
    const building = buildingsData.find(b => b.building_id == buildingId);
    document.getElementById('selectedBuildingName').textContent = building ? `- ${building.building_name}` : '';
    
    // Filter rooms for this building
    const buildingRooms = roomsData.filter(room => room.building_id == buildingId);
    displayRooms(buildingRooms);
}

// Display Rooms
function displayRooms(rooms) {
    const container = document.getElementById('roomsContainer');
    
    if (rooms.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-door-open fa-3x mb-3"></i>
                <p>No rooms available in this building</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="row">';
    rooms.forEach(room => {
        const availablePercentage = room.total_bedspaces > 0 ? Math.round((room.available_bedspaces / room.total_bedspaces) * 100) : 0;
        
        html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">Room ${room.room_number}</h6>
                        <p class="card-text small text-muted">
                            Floor ${room.floor} • ${room.room_type}<br>
                            ${room.total_bedspaces} bedspaces total
                        </p>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Availability</small>
                                <small>${room.available_bedspaces}/${room.total_bedspaces}</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${availablePercentage}%"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge bg-success">${room.available_bedspaces} Available</span>
                            <span class="badge bg-warning">${room.reserved_bedspaces} Reserved</span>
                            <span class="badge bg-danger">${room.occupied_bedspaces} Occupied</span>
                        </div>
                        
                        ${room.available_bedspaces > 0 ? `
                            <button class="btn btn-primary btn-sm w-100" onclick="viewBedspaces(${room.room_id}, '${room.room_number}', '${room.building_name}')">
                                <i class="fas fa-bed me-2"></i>View Bedspaces
                            </button>
                        ` : `
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="fas fa-times me-2"></i>No Available Bedspaces
                            </button>
                        `}
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// View Bedspaces
function viewBedspaces(roomId, roomNumber, buildingName) {
    // This would typically load bedspaces via AJAX
    // For now, we'll show a simplified view
    const container = document.getElementById('roomsContainer');
    
    // Get bedspaces for this room
    const room = roomsData.find(r => r.room_id == roomId);
    if (!room) return;
    
    let html = `
        <div class="mb-3">
            <button class="btn btn-outline-secondary btn-sm" onclick="selectBuilding(${room.building_id})">
                <i class="fas fa-arrow-left me-2"></i>Back to Buildings
            </button>
        </div>
        <h6>${buildingName} - Room ${roomNumber}</h6>
        <p class="text-muted">Select an available bedspace to reserve</p>
        <div class="row">
    `;
    
    // Simulate bedspaces (in real implementation, fetch from database)
    for (let i = 1; i <= room.total_bedspaces; i++) {
        const isAvailable = i <= room.available_bedspaces;
        const isReserved = i > room.available_bedspaces && i <= (room.available_bedspaces + room.reserved_bedspaces);
        const isOccupied = i > (room.available_bedspaces + room.reserved_bedspaces);
        
        let statusClass = 'bg-success';
        let statusText = 'Available';
        let buttonClass = 'btn-primary';
        let buttonText = 'Reserve';
        let disabled = false;
        
        if (isReserved) {
            statusClass = 'bg-warning';
            statusText = 'Reserved';
            buttonClass = 'btn-secondary';
            buttonText = 'Reserved';
            disabled = true;
        } else if (isOccupied) {
            statusClass = 'bg-danger';
            statusText = 'Occupied';
            buttonClass = 'btn-secondary';
            buttonText = 'Occupied';
            disabled = true;
        }
        
        html += `
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Bed ${i}</h5>
                        <span class="badge ${statusClass} mb-3">${statusText}</span>
                        <br>
                        <button class="btn ${buttonClass} btn-sm" 
                                onclick="reserveBedspace(${i}, '${buildingName}', '${roomNumber}', 'Bed ${i}')"
                                ${disabled ? 'disabled' : ''}>
                            ${buttonText}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Reserve Bedspace
function reserveBedspace(bedspaceId, buildingName, roomNumber, bedspaceLabel) {
    // In real implementation, bedspaceId would be the actual database ID
    // For demo purposes, we'll use a placeholder
    document.getElementById('reserve_bedspace_id').value = bedspaceId;
    document.getElementById('reserve_building_room').value = `${buildingName} - Room ${roomNumber}`;
    document.getElementById('reserve_bedspace_label').value = bedspaceLabel;
    
    $('#reservationModal').modal('show');
}

// Cancel Reservation
function cancelReservation(reservationId) {
    if (confirm('Are you sure you want to cancel your reservation? This action cannot be undone.')) {
        document.getElementById('cancel_reservation_id').value = reservationId;
        document.getElementById('cancelReservationForm').submit();
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Set default building if available
    if (buildingsData.length > 0) {
        selectBuilding(buildingsData[0].building_id);
    }
});
</script>
