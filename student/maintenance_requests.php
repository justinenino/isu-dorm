<?php
// Include database configuration first
require_once '../config/database.php';

// Handle form submissions BEFORE including header to avoid "headers already sent" error
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'includes/validation.php';
    $pdo = getConnection();
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit_request':
                try {
                    // Check rate limit
                    if (!StudentValidation::checkRateLimit('maintenance_request', $_SESSION['user_id'])) {
                        StudentSuccessHandler::setError(StudentErrorHandler::handleRateLimitError('maintenance request submission'));
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Sanitize input
                    $data = StudentValidation::sanitizeInput($_POST);
                    
                    // Validate data
                    $errors = StudentValidation::validateMaintenanceRequest($data);
                    
                    if (!empty($errors)) {
                        StudentSuccessHandler::setError(StudentErrorHandler::handleValidationErrors($errors));
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Check if student has a room assigned
                    $stmt = $pdo->prepare("SELECT room_id FROM students WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $student_room = $stmt->fetchColumn();
                    
                    if (!$student_room) {
                        StudentSuccessHandler::setError("You must have a room assigned before submitting maintenance requests.");
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Use the room_id from the form data, but validate it matches the student's assigned room
                    if ($data['room_id'] != $student_room) {
                        StudentSuccessHandler::setError("Invalid room assignment. Please refresh the page and try again.");
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Insert maintenance request
                    $stmt = $pdo->prepare("INSERT INTO maintenance_requests (student_id, room_id, title, description, priority) VALUES (?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$_SESSION['user_id'], $data['room_id'], $data['title'], $data['description'], $data['priority']]);
                    
                    if ($result) {
                        StudentSuccessHandler::setSuccess("Maintenance request submitted successfully.");
                    } else {
                        StudentSuccessHandler::setError("Failed to submit maintenance request. Please try again.");
                    }
                    
                } catch (Exception $e) {
                    StudentSuccessHandler::setError(StudentErrorHandler::handleDatabaseError($e, 'maintenance request submission'));
                    StudentErrorHandler::logError("Maintenance request submission failed", [
                        'user_id' => $_SESSION['user_id'],
                        'error' => $e->getMessage()
                    ]);
                }
                
                header("Location: maintenance_requests.php");
                exit;
                break;
                
            case 'edit_request':
                try {
                    $request_id = $_POST['request_id'];
                    
                    // Verify the request belongs to the current student and is pending
                    $stmt = $pdo->prepare("SELECT id, status FROM maintenance_requests WHERE id = ? AND student_id = ?");
                    $stmt->execute([$request_id, $_SESSION['user_id']]);
                    $request = $stmt->fetch();
                    
                    if (!$request) {
                        StudentSuccessHandler::setError("Maintenance request not found or you don't have permission to edit it.");
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Check if request is still pending (only pending requests can be edited)
                    if ($request['status'] != 'pending') {
                        StudentSuccessHandler::setError("Only pending maintenance requests can be edited.");
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Sanitize input
                    $data = StudentValidation::sanitizeInput($_POST);
                    
                    // Validate data
                    $errors = StudentValidation::validateMaintenanceRequest($data);
                    
                    if (!empty($errors)) {
                        StudentSuccessHandler::setError(StudentErrorHandler::handleValidationErrors($errors));
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Update maintenance request
                    $stmt = $pdo->prepare("UPDATE maintenance_requests SET title = ?, description = ?, priority = ? WHERE id = ? AND student_id = ?");
                    $result = $stmt->execute([$data['title'], $data['description'], $data['priority'], $request_id, $_SESSION['user_id']]);
                    
                    if ($result) {
                        StudentSuccessHandler::setSuccess("Maintenance request updated successfully.");
                    } else {
                        StudentSuccessHandler::setError("Failed to update maintenance request. Please try again.");
                    }
                    
                } catch (Exception $e) {
                    StudentSuccessHandler::setError(StudentErrorHandler::handleDatabaseError($e, 'updating maintenance request'));
                    StudentErrorHandler::logError("Maintenance request update failed", [
                        'user_id' => $_SESSION['user_id'],
                        'request_id' => $request_id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
                
                header("Location: maintenance_requests.php");
                exit;
                break;
                
            case 'mark_complete':
                try {
                    $request_id = $_POST['request_id'];
                    
                    // Verify the request belongs to the current student
                    $stmt = $pdo->prepare("SELECT id, status FROM maintenance_requests WHERE id = ? AND student_id = ?");
                    $stmt->execute([$request_id, $_SESSION['user_id']]);
                    $request = $stmt->fetch();
                    
                    if (!$request) {
                        StudentSuccessHandler::setError("Maintenance request not found or you don't have permission to modify it.");
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Check if request is already completed
                    if ($request['status'] == 'completed') {
                        StudentSuccessHandler::setError("This maintenance request is already marked as completed.");
                        header("Location: maintenance_requests.php");
                        exit;
                    }
                    
                    // Update status to completed
                    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = 'completed', completed_at = NOW() WHERE id = ? AND student_id = ?");
                    $result = $stmt->execute([$request_id, $_SESSION['user_id']]);
                    
                    if ($result) {
                        StudentSuccessHandler::setSuccess("Maintenance request marked as completed successfully.");
                    } else {
                        StudentSuccessHandler::setError("Failed to mark maintenance request as completed. Please try again.");
                    }
                    
                } catch (Exception $e) {
                    StudentSuccessHandler::setError(StudentErrorHandler::handleDatabaseError($e, 'marking maintenance request as complete'));
                    StudentErrorHandler::logError("Maintenance request completion failed", [
                        'user_id' => $_SESSION['user_id'],
                        'request_id' => $request_id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
                
                header("Location: maintenance_requests.php");
                exit;
                break;
        }
    }
}

// Include header and other files after form processing
$page_title = 'Maintenance Requests';
include 'includes/header.php';
include 'includes/validation.php';

$pdo = getConnection();

// Get student's room information
$stmt = $pdo->prepare("SELECT s.*, r.id as room_id, r.room_number, b.name as building_name
    FROM students s
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get student's maintenance requests with enhanced filtering
$where_conditions = ["mr.student_id = ?"];
$params = [$_SESSION['user_id']];

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if (!empty($search)) {
    $where_conditions[] = "(mr.title LIKE ? OR mr.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "mr.status = ?";
    $params[] = $status_filter;
}

if (!empty($priority_filter)) {
    $where_conditions[] = "mr.priority = ?";
    $params[] = $priority_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(mr.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(mr.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT mr.*, 
    r.room_number, 
    b.name as building_name
    FROM maintenance_requests mr
    LEFT JOIN rooms r ON mr.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE $where_clause
    ORDER BY mr.created_at DESC");
$stmt->execute($params);
$maintenance_requests = $stmt->fetchAll();
?>

<div class="mb-4">
    <h2><i class="fas fa-tools"></i> Maintenance Requests</h2>
    <p class="text-muted">Submit and manage your maintenance requests for your dormitory room.</p>
</div>

<!-- Success/Error Messages -->
<?php if (StudentSuccessHandler::getSuccess()): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo StudentSuccessHandler::getSuccess(); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (StudentSuccessHandler::getError()): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo StudentSuccessHandler::getError(); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- New Maintenance Request Form -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Submit New Maintenance Request</h5>
    </div>
    <div class="card-body">
        <form method="POST" id="maintenanceRequestForm">
            <input type="hidden" name="action" value="submit_request">
            <input type="hidden" name="room_id" value="<?php echo $student['room_id']; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-heading"></i> Request Title</label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="Brief description of the issue (e.g., Broken faucet, AC not working)">
                        <div class="form-text">Give your request a clear, descriptive title</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-exclamation-triangle"></i> Priority Level</label>
                        <select name="priority" class="form-select" required>
                            <option value="">Select Priority</option>
                            <option value="low">🟢 Low - Minor issue, not urgent</option>
                            <option value="medium">🟡 Medium - Moderate issue, needs attention</option>
                            <option value="high">🟠 High - Important issue, affects daily use</option>
                            <option value="urgent">🔴 Urgent - Critical issue, immediate attention needed</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-align-left"></i> Detailed Description</label>
                <textarea name="description" class="form-control" rows="4" required 
                          placeholder="Please provide a detailed description of the maintenance issue..."></textarea>
                <div class="form-text">
                    <i class="fas fa-info-circle"></i> Include specific details about the problem, location, and any relevant information. 
                    The more details you provide, the better we can help you.
                </div>
            </div>
            
            <!-- Room Information Display -->
            <?php if ($student['room_id']): ?>
            <div class="alert alert-info">
                <i class="fas fa-bed"></i> <strong>Room Information:</strong> 
                <?php echo htmlspecialchars($student['building_name'] . ' - Room ' . $student['room_number']); ?>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>No Room Assigned:</strong> You must have a room assigned before submitting maintenance requests.
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center">
                <div class="form-text">
                    <i class="fas fa-clock"></i> Requests are typically processed within 24-48 hours
                </div>
                <button type="submit" class="btn btn-primary btn-lg" <?php echo !$student['room_id'] ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count(array_filter($maintenance_requests, function($mr) { return $mr['status'] == 'pending'; })); ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($maintenance_requests, function($mr) { return $mr['status'] == 'in_progress'; })); ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($maintenance_requests, function($mr) { return $mr['status'] == 'completed'; })); ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo count($maintenance_requests); ?></h3>
            <p>Total</p>
        </div>
    </div>
</div>

<!-- Search and Filter Controls -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-filter"></i> Search & Filter</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by title or description..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="urgent" <?php echo $priority_filter == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="maintenance_requests.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Current Room Information -->
<?php if ($student['room_id']): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bed"></i> Your Room Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Building:</strong> <?php echo htmlspecialchars($student['building_name']); ?></p>
                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($student['room_number']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge bg-success">Assigned</span></p>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> You don't have a room assigned yet. Please wait for admin approval.
</div>
<?php endif; ?>

<!-- Maintenance Requests Table -->
<div class="card">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list"></i> Your Maintenance Requests</h5>
            <span class="badge bg-primary"><?php echo count($maintenance_requests); ?> total</span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($maintenance_requests)): ?>
            <div class="text-center py-4">
                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                <h5>No maintenance requests found</h5>
                <p class="text-muted">
                    <?php if (!empty($search) || !empty($status_filter) || !empty($priority_filter) || !empty($date_from) || !empty($date_to)): ?>
                        No requests match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        Submit your first maintenance request to get started.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="maintenanceTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Room</th>
                            <th>Assigned To</th>
                            <th>Submitted</th>
                            <th>Completed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance_requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                    <?php if ($request['priority'] == 'urgent'): ?>
                                        <i class="fas fa-exclamation-triangle text-danger ms-1" title="Urgent Priority"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($request['description']); ?>">
                                        <?php echo htmlspecialchars($request['description']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $priority_class = 'badge priority-' . $request['priority'];
                                    $priority_icon = '';
                                    switch ($request['priority']) {
                                        case 'low': 
                                            $priority_icon = 'fas fa-arrow-down';
                                            break;
                                        case 'medium': 
                                            $priority_icon = 'fas fa-minus';
                                            break;
                                        case 'high': 
                                            $priority_icon = 'fas fa-arrow-up';
                                            break;
                                        case 'urgent': 
                                            $priority_icon = 'fas fa-exclamation-triangle';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $priority_class; ?>">
                                        <i class="<?php echo $priority_icon; ?>"></i>
                                        <?php echo ucfirst($request['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'badge status-' . $request['status'];
                                    $status_icon = '';
                                    switch ($request['status']) {
                                        case 'pending': 
                                            $status_icon = 'fas fa-clock';
                                            break;
                                        case 'in_progress': 
                                            $status_icon = 'fas fa-cog fa-spin';
                                            break;
                                        case 'completed': 
                                            $status_icon = 'fas fa-check';
                                            break;
                                        case 'cancelled': 
                                            $status_icon = 'fas fa-times';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <i class="<?php echo $status_icon; ?>"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['room_number']): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-bed"></i>
                                            <?php echo htmlspecialchars($request['building_name'] . ' - ' . $request['room_number']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['assigned_to']): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-user-tie"></i>
                                            <?php echo htmlspecialchars($request['assigned_to']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?><br>
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($request['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($request['completed_at']): ?>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($request['completed_at'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal" 
                                                data-request='<?php echo htmlspecialchars(json_encode($request), ENT_QUOTES, 'UTF-8'); ?>' title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRequestModal" 
                                                    data-request='<?php echo htmlspecialchars(json_encode($request), ENT_QUOTES, 'UTF-8'); ?>' title="Edit Request">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($request['status'] != 'completed' && $request['status'] != 'cancelled'): ?>
                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#completeRequestModal" 
                                                    data-request-id="<?php echo $request['id']; ?>" title="Mark as Complete">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
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


<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Maintenance Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Complete Request Modal -->
<div class="modal fade" id="completeRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Request as Complete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="completeRequestForm">
                <input type="hidden" name="action" value="mark_complete">
                <input type="hidden" name="request_id" id="completeRequestId">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Please confirm:</strong> Are you sure you want to mark this maintenance request as completed? 
                        This action cannot be undone and the request will be sent to admin as completed.
                    </div>
                    <p class="mb-0">Once marked as complete, you will not be able to edit this request anymore.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Mark as Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Request Modal -->
<div class="modal fade" id="editRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Maintenance Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editRequestForm">
                <input type="hidden" name="action" value="edit_request">
                <input type="hidden" name="request_id" id="editRequestId">
                <input type="hidden" name="room_id" value="<?php echo $student['room_id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Request Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required placeholder="Brief description of the issue">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority Level</label>
                        <select name="priority" id="editPriority" class="form-select" required>
                            <option value="">Select Priority</option>
                            <option value="low">Low - Minor issue, not urgent</option>
                            <option value="medium">Medium - Moderate issue, needs attention</option>
                            <option value="high">High - Important issue, affects daily use</option>
                            <option value="urgent">Urgent - Critical issue, immediate attention needed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Detailed Description</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="6" required placeholder="Please provide a detailed description of the maintenance issue..."></textarea>
                        <small class="form-text text-muted">Include specific details about the problem, location, and any relevant information.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Custom styles for maintenance requests page */
.maintenance-form-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    margin-bottom: 20px;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card h3 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stats-card p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.9;
}

.table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    padding: 15px 12px;
}

.table td {
    vertical-align: middle;
    border-color: #e9ecef;
    padding: 15px 12px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    border-radius: 20px;
    font-weight: 600;
}

.alert {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    border-radius: 15px 15px 0 0;
    border: none;
}

.priority-urgent {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    color: white;
}

.priority-high {
    background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
    color: white;
}

.priority-medium {
    background: linear-gradient(135deg, #ffeb3b 0%, #ffc107 100%);
    color: #333;
}

.priority-low {
    background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);
    color: white;
}

.status-pending {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
}

.status-in_progress {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
}

.status-completed {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.status-cancelled {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

@media (max-width: 768px) {
    .stats-card h3 {
        font-size: 2rem;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<script>
// Ensure jQuery is loaded before proceeding
console.log('Checking jQuery availability...', typeof $);
if (typeof $ === 'undefined') {
    console.error('jQuery is not loaded. Please check the script loading order.');
    // Fallback: try to load jQuery dynamically
    var script = document.createElement('script');
    script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
    script.onload = function() {
        console.log('jQuery loaded dynamically, initializing DataTable...');
        initializeDataTable();
    };
    document.head.appendChild(script);
} else {
    console.log('jQuery is available, initializing DataTable...');
    $(document).ready(function() {
        initializeDataTable();
    });
}

function initializeDataTable() {
    // Initialize DataTable with enhanced options
    $('#maintenanceTable').DataTable({
        order: [[6, 'desc']], // Sort by submitted date
        pageLength: 10,
        responsive: true,
        columnDefs: [
            { targets: [1], orderable: false }, // Disable sorting on description
            { targets: [8], orderable: false }  // Disable sorting on actions
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            emptyTable: "No maintenance requests found"
        }
    });
    
    // Form validation and enhancement
    $('#maintenanceRequestForm').on('submit', function(e) {
        var form = $(this);
        var isValid = true;
        
        // Clear previous validation states
        form.find('.form-control, .form-select').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
        
        // Validate title
        var title = form.find('input[name="title"]').val().trim();
        if (title.length < 5) {
            showFieldError(form.find('input[name="title"]'), 'Title must be at least 5 characters long');
            isValid = false;
        }
        
        // Validate description
        var description = form.find('textarea[name="description"]').val().trim();
        if (description.length < 10) {
            showFieldError(form.find('textarea[name="description"]'), 'Description must be at least 10 characters long');
            isValid = false;
        }
        
        // Validate priority
        var priority = form.find('select[name="priority"]').val();
        if (!priority) {
            showFieldError(form.find('select[name="priority"]'), 'Please select a priority level');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            form.find('.is-invalid').first().focus();
        } else {
            // Show loading state
            var submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        }
    });
    
    // Real-time validation
    $('#maintenanceRequestForm input[name="title"]').on('input', function() {
        var value = $(this).val().trim();
        if (value.length >= 5) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid');
        }
    });
    
    $('#maintenanceRequestForm textarea[name="description"]').on('input', function() {
        var value = $(this).val().trim();
        if (value.length >= 10) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid');
        }
    });
    
    $('#maintenanceRequestForm select[name="priority"]').on('change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid');
        }
    });
    
    // Handle complete request modal
    $('#completeRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var requestId = button.data('request-id');
        $('#completeRequestId').val(requestId);
    });
    
    // Handle edit request modal
    $('#editRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var request = button.data('request');
        var modal = $(this);
        
        // Debug: Check if request data exists
        console.log('Edit request data:', request);
        
        // If data attribute doesn't work, try to get data from button's onclick or other attributes
        if (!request) {
            // Try to parse from data attribute as string
            var requestData = button.attr('data-request');
            if (requestData) {
                try {
                    request = JSON.parse(requestData);
                } catch (e) {
                    console.error('Error parsing request data:', e);
                }
            }
        }
        
        if (!request) {
            modal.find('.modal-body').html('<div class="alert alert-danger">Error: Could not load request details. Please refresh the page and try again.</div>');
            return;
        }
        
        // Populate form fields
        $('#editRequestId').val(request.id);
        $('#editTitle').val(request.title || '');
        $('#editPriority').val(request.priority || '');
        $('#editDescription').val(request.description || '');
    });
    
    // Form validation for edit form
    $('#editRequestForm').on('submit', function(e) {
        var form = $(this);
        var isValid = true;
        
        form.find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Handle view request modal
    $('#viewRequestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var request = button.data('request');
        var modal = $(this);
        
        // Debug: Check if request data exists
        console.log('Request data:', request);
        
        // If data attribute doesn't work, try to get data from button's onclick or other attributes
        if (!request) {
            // Try to parse from data attribute as string
            var requestData = button.attr('data-request');
            if (requestData) {
                try {
                    request = JSON.parse(requestData);
                } catch (e) {
                    console.error('Error parsing request data:', e);
                }
            }
        }
        
        if (!request) {
            modal.find('#requestDetails').html('<div class="alert alert-danger">Error: Could not load request details. Please refresh the page and try again.</div>');
            return;
        }
        
        var priorityClass = '';
        var priorityIcon = '';
        switch (request.priority) {
            case 'low': 
                priorityClass = 'badge bg-info'; 
                priorityIcon = 'fas fa-arrow-down';
                break;
            case 'medium': 
                priorityClass = 'badge bg-warning'; 
                priorityIcon = 'fas fa-minus';
                break;
            case 'high': 
                priorityClass = 'badge bg-danger'; 
                priorityIcon = 'fas fa-arrow-up';
                break;
            case 'urgent': 
                priorityClass = 'badge bg-dark'; 
                priorityIcon = 'fas fa-exclamation-triangle';
                break;
            default:
                priorityClass = 'badge bg-secondary';
                priorityIcon = 'fas fa-question';
        }
        
        var statusClass = '';
        var statusIcon = '';
        switch (request.status) {
            case 'pending': 
                statusClass = 'badge bg-warning'; 
                statusIcon = 'fas fa-clock';
                break;
            case 'in_progress': 
                statusClass = 'badge bg-info'; 
                statusIcon = 'fas fa-cog fa-spin';
                break;
            case 'completed': 
                statusClass = 'badge bg-success'; 
                statusIcon = 'fas fa-check';
                break;
            case 'cancelled': 
                statusClass = 'badge bg-secondary'; 
                statusIcon = 'fas fa-times';
                break;
            default:
                statusClass = 'badge bg-secondary';
                statusIcon = 'fas fa-question';
        }
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle"></i> Request Information</h6>
                    <p><strong>Title:</strong> ${request.title || 'N/A'}</p>
                    <p><strong>Priority:</strong> <span class="${priorityClass}"><i class="${priorityIcon}"></i> ${(request.priority || 'unknown').charAt(0).toUpperCase() + (request.priority || 'unknown').slice(1)}</span></p>
                    <p><strong>Status:</strong> <span class="${statusClass}"><i class="${statusIcon}"></i> ${(request.status || 'unknown').replace('_', ' ').charAt(0).toUpperCase() + (request.status || 'unknown').replace('_', ' ').slice(1)}</span></p>
                    <p><strong>Submitted:</strong> ${request.created_at ? new Date(request.created_at).toLocaleString() : 'N/A'}</p>
                    ${request.room_number ? `<p><strong>Room:</strong> ${request.building_name || ''} - ${request.room_number}</p>` : ''}
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-user-tie"></i> Assignment Details</h6>
                    <p><strong>Assigned To:</strong> ${request.assigned_to || 'Not assigned yet'}</p>
                    ${request.completed_at ? `<p><strong>Completed:</strong> ${new Date(request.completed_at).toLocaleString()}</p>` : ''}
                    <p><strong>Request ID:</strong> #${request.id || 'N/A'}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-align-left"></i> Description</h6>
                    <div class="border p-3 rounded bg-light">
                        <p class="mb-0">${request.description || 'No description available'}</p>
                    </div>
                </div>
            </div>
            ${request.status === 'completed' ? `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Request Completed:</strong> This maintenance request has been marked as completed and cannot be edited.
                    </div>
                </div>
            </div>
            ` : ''}
        `;
        
        modal.find('#requestDetails').html(content);
    });
}

function showFieldError(field, message) {
    field.addClass('is-invalid');
    field.after('<div class="invalid-feedback">' + message + '</div>');
}

// Auto-refresh every 5 minutes
setInterval(function() {
    if (!$('.modal').hasClass('show')) {
        location.reload();
    }
}, 300000);

// Function to refresh the page
function refreshPage() {
    location.reload();
}

// Test function to verify modal is working
function testModal() {
    var testData = {
        id: 1,
        title: 'Test Request',
        description: 'This is a test request to verify the modal is working.',
        priority: 'medium',
        status: 'pending',
        created_at: new Date().toISOString(),
        assigned_to: 'Test Admin',
        room_number: '101',
        building_name: 'Building A'
    };
    
    $('#viewRequestModal').modal('show');
    $('#viewRequestModal').find('#requestDetails').html('<div class="alert alert-info">Test modal is working! This would normally show request details.</div>');
}

</script>

<?php include 'includes/footer.php'; ?> 