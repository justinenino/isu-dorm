<?php
require_once '../config/database.php';

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo = getConnection();
            
            switch ($_POST['action']) {
                case 'add_announcement':
                    $title = trim($_POST['title']);
                    $content = trim($_POST['content']);
                    $status = $_POST['status'];
                    $category = $_POST['category'] ?? 'general';
                    $priority = $_POST['priority'] ?? 'medium';
                    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
                    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
                    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
                    
                    // Validate inputs
                    if (empty($title) || empty($content)) {
                        throw new Exception("Title and content are required.");
                    }
                    
                    if (!in_array($status, ['draft', 'published'])) {
                        throw new Exception("Invalid status value.");
                    }
                    
                    $admin_id = $_SESSION['user_id'] ?? null;
                    if (!$admin_id) {
                        throw new Exception("Admin session not found.");
                    }
                    
                    // Check if new columns exist
                    $stmt = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'category'");
                    $has_new_columns = $stmt->fetch() !== false;
                    
                    if ($has_new_columns) {
                        // Set published_at if status is published and no specific date given
                        if ($status == 'published' && !$published_at) {
                            $published_at = date('Y-m-d H:i:s');
                        }
                        
                        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, status, category, priority, published_at, expires_at, is_pinned, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $content, $status, $category, $priority, $published_at, $expires_at, $is_pinned, $admin_id]);
                    } else {
                        // Use old schema
                        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, status, created_by) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$title, $content, $status, $admin_id]);
                    }
                    
                    $_SESSION['success'] = "Announcement created successfully.";
                    header("Location: announcements.php");
                    exit;
                    break;
                    
                case 'update_announcement':
                    $announcement_id = (int)$_POST['announcement_id'];
                    $title = trim($_POST['title']);
                    $content = trim($_POST['content']);
                    $status = $_POST['status'];
                    $category = $_POST['category'];
                    $priority = $_POST['priority'];
                    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
                    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
                    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
                    
                    // Validate inputs
                    if (empty($title) || empty($content) || $announcement_id <= 0) {
                        throw new Exception("Invalid input data.");
                    }
                    
                    if (!in_array($status, ['draft', 'published'])) {
                        throw new Exception("Invalid status value.");
                    }
                    
                    // Set published_at if status is published and no specific date given
                    if ($status == 'published' && !$published_at) {
                        $published_at = date('Y-m-d H:i:s');
                    }
                    
                    $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, status = ?, category = ?, priority = ?, published_at = ?, expires_at = ?, is_pinned = ? WHERE id = ?");
                    $stmt->execute([$title, $content, $status, $category, $priority, $published_at, $expires_at, $is_pinned, $announcement_id]);
                    
                    $_SESSION['success'] = "Announcement updated successfully.";
                    header("Location: announcements.php");
                    exit;
                    break;
                    
                case 'delete_announcement':
                    $announcement_id = (int)$_POST['announcement_id'];
                    
                    if ($announcement_id <= 0) {
                        throw new Exception("Invalid announcement ID.");
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                    $stmt->execute([$announcement_id]);
                    
                    $_SESSION['success'] = "Announcement deleted successfully.";
                    header("Location: announcements.php");
                    exit;
                    break;
                    
                case 'toggle_pin':
                    $announcement_id = (int)$_POST['announcement_id'];
                    $is_pinned = (int)$_POST['is_pinned'];
                    
                    $stmt = $pdo->prepare("UPDATE announcements SET is_pinned = ? WHERE id = ?");
                    $stmt->execute([$is_pinned, $announcement_id]);
                    
                    $_SESSION['success'] = "Announcement pin status updated.";
                    header("Location: announcements.php");
                    exit;
                    break;
                    
                case 'archive_announcement':
                    $announcement_id = (int)$_POST['announcement_id'];
                    
                    $stmt = $pdo->prepare("UPDATE announcements SET is_archived = 1 WHERE id = ?");
                    $stmt->execute([$announcement_id]);
                    
                    $_SESSION['success'] = "Announcement archived successfully.";
                    header("Location: announcements.php");
                    exit;
                    break;
                    
                case 'unarchive_announcement':
                    $announcement_id = (int)$_POST['announcement_id'];
                    
                    $stmt = $pdo->prepare("UPDATE announcements SET is_archived = 0 WHERE id = ?");
                    $stmt->execute([$announcement_id]);
                    
                    $_SESSION['success'] = "Announcement unarchived successfully.";
                    header("Location: announcements.php");
                    exit;
                    break;
                    
                case 'add_comment':
                    $announcement_id = (int)$_POST['announcement_id'];
                    $comment = trim($_POST['comment']);
                    
                    if (empty($comment)) {
                        throw new Exception("Comment cannot be empty.");
                    }
                    
                    $admin_id = $_SESSION['user_id'] ?? null;
                    if (!$admin_id) {
                        throw new Exception("Admin session not found.");
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO announcement_comments (announcement_id, admin_id, comment) VALUES (?, ?, ?)");
                    $stmt->execute([$announcement_id, $admin_id, $comment]);
                    
                    echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
                    exit;
                    break;
                    
                case 'get_comments':
                    $announcement_id = (int)$_POST['announcement_id'];
                    
                    $stmt = $pdo->prepare("
                        SELECT ac.*, 
                               s.first_name, s.last_name, s.school_id as student_number,
                               a.username as admin_name,
                               (SELECT COUNT(*) FROM announcement_comment_likes acl WHERE acl.comment_id = ac.id) as like_count
                        FROM announcement_comments ac
                        LEFT JOIN students s ON ac.student_id = s.id
                        LEFT JOIN admins a ON ac.admin_id = a.id
                        WHERE ac.announcement_id = ? AND ac.is_deleted = 0
                        ORDER BY ac.created_at ASC
                    ");
                    $stmt->execute([$announcement_id]);
                    $comments = $stmt->fetchAll();
                    
                    echo json_encode(['success' => true, 'comments' => $comments]);
                    exit;
                    break;
                    
                case 'delete_comment':
                    $comment_id = (int)$_POST['comment_id'];
                    $admin_id = $_SESSION['user_id'] ?? null;
                    
                    if (!$admin_id) {
                        throw new Exception("Admin session not found.");
                    }
                    
                    // Soft delete the comment
                    $stmt = $pdo->prepare("UPDATE announcement_comments SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$comment_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
                    exit;
                    break;
            }
        } catch (Exception $e) {
            // Check if this is an AJAX request (comment-related actions)
            if (in_array($_POST['action'], ['add_comment', 'get_comments', 'delete_comment'])) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            } else {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                header("Location: announcements.php");
                exit;
            }
        }
    }
}

