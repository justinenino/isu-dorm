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
                    $reason = $_POST['reason'];
                    $current_room_id = $_POST['current_room_id'];
                    
                    // Basic validation
                    if (empty($requested_room_id) || empty($reason)) {
                        $_SESSION['error'] = "Please fill in all required fields.";
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
                    
                    $stmt = $pdo->prepare("INSERT INTO room_change_requests (student_id, current_room_id, requested_room_id, reason) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$_SESSION['user_id'], $current_room_id, $requested_room_id, $reason]);
                    
                    if ($result) {
                        $_SESSION['success'] = "Room change request submitted successfully.";
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
                    <div class="mb-3">
                        <label class="form-label">Current Room</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['building_name'] . ' - ' . $student['room_number']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requested Room</label>
                        
                        <!-- Search Input -->
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="roomSearch" class="form-control" placeholder="Search rooms by building, room number, or floor...">
                            <button class="btn btn-outline-primary" type="button" id="searchButton">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display: none;">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                        
                        <!-- Quick Search Filters -->
                        <div class="mb-2">
                            <small class="text-muted d-block mb-2">Quick filters:</small>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-info" data-filter="available">
                                    <i class="fas fa-check-circle"></i> Available Only
                                </button>
                                <button type="button" class="btn btn-outline-warning" data-filter="partial">
                                    <i class="fas fa-exclamation-triangle"></i> Partially Available
                                </button>
                                <button type="button" class="btn btn-outline-success" data-filter="fully">
                                    <i class="fas fa-star"></i> Fully Available
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-filter="all">
                                    <i class="fas fa-list"></i> Show All
                                </button>
                            </div>
                        </div>
                        
                        <!-- Room Selection -->
                        <div class="room-selection-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 10px;">
                            <div class="row g-2" id="roomList">
                                <?php foreach ($available_rooms as $room): 
                                    $available_beds = $room['total_beds'] - $room['occupied_beds'];
                                    $availability_percentage = $room['total_beds'] > 0 ? round(($available_beds / $room['total_beds']) * 100) : 0;
                                    
                                    // Determine availability status
                                    if ($available_beds == $room['total_beds']) {
                                        $status_class = 'success';
                                        $status_text = 'Fully Available';
                                    } elseif ($available_beds > 0) {
                                        $status_class = 'warning';
                                        $status_text = 'Partially Available';
                                    } else {
                                        $status_class = 'danger';
                                        $status_text = 'Fully Occupied';
                                    }
                                ?>
                                <div class="col-md-6 room-option" data-room-id="<?php echo $room['id']; ?>" 
                                     data-building="<?php echo $room['building_name']; ?>"
                                     data-room-number="<?php echo $room['room_number']; ?>"
                                     data-floor="<?php echo $room['floor_number']; ?>">
                                    <div class="card room-card h-100" style="cursor: pointer; transition: all 0.3s;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-bed text-primary"></i>
                                                    <?php echo htmlspecialchars($room['building_name'] . ' - ' . $room['room_number']); ?>
                                                </h6>
                                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </div>
                                            <div class="room-details">
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-layer-group"></i> Floor <?php echo $room['floor_number']; ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-users"></i> <?php echo $available_beds; ?> of <?php echo $room['total_beds']; ?> beds available
                                                </small>
                                                <div class="progress mt-2" style="height: 4px;">
                                                    <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                                         style="width: <?php echo $availability_percentage; ?>%"></div>
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
                        
                        <!-- Hidden input for selected room -->
                        <input type="hidden" name="requested_room_id" id="selectedRoomId" required>
                        
                        <!-- Selected room display -->
                        <div id="selectedRoomDisplay" class="mt-2" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-check-circle"></i>
                                <strong>Selected Room:</strong> <span id="selectedRoomText"></span>
                            </div>
                        </div>
                        
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Click on a room card to select it. Only rooms with available beds are shown.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Please provide a detailed reason for requesting this room change..."></textarea>
                        <small class="form-text text-muted">Be specific about why you need to change rooms. This will help the admin make a decision.</small>
                    </div>
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
.room-card {
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.room-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.room-card.border-primary {
    border-color: #0d6efd !important;
    background-color: #f8f9ff !important;
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
</style>

<script>
$(document).ready(function() {
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
        
        
        // Room selection functionality
        $('.room-card').off('click').on('click', function() {
            var roomCard = $(this);
            var roomOption = roomCard.closest('.room-option');
            var roomId = roomOption.data('room-id');
            var roomTitle = roomCard.find('.card-title').text().trim();
            
            // Remove previous selection
            $('.room-card').removeClass('border-primary bg-light');
            $('.room-card').addClass('border-light');
            
            // Highlight selected room
            roomCard.removeClass('border-light');
            roomCard.addClass('border-primary bg-light');
            
            // Set hidden input value
            $('#selectedRoomId').val(roomId);
            
            // Show selected room display
            $('#selectedRoomText').text(roomTitle);
            $('#selectedRoomDisplay').show();
            
            // Scroll to selected room display
            $('#selectedRoomDisplay')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
        
        // Add hover effects to room cards
        $('.room-card').off('mouseenter mouseleave').hover(
            function() {
                if (!$(this).hasClass('border-primary')) {
                    $(this).addClass('border-secondary shadow-sm');
                }
            },
            function() {
                if (!$(this).hasClass('border-primary')) {
                    $(this).removeClass('border-secondary shadow-sm');
                }
            }
        );
        
        // Form validation for room selection
        $('form').off('submit').on('submit', function(e) {
            if (!$('#selectedRoomId').val()) {
                e.preventDefault();
                alert('Please select a room before submitting your request.');
                $('#roomSearch').focus();
                return false;
            }
        });
    });
    
    // Reset form when modal is closed
    $('#submitRequestModal').on('hidden.bs.modal', function() {
        $('#roomSearch').val('');
        $('.room-option').show();
        $('#noResults').hide();
        $('#clearSearch').hide();
        $('.room-card').removeClass('border-primary bg-light border-secondary shadow-sm');
        $('.room-card').addClass('border-light');
        $('#selectedRoomId').val('');
        $('#selectedRoomDisplay').hide();
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