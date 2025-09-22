<?php
require_once '../config/database.php';

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_policy':
                $title = $_POST['title'];
                $content = $_POST['content'];
                $offense_descriptions = $_POST['offense_descriptions'];
                
                $pdo = getConnection();
                $stmt = $pdo->prepare("INSERT INTO policies (title, content, offense_descriptions, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $offense_descriptions, $_SESSION['admin_id']]);
                
                $_SESSION['success'] = "Policy created successfully.";
                header("Location: policies_management.php");
                exit;
                break;
                
            case 'update_policy':
                $policy_id = $_POST['policy_id'];
                $title = $_POST['title'];
                $content = $_POST['content'];
                $offense_descriptions = $_POST['offense_descriptions'];
                
                $pdo = getConnection();
                $stmt = $pdo->prepare("UPDATE policies SET title = ?, content = ?, offense_descriptions = ? WHERE id = ?");
                $stmt->execute([$title, $content, $offense_descriptions, $policy_id]);
                
                $_SESSION['success'] = "Policy updated successfully.";
                header("Location: policies_management.php");
                exit;
                break;
                
            case 'delete_policy':
                $policy_id = $_POST['policy_id'];
                
                $pdo = getConnection();
                $stmt = $pdo->prepare("DELETE FROM policies WHERE id = ?");
                $stmt->execute([$policy_id]);
                
                $_SESSION['success'] = "Policy deleted successfully.";
                header("Location: policies_management.php");
                exit;
                break;
        }
    }
}

$page_title = 'Policies Management';
include 'includes/header.php';

$pdo = getConnection();

// Get all policies
$stmt = $pdo->query("SELECT p.*, 
    CONCAT(adm.username) as created_by_name
    FROM policies p
    LEFT JOIN admins adm ON p.created_by = adm.id
    ORDER BY p.created_at DESC");
$policies = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-alt"></i> Policies Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
        <i class="fas fa-plus"></i> Add New Policy
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count($policies); ?></h3>
            <p>Total Policies</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count(array_filter($policies, function($p) { return strtotime($p['created_at']) > strtotime('-30 days'); })); ?></h3>
            <p>This Month</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo count(array_filter($policies, function($p) { return strtotime($p['updated_at']) > strtotime('-7 days'); })); ?></h3>
            <p>Recently Updated</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo count(array_filter($policies, function($p) { return strtotime($p['created_at']) > strtotime('-7 days'); })); ?></h3>
            <p>This Week</p>
        </div>
    </div>
</div>

<!-- Policies Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Dormitory Policies</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="policiesTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content Preview</th>
                        <th>Offense Descriptions</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($policies as $policy): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($policy['title']); ?></strong>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($policy['content']); ?>">
                                    <?php echo htmlspecialchars(substr($policy['content'], 0, 100)) . (strlen($policy['content']) > 100 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($policy['offense_descriptions']); ?>">
                                    <?php echo htmlspecialchars(substr($policy['offense_descriptions'], 0, 100)) . (strlen($policy['offense_descriptions']) > 100 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($policy['created_by_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($policy['created_at'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($policy['updated_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPolicyModal" 
                                        data-policy='<?php echo json_encode($policy); ?>'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#editPolicyModal" 
                                        data-policy='<?php echo json_encode($policy); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deletePolicy(<?php echo $policy['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
            <form method="POST">
                <input type="hidden" name="action" value="add_policy">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Policy Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Policy Content</label>
                        <textarea name="content" class="form-control" rows="8" required placeholder="Enter the full policy content..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Offense Descriptions</label>
                        <textarea name="offense_descriptions" class="form-control" rows="4" placeholder="Describe what constitutes violations of this policy..."></textarea>
                        <small class="form-text text-muted">Optional: Describe specific offenses that violate this policy</small>
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

<!-- View Policy Modal -->
<div class="modal fade" id="viewPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Policy Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="policyDetails">
                <!-- Content will be loaded dynamically -->
            </div>
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
                <input type="hidden" name="action" value="update_policy">
                <input type="hidden" name="policy_id" id="editPolicyId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Policy Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Policy Content</label>
                        <textarea name="content" id="editContent" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Offense Descriptions</label>
                        <textarea name="offense_descriptions" id="editOffenseDescriptions" class="form-control" rows="4"></textarea>
                        <small class="form-text text-muted">Optional: Describe specific offenses that violate this policy</small>
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

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_policy">
    <input type="hidden" name="policy_id" id="deletePolicyId">
</form>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#policiesTable').DataTable({
        order: [[4, 'desc']],
        pageLength: 25
    });
    
    // Handle view policy modal
    $('#viewPolicyModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var policy = button.data('policy');
        var modal = $(this);
        
        var content = `
            <div class="row">
                <div class="col-12">
                    <h4>${policy.title}</h4>
                    <p class="text-muted">
                        <i class="fas fa-user"></i> ${policy.created_by_name} | 
                        <i class="fas fa-clock"></i> Created: ${new Date(policy.created_at).toLocaleDateString()} | 
                        Updated: ${new Date(policy.updated_at).toLocaleDateString()}
                    </p>
                    <hr>
                    <h6>Policy Content</h6>
                    <div style="white-space: pre-wrap;">${policy.content}</div>
                    ${policy.offense_descriptions ? `
                    <hr>
                    <h6>Offense Descriptions</h6>
                    <div style="white-space: pre-wrap;">${policy.offense_descriptions}</div>
                    ` : ''}
                </div>
            </div>
        `;
        
        modal.find('#policyDetails').html(content);
    });
    
    // Handle edit policy modal
    $('#editPolicyModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var policy = button.data('policy');
        
        $('#editPolicyId').val(policy.id);
        $('#editTitle').val(policy.title);
        $('#editContent').val(policy.content);
        $('#editOffenseDescriptions').val(policy.offense_descriptions);
    });
});

function deletePolicy(id) {
    if (confirm('Are you sure you want to delete this policy? This action cannot be undone.')) {
        $('#deletePolicyId').val(id);
        $('#deleteForm').submit();
    }
}
</script> 