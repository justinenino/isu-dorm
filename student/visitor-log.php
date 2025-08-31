<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$page_title = "Visitor Log";
require_once 'includes/header.php';

// Handle visitor submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_visitor') {
        $visitor_name = sanitizeInput($_POST['visitor_name']);
        $visitor_age = sanitizeInput($_POST['visitor_age']);
        $visitor_address = sanitizeInput($_POST['visitor_address']);
        $visitor_contact = sanitizeInput($_POST['visitor_contact']);
        $relationship = sanitizeInput($_POST['relationship']);
        $purpose = sanitizeInput($_POST['purpose']);
        $expected_duration = sanitizeInput($_POST['expected_duration']);
        
        // Validate inputs
        if (empty($visitor_name) || empty($visitor_age) || empty($visitor_contact)) {
            $error_message = "Please fill in all required fields.";
        } else {
            try {
                $pdo = getDBConnection();
                
                // Get student's current room
                $room_stmt = $pdo->prepare("
                    SELECT r.room_number, b.building_name, r.id as room_id
                    FROM reservations res
                    JOIN bedspaces bs ON res.bedspace_id = bs.id
                    JOIN rooms r ON bs.room_id = r.id
                    JOIN buildings b ON r.building_id = b.id
                    WHERE res.student_id = ? AND res.status = 'approved'
                    ORDER BY res.created_at DESC
                    LIMIT 1
                ");
                $room_stmt->execute([$_SESSION['user_id']]);
                $room_info = $room_stmt->fetch();
                
                if (!$room_info) {
                    $error_message = "You must have an approved room reservation to register visitors.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO visitors (student_id, visitor_name, visitor_age, visitor_address, visitor_contact, 
                                            relationship, purpose, room_number, expected_duration, time_in, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', NOW())
                    ");
                    
                    if ($stmt->execute([$_SESSION['user_id'], $visitor_name, $visitor_age, $visitor_address, 
                                      $visitor_contact, $relationship, $purpose, $room_info['room_number'], $expected_duration])) {
                        logActivity($_SESSION['user_id'], "Registered visitor: $visitor_name");
                        $success_message = "Visitor registered successfully! Please wait for admin approval.";
                        
                        // Clear form data
                        $visitor_name = $visitor_age = $visitor_address = $visitor_contact = $relationship = $purpose = $expected_duration = '';
                    } else {
                        $error_message = "Failed to register visitor. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = "Database error occurred. Please try again.";
            }
        }
    }
}

// Get student's visitor history
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM visitors 
        WHERE student_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $visitors = $stmt->fetchAll();
    
    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
            SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as checked_out
        FROM visitors 
        WHERE student_id = ?
    ");
    $stats_stmt->execute([$_SESSION['user_id']]);
    $stats = $stats_stmt->fetch();
    
    // Get current room info
    $room_stmt = $pdo->prepare("
        SELECT r.room_number, b.building_name
        FROM reservations res
        JOIN bedspaces bs ON res.bedspace_id = bs.id
        JOIN rooms r ON bs.room_id = r.id
        JOIN buildings b ON r.building_id = b.id
        WHERE res.student_id = ? AND res.status = 'approved'
        ORDER BY res.created_at DESC
        LIMIT 1
    ");
    $room_stmt->execute([$_SESSION['user_id']]);
    $current_room = $room_stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database error occurred.";
    $visitors = [];
    $stats = [];
    $current_room = null;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-white">Visitor Log</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Register Visitor Form -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Register New Visitor</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($current_room): ?>
                                <div class="alert alert-info mb-4">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Your Current Room:</strong> <?php echo $current_room['building_name'] . ' - Room ' . $current_room['room_number']; ?>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_visitor">
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="visitor_name" class="form-label">Visitor Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="visitor_name" name="visitor_name" 
                                                       value="<?php echo isset($visitor_name) ? htmlspecialchars($visitor_name) : ''; ?>" 
                                                       placeholder="Enter visitor's full name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="visitor_age" class="form-label">Age <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="visitor_age" name="visitor_age" 
                                                       value="<?php echo isset($visitor_age) ? htmlspecialchars($visitor_age) : ''; ?>" 
                                                       placeholder="Age" min="1" max="120" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="visitor_address" class="form-label">Visitor Address</label>
                                        <input type="text" class="form-control" id="visitor_address" name="visitor_address" 
                                               value="<?php echo isset($visitor_address) ? htmlspecialchars($visitor_address) : ''; ?>" 
                                               placeholder="Enter visitor's address">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="visitor_contact" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="visitor_contact" name="visitor_contact" 
                                                       value="<?php echo isset($visitor_contact) ? htmlspecialchars($visitor_contact) : ''; ?>" 
                                                       placeholder="e.g., 09123456789" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="relationship" class="form-label">Relationship to You</label>
                                                <input type="text" class="form-control" id="relationship" name="relationship" 
                                                       value="<?php echo isset($relationship) ? htmlspecialchars($relationship) : ''; ?>" 
                                                       placeholder="e.g., Parent, Friend, Sibling">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="purpose" class="form-label">Purpose of Visit</label>
                                                <input type="text" class="form-control" id="purpose" name="purpose" 
                                                       value="<?php echo isset($purpose) ? htmlspecialchars($purpose) : ''; ?>" 
                                                       placeholder="e.g., Family visit, Study session">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="expected_duration" class="form-label">Expected Duration</label>
                                                <select class="form-select" id="expected_duration" name="expected_duration">
                                                    <option value="1-2 hours" <?php echo (isset($expected_duration) && $expected_duration === '1-2 hours') ? 'selected' : ''; ?>>1-2 hours</option>
                                                    <option value="3-4 hours" <?php echo (isset($expected_duration) && $expected_duration === '3-4 hours') ? 'selected' : ''; ?>>3-4 hours</option>
                                                    <option value="Half day" <?php echo (isset($expected_duration) && $expected_duration === 'Half day') ? 'selected' : ''; ?>>Half day</option>
                                                    <option value="Full day" <?php echo (isset($expected_duration) && $expected_duration === 'Full day') ? 'selected' : ''; ?>>Full day</option>
                                                    <option value="Overnight" <?php echo (isset($expected_duration) && $expected_duration === 'Overnight') ? 'selected' : ''; ?>>Overnight</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Visitor Guidelines</h6>
                                        <ul class="mb-0">
                                            <li>All visitors must be registered and approved before entry</li>
                                            <li>Visitors must check in at the front desk upon arrival</li>
                                            <li>Students are responsible for their visitors' conduct</li>
                                            <li>Overnight visitors require special approval</li>
                                            <li>Maximum 2 visitors per student at any time</li>
                                            <li>Visiting hours: 8:00 AM - 10:00 PM (weekdays), 8:00 AM - 11:00 PM (weekends)</li>
                                        </ul>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Register Visitor
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Room Required:</strong> You must have an approved room reservation to register visitors.
                                    <a href="reserve_room.php" class="btn btn-sm btn-primary ms-2">Reserve Room</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics and Guidelines -->
                <div class="col-lg-4">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Visitors</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Checked In</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['checked_in'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visiting Hours -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Visiting Hours</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Weekdays:</strong> 8:00 AM - 10:00 PM
                            </div>
                            <div class="mb-2">
                                <strong>Weekends:</strong> 8:00 AM - 11:00 PM
                            </div>
                            <div class="mb-2">
                                <strong>Holidays:</strong> 8:00 AM - 11:00 PM
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                All visitors must check out before closing time.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Visitor History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Your Visitor History</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($visitors)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No visitors registered yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="visitorsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Visitor Name</th>
                                        <th>Contact</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visitors as $visitor): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($visitor['visitor_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">Age: <?php echo $visitor['visitor_age']; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($visitor['visitor_contact']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['purpose'] ?: 'Not specified'); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($visitor['status']) {
                                                    case 'pending':
                                                        $status_class = 'warning';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'success';
                                                        break;
                                                    case 'checked_in':
                                                        $status_class = 'info';
                                                        break;
                                                    case 'checked_out':
                                                        $status_class = 'secondary';
                                                        break;
                                                    case 'rejected':
                                                        $status_class = 'danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $visitor['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($visitor['time_in']) {
                                                    echo date('M j, Y g:i A', strtotime($visitor['time_in']));
                                                } else {
                                                    echo '<span class="text-muted">Not checked in</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($visitor['time_out']) {
                                                    echo date('M j, Y g:i A', strtotime($visitor['time_out']));
                                                } else {
                                                    echo '<span class="text-muted">Not checked out</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewVisitor(<?php echo $visitor['id']; ?>)">
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
        </div>
    </div>
</div>

<!-- View Visitor Modal -->
<div class="modal fade" id="viewVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visitor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitorDetails">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#visitorsTable').DataTable({
        order: [[4, 'desc']], // Sort by time in descending
        pageLength: 10
    });
});

function viewVisitor(visitorId) {
    // Load visitor details via AJAX
    $.post('get_visitor_details.php', {visitor_id: visitorId}, function(response) {
        if (response.success) {
            $('#visitorDetails').html(response.data);
            $('#viewVisitorModal').modal('show');
        } else {
            alert('Error loading visitor details: ' + response.message);
        }
    }, 'json');
}
</script>

<?php require_once 'includes/footer.php'; ?>
