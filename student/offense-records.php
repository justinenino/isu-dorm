<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    // Fetch student's offense records
    $stmt = $pdo->prepare("
        SELECT o.*, u.username as created_by_name
        FROM offenses o
        LEFT JOIN users u ON o.created_by = u.user_id
        WHERE o.student_id = ?
        ORDER BY o.date_occurred DESC, o.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $offenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_offenses,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_offenses,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_offenses,
            COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
            COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
            COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity
        FROM offenses
        WHERE student_id = ?
    ");
    
    $stats_stmt->execute([$_SESSION['user_id']]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $offenses = [];
    $stats = ['total_offenses' => 0, 'active_offenses' => 0, 'resolved_offenses' => 0, 'high_severity' => 0, 'medium_severity' => 0, 'low_severity' => 0];
}

$page_title = "My Offense Records";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-exclamation-triangle me-2"></i>My Offense Records
                </h1>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

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
                                        Total Records
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_offenses']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-list fa-2x text-gray-300"></i>
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
                                        Active Records
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

            <!-- Information Panel -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-left-info shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Important Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">Understanding Your Records:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i><strong>Active:</strong> Offense is being reviewed or action is pending</li>
                                        <li><i class="fas fa-check text-success me-2"></i><strong>Resolved:</strong> Offense has been addressed and closed</li>
                                        <li><i class="fas fa-check text-success me-2"></i><strong>Severity Levels:</strong> Help determine appropriate actions</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary">What to Do:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-info text-info me-2"></i>Review all details carefully</li>
                                        <li><i class="fas fa-info text-info me-2"></i>Contact admin if you have questions</li>
                                        <li><i class="fas fa-info text-info me-2"></i>Follow any instructions provided</li>
                                        <li><i class="fas fa-info text-info me-2"></i>Learn from past incidents</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                    <th>Offense Type</th>
                                    <th>Severity</th>
                                    <th>Date Occurred</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($offenses)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h5 class="text-success">No Offense Records</h5>
                                            <p>Great job! You have a clean record.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($offenses as $offense): ?>
                                        <tr>
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
                                                <small class="text-muted">Recorded: <?php echo formatDate($offense['created_at'], 'M d, Y h:i A'); ?></small>
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
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="viewOffenseDetails(<?php echo $offense['offense_id']; ?>)" 
                                                        title="View Full Details">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </button>
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
function viewOffenseDetails(offenseId) {
    // Load offense details via AJAX
    fetch(`../admin/get_offense_details.php?offense_id=${offenseId}&format=html`)
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
        order: [[2, 'desc']], // Sort by date_occurred
        responsive: true,
        language: {
            search: "Search records:",
            lengthMenu: "Show _MENU_ records per page",
            info: "Showing _START_ to _END_ of _TOTAL_ records"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
