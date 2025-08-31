<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$page_title = "Policies Management";
require_once 'includes/header.php';

// Handle policy operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_policy') {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $policy_type = sanitizeInput($_POST['policy_type']);
        $status = sanitizeInput($_POST['status']);
        
        // Handle file upload
        $file_path = null;
        if (isset($_FILES['policy_file']) && $_FILES['policy_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/policies/';
            $file_extension = strtolower(pathinfo($_FILES['policy_file']['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, ['pdf', 'doc', 'docx'])) {
                $filename = uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $filename;
                
                if (!move_uploaded_file($_FILES['policy_file']['tmp_name'], $upload_dir . $filename)) {
                    $error_message = "Failed to upload file.";
                }
            } else {
                $error_message = "Only PDF, DOC, and DOCX files are allowed.";
            }
        }
        
        if (!isset($error_message)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO policies (title, content, policy_type, file_path, status, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$title, $content, $policy_type, $file_path, $status, $_SESSION['user_id']])) {
                    logActivity($_SESSION['user_id'], "Added new policy: $title");
                    $success_message = "Policy added successfully!";
                } else {
                    $error_message = "Failed to add policy.";
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = "Database error occurred.";
            }
        }
    } elseif ($_POST['action'] === 'edit_policy') {
        $policy_id = sanitizeInput($_POST['policy_id']);
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $policy_type = sanitizeInput($_POST['policy_type']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE policies 
                SET title = ?, content = ?, policy_type = ?, status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$title, $content, $policy_type, $status, $policy_id])) {
                logActivity($_SESSION['user_id'], "Updated policy #$policy_id: $title");
                $success_message = "Policy updated successfully!";
            } else {
                $error_message = "Failed to update policy.";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = "Database error occurred.";
        }
    } elseif ($_POST['action'] === 'delete_policy') {
        $policy_id = sanitizeInput($_POST['policy_id']);
        
        try {
            $pdo = getDBConnection();
            
            // Get file path before deleting
            $file_stmt = $pdo->prepare("SELECT file_path FROM policies WHERE id = ?");
            $file_stmt->execute([$policy_id]);
            $policy_file = $file_stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM policies WHERE id = ?");
            
            if ($stmt->execute([$policy_id])) {
                // Delete associated file if exists
                if ($policy_file && $policy_file['file_path']) {
                    $file_path = '../uploads/policies/' . $policy_file['file_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                logActivity($_SESSION['user_id'], "Deleted policy #$policy_id");
                $success_message = "Policy deleted successfully!";
            } else {
                $error_message = "Failed to delete policy.";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = "Failed to delete policy.";
        }
    }
}

// Get policies with filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param]);
    }
    
    if (!empty($type_filter)) {
        $where_conditions[] = "p.policy_type = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "p.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get policies
    $sql = "
        SELECT 
            p.*,
            u.username as created_by_name
        FROM policies p
        LEFT JOIN users u ON p.created_by = u.id
        $where_clause
        ORDER BY p.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $policies = $stmt->fetchAll();
    
    // Get statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN policy_type = 'general' THEN 1 ELSE 0 END) as general,
            SUM(CASE WHEN policy_type = 'conduct' THEN 1 ELSE 0 END) as conduct,
            SUM(CASE WHEN policy_type = 'safety' THEN 1 ELSE 0 END) as safety
        FROM policies
    ";
    
    $stats_stmt = $pdo->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database error occurred.";
    $policies = [];
    $stats = [];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-white">Policies Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
                    <i class="fas fa-plus"></i> Add New Policy
                </button>
            </div>
            
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
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Policies</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-book fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Draft</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['draft'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-edit fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">General</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['general'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-home fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Conduct</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['conduct'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Safety</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['safety'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search policies..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="general" <?php echo $type_filter === 'general' ? 'selected' : ''; ?>>General</option>
                                <option value="conduct" <?php echo $type_filter === 'conduct' ? 'selected' : ''; ?>>Code of Conduct</option>
                                <option value="safety" <?php echo $type_filter === 'safety' ? 'selected' : ''; ?>>Safety & Security</option>
                                <option value="visitors" <?php echo $type_filter === 'visitors' ? 'selected' : ''; ?>>Visitor Policies</option>
                                <option value="maintenance" <?php echo $type_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="offenses" <?php echo $type_filter === 'offenses' ? 'selected' : ''; ?>>Offenses</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Policies Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Policies List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="policiesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>File</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($policies as $policy): ?>
                                    <tr>
                                        <td><?php echo $policy['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($policy['title']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo substr(htmlspecialchars($policy['content']), 0, 100); ?>...</small>
                                        </td>
                                        <td>
                                            <?php
                                            $type_class = '';
                                            switch ($policy['policy_type']) {
                                                case 'general':
                                                    $type_class = 'primary';
                                                    break;
                                                case 'conduct':
                                                    $type_class = 'info';
                                                    break;
                                                case 'safety':
                                                    $type_class = 'danger';
                                                    break;
                                                case 'visitors':
                                                    $type_class = 'success';
                                                    break;
                                                case 'maintenance':
                                                    $type_class = 'warning';
                                                    break;
                                                case 'offenses':
                                                    $type_class = 'dark';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $type_class; ?>">
                                                <?php echo ucfirst($policy['policy_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($policy['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    break;
                                                case 'draft':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'archived':
                                                    $status_class = 'secondary';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($policy['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($policy['file_path']): ?>
                                                <a href="../uploads/policies/<?php echo htmlspecialchars($policy['file_path']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" download>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($policy['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewPolicy(<?php echo $policy['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editPolicy(<?php echo $policy['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deletePolicy(<?php echo $policy['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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

<!-- Add Policy Modal -->
<div class="modal fade" id="addPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_policy">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Policy Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="policy_type" class="form-label">Policy Type</label>
                                <select class="form-select" name="policy_type" required>
                                    <option value="">Select Type</option>
                                    <option value="general">General Rules</option>
                                    <option value="conduct">Code of Conduct</option>
                                    <option value="safety">Safety & Security</option>
                                    <option value="visitors">Visitor Policies</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="offenses">Offenses & Penalties</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Policy Content</label>
                        <textarea class="form-control" name="content" rows="8" required placeholder="Enter the policy content..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="policy_file" class="form-label">Policy File (Optional)</label>
                        <input type="file" class="form-control" name="policy_file" accept=".pdf,.doc,.docx">
                        <div class="form-text">Upload PDF, DOC, or DOCX file. Max size: 10MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Policy Modal -->
<div class="modal fade" id="editPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_policy">
                    <input type="hidden" name="policy_id" id="editPolicyId">
                    
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Policy Title</label>
                        <input type="text" class="form-control" name="title" id="editTitle" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editPolicyType" class="form-label">Policy Type</label>
                                <select class="form-select" name="policy_type" id="editPolicyType" required>
                                    <option value="general">General Rules</option>
                                    <option value="conduct">Code of Conduct</option>
                                    <option value="safety">Safety & Security</option>
                                    <option value="visitors">Visitor Policies</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="offenses">Offenses & Penalties</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" name="status" id="editStatus" required>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editContent" class="form-label">Policy Content</label>
                        <textarea class="form-control" name="content" id="editContent" rows="8" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Policy Modal -->
<div class="modal fade" id="viewPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Policy Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="policyDetails">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this policy? This action cannot be undone and will also delete any associated files.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_policy">
                    <input type="hidden" name="policy_id" id="deletePolicyId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#policiesTable').DataTable({
        order: [[5, 'desc']], // Sort by created date descending
        pageLength: 25
    });
});

function viewPolicy(policyId) {
    // Load policy details via AJAX
    $.post('get_policy_details.php', {policy_id: policyId}, function(response) {
        if (response.success) {
            $('#policyDetails').html(response.data);
            $('#viewPolicyModal').modal('show');
        } else {
            alert('Error loading policy details: ' + response.message);
        }
    }, 'json');
}

function editPolicy(policyId) {
    // Load policy data for editing
    $.post('get_policy_details.php', {policy_id: policyId}, function(response) {
        if (response.success && response.edit_data) {
            const data = response.edit_data;
            $('#editPolicyId').val(data.id);
            $('#editTitle').val(data.title);
            $('#editPolicyType').val(data.policy_type);
            $('#editStatus').val(data.status);
            $('#editContent').val(data.content);
            $('#editPolicyModal').modal('show');
        } else {
            alert('Error loading policy data: ' + (response.message || 'Unknown error'));
        }
    }, 'json');
}

function deletePolicy(policyId) {
    $('#deletePolicyId').val(policyId);
    $('#deletePolicyModal').modal('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>
