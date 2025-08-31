<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle announcement operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_announcement') {
            $title = sanitizeInput($_POST['title']);
            $content = sanitizeInput($_POST['content']);
            $priority = sanitizeInput($_POST['priority']);
            $publish_date = sanitizeInput($_POST['publish_date']);
            $expiry_date = !empty($_POST['expiry_date']) ? sanitizeInput($_POST['expiry_date']) : null;
            $is_published = isset($_POST['is_published']) ? 1 : 0;
            
            if (empty($title) || empty($content)) {
                $error = 'Title and content are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        INSERT INTO announcements (title, content, priority, publish_date, expiry_date, is_published, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$title, $content, $priority, $publish_date, $expiry_date, $is_published, $_SESSION['user_id']]);
                    
                    $message = 'Announcement created successfully!';
                    logActivity($_SESSION['user_id'], 'Announcement created: ' . $title);
                    
                } catch (PDOException $e) {
                    $error = 'Error creating announcement: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit_announcement') {
            $announcement_id = (int)$_POST['announcement_id'];
            $title = sanitizeInput($_POST['title']);
            $content = sanitizeInput($_POST['content']);
            $priority = sanitizeInput($_POST['priority']);
            $publish_date = sanitizeInput($_POST['publish_date']);
            $expiry_date = !empty($_POST['expiry_date']) ? sanitizeInput($_POST['expiry_date']) : null;
            $is_published = isset($_POST['is_published']) ? 1 : 0;
            
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    UPDATE announcements 
                    SET title = ?, content = ?, priority = ?, publish_date = ?, expiry_date = ?, is_published = ?, updated_at = NOW() 
                    WHERE announcement_id = ?
                ");
                $stmt->execute([$title, $content, $priority, $publish_date, $expiry_date, $is_published, $announcement_id]);
                
                $message = 'Announcement updated successfully!';
                logActivity($_SESSION['user_id'], 'Announcement updated: ' . $title);
                
            } catch (PDOException $e) {
                $error = 'Error updating announcement: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_announcement') {
            $announcement_id = (int)$_POST['announcement_id'];
            
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
                $stmt->execute([$announcement_id]);
                
                $message = 'Announcement deleted successfully!';
                logActivity($_SESSION['user_id'], 'Announcement deleted: ID ' . $announcement_id);
                
            } catch (PDOException $e) {
                $error = 'Error deleting announcement: ' . $e->getMessage();
            }
        } elseif ($action === 'toggle_publish') {
            $announcement_id = (int)$_POST['announcement_id'];
            $is_published = (int)$_POST['is_published'];
            
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("UPDATE announcements SET is_published = ?, updated_at = NOW() WHERE announcement_id = ?");
                $stmt->execute([$is_published, $announcement_id]);
                
                $status_text = $is_published ? 'published' : 'unpublished';
                $message = 'Announcement ' . $status_text . ' successfully!';
                logActivity($_SESSION['user_id'], 'Announcement ' . $status_text . ': ID ' . $announcement_id);
                
            } catch (PDOException $e) {
                $error = 'Error updating announcement status: ' . $e->getMessage();
            }
        }
    }
}

// Fetch announcements
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.username as created_by_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        ORDER BY a.created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for different statuses
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as draft
        FROM announcements
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error fetching announcements: ' . $e->getMessage();
    $announcements = [];
    $counts = ['total' => 0, 'published' => 0, 'draft' => 0];
}

