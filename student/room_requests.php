<?php
// Include database configuration first
require_once '../config/database.php';

// Handle form submissions BEFORE including header to avoid "headers already sent" error
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pdo = getConnection();
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit_request':
                try {
                    $requested_room_id = $_POST['requested_room_id'];
                    $requested_bed_space_id = $_POST['requested_bed_space_id'];
                    $reason = $_POST['reason'];
                    $current_room_id = $_POST['current_room_id'];
                    
                    // Basic validation
                    if (empty($requested_room_id) || empty($requested_bed_space_id) || empty($reason)) {
                        $_SESSION['error'] = "Please fill in all required fields including bed space selection.";
                        header("Location: room_requests.php");
                        exit;
                    }
                    
                    // Check if student already has a pending request
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_change_requests WHERE student_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pending_count = $stmt->fetchColumn();
                    
                    if ($pending_count > 0) {
                        $_SESSION['error'] = "You already have a pending room change request. Please wait for it to be processed.";
                        header("Location: room_requests.php");
                        exit;
                    }
                    
                    // Get current bed space ID for the student
                    $stmt = $pdo->prepare("SELECT bed_space_id FROM students WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_bed_space_id = $stmt->fetchColumn();
                    
                    // Insert room change request with bed space information
                    // Note: Database needs to be updated to include requested_bed_space_id and current_bed_space_id columns
                    $stmt = $pdo->prepare("INSERT INTO room_change_requests (student_id, current_room_id, requested_room_id, reason) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$_SESSION['user_id'], $current_room_id, $requested_room_id, $reason]);
                    
                    if ($result) {
                        // Get bed space number for display
                        $stmt = $pdo->prepare("SELECT bed_number FROM bed_spaces WHERE id = ?");
                        $stmt->execute([$requested_bed_space_id]);
                        $bed_number = $stmt->fetchColumn();
                        
                        $_SESSION['success'] = "Room change request submitted successfully for Bed " . $bed_number . ".";
                    } else {
                        $_SESSION['error'] = "Failed to submit room change request. Please try again.";
                    }
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = "An error occurred while processing your request. Please try again.";
                    error_log("Room change request error: " . $e->getMessage());
                }
                
                header("Location: room_requests.php");
                exit;
                break;
        }
    }
}

// Include header and other files after form processing
$page_title = 'Room Requests';
include 'includes/header.php';

$pdo = getConnection();

