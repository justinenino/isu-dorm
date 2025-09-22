<?php
$page_title = 'Offense Records';
include 'includes/header.php';

// Validate session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Get student information
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT s.*, 
        CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) as full_name
        FROM students s WHERE s.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        throw new Exception("Student information not found.");
    }
    
    // Get student's offense records
    $stmt = $pdo->prepare("SELECT * FROM offenses WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $offenses = $stmt->fetchAll();
    
    // Calculate statistics
    $total_offenses = count($offenses);
    $pending_offenses = 0;
    $resolved_offenses = 0;
    $escalated_offenses = 0;
    
    foreach ($offenses as $offense) {
        switch ($offense['status']) {
            case 'pending':
                $pending_offenses++;
                break;
            case 'resolved':
                $resolved_offenses++;
                break;
            case 'escalated':
                $escalated_offenses++;
                break;
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in offense_records.php: " . $e->getMessage());
    $student = null;
    $offenses = [];
    $total_offenses = 0;
    $pending_offenses = 0;
    $resolved_offenses = 0;
    $escalated_offenses = 0;
} catch (Exception $e) {
    error_log("Error in offense_records.php: " . $e->getMessage());
    $student = null;
    $offenses = [];
    $total_offenses = 0;
    $pending_offenses = 0;
    $resolved_offenses = 0;
    $escalated_offenses = 0;
}
?>

<!-- Error Message Display -->
<?php if (!$student): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Error:</strong> Unable to load student information. Please contact the administration if this problem persists.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> My Offense Records</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6>Student Information</h6>
                        <?php if ($student): ?>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                            <p><strong>School ID:</strong> <?php echo htmlspecialchars($student['school_id']); ?></p>
                            <p><strong>LRN:</strong> <?php echo htmlspecialchars($student['learner_reference_number']); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Student information not available</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-info-circle"></i> Important</h6>
                            <p class="mb-0">This page shows all recorded violations and their current status.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $total_offenses; ?></h3>
                    <p class="mb-0">Total Offenses</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $pending_offenses; ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $resolved_offenses; ?></h3>
                    <p class="mb-0">Resolved</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo $escalated_offenses; ?></h3>
                    <p class="mb-0">Escalated</p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Offense Records -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Offense History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($offenses)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5 class="text-success">Clean Record</h5>
                        <p class="text-muted">You have no recorded offenses. Keep up the good behavior!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar"></i> Date Reported</th>
                                    <th><i class="fas fa-exclamation"></i> Offense Type</th>
                                    <th><i class="fas fa-info-circle"></i> Description</th>
                                    <th><i class="fas fa-level-up-alt"></i> Severity</th>
                                    <th><i class="fas fa-tasks"></i> Status</th>
                                    <th><i class="fas fa-comment"></i> Action Taken</th>
                                    <th><i class="fas fa-user"></i> Reported By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($offenses as $offense): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            if ($offense['created_at']) {
                                                echo date('M j, Y g:i A', strtotime($offense['created_at']));
                                            } else {
                                                echo '<span class="text-muted">Date not available</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($offense['offense_type'] ?? 'Unknown'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($offense['description'] ?? 'No description available'); ?></td>
                                        <td>
                                            <?php
                                            $severity_class = 'bg-secondary';
                                            switch ($offense['severity']) {
                                                case 'minor':
                                                    $severity_class = 'bg-warning';
                                                    break;
                                                case 'major':
                                                    $severity_class = 'bg-danger';
                                                    break;
                                                case 'severe':
                                                    $severity_class = 'bg-dark';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $severity_class; ?>">
                                                <?php echo ucfirst($offense['severity'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'bg-secondary';
                                            switch ($offense['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'resolved':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'escalated':
                                                    $status_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($offense['status'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($offense['action_taken'])): ?>
                                                <?php echo htmlspecialchars($offense['action_taken']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No action taken yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($offense['reported_by'] ?? 'Unknown'); ?></td>
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

<!-- Information Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Understanding Your Records</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle text-info"></i> Severity Levels</h6>
                        <ul>
                            <li><span class="badge bg-warning">Minor</span> - First-time violations, warnings</li>
                            <li><span class="badge bg-danger">Major</span> - Repeated violations, serious misconduct</li>
                            <li><span class="badge bg-dark">Severe</span> - Severe violations, possible disciplinary action</li>
                        </ul>
                        
                        <h6><i class="fas fa-clock text-warning"></i> Status Meanings</h6>
                        <ul>
                            <li><span class="badge bg-warning">Pending</span> - Under review by administration</li>
                            <li><span class="badge bg-success">Resolved</span> - Issue has been addressed</li>
                            <li><span class="badge bg-danger">Escalated</span> - Sent to higher authorities</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-exclamation-triangle text-warning"></i> Important Notes</h6>
                        <ul>
                            <li>All offenses are recorded for administrative purposes</li>
                            <li>Multiple offenses may result in stricter penalties</li>
                            <li>Contact admin if you believe there's an error</li>
                            <li>Maintain good behavior to avoid future violations</li>
                        </ul>
                        
                        <h6><i class="fas fa-headset text-primary"></i> Need Help?</h6>
                        <p>If you have questions about your offense records or need to dispute a violation, please contact the dormitory administration.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 