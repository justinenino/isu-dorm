<?php
$page_title = 'Dormitory Policies';
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
    <h2><i class="fas fa-file-alt"></i> Dormitory Policies</h2>
    <div>
        <span class="badge bg-primary"><?php echo count($policies); ?> Policies</span>
    </div>
</div>

<!-- Important Notice -->
<div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Important:</strong> All students must read and understand these policies. Violations may result in disciplinary action. Ignorance of these policies is not an excuse for violations.
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
            <h3><?php echo count(array_filter($policies, function($p) { return !empty($p['offense_descriptions']); })); ?></h3>
            <p>With Offense Details</p>
        </div>
    </div>
</div>

<!-- Policies List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Dormitory Policies</h5>
    </div>
    <div class="card-body">
        <?php if (empty($policies)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5>No policies available</h5>
                <p class="text-muted">Policies will be posted here by the dormitory administration.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($policies as $policy): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 policy-card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-alt text-primary"></i>
                                    <?php echo htmlspecialchars($policy['title']); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <?php 
                                    $preview = substr($policy['content'], 0, 150);
                                    echo htmlspecialchars($preview) . (strlen($policy['content']) > 150 ? '...' : '');
                                    ?>
                                </p>
                                <?php if (!empty($policy['offense_descriptions'])): ?>
                                    <div class="alert alert-warning py-2 mb-3">
                                        <small>
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>Offense Details Available</strong>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        Updated: <?php echo date('M j, Y', strtotime($policy['updated_at'])); ?>
                                    </small>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPolicyModal" 
                                            data-policy='<?php echo json_encode($policy); ?>'>
                                        Read Full Policy
                                    </button>
                                </div>
                            </div>
                            <?php if (strtotime($policy['updated_at']) > strtotime('-7 days')): ?>
                                <div class="card-footer">
                                    <span class="badge bg-success">Recently Updated</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Policy Modal -->
<div class="modal fade" id="viewPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
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

<style>
.policy-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e9ecef;
}

.policy-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.policy-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.policy-card .card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.policy-content {
    line-height: 1.8;
    font-size: 1rem;
}

.offense-details {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 5px;
    padding: 15px;
    margin-top: 15px;
}
</style>

<script>
$(document).ready(function() {
    // Handle view policy modal
    $('#viewPolicyModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var policy = button.data('policy');
        var modal = $(this);
        
        var isRecent = new Date(policy.updated_at) > new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
        
        var content = `
            <div class="row">
                <div class="col-12">
                    <h4>${policy.title}</h4>
                    ${isRecent ? '<span class="badge bg-success mb-2">Recently Updated</span>' : ''}
                    <p class="text-muted">
                        <i class="fas fa-user"></i> ${policy.created_by_name} | 
                        <i class="fas fa-clock"></i> Created: ${new Date(policy.created_at).toLocaleDateString()} | 
                        Updated: ${new Date(policy.updated_at).toLocaleDateString()}
                    </p>
                    <hr>
                    <div class="policy-content">
                        <h6>Policy Content:</h6>
                        <div style="white-space: pre-wrap;">${policy.content}</div>
                    </div>
                    ${policy.offense_descriptions ? `
                    <div class="offense-details">
                        <h6><i class="fas fa-exclamation-triangle text-warning"></i> Offense Descriptions:</h6>
                        <div style="white-space: pre-wrap;">${policy.offense_descriptions}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        modal.find('#policyDetails').html(content);
    });
});
</script>

<?php include 'includes/footer.php'; ?> 