<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

// Fetch published announcements
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.username as created_by_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.is_published = 1 
        AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
        ORDER BY a.priority DESC, a.publish_date DESC, a.created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error fetching announcements: ' . $e->getMessage();
    $announcements = [];
}

$page_title = "Announcements";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-bullhorn me-2"></i>Announcements
                </h1>
            </div>

            <?php if (empty($announcements)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-bullhorn fa-3x mb-3"></i>
                    <h5>No Announcements Available</h5>
                    <p>There are no current announcements. Check back later for updates!</p>
                </div>
            <?php else: ?>
                <!-- Announcements Grid -->
                <div class="row">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                        </h6>
                                        <?php
                                        $priorityClass = '';
                                        $priorityIcon = '';
                                        switch ($announcement['priority']) {
                                            case 'high':
                                                $priorityClass = 'bg-danger';
                                                $priorityIcon = 'fas fa-exclamation-triangle';
                                                break;
                                            case 'medium':
                                                $priorityClass = 'bg-warning';
                                                $priorityIcon = 'fas fa-info-circle';
                                                break;
                                            case 'low':
                                                $priorityClass = 'bg-info';
                                                $priorityIcon = 'fas fa-info';
                                                break;
                                            default:
                                                $priorityClass = 'bg-secondary';
                                                $priorityIcon = 'fas fa-bullhorn';
                                        }
                                        ?>
                                        <span class="badge <?php echo $priorityClass; ?>">
                                            <i class="<?php echo $priorityIcon; ?> me-1"></i><?php echo ucfirst($announcement['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <p class="card-text">
                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="small text-muted">
                                        <div class="row">
                                            <div class="col-6">
                                                <i class="fas fa-calendar me-1"></i>
                                                <strong>Published:</strong><br>
                                                <?php echo formatDate($announcement['publish_date'], 'M d, Y'); ?>
                                            </div>
                                            <?php if ($announcement['expiry_date']): ?>
                                                <div class="col-6">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <strong>Expires:</strong><br>
                                                    <?php echo formatDate($announcement['expiry_date'], 'M d, Y'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <hr class="my-2">
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small>
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($announcement['created_by_name']); ?>
                                            </small>
                                            <small>
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo formatDate($announcement['created_at'], 'M d, h:i A'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($announcement['priority'] === 'high'): ?>
                                    <div class="card-footer bg-danger text-white">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>High Priority Announcement</strong> - Please read carefully!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Priority Legend -->
                <div class="card shadow mt-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle me-2"></i>Priority Legend
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <span class="badge bg-danger fs-6 mb-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>High Priority
                                </span>
                                <p class="small text-muted">Important announcements requiring immediate attention</p>
                            </div>
                            <div class="col-md-4">
                                <span class="badge bg-warning fs-6 mb-2">
                                    <i class="fas fa-info-circle me-1"></i>Medium Priority
                                </span>
                                <p class="small text-muted">General announcements and updates</p>
                            </div>
                            <div class="col-md-4">
                                <span class="badge bg-info fs-6 mb-2">
                                    <i class="fas fa-info me-1"></i>Low Priority
                                </span>
                                <p class="small text-muted">Informational announcements</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Add any additional JavaScript functionality here
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh announcements every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000); // 5 minutes
});
</script>