$page_title = "Announcements Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-bullhorn me-2"></i>Announcements Management
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                    <i class="fas fa-plus me-2"></i>New Announcement
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

            <!-- Status Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Announcements
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $counts['total']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Published
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $counts['published']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Draft
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $counts['draft']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-edit fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>All Announcements
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-bullhorn fa-3x mb-3"></i>
                            <h5>No Announcements Yet</h5>
                            <p>Create your first announcement to keep students informed!</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                                <i class="fas fa-plus me-2"></i>Create First Announcement
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="announcementsTable">
                                <thead>
                                    <tr>
                                        <th>Title & Content</th>
                                        <th>Priority</th>
                                        <th>Publish Date</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($announcement['content']), 0, 100) . (strlen($announcement['content']) > 100 ? '...' : ''); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $priorityClass = '';
                                                switch ($announcement['priority']) {
                                                    case 'high':
                                                        $priorityClass = 'bg-danger';
                                                        break;
                                                    case 'medium':
                                                        $priorityClass = 'bg-warning';
                                                        break;
                                                    case 'low':
                                                        $priorityClass = 'bg-info';
                                                        break;
                                                    default:
                                                        $priorityClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $priorityClass; ?>"><?php echo ucfirst($announcement['priority']); ?></span>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div><strong>Publish:</strong> <?php echo formatDate($announcement['publish_date'], 'M d, Y'); ?></div>
                                                    <?php if ($announcement['expiry_date']): ?>
                                                        <div><strong>Expires:</strong> <?php echo formatDate($announcement['expiry_date'], 'M d, Y'); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($announcement['is_published']): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($announcement['created_by_name']); ?></small>
                                                <br><small class="text-muted"><?php echo formatDate($announcement['created_at'], 'M d, Y'); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical" role="group">
                                                    <button class="btn btn-sm btn-info mb-1" onclick="viewAnnouncement(<?php echo $announcement['announcement_id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-warning mb-1" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal"
                                                            data-announcement='<?php echo json_encode($announcement); ?>'>
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    
                                                    <?php if ($announcement['is_published']): ?>
                                                        <button class="btn btn-sm btn-secondary mb-1" onclick="togglePublish(<?php echo $announcement['announcement_id']; ?>, 0)">
                                                            <i class="fas fa-eye-slash"></i> Unpublish
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success mb-1" onclick="togglePublish(<?php echo $announcement['announcement_id']; ?>, 1)">
                                                            <i class="fas fa-eye"></i> Publish
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(<?php echo $announcement['announcement_id']; ?>, '<?php echo htmlspecialchars($announcement['title']); ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
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
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_announcement">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content *</label>
                        <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="publish_date" class="form-label">Publish Date *</label>
                                <input type="date" class="form-control" id="publish_date" name="publish_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                <small class="form-text text-muted">Leave empty for no expiry</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" checked>
                            <label class="form-check-label" for="is_published">
                                Publish immediately
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_announcement">
                    <input type="hidden" name="announcement_id" id="edit_announcement_id">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Content *</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="6" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <select class="form-control" id="edit_priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_publish_date" class="form-label">Publish Date *</label>
                                <input type="date" class="form-control" id="edit_publish_date" name="publish_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="edit_expiry_date" name="expiry_date">
                                <small class="form-text text-muted">Leave empty for no expiry</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_published" name="is_published">
                            <label class="form-check-label" for="edit_is_published">
                                Published
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Forms -->
<form id="deleteAnnouncementForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_announcement">
    <input type="hidden" name="announcement_id" id="delete_announcement_id">
</form>

<form id="togglePublishForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_publish">
    <input type="hidden" name="announcement_id" id="toggle_announcement_id">
    <input type="hidden" name="is_published" id="toggle_is_published">
</form>

<?php include 'includes/footer.php'; ?>

<script>
// Initialize DataTable
$(document).ready(function() {
    if (document.getElementById('announcementsTable')) {
        $('#announcementsTable').DataTable({
            order: [[4, 'desc']],
            pageLength: 15,
            responsive: true
        });
    }
});

// Handle Edit Announcement Modal
$('#editAnnouncementModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const announcement = button.data('announcement');
    
    $('#edit_announcement_id').val(announcement.announcement_id);
    $('#edit_title').val(announcement.title);
    $('#edit_content').val(announcement.content);
    $('#edit_priority').val(announcement.priority);
    $('#edit_publish_date').val(announcement.publish_date);
    $('#edit_expiry_date').val(announcement.expiry_date);
    $('#edit_is_published').prop('checked', announcement.is_published == 1);
});

// Delete Announcement
function deleteAnnouncement(announcementId, title) {
    if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
        $('#delete_announcement_id').val(announcementId);
        $('#deleteAnnouncementForm').submit();
    }
}

// Toggle Publish Status
function togglePublish(announcementId, isPublished) {
    const action = isPublished ? 'publish' : 'unpublish';
    if (confirm(`Are you sure you want to ${action} this announcement?`)) {
        $('#toggle_announcement_id').val(announcementId);
        $('#toggle_is_published').val(isPublished);
        $('#togglePublishForm').submit();
    }
}

// View Announcement
function viewAnnouncement(announcementId) {
    // Implement announcement view functionality
    alert('Announcement view functionality will be implemented in the next phase');
}
</script>