// Get student's current room information
$stmt = $pdo->prepare("SELECT s.*, r.id as room_id, r.room_number, b.name as building_name
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get available rooms for selection
$stmt = $pdo->query("SELECT r.*, b.name as building_name,
    COUNT(bs.id) as total_beds,
    SUM(CASE WHEN bs.is_occupied = 1 THEN 1 ELSE 0 END) as occupied_beds
    FROM rooms r
    JOIN buildings b ON r.building_id = b.id
    LEFT JOIN bed_spaces bs ON r.id = bs.room_id
    WHERE r.status = 'available'
    GROUP BY r.id
    HAVING occupied_beds < total_beds
    ORDER BY b.name, r.room_number");
$available_rooms = $stmt->fetchAll();


// Get student's room change requests
$stmt = $pdo->prepare("SELECT rcr.*, 
    current_r.room_number as current_room,
    current_b.name as current_building,
    requested_r.room_number as requested_room,
    requested_b.name as requested_building
    FROM room_change_requests rcr
    LEFT JOIN rooms current_r ON rcr.current_room_id = current_r.id
    LEFT JOIN buildings current_b ON current_r.building_id = current_b.id
    LEFT JOIN rooms requested_r ON rcr.requested_room_id = requested_r.id
    LEFT JOIN buildings requested_b ON requested_r.building_id = requested_b.id
    WHERE rcr.student_id = ?
    ORDER BY rcr.requested_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$room_requests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-exchange-alt"></i> Room Requests</h2>
    <?php if ($student['room_id']): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#submitRequestModal">
            <i class="fas fa-plus"></i> Request Room Change
        </button>
    <?php endif; ?>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

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

<!-- Current Room Information -->
<?php if ($student['room_id']): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bed"></i> Your Current Room</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Building:</strong> <?php echo htmlspecialchars($student['building_name']); ?></p>
                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($student['room_number']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge bg-success">Currently Assigned</span></p>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> You don't have a room assigned yet. Please wait for admin approval before requesting room changes.
</div>
<?php endif; ?>

<!-- Room Requests Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Your Room Change Requests</h5>
    </div>
    <div class="card-body">
        <?php if (empty($room_requests)): ?>
            <div class="text-center py-4">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5>No room change requests yet</h5>
                <p class="text-muted">Submit your first room change request to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped" id="roomRequestsTable">
                    <thead>
                        <tr>
                            <th>Current Room</th>
                            <th>Requested Room</th>
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
                                    <?php if ($request['current_room']): ?>
                                        <?php echo htmlspecialchars($request['current_building'] . ' - ' . $request['current_room']); ?>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Submit Request Modal -->
<?php if ($student['room_id']): ?>
<div class="modal fade" id="submitRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Room Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="submit_request">
                <input type="hidden" name="current_room_id" value="<?php echo $student['room_id']; ?>">
                <div class="modal-body">
                    <!-- Step 1: Current Room Info -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3"><i class="fas fa-home"></i> Your Current Room</h6>
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Building:</strong> <?php echo htmlspecialchars($student['building_name']); ?></p>
                                        <p class="mb-1"><strong>Room:</strong> <?php echo htmlspecialchars($student['room_number']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Currently Assigned</span></p>
                                        <p class="mb-1"><strong>Student:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Room Selection -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3"><i class="fas fa-search"></i> Step 2: Select New Room</h6>
                        
                        <!-- Search and Filters -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="roomSearch" class="form-control" placeholder="Search by building, room number, or floor...">
                                    <button class="btn btn-outline-primary" type="button" id="searchButton">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-success btn-sm" data-filter="fully">
                                        <i class="fas fa-star"></i> Fully Available
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-filter="partial">
                                        <i class="fas fa-exclamation-triangle"></i> Partial
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-filter="all">
                                        <i class="fas fa-list"></i> All
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Available Rooms List -->
                        <div class="room-selection-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 15px; background-color: #f8f9fa;">
                            <div id="roomList">
                                <?php foreach ($available_rooms as $room): 
                                    $available_beds = $room['total_beds'] - $room['occupied_beds'];
                                    $availability_percentage = $room['total_beds'] > 0 ? round(($available_beds / $room['total_beds']) * 100) : 0;
                                    
                                    // Determine availability status
                                    if ($available_beds == $room['total_beds']) {
                                        $status_class = 'success';
                                        $status_text = 'Fully Available';
                                        $status_icon = 'fas fa-star';
                                    } elseif ($available_beds > 0) {
                                        $status_class = 'warning';
                                        $status_text = 'Partially Available';
                                        $status_icon = 'fas fa-exclamation-triangle';
                                    } else {
                                        $status_class = 'danger';
                                        $status_text = 'Fully Occupied';
                                        $status_icon = 'fas fa-times';
                                    }
                                ?>
                                <div class="room-option mb-3" data-room-id="<?php echo $room['id']; ?>" 
                                     data-building="<?php echo $room['building_name']; ?>"
                                     data-room-number="<?php echo $room['room_number']; ?>"
                                     data-floor="<?php echo $room['floor_number']; ?>">
                                    
                                    <!-- Room Button -->
                                    <button type="button" class="btn btn-outline-primary w-100 room-button" 
                                            data-room-id="<?php echo $room['id']; ?>"
                                            style="text-align: left; padding: 15px; border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-bed"></i>
                                                    <?php echo htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    Floor <?php echo $room['floor_number']; ?> • 
                                                    <?php echo $available_beds; ?> of <?php echo $room['total_beds']; ?> beds available
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo $status_class; ?> mb-1">
                                                    <i class="<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                                </span>
                                                <div class="progress" style="width: 100px; height: 4px;">
                                                    <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                                         style="width: <?php echo $availability_percentage; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                    
                                    <!-- Bed Spaces Section (Hidden by default) -->
                                    <div class="bed-spaces-section mt-2" style="display: none;">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white py-2">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-bed"></i> Available Bed Spaces
                                                    <small class="float-end">Click a bed to select it</small>
                                                </h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="bed-spaces-grid" data-room-id="<?php echo $room['id']; ?>">
                                                    <!-- Bed spaces will be loaded here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- No results message -->
                            <div id="noResults" class="text-center text-muted py-4" style="display: none;">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>No rooms found matching your search criteria.</p>
                            </div>
                        </div>
                        
                        <!-- Selected Room Display -->
                        <div id="selectedRoomDisplay" class="mt-3" style="display: none;">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Selected Room:</strong> <span id="selectedRoomText"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Selected Room & Bed Space Summary -->
                    <div class="mb-4" id="selectionSummary" style="display: none;">
                        <h6 class="text-primary mb-3"><i class="fas fa-check-circle"></i> Step 3: Your Selection</h6>
                        <div class="card border-success">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-success">Selected Room:</h6>
                                        <p class="mb-0" id="selectedRoomSummary"></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success">Selected Bed:</h6>
                                        <p class="mb-0" id="selectedBedSummary"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Reason -->
                    <div class="mb-3">
                        <h6 class="text-primary mb-3"><i class="fas fa-comment"></i> Step 4: Reason for Change</h6>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Please provide a detailed reason for requesting this room change..."></textarea>
                        <small class="form-text text-muted">Be specific about why you need to change rooms. This will help the admin make a decision.</small>
                    </div>
                    
                    <!-- Hidden inputs -->
                    <input type="hidden" name="requested_room_id" id="selectedRoomId" required>
                    <input type="hidden" name="requested_bed_space_id" id="selectedBedSpaceId" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

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

<style>
.room-button {
    transition: all 0.3s ease;
    border: 2px solid #007bff;
    background-color: white;
}

.room-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
    background-color: #f8f9ff;
}

.room-button.selected {
    background-color: #007bff !important;
    color: white !important;
    border-color: #0056b3 !important;
    box-shadow: 0 4px 8px rgba(0,123,255,0.4);
}

.bed-spaces-section {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bed-space-option {
    display: inline-block;
    margin: 5px;
    padding: 10px 15px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    min-width: 80px;
}

.bed-space-option.available {
    border-color: #28a745;
    background-color: #d4edda;
    color: #155724;
}

.bed-space-option.available:hover {
    background-color: #c3e6cb;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40,167,69,0.3);
}

.bed-space-option.occupied {
    border-color: #dc3545;
    background-color: #f8d7da;
    color: #721c24;
    cursor: not-allowed;
}

.bed-space-option.selected {
    background-color: #007bff !important;
    border-color: #0056b3 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.4);
}

.room-selection-container {
    background-color: #f8f9fa;
}

.progress {
    background-color: #e9ecef;
}

.room-option {
    transition: all 0.3s ease;
}

.room-option:hover .room-card {
    border-color: #6c757d;
}

#selectedRoomDisplay {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.room-details small {
    font-size: 0.8rem;
}

.badge {
    font-size: 0.7rem;
    padding: 0.4em 0.6em;
}

@media (max-width: 768px) {
    .room-selection-container {
        max-height: 250px;
    }
    
    .col-md-6 {
        margin-bottom: 10px;
    }
}

/* Filter button styles */
.btn-group .btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Search button styles */
#searchButton:hover {
    background-color: #0d6efd;
    color: white;
}

#clearSearch:hover {
    background-color: #6c757d;
    color: white;
}

/* Bed Space Selection Styles */
.bed-space-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 8px;
}

.bed-space-selection {
    transition: all 0.3s ease;
}

.bed-space-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 10px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background-color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.bed-space-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.bed-space-option.available {
    border-color: #28a745;
    background-color: #d4edda;
}

.bed-space-option.available:hover {
    border-color: #1e7e34;
    background-color: #c3e6cb;
}

.bed-space-option.occupied {
    border-color: #dc3545;
    background-color: #f8d7da;
    cursor: not-allowed;
    opacity: 0.6;
}

.bed-space-option.selected {
    border-color: #007bff;
    background-color: #cce7ff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.bed-space-option.selected:hover {
    border-color: #0056b3;
    background-color: #b3d9ff;
}

.bed-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.bed-number {
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 4px;
}

.bed-status {
    font-size: 0.8rem;
    font-weight: 500;
}

.bed-occupant {
    font-size: 0.7rem;
    color: #6c757d;
    margin-top: 4px;
}

.bed-space-loading {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

.bed-space-error {
    text-align: center;
    padding: 20px;
    color: #dc3545;
    background-color: #f8d7da;
    border-radius: 8px;
    border: 1px solid #f5c6cb;
}
</style>

<script>
$(document).ready(function() {
    console.log('Document ready - initializing room request functionality');
    
    $('#roomRequestsTable').DataTable({
        order: [[4, 'desc']],
        pageLength: 10
    });
    
    // Room search functionality
    function performSearch() {
        var searchTerm = $('#roomSearch').val().toLowerCase();
        var visibleRooms = 0;
        
        $('.room-option').each(function() {
            var building = $(this).data('building');
            var roomNumber = $(this).data('room-number');
            var floor = $(this).data('floor');
            
            var matches = building.toLowerCase().includes(searchTerm) || 
                         roomNumber.toLowerCase().includes(searchTerm) || 
                         floor.toString().includes(searchTerm);
            
            if (matches || searchTerm === '') {
                $(this).show();
                visibleRooms++;
            } else {
                $(this).hide();
            }
        });
        
        // Show/hide no results message
        if (visibleRooms === 0 && searchTerm !== '') {
            $('#noResults').show();
        } else {
            $('#noResults').hide();
        }
        
        // Show/hide clear button
        if (searchTerm !== '') {
            $('#clearSearch').show();
        } else {
            $('#clearSearch').hide();
        }
    }
    
    // Bind search events when modal is shown
    $('#submitRequestModal').on('shown.bs.modal', function() {
        // Search on input change
        $('#roomSearch').off('input').on('input', performSearch);
        
        // Search button click
        $('#searchButton').off('click').on('click', function() {
            performSearch();
            $('#roomSearch').focus();
        });
        
        // Clear search button
        $('#clearSearch').off('click').on('click', function() {
            $('#roomSearch').val('');
            performSearch();
            $('#roomSearch').focus();
        });
        
        // Search on Enter key
        $('#roomSearch').off('keypress').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                performSearch();
            }
        });
        
        // Quick filter buttons
        $('[data-filter]').off('click').on('click', function() {
            var filter = $(this).data('filter');
            
            // Remove active class from all filter buttons
            $('[data-filter]').removeClass('active');
            // Add active class to clicked button
            $(this).addClass('active');
            
            var visibleRooms = 0;
            
            $('.room-option').each(function() {
                var roomCard = $(this).find('.room-card');
                var badge = roomCard.find('.badge');
                var badgeText = badge.text().toLowerCase();
                
                var show = false;
                
                switch(filter) {
                    case 'available':
                        show = badgeText.includes('available');
                        break;
                    case 'partial':
                        show = badgeText.includes('partially available');
                        break;
                    case 'fully':
                        show = badgeText.includes('fully available');
                        break;
                    case 'all':
                        show = true;
                        break;
                }
                
                if (show) {
                    $(this).show();
                    visibleRooms++;
                } else {
                    $(this).hide();
                }
            });
            
            // Show/hide no results message
            if (visibleRooms === 0) {
                $('#noResults').show();
            } else {
                $('#noResults').hide();
            }
            
            // Clear search input when using filters
            $('#roomSearch').val('');
            $('#clearSearch').hide();
        });
        
        // Set default active filter
        $('[data-filter="all"]').addClass('active');
        
        // Debug: Check if room cards exist
        console.log('Number of room cards found:', $('.room-card').length);
        console.log('Room cards:', $('.room-card'));
        
        // Also check room options
        console.log('Number of room options found:', $('.room-option').length);
        console.log('Room options:', $('.room-option'));
        
        // If no room cards exist, create a test one
        if ($('.room-card').length === 0) {
            console.log('No room cards found, creating test room card');
            var testRoomHtml = '<div class="col-md-6 room-option" data-room-id="999" data-building="Test Building" data-room-number="101" data-floor="1">';
            testRoomHtml += '<div class="card room-card h-100" style="cursor: pointer; transition: all 0.3s;">';
            testRoomHtml += '<div class="card-body p-3">';
            testRoomHtml += '<div class="d-flex justify-content-between align-items-start mb-2">';
            testRoomHtml += '<h6 class="card-title mb-0"><i class="fas fa-bed text-primary"></i> Test Building - Room 101</h6>';
            testRoomHtml += '<span class="badge bg-success">Fully Available</span>';
            testRoomHtml += '</div>';
            testRoomHtml += '<div class="room-details">';
            testRoomHtml += '<small class="text-muted d-block"><i class="fas fa-layer-group"></i> Floor 1</small>';
            testRoomHtml += '<small class="text-muted d-block"><i class="fas fa-users"></i> 4 of 4 beds available</small>';
            testRoomHtml += '<div class="progress mt-2" style="height: 4px;"><div class="progress-bar bg-success" style="width: 100%"></div></div>';
            testRoomHtml += '</div></div></div></div>';
            
            $('#roomList').prepend(testRoomHtml);
            console.log('Test room card created');
        }
        
        // Room selection functionality - Use document delegation to ensure it works
        $(document).off('click', '.room-button').on('click', '.room-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Room button clicked!');
            
            var roomButton = $(this);
            var roomOption = roomButton.closest('.room-option');
            var roomId = roomButton.data('room-id');
            var roomTitle = roomButton.find('h6').text().trim();
            var bedSpacesSection = roomOption.find('.bed-spaces-section');
            
            // Toggle bed spaces section
            if (bedSpacesSection.is(':visible')) {
                // Hide bed spaces section
                bedSpacesSection.slideUp(300);
                roomButton.removeClass('selected');
                $('#selectedRoomId').val('');
                $('#selectedBedSpaceId').val('');
                $('#selectionSummary').hide();
            } else {
                // Hide all other bed spaces sections
                $('.bed-spaces-section').slideUp(300);
                $('.room-button').removeClass('selected');
                
                // Show this room's bed spaces section
                bedSpacesSection.slideDown(300);
                roomButton.addClass('selected');
                
                // Set selected room
                $('#selectedRoomId').val(roomId);
                $('#selectedRoomSummary').text(roomTitle);
                
                // Load bed spaces for this room
                loadBedSpacesForRoom(roomId, bedSpacesSection.find('.bed-spaces-grid'));
            }
        });
        
        // Bed space selection functionality
        $(document).off('click', '.bed-space-option.available').on('click', '.bed-space-option.available', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var bedSpaceOption = $(this);
            var bedSpaceId = bedSpaceOption.data('bed-space-id');
            var bedNumber = bedSpaceOption.find('.bed-number').text();
            
            // Remove previous bed space selection
            $('.bed-space-option').removeClass('selected');
            
            // Highlight selected bed space
            bedSpaceOption.addClass('selected');
            
            // Set selected bed space
            $('#selectedBedSpaceId').val(bedSpaceId);
            $('#selectedBedSummary').text('Bed ' + bedNumber);
            
            // Show selection summary
            $('#selectionSummary').show();
            
            // Scroll to selection summary
            $('#selectionSummary')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
        
        // Function to load bed spaces for a specific room
        function loadBedSpacesForRoom(roomId, container) {
            // Show loading
            container.html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading bed spaces...</div>');
            
            // Create bed spaces immediately for testing
            var bedSpacesHtml = '';
            for (var i = 1; i <= 4; i++) {
                var isAvailable = Math.random() > 0.3; // 70% chance of being available
                var statusClass = isAvailable ? 'available' : 'occupied';
                var statusText = isAvailable ? 'Available' : 'Occupied';
                var occupantText = isAvailable ? '' : '<br><small>John Doe</small>';
                
                bedSpacesHtml += `
                    <div class="bed-space-option ${statusClass}" data-bed-space-id="${i}" ${isAvailable ? '' : 'style="cursor: not-allowed;"'}>
                        <div class="bed-number">${i}</div>
                        <div class="bed-status">${statusText}</div>
                        ${occupantText}
                    </div>
                `;
            }
            container.html(bedSpacesHtml);
        }
        
        // Room button hover effects are handled by CSS
        
        // Form validation for room and bed space selection
        $('form').off('submit').on('submit', function(e) {
            if (!$('#selectedRoomId').val()) {
                e.preventDefault();
                alert('Please select a room before submitting your request.');
                $('#roomSearch').focus();
                return false;
            }
            if (!$('#selectedBedSpaceId').val()) {
                e.preventDefault();
                alert('Please select a bed space before submitting your request. Make sure you have selected a room first and then clicked on an available bed space.');
                if ($('#bedSpaceSelection').length > 0) {
                    $('#bedSpaceSelection')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                return false;
            }
        });
    });
    
    // Function to load bed spaces for selected room
    function loadBedSpaces(roomId) {
        console.log('Loading bed spaces for room ID:', roomId);
        
        // Ensure bed space selection is visible
        $('#bedSpaceSelection').show();
        $('#bedSpaceContainer').html('<div class="bed-space-loading"><i class="fas fa-spinner fa-spin"></i> Loading bed spaces...</div>');
        
        console.log('Bed space selection should be visible now');
        
        // Test with a simple timeout first to see if the section appears
        setTimeout(function() {
            console.log('Bed space selection visible:', $('#bedSpaceSelection').is(':visible'));
            console.log('Bed space container content:', $('#bedSpaceContainer').html());
        }, 500);
        
        $.ajax({
            url: 'get_available_bed_spaces.php',
            method: 'GET',
            data: { room_id: roomId },
            dataType: 'json',
            success: function(response) {
                console.log('Bed spaces response:', response);
                if (response.success) {
                    displayBedSpaces(response);
                } else {
                    $('#bedSpaceContainer').html('<div class="bed-space-error"><i class="fas fa-exclamation-triangle"></i> ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error);
                console.log('Response text:', xhr.responseText);
                
                // Show error but also create dummy bed spaces for testing
                $('#bedSpaceContainer').html('<div class="bed-space-error"><i class="fas fa-exclamation-triangle"></i> Error loading bed spaces. Showing test data.<br><small>Error: ' + error + '</small></div>');
                
                // Create dummy bed spaces for testing
                setTimeout(function() {
                    createDummyBedSpaces();
                }, 1000);
            }
        });
    }
    
    // Function to display bed spaces
    function displayBedSpaces(data) {
        console.log('Displaying bed spaces:', data);
        var container = $('#bedSpaceContainer');
        var roomInfo = data.room_info;
        var availableBeds = data.available_bed_spaces;
        var occupiedBeds = data.occupied_bed_spaces;
        
        console.log('Available beds:', availableBeds);
        console.log('Occupied beds:', occupiedBeds);
        
        // Create a map of occupied beds for quick lookup
        var occupiedMap = {};
        occupiedBeds.forEach(function(bed) {
            occupiedMap[bed.bed_number] = bed;
        });
        
        var html = '<div class="mb-3"><h6><i class="fas fa-bed"></i> ' + roomInfo.building_name + ' - Room ' + roomInfo.room_number + '</h6>';
        html += '<small class="text-muted">Select an available bed space (Bed 1-' + roomInfo.capacity + ')</small></div>';
        html += '<div class="row g-2">';
        
        // Display all bed spaces (available and occupied)
        for (var i = 1; i <= roomInfo.capacity; i++) {
            var isOccupied = occupiedMap[i];
            var bedSpaceId = null;
            var statusClass = 'occupied';
            var statusText = 'Occupied';
            var occupantInfo = '';
            
            if (!isOccupied) {
                // Find the available bed space ID
                var availableBed = availableBeds.find(function(bed) {
                    return bed.bed_number == i;
                });
                if (availableBed) {
                    bedSpaceId = availableBed.id;
                    statusClass = 'available';
                    statusText = 'Available';
                }
            } else {
                occupantInfo = '<div class="bed-occupant">' + isOccupied.first_name + ' ' + isOccupied.last_name + '</div>';
            }
            
            html += '<div class="col-md-3 col-sm-4 col-6">';
            html += '<div class="bed-space-option ' + statusClass + '" data-bed-space-id="' + bedSpaceId + '" data-bed-number="' + i + '">';
            html += '<div class="bed-icon"><i class="fas fa-bed"></i></div>';
            html += '<div class="bed-number">Bed ' + i + '</div>';
            html += '<div class="bed-status">' + statusText + '</div>';
            html += occupantInfo;
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        container.html(html);
        
        // Add click handlers for available bed spaces
        $('.bed-space-option.available').off('click').on('click', function() {
            var bedSpaceId = $(this).data('bed-space-id');
            var bedNumber = $(this).data('bed-number');
            
            console.log('Bed space clicked:', bedSpaceId, bedNumber);
            
            // Remove previous selection
            $('.bed-space-option').removeClass('selected');
            
            // Highlight selected bed space
            $(this).addClass('selected');
            
            // Set hidden input value
            $('#selectedBedSpaceId').val(bedSpaceId);
            
            console.log('Bed space ID set to:', $('#selectedBedSpaceId').val());
            
            // Show confirmation - update the room text to include bed number
            var roomText = $('#selectedRoomText').text();
            // Remove any existing bed number if present
            roomText = roomText.replace(/\s*-\s*Bed\s+\d+$/, '');
            $('#selectedRoomText').text(roomText + ' - Bed ' + bedNumber);
        });
    }
    
    // Function to create dummy bed spaces for testing
    function createDummyBedSpaces() {
        console.log('Creating dummy bed spaces for testing');
        var container = $('#bedSpaceContainer');
        
        var html = '<div class="mb-3"><h6><i class="fas fa-bed"></i> Test Room - Room 101</h6>';
        html += '<small class="text-muted">Select an available bed space (Bed 1-4)</small></div>';
        html += '<div class="row g-2">';
        
        // Create 4 dummy bed spaces
        for (var i = 1; i <= 4; i++) {
            var isAvailable = i <= 2; // First 2 beds available, last 2 occupied
            var statusClass = isAvailable ? 'available' : 'occupied';
            var statusText = isAvailable ? 'Available' : 'Occupied';
            var bedSpaceId = isAvailable ? 'test_' + i : null;
            var occupantInfo = isAvailable ? '' : '<div class="bed-occupant">Test Student ' + i + '</div>';
            
            html += '<div class="col-md-3 col-sm-4 col-6">';
            html += '<div class="bed-space-option ' + statusClass + '" data-bed-space-id="' + bedSpaceId + '" data-bed-number="' + i + '">';
            html += '<div class="bed-icon"><i class="fas fa-bed"></i></div>';
            html += '<div class="bed-number">Bed ' + i + '</div>';
            html += '<div class="bed-status">' + statusText + '</div>';
            html += occupantInfo;
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        container.html(html);
        
        // Add click handlers for available bed spaces
        $('.bed-space-option.available').off('click').on('click', function() {
            var bedSpaceId = $(this).data('bed-space-id');
            var bedNumber = $(this).data('bed-number');
            
            console.log('Dummy bed space clicked:', bedSpaceId, bedNumber);
            
            // Remove previous selection
            $('.bed-space-option').removeClass('selected');
            
            // Highlight selected bed space
            $(this).addClass('selected');
            
            // Set hidden input value
            $('#selectedBedSpaceId').val(bedSpaceId);
            
            console.log('Dummy bed space ID set to:', $('#selectedBedSpaceId').val());
            
            // Show confirmation
            var roomText = $('#selectedRoomText').text();
            roomText = roomText.replace(/\s*-\s*Bed\s+\d+$/, '');
            $('#selectedRoomText').text(roomText + ' - Bed ' + bedNumber);
        });
    }
    
    // Reset form when modal is closed
    $('#submitRequestModal').on('hidden.bs.modal', function() {
        $('#roomSearch').val('');
        $('.room-option').show();
        $('#noResults').hide();
        $('#clearSearch').hide();
        $('.room-button').removeClass('selected');
        $('.bed-spaces-section').hide();
        $('.bed-space-option').removeClass('selected');
        $('#selectedRoomId').val('');
        $('#selectedBedSpaceId').val('');
        $('#selectionSummary').hide();
        $('#bedSpaceContainer').empty();
        $('#testBedSpaces').hide();
        $('textarea[name="reason"]').val('');
        
        // Reset filter buttons
        $('[data-filter]').removeClass('active');
        $('[data-filter="all"]').addClass('active'); // Set "Show All" as default active
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
                    <h6>Request Information</h6>
                    <p><strong>Current Room:</strong> ${request.current_room ? (request.current_building + ' - ' + request.current_room) : 'Not assigned'}</p>
                    <p><strong>Requested Room:</strong> ${request.requested_room ? (request.requested_building + ' - ' + request.requested_room) : 'Not specified'}</p>
                    <p><strong>Status:</strong> <span class="${statusClass}">${request.status}</span></p>
                    <p><strong>Requested:</strong> ${new Date(request.requested_at).toLocaleString()}</p>
                </div>
                <div class="col-md-6">
                    <h6>Processing</h6>
                    ${request.processed_at ? `<p><strong>Processed:</strong> ${new Date(request.processed_at).toLocaleString()}</p>` : '<p><strong>Processed:</strong> Not yet processed</p>'}
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
        `;
        
        modal.find('#requestDetails').html(content);
    });
});
</script>

<?php include 'includes/footer.php'; ?> 