$page_title = 'Announcements Management';
include 'includes/header.php';

$pdo = getConnection();

// Get all announcements with enhanced data
try {
    // First check if new columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'category'");
    $has_new_columns = $stmt->fetch() !== false;
    
    // Check if analytics tables exist
    $check_views = $pdo->query("SHOW TABLES LIKE 'announcement_views'");
    $check_likes = $pdo->query("SHOW TABLES LIKE 'announcement_likes'");
    $has_analytics = $check_views->rowCount() > 0 && $check_likes->rowCount() > 0;
    
    if ($has_new_columns && $has_analytics) {
        // Use enhanced query with analytics
        $stmt = $pdo->query("SELECT a.*, 
            adm.username as created_by_name,
            CASE 
                WHEN a.is_archived = 1 THEN 'archived'
                WHEN a.expires_at IS NOT NULL AND a.expires_at < NOW() THEN 'expired'
                WHEN a.status = 'published' THEN 'published'
                ELSE 'draft'
            END as display_status,
            COALESCE(a.view_count, 0) as view_count,
            COALESCE(a.like_count, 0) as like_count,
            (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.id AND ac.is_deleted = 0) as comment_count,
            (SELECT COUNT(DISTINCT av.student_id) FROM announcement_views av WHERE av.announcement_id = a.id) as unique_viewers,
            (SELECT COUNT(DISTINCT al.student_id) FROM announcement_likes al WHERE al.announcement_id = a.id) as unique_likers
            FROM announcements a
            LEFT JOIN admins adm ON a.created_by = adm.id
            ORDER BY a.is_pinned DESC, a.published_at DESC, a.created_at DESC");
    } elseif ($has_new_columns) {
        // Use enhanced query without analytics
        $stmt = $pdo->query("SELECT a.*, 
            adm.username as created_by_name,
            CASE 
                WHEN a.is_archived = 1 THEN 'archived'
                WHEN a.expires_at IS NOT NULL AND a.expires_at < NOW() THEN 'expired'
                WHEN a.status = 'published' THEN 'published'
                ELSE 'draft'
            END as display_status,
            COALESCE(a.view_count, 0) as view_count,
            COALESCE(a.like_count, 0) as like_count,
            (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.id AND ac.is_deleted = 0) as comment_count,
            0 as unique_viewers,
            0 as unique_likers
            FROM announcements a
            LEFT JOIN admins adm ON a.created_by = adm.id
            ORDER BY a.is_pinned DESC, a.published_at DESC, a.created_at DESC");
    } else {
        // Use basic query for old schema
        $stmt = $pdo->query("SELECT a.*, 
            adm.username as created_by_name,
            CASE 
                WHEN a.status = 'published' THEN 'published'
                ELSE 'draft'
            END as display_status,
            'general' as category,
            'medium' as priority,
            NULL as published_at,
            NULL as expires_at,
            0 as is_pinned,
            0 as is_archived,
            0 as view_count,
            0 as like_count,
            (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.id AND ac.is_deleted = 0) as comment_count,
            0 as unique_viewers,
            0 as unique_likers
            FROM announcements a
            LEFT JOIN admins adm ON a.created_by = adm.id
            ORDER BY a.created_at DESC");
    }
    
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $announcements = [];
    error_log("Database error in announcements.php: " . $e->getMessage());
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bullhorn"></i> Announcements Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                <i class="fas fa-plus"></i> Create Announcement
            </button>
        </div>

        <?php if (!$has_analytics): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i>
                <strong>Analytics Available:</strong> To track announcement views and likes, please run the analytics update script. 
                <a href="../update_announcements_analytics.php" class="alert-link" target="_blank">Run Analytics Update</a>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <h3><?php echo count(array_filter($announcements, function($a) { return $a['display_status'] == 'published'; })); ?></h3>
                    <p>Published</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                    <h3><?php echo count(array_filter($announcements, function($a) { return $a['display_status'] == 'draft'; })); ?></h3>
                    <p>Drafts</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);">
                    <h3><?php echo count(array_filter($announcements, function($a) { return $a['display_status'] == 'expired'; })); ?></h3>
                    <p>Expired</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    <h3><?php echo count(array_filter($announcements, function($a) { return $a['display_status'] == 'archived'; })); ?></h3>
                    <p>Archived</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
                    <h3><?php echo count(array_filter($announcements, function($a) { return $a['is_pinned'] == 1; })); ?></h3>
                    <p>Pinned</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3><?php echo count(array_filter($announcements, function($a) { return strtotime($a['created_at']) > strtotime('-7 days'); })); ?></h3>
                    <p>This Week</p>
                </div>
            </div>
        </div>

        <?php if ($has_analytics): ?>
        <!-- Analytics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
                    <h3><?php echo array_sum(array_column($announcements, 'view_count')); ?></h3>
                    <p>Total Views</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #e83e8c 0%, #fd7e14 100%);">
                    <h3><?php echo array_sum(array_column($announcements, 'like_count')); ?></h3>
                    <p>Total Likes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #6f42c1 0%, #20c997 100%);">
                    <h3><?php echo array_sum(array_column($announcements, 'unique_viewers')); ?></h3>
                    <p>Unique Viewers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);">
                    <h3><?php 
                    $total_views = array_sum(array_column($announcements, 'view_count'));
                    $total_likes = array_sum(array_column($announcements, 'like_count'));
                    echo $total_views > 0 ? round(($total_likes / $total_views) * 100, 1) : 0;
                    ?>%</h3>
                    <p>Engagement Rate</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Advanced Filter & Search Controls -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter"></i> Advanced Filters & Search</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="expired">Expired</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                            <option value="general">General</option>
                            <option value="urgent">Urgent</option>
                            <option value="events">Events</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="academic">Academic</option>
                            <option value="sports">Sports</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Priority</label>
                        <select id="priorityFilter" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" id="dateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" id="dateToFilter" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Actions</label>
                        <button id="resetFilters" class="btn reset-btn w-100">Reset Filters</button>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-8">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by title, content, or author...">
                            <button class="btn btn-outline-primary" type="button" id="searchBtn">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quick Actions</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-success" id="exportBtn">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <button type="button" class="btn btn-outline-info" id="refreshBtn">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Announcements (<?php echo count($announcements); ?> total)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="announcementsTable">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 60px;">Pin</th>
                                <th>Title</th>
                                <th style="width: 100px;">Category</th>
                                <th style="width: 80px;">Priority</th>
                                <th style="width: 80px;">Status</th>
                                <th style="width: 120px;">Published</th>
                                <th style="width: 120px;">Expires</th>
                                <th style="width: 60px;">Views</th>
                                <th style="width: 60px;">Likes</th>
                                <th style="width: 80px;">Comments</th>
                                <th style="width: 120px;">Created By</th>
                                <th class="action-column" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">
                                        <i class="fas fa-bullhorn fa-3x mb-3"></i><br>
                                        No announcements found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <tr class="<?php echo $announcement['is_pinned'] ? 'table-warning' : ''; ?>">
                                        <td class="text-center">
                                            <?php if ($announcement['is_pinned']): ?>
                                                <i class="fas fa-thumbtack text-warning" title="Pinned"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                            <?php if ($announcement['is_pinned']): ?>
                                                <span class="badge bg-warning ms-1">PINNED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $category_class = '';
                                            switch ($announcement['category']) {
                                                case 'urgent': $category_class = 'badge bg-danger'; break;
                                                case 'events': $category_class = 'badge bg-info'; break;
                                                case 'maintenance': $category_class = 'badge bg-warning'; break;
                                                case 'academic': $category_class = 'badge bg-primary'; break;
                                                case 'sports': $category_class = 'badge bg-success'; break;
                                                default: $category_class = 'badge bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="<?php echo $category_class; ?>"><?php echo ucfirst($announcement['category']); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $priority_class = '';
                                            switch ($announcement['priority']) {
                                                case 'low': $priority_class = 'badge bg-info'; break;
                                                case 'medium': $priority_class = 'badge bg-warning'; break;
                                                case 'high': $priority_class = 'badge bg-danger'; break;
                                                case 'urgent': $priority_class = 'badge bg-dark'; break;
                                            }
                                            ?>
                                            <span class="<?php echo $priority_class; ?>"><?php echo ucfirst($announcement['priority']); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($announcement['display_status']) {
                                                case 'published': $status_class = 'badge bg-success'; break;
                                                case 'draft': $status_class = 'badge bg-warning'; break;
                                                case 'expired': $status_class = 'badge bg-danger'; break;
                                                case 'archived': $status_class = 'badge bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst($announcement['display_status']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($announcement['published_at']): ?>
                                                <?php echo date('M j, Y g:i A', strtotime($announcement['published_at'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($announcement['expires_at']): ?>
                                                <?php echo date('M j, Y g:i A', strtotime($announcement['expires_at'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $announcement['view_count'] ?? 0; ?></td>
                                        <td><?php echo $announcement['like_count'] ?? 0; ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $announcement['comment_count'] ?? 0; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($announcement['created_by_name']); ?></td>
                                        <td class="action-column">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                        id="dropdownMenuButton<?php echo $announcement['id']; ?>" 
                                                        data-bs-toggle="dropdown" 
                                                        data-bs-auto-close="true"
                                                        aria-expanded="false" 
                                                        title="Actions">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" 
                                                    aria-labelledby="dropdownMenuButton<?php echo $announcement['id']; ?>">
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" 
                                                           data-bs-target="#viewAnnouncementModal" 
                                                           data-announcement='<?php echo htmlspecialchars(json_encode($announcement)); ?>'>
                                                            <i class="fas fa-eye me-2"></i>View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" 
                                                           data-bs-target="#commentsModal" 
                                                           data-announcement='<?php echo htmlspecialchars(json_encode($announcement)); ?>'>
                                                            <i class="fas fa-comments me-2"></i>Comments (<?php echo $announcement['comment_count'] ?? 0; ?>)
                                                        </a>
                                                    </li>
                                                    <?php if ($has_analytics): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" 
                                                               data-bs-toggle="modal" 
                                                               data-bs-target="#analyticsModal" 
                                                               data-announcement='<?php echo htmlspecialchars(json_encode($announcement)); ?>'>
                                                                <i class="fas fa-chart-bar me-2"></i>Analytics
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" 
                                                           data-bs-target="#editAnnouncementModal" 
                                                           data-announcement='<?php echo htmlspecialchars(json_encode($announcement)); ?>'>
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           onclick="togglePin(<?php echo $announcement['id']; ?>, <?php echo $announcement['is_pinned'] ? 0 : 1; ?>); return false;">
                                                            <i class="fas fa-thumbtack me-2"></i><?php echo $announcement['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                                        </a>
                                                    </li>
                                                    <?php if ($announcement['display_status'] != 'archived'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" 
                                                               onclick="archiveAnnouncement(<?php echo $announcement['id']; ?>); return false;">
                                                                <i class="fas fa-archive me-2"></i>Archive
                                                            </a>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" 
                                                               onclick="unarchiveAnnouncement(<?php echo $announcement['id']; ?>); return false;">
                                                                <i class="fas fa-box-open me-2"></i>Unarchive
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>); return false;">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </a>
                                                    </li>
                                                </ul>
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

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addAnnouncementForm">
                <input type="hidden" name="action" value="add_announcement">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="general">General</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="events">Events</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="academic">Academic</option>
                                    <option value="sports">Sports</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Publish Date & Time</label>
                                <input type="datetime-local" name="published_at" class="form-control">
                                <small class="form-text text-muted">Leave empty to publish immediately</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date & Time</label>
                                <input type="datetime-local" name="expires_at" class="form-control">
                                <small class="form-text text-muted">Leave empty for no expiry</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned">
                            <label class="form-check-label" for="is_pinned">
                                Pin this announcement to the top
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="10" required placeholder="Enter announcement content..."></textarea>
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

<!-- View Announcement Modal -->
<div class="modal fade" id="viewAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Announcement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="announcementDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editAnnouncementForm">
                <input type="hidden" name="action" value="update_announcement">
                <input type="hidden" name="announcement_id" id="editAnnouncementId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="editTitle" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="editStatus" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" id="editCategory" class="form-select" required>
                                    <option value="general">General</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="events">Events</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="academic">Academic</option>
                                    <option value="sports">Sports</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" id="editPriority" class="form-select" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Publish Date & Time</label>
                                <input type="datetime-local" name="published_at" id="editPublishedAt" class="form-control">
                                <small class="form-text text-muted">Leave empty to publish immediately</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date & Time</label>
                                <input type="datetime-local" name="expires_at" id="editExpiresAt" class="form-control">
                                <small class="form-text text-muted">Leave empty for no expiry</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_pinned" id="editIsPinned">
                            <label class="form-check-label" for="editIsPinned">
                                Pin this announcement to the top
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="editContent" class="form-control" rows="10" required></textarea>
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

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_announcement">
    <input type="hidden" name="announcement_id" id="deleteAnnouncementId">
</form>

<!-- Toggle Pin Form -->
<form id="togglePinForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_pin">
    <input type="hidden" name="announcement_id" id="togglePinAnnouncementId">
    <input type="hidden" name="is_pinned" id="togglePinValue">
</form>

<!-- Archive Form -->
<form id="archiveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="archive_announcement">
    <input type="hidden" name="announcement_id" id="archiveAnnouncementId">
</form>

<!-- Unarchive Form -->
<form id="unarchiveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="unarchive_announcement">
    <input type="hidden" name="announcement_id" id="unarchiveAnnouncementId">
</form>

<!-- Analytics Modal -->
<div class="modal fade" id="analyticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Announcement Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="analyticsDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Comments Modal -->
<div class="modal fade" id="commentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Announcement Comments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="commentsContainer">
                    <!-- Comments will be loaded here -->
                </div>
                <div class="mt-3">
                    <form id="addCommentForm">
                        <input type="hidden" id="commentAnnouncementId" name="announcement_id">
                        <div class="input-group">
                            <textarea class="form-control" name="comment" placeholder="Add a comment as admin..." rows="3" required></textarea>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i> Post
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Fallback click handler for dropdowns
    $('.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $dropdown = $(this).closest('.dropdown');
        var $menu = $dropdown.find('.dropdown-menu');
        
        // Close other dropdowns
        $('.dropdown-menu').not($menu).removeClass('show');
        
        // Toggle current dropdown
        $menu.toggleClass('show');
        
        // Ensure dropdown is positioned correctly
        if ($menu.hasClass('show')) {
            // Reset any previous positioning
            $menu.removeClass('dropdown-menu-up');
            
            // Check if there's enough space below
            var dropdownRect = $dropdown[0].getBoundingClientRect();
            var menuHeight = $menu.outerHeight();
            var spaceBelow = window.innerHeight - dropdownRect.bottom;
            
            // If not enough space below, position above
            if (spaceBelow < menuHeight && dropdownRect.top > menuHeight) {
                $menu.addClass('dropdown-menu-up');
            }
        }
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            closeAllDropdowns();
        }
    });
    
    // Close dropdowns when clicking on table rows
    $('#announcementsTable tbody').on('click', 'tr', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            closeAllDropdowns();
        }
    });
    
    // Initialize DataTable with enhanced options
    var table = $('#announcementsTable').DataTable({
        order: [[0, 'desc']], // Sort by pin status first, then by date
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            { targets: [0, 7, 8], orderable: false }, // Disable sorting on pin, views, likes
            { targets: [1], searchable: true } // Enable search on title
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
            }
        }
    });

    // Enhanced filtering function
    function filterTable() {
        var status = $('#statusFilter').val();
        var category = $('#categoryFilter').val();
        var priority = $('#priorityFilter').val();
        var dateFrom = $('#dateFromFilter').val();
        var dateTo = $('#dateToFilter').val();
        var search = $('#searchInput').val();
        
        // Clear previous filters
        table.columns().search('');
        
        // Apply filters
        if (status) {
            table.column(4).search(status, false, false);
        }
        if (category) {
            table.column(2).search(category, false, false);
        }
        if (priority) {
            table.column(3).search(priority, false, false);
        }
        
        // Date range filtering
        if (dateFrom || dateTo) {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var publishedDate = new Date(data[5]);
                var fromDate = dateFrom ? new Date(dateFrom) : null;
                var toDate = dateTo ? new Date(dateTo) : null;
                
                if (fromDate && publishedDate < fromDate) return false;
                if (toDate && publishedDate > toDate) return false;
                return true;
            });
        }
        
        // Global search
        if (search) {
            table.search(search).draw();
        } else {
            table.draw();
        }
    }

    // Event listeners for filters
    $('#statusFilter, #categoryFilter, #priorityFilter, #dateFromFilter, #dateToFilter').on('change', function() {
        $.fn.dataTable.ext.search.pop(); // Remove previous date filter
        filterTable();
    });
    
    $('#searchInput').on('keyup', function() {
        $.fn.dataTable.ext.search.pop(); // Remove previous date filter
        filterTable();
    });

    // Search button
    $('#searchBtn').on('click', function() {
        $.fn.dataTable.ext.search.pop(); // Remove previous date filter
        filterTable();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#statusFilter').val('');
        $('#categoryFilter').val('');
        $('#priorityFilter').val('');
        $('#dateFromFilter').val('');
        $('#dateToFilter').val('');
        $('#searchInput').val('');
        $.fn.dataTable.ext.search.pop(); // Remove date filter
        table.columns().search('').draw();
    });

    // Refresh button
    $('#refreshBtn').on('click', function() {
        location.reload();
    });

    // Export functionality
    $('#exportBtn').on('click', function() {
        table.button('.buttons-csv').trigger();
    });
    
    // Handle view announcement modal
    $('#viewAnnouncementModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var announcement = button.data('announcement');
        var modal = $(this);
        
        if (!announcement) {
            modal.find('#announcementDetails').html('<div class="alert alert-danger">Error loading announcement details.</div>');
            return;
        }
        
        var createdBy = announcement.created_by_name || 'Unknown';
        var statusClass = announcement.display_status === 'published' ? 'success' : 
                         announcement.display_status === 'draft' ? 'warning' : 
                         announcement.display_status === 'expired' ? 'danger' : 'secondary';
        
        var categoryClass = '';
        switch (announcement.category) {
            case 'urgent': categoryClass = 'danger'; break;
            case 'events': categoryClass = 'info'; break;
            case 'maintenance': categoryClass = 'warning'; break;
            case 'academic': categoryClass = 'primary'; break;
            case 'sports': categoryClass = 'success'; break;
            default: categoryClass = 'secondary'; break;
        }
        
        var priorityClass = '';
        switch (announcement.priority) {
            case 'low': priorityClass = 'info'; break;
            case 'medium': priorityClass = 'warning'; break;
            case 'high': priorityClass = 'danger'; break;
            case 'urgent': priorityClass = 'dark'; break;
        }
        
        var content = `
            <div class="row">
                <div class="col-12">
                    <h4>${announcement.title || 'Untitled'}</h4>
                    <div class="mb-3">
                        <span class="badge bg-${categoryClass} me-2">${announcement.category || 'General'}</span>
                        <span class="badge bg-${priorityClass} me-2">${announcement.priority || 'Medium'}</span>
                        <span class="badge bg-${statusClass} me-2">${announcement.display_status || 'Unknown'}</span>
                        ${announcement.is_pinned ? '<span class="badge bg-warning">PINNED</span>' : ''}
                    </div>
                    <p class="text-muted">
                        <i class="fas fa-user"></i> ${createdBy} |
                        <i class="fas fa-calendar"></i> ${announcement.published_at ? new Date(announcement.published_at).toLocaleString() : 'Not published'} |
                        <i class="fas fa-eye"></i> ${announcement.view_count || 0} views |
                        <i class="fas fa-heart"></i> ${announcement.like_count || 0} likes
                    </p>
                    ${announcement.expires_at ? `<p class="text-muted"><i class="fas fa-clock"></i> Expires: ${new Date(announcement.expires_at).toLocaleString()}</p>` : ''}
                    <hr>
                    <div style="white-space: pre-wrap;">${announcement.content || 'No content available.'}</div>
                </div>
            </div>
        `;
        
        modal.find('#announcementDetails').html(content);
    });
    
    // Handle edit announcement modal
    $('#editAnnouncementModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var announcement = button.data('announcement');
        
        if (!announcement) {
            alert('Error loading announcement data.');
            return;
        }
        
        $('#editAnnouncementId').val(announcement.id || '');
        $('#editTitle').val(announcement.title || '');
        $('#editContent').val(announcement.content || '');
        $('#editStatus').val(announcement.status || 'draft');
        $('#editCategory').val(announcement.category || 'general');
        $('#editPriority').val(announcement.priority || 'medium');
        $('#editIsPinned').prop('checked', announcement.is_pinned == 1);
        
        // Set datetime values
        if (announcement.published_at) {
            var publishedDate = new Date(announcement.published_at);
            $('#editPublishedAt').val(publishedDate.toISOString().slice(0, 16));
        } else {
            $('#editPublishedAt').val('');
        }
        
        if (announcement.expires_at) {
            var expiresDate = new Date(announcement.expires_at);
            $('#editExpiresAt').val(expiresDate.toISOString().slice(0, 16));
        } else {
            $('#editExpiresAt').val('');
        }
    });
    
    // Handle analytics modal
    $('#analyticsModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var announcement = button.data('announcement');
        var modal = $(this);
        
        if (!announcement) {
            modal.find('#analyticsDetails').html('<div class="alert alert-danger">Error loading analytics data.</div>');
            return;
        }
        
        var engagementRate = announcement.view_count > 0 ? ((announcement.like_count / announcement.view_count) * 100).toFixed(1) : 0;
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Basic Metrics</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h3>${announcement.view_count || 0}</h3>
                                    <small>Total Views</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h3>${announcement.like_count || 0}</h3>
                                    <small>Total Likes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Engagement Metrics</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h3>${announcement.unique_viewers || 0}</h3>
                                    <small>Unique Viewers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h3>${engagementRate}%</h3>
                                    <small>Engagement Rate</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Announcement Details</h6>
                    <p><strong>Title:</strong> ${announcement.title}</p>
                    <p><strong>Category:</strong> ${announcement.category}</p>
                    <p><strong>Priority:</strong> ${announcement.priority}</p>
                    <p><strong>Status:</strong> ${announcement.display_status}</p>
                    <p><strong>Published:</strong> ${announcement.published_at ? new Date(announcement.published_at).toLocaleString() : 'Not published'}</p>
                    ${announcement.expires_at ? `<p><strong>Expires:</strong> ${new Date(announcement.expires_at).toLocaleString()}</p>` : ''}
                </div>
            </div>
        `;
        
        modal.find('#analyticsDetails').html(content);
    });
    
    // Handle comments modal
    $('#commentsModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var announcement = button.data('announcement');
        var modal = $(this);
        
        if (!announcement) {
            modal.find('#commentsContainer').html('<div class="alert alert-danger">Error loading comments.</div>');
            return;
        }
        
        $('#commentAnnouncementId').val(announcement.id);
        loadCommentsForAdmin(announcement.id);
    });
    
    // Handle add comment form
    $('#addCommentForm').on('submit', function(e) {
        e.preventDefault();
        
        var announcementId = $('#commentAnnouncementId').val();
        var comment = $('textarea[name="comment"]').val().trim();
        
        if (!comment) return;
        
        $.post('announcements.php', {
            action: 'add_comment',
            announcement_id: announcementId,
            comment: comment
        }, function(response) {
            console.log('Add comment response:', response);
            if (response.success) {
                $('textarea[name="comment"]').val('');
                loadCommentsForAdmin(announcementId);
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json').fail(function(xhr, status, error) {
            console.error('Add comment failed:', xhr.responseText);
            alert('Failed to add comment. Please try again.');
        });
    });
    
    
    function displayCommentsForAdmin(comments) {
        var commentsHtml = '';
        
        if (comments.length === 0) {
            commentsHtml = '<p class="text-muted text-center">No comments yet.</p>';
        } else {
            comments.forEach(function(comment) {
                var authorName = comment.student_id ? 
                    (comment.first_name + ' ' + comment.last_name + ' (' + comment.student_number + ')') : 
                    comment.admin_name;
                var authorType = comment.student_id ? 'student' : 'admin';
                var authorClass = comment.student_id ? 'text-primary' : 'text-success';
                var authorIcon = comment.student_id ? 'fas fa-user-graduate' : 'fas fa-user-shield';
                
                commentsHtml += `
                    <div class="comment-item mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <i class="${authorIcon} me-2 ${authorClass}"></i>
                                <strong class="${authorClass}">${authorName}</strong>
                                <small class="text-muted ms-2">${formatTime(comment.created_at)}</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteComment(${comment.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="comment-content">
                            ${escapeHtml(comment.comment)}
                        </div>
                    </div>
                `;
            });
        }
        
        $('#commentsContainer').html(commentsHtml);
    }
    
    

    // Form validation
    $('#addAnnouncementForm, #editAnnouncementForm').on('submit', function(e) {
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

    // Auto-refresh every 5 minutes
    setInterval(function() {
        if (!$('.modal').hasClass('show')) {
            location.reload();
        }
    }, 300000);
    
    // Close dropdowns when scrolling to prevent positioning issues
    $(window).on('scroll', function() {
        closeAllDropdowns();
    });
});

function deleteAnnouncement(id) {
    if (confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) {
        $('#deleteAnnouncementId').val(id);
        $('#deleteForm').submit();
    }
}

function togglePin(id, isPinned) {
    var action = isPinned ? 'pin' : 'unpin';
    if (confirm('Are you sure you want to ' + action + ' this announcement?')) {
        $('#togglePinAnnouncementId').val(id);
        $('#togglePinValue').val(isPinned);
        $('#togglePinForm').submit();
    }
}

function archiveAnnouncement(id) {
    if (confirm('Are you sure you want to archive this announcement?')) {
        $('#archiveAnnouncementId').val(id);
        $('#archiveForm').submit();
    }
}

function unarchiveAnnouncement(id) {
    if (confirm('Are you sure you want to unarchive this announcement?')) {
        $('#unarchiveAnnouncementId').val(id);
        $('#unarchiveForm').submit();
    }
}

// Global functions for comment management
function loadCommentsForAdmin(announcementId) {
    $('#commentsContainer').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading comments...</div>');
    
    $.post('announcements.php', {
        action: 'get_comments',
        announcement_id: announcementId
    }, function(response) {
        console.log('Get comments response:', response);
        if (response.success) {
            displayCommentsForAdmin(response.comments);
        } else {
            $('#commentsContainer').html('<div class="alert alert-danger">Error loading comments: ' + response.message + '</div>');
        }
    }, 'json').fail(function(xhr, status, error) {
        console.error('Get comments failed:', xhr.responseText);
        $('#commentsContainer').html('<div class="alert alert-danger">Failed to load comments. Please try again.</div>');
    });
}

function displayCommentsForAdmin(comments) {
    var commentsHtml = '';
    
    if (comments.length === 0) {
        commentsHtml = '<p class="text-muted text-center">No comments yet.</p>';
    } else {
        comments.forEach(function(comment) {
            var authorName = comment.student_id ? 
                (comment.first_name + ' ' + comment.last_name + ' (' + comment.student_number + ')') : 
                comment.admin_name;
            var authorType = comment.student_id ? 'student' : 'admin';
            var authorClass = comment.student_id ? 'text-primary' : 'text-success';
            var authorIcon = comment.student_id ? 'fas fa-user-graduate' : 'fas fa-user-shield';
            
            commentsHtml += `
                <div class="comment-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center">
                            <i class="${authorIcon} me-2 ${authorClass}"></i>
                            <strong class="${authorClass}">${authorName}</strong>
                            <small class="text-muted ms-2">${formatTime(comment.created_at)}</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteComment(${comment.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-content">
                        ${escapeHtml(comment.comment)}
                    </div>
                </div>
            `;
        });
    }
    
    $('#commentsContainer').html(commentsHtml);
}

function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment?')) {
        $.post('announcements.php', {
            action: 'delete_comment',
            comment_id: commentId
        }, function(response) {
            console.log('Delete comment response:', response);
            if (response.success) {
                var announcementId = $('#commentAnnouncementId').val();
                loadCommentsForAdmin(announcementId);
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json').fail(function(xhr, status, error) {
            console.error('Delete comment failed:', xhr.responseText);
            alert('Failed to delete comment. Please try again.');
        });
    }
}

function formatTime(timestamp) {
    var date = new Date(timestamp);
    var now = new Date();
    var diff = now - date;
    
    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        return Math.floor(diff / 60000) + 'm ago';
    } else if (diff < 86400000) { // Less than 1 day
        return Math.floor(diff / 3600000) + 'h ago';
    } else {
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
}

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Simple function to close all dropdowns
function closeAllDropdowns() {
    $('.dropdown-menu').removeClass('show');
}
</script>

<style>
.reset-btn {
    background: linear-gradient(90deg, #28a745, #ffc107);
    color: #fff;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    transition: 0.3s ease-in-out;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
.reset-btn:hover {
    background: linear-gradient(90deg, #218838, #e0a800);
    transform: scale(1.05);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stats-card p {
    font-size: 1rem;
    margin: 0;
    opacity: 0.9;
}

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    border-color: #e9ecef;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.table td.action-column {
    white-space: nowrap;
    width: 80px;
    min-width: 80px;
    text-align: center;
}

.table th.action-column {
    width: 80px;
    min-width: 80px;
    text-align: center;
}

.dropdown-toggle::after {
    display: none;
}

.dropdown-toggle {
    cursor: pointer;
    position: relative;
    z-index: 10;
}

.dropdown-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 1050;
    display: none;
    min-width: 200px;
    max-width: 250px;
    max-height: 300px;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    font-size: 1rem;
    color: #212529;
    text-align: left;
    list-style: none;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
    overflow-y: auto;
    overflow-x: hidden;
    transform: none !important;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-menu-up {
    top: auto !important;
    bottom: 100% !important;
    margin-top: 0 !important;
    margin-bottom: 0.125rem !important;
}

/* Table responsive improvements */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 0.375rem;
    position: relative;
}

/* Action column styling */
.table th.action-column,
.table td.action-column {
    width: 100px;
    min-width: 100px;
    text-align: center;
    white-space: nowrap;
}

/* Table cell styling */
.table td {
    vertical-align: middle;
    white-space: nowrap;
}

/* Title column can wrap */
.table td:nth-child(2),
.table th:nth-child(2) {
    white-space: normal;
    word-wrap: break-word;
}

/* Action button styling */
.action-column .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

/* Dropdown menu styling */
.dropdown-menu {
    z-index: 1050;
    min-width: 200px;
    max-width: 250px;
    max-height: 300px;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

/* Custom scrollbar for webkit browsers */
.dropdown-menu::-webkit-scrollbar {
    width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

.dropdown-item {
    padding: 10px 16px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-bottom: 1px solid #f1f5f9;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #495057;
    transform: translateX(2px);
}

.dropdown-item.text-danger:hover {
    background-color: #f8d7da;
    color: #721c24;
}

.dropdown-item i {
    width: 16px;
    margin-right: 8px;
    text-align: center;
}

.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
}

.modal-header .btn-close {
    filter: invert(1);
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    border-radius: 20px;
}

@media (max-width: 768px) {
    .stats-card h3 {
        font-size: 1.5rem;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}

@media (max-width: 576px) {
    .col-md-2, .col-md-3, .col-md-4 {
        margin-bottom: 0.5rem;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
    
    .stats-card {
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .stats-card h3 {
        font-size: 1.2rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .modal-dialog {
        margin: 0.25rem;
    }
    
    .content-wrapper {
        padding: 0 10px 10px 10px;
    }
    
    
    /* Hide less important columns on smaller screens */
    .table th:nth-child(7),
    .table td:nth-child(7),
    .table th:nth-child(8),
    .table td:nth-child(8),
    .table th:nth-child(9),
    .table td:nth-child(9) {
        display: none;
    }
    
    .dropdown-menu {
        min-width: 180px;
        max-width: 220px;
        max-height: 250px;
        font-size: 0.85rem;
    }
    
    .dropdown-item {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}

@media (max-width: 768px) {
    
    .dropdown-menu {
        min-width: 160px;
        max-width: 200px;
        max-height: 200px;
        font-size: 0.8rem;
    }
    
    .dropdown-item {
        padding: 6px 10px;
        font-size: 0.75rem;
    }
    
    /* Hide more columns on mobile */
    .table th:nth-child(6),
    .table td:nth-child(6),
    .table th:nth-child(10),
    .table td:nth-child(10) {
        display: none;
    }
    
    /* Make title column more compact */
    .table td:nth-child(2),
    .table th:nth-child(2) {
        max-width: 150px;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .content-wrapper {
        padding: 0 5px 5px 5px;
    }
    
    
    .dropdown-menu {
        min-width: 150px;
        max-width: 180px;
        max-height: 180px;
        font-size: 0.75rem;
    }
    
    .dropdown-item {
        padding: 5px 8px;
        font-size: 0.7rem;
    }
    
    /* Hide category and priority on very small screens */
    .table th:nth-child(3),
    .table td:nth-child(3),
    .table th:nth-child(4),
    .table td:nth-child(4) {
        display: none;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 