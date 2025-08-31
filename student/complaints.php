<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$page_title = "Submit Complaint";
require_once 'includes/header.php';

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit_complaint') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $priority = sanitizeInput($_POST['priority']);
        $contact_preference = sanitizeInput($_POST['contact_preference']);
        
        // Validate inputs
        if (empty($title) || empty($description) || empty($priority)) {
            $error_message = "Please fill in all required fields.";
        } else {
            try {
                $pdo = getDBConnection();
                
                // Get student ID from session
                $student_id = $_SESSION['user_id'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO complaints (student_id, title, description, priority, status, contact_preference, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', ?, NOW())
                ");
                
                if ($stmt->execute([$student_id, $title, $description, $priority, $contact_preference])) {
                    logActivity($_SESSION['user_id'], "Submitted new complaint: $title");
                    $success_message = "Complaint submitted successfully! We will review and respond to your concern.";
                    
                    // Clear form data
                    $title = $description = $priority = $contact_preference = '';
                } else {
                    $error_message = "Failed to submit complaint. Please try again.";
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = "Database error occurred. Please try again.";
            }
        }
    }
}

// Get student's complaint history
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM complaints 
        WHERE student_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $complaints = $stmt->fetchAll();
    
    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
        FROM complaints 
        WHERE student_id = ?
    ");
    $stats_stmt->execute([$_SESSION['user_id']]);
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database error occurred.";
    $complaints = [];
    $stats = [];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-white">Submit Complaint</h1>
            
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
                <!-- Submit Complaint Form -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Submit New Complaint</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="submit_complaint">
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Complaint Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                                           placeholder="Brief description of your complaint" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Detailed Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="5" 
                                              placeholder="Please provide detailed information about your complaint..." required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority Level <span class="text-danger">*</span></label>
                                            <select class="form-select" id="priority" name="priority" required>
                                                <option value="">Select Priority</option>
                                                <option value="low" <?php echo (isset($priority) && $priority === 'low') ? 'selected' : ''; ?>>Low - General concern</option>
                                                <option value="medium" <?php echo (isset($priority) && $priority === 'medium') ? 'selected' : ''; ?>>Medium - Important issue</option>
                                                <option value="high" <?php echo (isset($priority) && $priority === 'high') ? 'selected' : ''; ?>>High - Urgent matter</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_preference" class="form-label">Preferred Contact Method</label>
                                            <select class="form-select" id="contact_preference" name="contact_preference">
                                                <option value="email" <?php echo (isset($contact_preference) && $contact_preference === 'email') ? 'selected' : ''; ?>>Email</option>
                                                <option value="mobile" <?php echo (isset($contact_preference) && $contact_preference === 'mobile') ? 'selected' : ''; ?>>Mobile</option>
                                                <option value="both" <?php echo (isset($contact_preference) && $contact_preference === 'both') ? 'selected' : ''; ?>>Both</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Guidelines for Submitting Complaints</h6>
                                    <ul class="mb-0">
                                        <li>Be specific and provide clear details about your concern</li>
                                        <li>Include relevant dates, times, and locations if applicable</li>
                                        <li>Use appropriate priority levels to help us address urgent matters first</li>
                                        <li>We aim to respond to all complaints within 24-48 hours</li>
                                        <li>For urgent matters, please contact the dormitory office directly</li>
                                    </ul>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Complaint
                                </button>
                            </form>
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
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Complaints</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Resolved</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['resolved'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Time Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Response Times</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Low Priority:</strong> 3-5 business days
                            </div>
                            <div class="mb-2">
                                <strong>Medium Priority:</strong> 1-3 business days
                            </div>
                            <div class="mb-2">
                                <strong>High Priority:</strong> 24 hours or less
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Response times may vary based on complexity and current workload.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Complaint History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Your Complaint History</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($complaints)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No complaints submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="complaintsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Submitted Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                        <tr>
                                            <td><?php echo $complaint['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($complaint['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo substr(htmlspecialchars($complaint['description']), 0, 100); ?>...</small>
                                            </td>
                                            <td>
                                                <?php
                                                $priority_class = '';
                                                switch ($complaint['priority']) {
                                                    case 'low':
                                                        $priority_class = 'success';
                                                        break;
                                                    case 'medium':
                                                        $priority_class = 'warning';
                                                        break;
                                                    case 'high':
                                                        $priority_class = 'danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $priority_class; ?>">
                                                    <?php echo ucfirst($complaint['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($complaint['status']) {
                                                    case 'pending':
                                                        $status_class = 'warning';
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'info';
                                                        break;
                                                    case 'resolved':
                                                        $status_class = 'success';
                                                        break;
                                                    case 'closed':
                                                        $status_class = 'secondary';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View Details
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

<!-- View Complaint Modal -->
<div class="modal fade" id="viewComplaintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complaint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="complaintDetails">
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
    $('#complaintsTable').DataTable({
        order: [[4, 'desc']], // Sort by submitted date descending
        pageLength: 10
    });
});

function viewComplaint(complaintId) {
    // Load complaint details via AJAX
    $.post('get_complaint_details.php', {complaint_id: complaintId}, function(response) {
        if (response.success) {
            $('#complaintDetails').html(response.data);
            $('#viewComplaintModal').modal('show');
        } else {
            alert('Error loading complaint details: ' + response.message);
        }
    }, 'json');
}
</script>

<?php require_once 'includes/footer.php'; ?>
