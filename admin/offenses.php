<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle offense operations
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_offense') {
        $student_id = sanitizeInput($_POST['student_id']);
        $offense_type = sanitizeInput($_POST['offense_type']);
        $description = sanitizeInput($_POST['description']);
        $severity = sanitizeInput($_POST['severity']);
        $action_taken = sanitizeInput($_POST['action_taken']);
        $date_occurred = sanitizeInput($_POST['date_occurred']);
        $witnesses = sanitizeInput($_POST['witnesses']);
        $location = sanitizeInput($_POST['location']);
        
        if (empty($student_id) || empty($offense_type) || empty($description)) {
            $error = 'Student, offense type, and description are required.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO offenses (
                        student_id, offense_type, description, severity, action_taken,
                        date_occurred, witnesses, location, status, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())
                ");
                
                $stmt->execute([
                    $student_id, $offense_type, $description, $severity, $action_taken,
                    $date_occurred, $witnesses, $location, $_SESSION['user_id']
                ]);
                
                $message = 'Offense record created successfully!';
                logActivity($_SESSION['user_id'], "Created offense record for student ID: $student_id");
            } catch (PDOException $e) {
                $error = 'Error creating offense record: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit_offense') {
        $offense_id = (int)$_POST['offense_id'];
        $offense_type = sanitizeInput($_POST['offense_type']);
        $description = sanitizeInput($_POST['description']);
        $severity = sanitizeInput($_POST['severity']);
        $action_taken = sanitizeInput($_POST['action_taken']);
        $date_occurred = sanitizeInput($_POST['date_occurred']);
        $witnesses = sanitizeInput($_POST['witnesses']);
        $location = sanitizeInput($_POST['location']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE offenses 
                SET offense_type = ?, description = ?, severity = ?, action_taken = ?,
                    date_occurred = ?, witnesses = ?, location = ?, status = ?, updated_at = NOW()
                WHERE offense_id = ?
            ");
            
            $stmt->execute([
                $offense_type, $description, $severity, $action_taken,
                $date_occurred, $witnesses, $location, $status, $offense_id
            ]);
            
            $message = 'Offense record updated successfully!';
            logActivity($_SESSION['user_id'], "Updated offense record ID: $offense_id");
        } catch (PDOException $e) {
            $error = 'Error updating offense record: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_offense') {
        $offense_id = (int)$_POST['offense_id'];
        
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM offenses WHERE offense_id = ?");
            
            if ($stmt->execute([$offense_id])) {
                $message = 'Offense record deleted successfully.';
                logActivity($_SESSION['user_id'], "Deleted offense record ID: $offense_id");
            } else {
                $error = 'Error deleting offense record.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch offenses with filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$severity_filter = isset($_GET['severity']) ? sanitizeInput($_GET['severity']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(o.offense_type LIKE ? OR o.description LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if ($status_filter) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status_filter;
    }
    
    if ($severity_filter) {
        $where_conditions[] = "o.severity = ?";
        $params[] = $severity_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT o.*, s.first_name, s.last_name, s.student_id as student_id_number,
               u.username as created_by_name
        FROM offenses o
        JOIN students s ON o.student_id = s.user_id
        LEFT JOIN users u ON o.created_by = u.user_id
        $where_clause
        ORDER BY o.date_occurred DESC, o.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $offenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_offenses,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_offenses,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_offenses,
            COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
            COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
            COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity
        FROM offenses
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch students for dropdown
    $students_stmt = $pdo->query("
        SELECT user_id, first_name, last_name, student_id 
        FROM students 
        WHERE status = 'approved' 
        ORDER BY last_name, first_name
    ");
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $offenses = [];
    $stats = ['total_offenses' => 0, 'active_offenses' => 0, 'resolved_offenses' => 0, 'high_severity' => 0, 'medium_severity' => 0, 'low_severity' => 0];
    $students = [];
}

$page_title = "Offense Log Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-exclamation-triangle me-2"></i>Offense Log Management
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOffenseModal">
                    <i class="fas fa-plus me-2"></i>Add Offense Record
                </button>
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

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Offenses
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_offenses']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Active Offenses
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['active_offenses']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Resolved
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['resolved_offenses']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        High Severity
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['high_severity']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Medium Severity
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['medium_severity']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Low Severity
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['low_severity']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-info-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Offense type, description, or student name...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="severity" class="form-label">Severity</label>
                            <select class="form-select" id="severity" name="severity">
                                <option value="">All Severities</option>
                                <option value="high" <?php echo $severity_filter === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo $severity_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo $severity_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Offenses Table -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Offense Records
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="offensesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student</th>
                                    <th>Offense Type</th>
                                    <th>Severity</th>
                                    <th>Date Occurred</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($offenses)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No offense records found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($offenses as $offense): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($offense['first_name'] . ' ' . $offense['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($offense['student_id_number']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($offense['offense_type']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($offense['description'], 0, 100)) . (strlen($offense['description']) > 100 ? '...' : ''); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $severity_badges = [
                                                    'high' => 'bg-danger',
                                                    'medium' => 'bg-warning',
                                                    'low' => 'bg-success'
                                                ];
                                                $badge_class = $severity_badges[$offense['severity']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($offense['severity']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo formatDate($offense['date_occurred'], 'M d, Y'); ?>
                                                <br>
                                                <small class="text-muted"><?php echo formatDate($offense['created_at'], 'M d, Y h:i A'); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_badges = [
                                                    'active' => 'bg-warning',
                                                    'resolved' => 'bg-success'
                                                ];
                                                $badge_class = $status_badges[$offense['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($offense['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($offense['created_by_name']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            onclick="editOffense(<?php echo $offense['offense_id']; ?>)" 
                                                            title="Edit Record">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewOffenseDetails(<?php echo $offense['offense_id']; ?>)" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Delete this offense record? This action cannot be undone.')">
                                                        <input type="hidden" name="action" value="delete_offense">
                                                        <input type="hidden" name="offense_id" value="<?php echo $offense['offense_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                title="Delete Record">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Offense Modal -->
<div class="modal fade" id="addOffenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Offense Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_offense">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student *</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['user_id']; ?>">
                                            <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['student_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offense_type" class="form-label">Offense Type *</label>
                                <input type="text" class="form-control" id="offense_type" name="offense_type" 
                                       required placeholder="e.g., Noise violation, Property damage...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="severity" class="form-label">Severity Level *</label>
                                <select class="form-select" id="severity" name="severity" required>
                                    <option value="">Select Severity</option>
                                    <option value="low">Low - Minor violation, warning sufficient</option>
                                    <option value="medium">Medium - Moderate violation, requires action</option>
                                    <option value="high">High - Serious violation, immediate action required</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_occurred" class="form-label">Date Occurred *</label>
                                <input type="date" class="form-control" id="date_occurred" name="date_occurred" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Detailed Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  required placeholder="Provide a detailed description of the offense..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="action_taken" class="form-label">Action Taken</label>
                                <textarea class="form-control" id="action_taken" name="action_taken" rows="3" 
                                          placeholder="What action was taken or will be taken..."></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       placeholder="Where did the offense occur?">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="witnesses" class="form-label">Witnesses (Optional)</label>
                        <input type="text" class="form-control" id="witnesses" name="witnesses" 
                               placeholder="Names of any witnesses...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Offense Modal -->
<div class="modal fade" id="editOffenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Offense Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_offense">
                    <input type="hidden" name="offense_id" id="edit_offense_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_offense_type" class="form-label">Offense Type *</label>
                                <input type="text" class="form-control" id="edit_offense_type" name="offense_type" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_severity" class="form-label">Severity Level *</label>
                                <select class="form-select" id="edit_severity" name="severity" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_date_occurred" class="form-label">Date Occurred *</label>
                                <input type="date" class="form-control" id="edit_date_occurred" name="date_occurred" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Detailed Description *</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_action_taken" class="form-label">Action Taken</label>
                                <textarea class="form-control" id="edit_action_taken" name="action_taken" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="edit_location" name="location">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_witnesses" class="form-label">Witnesses</label>
                        <input type="text" class="form-control" id="edit_witnesses" name="witnesses">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Offense Details Modal -->
<div class="modal fade" id="offenseDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Offense Record Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="offenseDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editOffense(offenseId) {
    // Load offense data via AJAX
    fetch(`get_offense_details.php?offense_id=${offenseId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_offense_id').value = data.offense_id;
            document.getElementById('edit_offense_type').value = data.offense_type;
            document.getElementById('edit_severity').value = data.severity;
            document.getElementById('edit_date_occurred').value = data.date_occurred;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_action_taken').value = data.action_taken || '';
            document.getElementById('edit_location').value = data.location || '';
            document.getElementById('edit_witnesses').value = data.witnesses || '';
            
            new bootstrap.Modal(document.getElementById('editOffenseModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading offense details');
        });
}

function viewOffenseDetails(offenseId) {
    // Load offense details via AJAX
    fetch(`get_offense_details.php?offense_id=${offenseId}&format=html`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('offenseDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('offenseDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading offense details');
        });
}

// Initialize DataTable
$(document).ready(function() {
    $('#offensesTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc'], [5, 'desc']], // Sort by date_occurred, then created_at
        responsive: true,
        language: {
            search: "Search offenses:",
            lengthMenu: "Show _MENU_ offenses per page",
            info: "Showing _START_ to _END_ of _TOTAL_ offenses"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
