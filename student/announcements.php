<?php
require_once '../config/database.php';

// Handle AJAX requests for interactions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = getConnection();
        $student_id = $_SESSION['user_id'] ?? null;
        
        if (!$student_id) {
            throw new Exception("Student session not found.");
        }
        
        switch ($_POST['action']) {
            case 'like_announcement':
                $announcement_id = (int)$_POST['announcement_id'];
                
                // Check if already liked using announcement_likes table
                $stmt = $pdo->prepare("SELECT id FROM announcement_likes WHERE announcement_id = ? AND student_id = ?");
                $stmt->execute([$announcement_id, $student_id]);
                
                if ($stmt->fetch()) {
                    // Unlike
                    $stmt = $pdo->prepare("DELETE FROM announcement_likes WHERE announcement_id = ? AND student_id = ?");
                    $stmt->execute([$announcement_id, $student_id]);
                    $liked = false;
                } else {
                    // Like
                    $stmt = $pdo->prepare("INSERT INTO announcement_likes (announcement_id, student_id) VALUES (?, ?)");
                    $stmt->execute([$announcement_id, $student_id]);
                    $liked = true;
                }
                
                // Update like count in announcements table
                $stmt = $pdo->prepare("UPDATE announcements SET like_count = (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = ?) WHERE id = ?");
                $stmt->execute([$announcement_id, $announcement_id]);
                
                // Get updated count
                $stmt = $pdo->prepare("SELECT like_count FROM announcements WHERE id = ?");
                $stmt->execute([$announcement_id]);
                $like_count = $stmt->fetchColumn();
                
                echo json_encode(['success' => true, 'liked' => $liked, 'like_count' => $like_count]);
                exit;
                
            case 'acknowledge_announcement':
                $announcement_id = (int)$_POST['announcement_id'];
                
                // Check if already acknowledged
                $stmt = $pdo->prepare("SELECT id FROM announcement_interactions WHERE announcement_id = ? AND student_id = ? AND interaction_type = 'acknowledge'");
                $stmt->execute([$announcement_id, $student_id]);
                
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Already acknowledged']);
                    exit;
                }
                
                // Acknowledge
                $stmt = $pdo->prepare("INSERT INTO announcement_interactions (announcement_id, student_id, interaction_type) VALUES (?, ?, 'acknowledge')");
                $stmt->execute([$announcement_id, $student_id]);
                
                echo json_encode(['success' => true, 'message' => 'Announcement acknowledged']);
                exit;
                
            case 'add_comment':
                $announcement_id = (int)$_POST['announcement_id'];
                $comment = trim($_POST['comment']);
                
                if (empty($comment)) {
                    throw new Exception("Comment cannot be empty.");
                }
                
                $stmt = $pdo->prepare("INSERT INTO announcement_comments (announcement_id, student_id, comment) VALUES (?, ?, ?)");
                $stmt->execute([$announcement_id, $student_id, $comment]);
                
                // Get the comment with student details
                $stmt = $pdo->prepare("
                    SELECT ac.*, 
                           s.first_name, s.last_name, s.school_id as student_number,
                           a.username as admin_name
                    FROM announcement_comments ac
                    LEFT JOIN students s ON ac.student_id = s.id
                    LEFT JOIN admins a ON ac.admin_id = a.id
                    WHERE ac.id = LAST_INSERT_ID()
                ");
                $stmt->execute();
                $new_comment = $stmt->fetch();
                
                echo json_encode(['success' => true, 'message' => 'Comment added successfully', 'comment' => $new_comment]);
                exit;
                
            case 'get_comments':
                $announcement_id = (int)$_POST['announcement_id'];
                
                $stmt = $pdo->prepare("
                    SELECT ac.*, 
                           s.first_name, s.last_name, s.school_id as student_number,
                           a.username as admin_name
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
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$page_title = 'Announcements';
include 'includes/header.php';

try {
    $pdo = getConnection();
    $student_id = $_SESSION['user_id'] ?? null;
    
    // Check if analytics tables exist
    $check_views = $pdo->query("SHOW TABLES LIKE 'announcement_views'");
    $check_likes = $pdo->query("SHOW TABLES LIKE 'announcement_likes'");
    $check_interactions = $pdo->query("SHOW TABLES LIKE 'announcement_interactions'");
    $has_analytics = $check_views->rowCount() > 0 && $check_likes->rowCount() > 0;
    $has_interactions = $check_interactions->rowCount() > 0;
    
    // Track view for each announcement
    if ($student_id) {
        // Get active announcements first - simplified query
        if ($has_analytics && $has_interactions) {
            $stmt = $pdo->prepare("SELECT a.*, 
                adm.username as created_by_name,
                (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id) as like_count,
                (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id AND al.student_id = ?) as user_liked,
                (SELECT COUNT(*) FROM announcement_interactions ai WHERE ai.announcement_id = a.id AND ai.student_id = ? AND ai.interaction_type = 'acknowledge') as user_acknowledged
                FROM announcements a
                LEFT JOIN admins adm ON a.created_by = adm.id
                WHERE (a.is_archived IS NULL OR a.is_archived = 0) 
                AND (a.expires_at IS NULL OR a.expires_at > NOW())
                AND a.status = 'published'
                ORDER BY COALESCE(a.is_pinned, 0) DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC");
        } else {
            $stmt = $pdo->prepare("SELECT a.*, 
                adm.username as created_by_name,
                0 as like_count,
                0 as user_liked,
                0 as user_acknowledged
                FROM announcements a
                LEFT JOIN admins adm ON a.created_by = adm.id
                WHERE (a.is_archived IS NULL OR a.is_archived = 0) 
                AND (a.expires_at IS NULL OR a.expires_at > NOW())
                AND a.status = 'published'
                ORDER BY COALESCE(a.is_pinned, 0) DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC");
        }
        $stmt->execute([$student_id, $student_id]);
        $announcements = $stmt->fetchAll();
        
        // Track views for each announcement (only if analytics tables exist)
        if ($has_analytics) {
            foreach ($announcements as $announcement) {
                try {
                    // Check if already viewed by this student
                    $check_stmt = $pdo->prepare("SELECT id FROM announcement_views WHERE announcement_id = ? AND student_id = ?");
                    $check_stmt->execute([$announcement['id'], $student_id]);
                    
                    if (!$check_stmt->fetch()) {
                        // Record the view
                        $view_stmt = $pdo->prepare("INSERT INTO announcement_views (announcement_id, student_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                        $view_stmt->execute([
                            $announcement['id'], 
                            $student_id, 
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        // Update view count
                        $update_stmt = $pdo->prepare("UPDATE announcements SET view_count = view_count + 1 WHERE id = ?");
                        $update_stmt->execute([$announcement['id']]);
                    }
                } catch (Exception $e) {
                    // Silently continue if view tracking fails
                    error_log("View tracking error: " . $e->getMessage());
                }
            }
        }
        
        // Re-fetch announcements with updated view counts (only if analytics tables exist)
        if ($has_analytics) {
            if ($has_interactions) {
                $stmt = $pdo->prepare("SELECT a.*, 
                    adm.username as created_by_name,
                    (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id) as like_count,
                    (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id AND al.student_id = ?) as user_liked,
                    (SELECT COUNT(*) FROM announcement_interactions ai WHERE ai.announcement_id = a.id AND ai.student_id = ? AND ai.interaction_type = 'acknowledge') as user_acknowledged
                    FROM announcements a
                    LEFT JOIN admins adm ON a.created_by = adm.id
                    WHERE (a.is_archived IS NULL OR a.is_archived = 0) 
                    AND (a.expires_at IS NULL OR a.expires_at > NOW())
                    AND a.status = 'published'
                    ORDER BY COALESCE(a.is_pinned, 0) DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC");
            } else {
                $stmt = $pdo->prepare("SELECT a.*, 
                    adm.username as created_by_name,
                    (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id) as like_count,
                    (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id AND al.student_id = ?) as user_liked,
                    0 as user_acknowledged
                    FROM announcements a
                    LEFT JOIN admins adm ON a.created_by = adm.id
                    WHERE (a.is_archived IS NULL OR a.is_archived = 0) 
                    AND (a.expires_at IS NULL OR a.expires_at > NOW())
                    AND a.status = 'published'
                    ORDER BY COALESCE(a.is_pinned, 0) DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC");
            }
            $stmt->execute([$student_id, $student_id]);
            $announcements = $stmt->fetchAll();
        }
    } else {
        // Fallback for non-logged in users
        if ($has_analytics) {
            $stmt = $pdo->query("SELECT a.*, 
                adm.username as created_by_name,
                (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.id) as like_count,
                0 as user_liked,
                0 as user_acknowledged
                FROM announcements a
                LEFT JOIN admins adm ON a.created_by = adm.id
                WHERE (a.is_archived IS NULL OR a.is_archived = 0) 
                AND (a.expires_at IS NULL OR a.expires_at > NOW())
                AND a.status = 'published'
                ORDER BY COALESCE(a.is_pinned, 0) DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC");
        } else {
            $stmt = $pdo->query("SELECT a.*, 
                adm.username as created_by_name,
                0 as like_count,
                0 as user_liked,
                0 as user_acknowledged
                FROM announcements a
                LEFT JOIN admins adm ON a.created_by = adm.id
                WHERE (a.is_archived IS NULL OR a.is_archived = 0) 
                AND (a.expires_at IS NULL OR a.expires_at > NOW())
                AND a.status = 'published'
                ORDER BY COALESCE(a.is_pinned, 0) DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC");
        }
        $announcements = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $announcements = [];
    error_log("Database error in student announcements: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
    <div class="btn-group" role="group">
        <button class="btn btn-outline-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<?php if (!$has_analytics): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i>
        <strong>Enhanced Features Available:</strong> Like and view tracking features are available. 
        Ask your administrator to enable announcement analytics for the full experience.
    </div>
<?php endif; ?>

<!-- Filter Controls -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select id="priorityFilter" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search announcements...">
                    <button class="btn btn-outline-primary" type="button" id="searchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Actions</label>
                <button id="resetFilters" class="btn btn-outline-secondary w-100">Reset</button>
            </div>
        </div>
    </div>
</div>

<!-- Announcements Feed -->
<div class="row">
    <div class="col-lg-8">
        <div id="announcementsFeed">
            <?php if (empty($announcements)): ?>
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No announcements available</h5>
                        <p class="text-muted">Check back later for new updates.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="card mb-4 announcement-card post-card" data-category="<?php echo $announcement['category']; ?>" data-priority="<?php echo $announcement['priority']; ?>">
                        <?php if ($announcement['is_pinned']): ?>
                            <div class="card-header bg-warning text-dark d-flex align-items-center">
                                <i class="fas fa-thumbtack me-2"></i>
                                <strong>PINNED ANNOUNCEMENT</strong>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Post Header -->
                        <div class="card-header bg-white border-0 pb-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    <div class="avatar-circle bg-primary text-white">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($announcement['created_by_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($announcement['published_at'])); ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick="shareAnnouncement(<?php echo $announcement['id']; ?>)">
                                            <i class="fas fa-share me-2"></i>Share
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="reportAnnouncement(<?php echo $announcement['id']; ?>)">
                                            <i class="fas fa-flag me-2"></i>Report
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Post Content -->
                        <div class="card-body pt-2">
                            <h5 class="card-title mb-3"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            
                            <div class="mb-3">
                                <?php
                                $category_class = '';
                                switch ($announcement['category']) {
                                    case 'urgent': $category_class = 'danger'; break;
                                    case 'events': $category_class = 'info'; break;
                                    case 'maintenance': $category_class = 'warning'; break;
                                    case 'academic': $category_class = 'primary'; break;
                                    case 'sports': $category_class = 'success'; break;
                                    default: $category_class = 'secondary'; break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $category_class; ?> me-2">
                                    <i class="fas fa-tag me-1"></i><?php echo ucfirst($announcement['category']); ?>
                                </span>
                                <?php
                                $priority_class = '';
                                switch ($announcement['priority']) {
                                    case 'low': $priority_class = 'info'; break;
                                    case 'medium': $priority_class = 'warning'; break;
                                    case 'high': $priority_class = 'danger'; break;
                                    case 'urgent': $priority_class = 'dark'; break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $priority_class; ?>">
                                    <i class="fas fa-exclamation-circle me-1"></i><?php echo ucfirst($announcement['priority']); ?>
                                </span>
                            </div>
                            
                            <div class="post-content">
                                <div style="white-space: pre-wrap; line-height: 1.6;"><?php echo htmlspecialchars($announcement['content']); ?></div>
                            </div>
                            
                            <?php if ($announcement['expires_at']): ?>
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Expires:</strong> <?php echo date('M j, Y g:i A', strtotime($announcement['expires_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Post Stats -->
                        <div class="card-footer bg-light border-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="me-3">
                                        <i class="fas fa-eye text-muted me-1"></i>
                                        <small class="text-muted"><?php echo $announcement['view_count'] ?? 0; ?> views</small>
                                    </span>
                                    <span class="me-3">
                                        <i class="fas fa-heart text-muted me-1"></i>
                                        <small class="text-muted"><?php echo $announcement['like_count']; ?> likes</small>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-comments me-1"></i>
                                    <span id="comment-count-<?php echo $announcement['id']; ?>">0</span> comments
                                </small>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-outline-<?php echo $announcement['user_liked'] ? 'danger' : 'secondary'; ?> btn-sm flex-fill" 
                                            onclick="toggleLike(<?php echo $announcement['id']; ?>, this)">
                                        <i class="fas fa-heart me-1"></i>
                                        <span class="like-text"><?php echo $announcement['user_liked'] ? 'Liked' : 'Like'; ?></span>
                                        <span class="like-count">(<?php echo $announcement['like_count']; ?>)</span>
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm flex-fill" 
                                            onclick="toggleComments(<?php echo $announcement['id']; ?>)">
                                        <i class="fas fa-comments me-1"></i>Comment
                                    </button>
                                    <button class="btn btn-outline-info btn-sm flex-fill" 
                                            onclick="shareAnnouncement(<?php echo $announcement['id']; ?>)">
                                        <i class="fas fa-share me-1"></i>Share
                                    </button>
                                    <?php if (!$announcement['user_acknowledged']): ?>
                                        <button class="btn btn-outline-success btn-sm flex-fill" 
                                                onclick="acknowledgeAnnouncement(<?php echo $announcement['id']; ?>, this)">
                                            <i class="fas fa-check me-1"></i>Got it
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm flex-fill" disabled>
                                            <i class="fas fa-check me-1"></i>Acknowledged
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Comments Section (Hidden by default) -->
                            <div class="comments-section mt-3" id="comments-<?php echo $announcement['id']; ?>" style="display: none;">
                                <div class="comments-list mb-3" id="comments-list-<?php echo $announcement['id']; ?>">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Loading comments...
                                    </div>
                                </div>
                                <div class="comment-form">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar me-2">
                                            <div class="avatar-circle bg-secondary text-white">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Write a comment..." id="comment-input-<?php echo $announcement['id']; ?>" onkeypress="if(event.key==='Enter') addComment(<?php echo $announcement['id']; ?>)">
                                                <button class="btn btn-primary" onclick="addComment(<?php echo $announcement['id']; ?>)">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Quick Stats</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary"><?php echo count($announcements); ?></h4>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning"><?php echo count(array_filter($announcements, function($a) { return $a['is_pinned']; })); ?></h4>
                        <small class="text-muted">Pinned</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter"></i> Quick Filters</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="filterByCategory('urgent')">
                        <i class="fas fa-exclamation-triangle"></i> Urgent
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="filterByCategory('events')">
                        <i class="fas fa-calendar"></i> Events
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="filterByCategory('maintenance')">
                        <i class="fas fa-tools"></i> Maintenance
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="filterByCategory('academic')">
                        <i class="fas fa-graduation-cap"></i> Academic
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Filter functionality
    function filterAnnouncements() {
        var category = $('#categoryFilter').val();
        var priority = $('#priorityFilter').val();
        var search = $('#searchInput').val().toLowerCase();
        
        $('.announcement-card').each(function() {
            var card = $(this);
            var cardCategory = card.data('category');
            var cardPriority = card.data('priority');
            var cardText = card.text().toLowerCase();
            
            var showCard = true;
            
            if (category && cardCategory !== category) showCard = false;
            if (priority && cardPriority !== priority) showCard = false;
            if (search && !cardText.includes(search)) showCard = false;
            
            if (showCard) {
                card.show();
            } else {
                card.hide();
            }
        });
    }
    
    $('#categoryFilter, #priorityFilter').on('change', filterAnnouncements);
    $('#searchInput').on('keyup', filterAnnouncements);
    $('#searchBtn').on('click', filterAnnouncements);
    
    $('#resetFilters').on('click', function() {
        $('#categoryFilter').val('');
        $('#priorityFilter').val('');
        $('#searchInput').val('');
        $('.announcement-card').show();
    });
});

function filterByCategory(category) {
    $('#categoryFilter').val(category);
    $('.announcement-card').each(function() {
        if ($(this).data('category') === category) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function toggleLike(announcementId, button) {
    var $button = $(button);
    var $count = $button.find('.like-count');
    var $text = $button.find('.like-text');
    var $icon = $button.find('.fa-heart');
    
    // Add loading state
    $button.prop('disabled', true);
    $icon.addClass('fa-spinner fa-spin').removeClass('fa-heart');
    
    $.post('announcements.php', {
        action: 'like_announcement',
        announcement_id: announcementId
    }, function(response) {
        if (response.success) {
            if (response.liked) {
                $button.removeClass('btn-outline-secondary').addClass('btn-outline-danger');
                $text.text('Liked');
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-heart');
                
                // Add like animation
                $icon.css('animation', 'heartbeat 0.6s ease-in-out');
                setTimeout(() => {
                    $icon.css('animation', '');
                }, 600);
            } else {
                $button.removeClass('btn-outline-danger').addClass('btn-outline-secondary');
                $text.text('Like');
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-heart');
            }
            
            $count.text('(' + response.like_count + ')');
        } else {
            // Revert on error
            $icon.removeClass('fa-spinner fa-spin').addClass('fa-heart');
        }
    }, 'json').fail(function() {
        // Revert on error
        $icon.removeClass('fa-spinner fa-spin').addClass('fa-heart');
    }).always(function() {
        $button.prop('disabled', false);
    });
}

function acknowledgeAnnouncement(announcementId, button) {
    $.post('announcements.php', {
        action: 'acknowledge_announcement',
        announcement_id: announcementId
    }, function(response) {
        if (response.success) {
            $(button).removeClass('btn-outline-success').addClass('btn-success').prop('disabled', true);
            $(button).html('<i class="fas fa-check"></i> Acknowledged');
        } else {
            alert(response.message);
        }
    }, 'json');
}

function toggleComments(announcementId) {
    var $commentsSection = $('#comments-' + announcementId);
    var $button = $('button[onclick="toggleComments(' + announcementId + ')"]');
    
    if ($commentsSection.is(':visible')) {
        $commentsSection.slideUp();
        $button.html('<i class="fas fa-comments me-1"></i>Comment');
    } else {
        $commentsSection.slideDown();
        $button.html('<i class="fas fa-comments me-1"></i>Hide Comments');
        loadComments(announcementId);
    }
}

function loadComments(announcementId) {
    $('#comments-list-' + announcementId).html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading comments...</div>');
    
    $.post('announcements.php', {
        action: 'get_comments',
        announcement_id: announcementId
    }, function(response) {
        if (response.success) {
            displayComments(announcementId, response.comments);
            updateCommentCount(announcementId, response.comments.length);
        } else {
            $('#comments-list-' + announcementId).html('<div class="alert alert-danger">Error loading comments: ' + response.message + '</div>');
        }
    }, 'json').fail(function() {
        $('#comments-list-' + announcementId).html('<div class="alert alert-danger">Failed to load comments. Please try again.</div>');
    });
}

function displayComments(announcementId, comments) {
    var commentsHtml = '';
    
    if (comments.length === 0) {
        commentsHtml = '<div class="text-center text-muted py-3"><i class="fas fa-comment-slash me-2"></i>No comments yet. Be the first to comment!</div>';
    } else {
        comments.forEach(function(comment) {
            var timeAgo = getTimeAgo(comment.created_at);
            var isAdmin = comment.admin_id && !comment.student_id;
            var authorName, authorTitle, avatarClass, avatarIcon;
            
            if (isAdmin) {
                authorName = comment.admin_name || 'Administrator';
                authorTitle = 'Administrator';
                avatarClass = 'bg-success';
                avatarIcon = 'fas fa-user-shield';
            } else {
                authorName = comment.first_name + ' ' + comment.last_name;
                authorTitle = 'Student #' + comment.student_number;
                avatarClass = 'bg-info';
                avatarIcon = 'fas fa-user-graduate';
            }
            
            commentsHtml += `
                <div class="comment-item mb-3 p-3 bg-white rounded border ${isAdmin ? 'admin-comment' : ''}">
                    <div class="d-flex align-items-start">
                        <div class="avatar me-2">
                            <div class="avatar-circle ${avatarClass} text-white">
                                <i class="${avatarIcon}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-bold">${authorName}</h6>
                                <small class="text-muted">${timeAgo}</small>
                            </div>
                            <small class="text-muted d-block mb-2">${authorTitle}</small>
                            <div class="comment-text">${escapeHtml(comment.comment)}</div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    $('#comments-list-' + announcementId).html(commentsHtml);
}

function addComment(announcementId) {
    var comment = $('#comment-input-' + announcementId).val().trim();
    if (!comment) return;
    
    // Disable the button and show loading
    var $button = $('#comment-input-' + announcementId).next('button');
    var originalHtml = $button.html();
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post('announcements.php', {
        action: 'add_comment',
        announcement_id: announcementId,
        comment: comment
    }, function(response) {
        if (response.success) {
            $('#comment-input-' + announcementId).val('');
            // Add the new comment to the list
            if (response.comment) {
                var isAdmin = response.comment.admin_id && !response.comment.student_id;
                var authorName, authorTitle, avatarClass, avatarIcon;
                
                if (isAdmin) {
                    authorName = response.comment.admin_name || 'Administrator';
                    authorTitle = 'Administrator';
                    avatarClass = 'bg-success';
                    avatarIcon = 'fas fa-user-shield';
                } else {
                    authorName = response.comment.first_name + ' ' + response.comment.last_name;
                    authorTitle = 'Student #' + response.comment.student_number;
                    avatarClass = 'bg-info';
                    avatarIcon = 'fas fa-user-graduate';
                }
                
                var newCommentHtml = `
                    <div class="comment-item mb-3 p-3 bg-white rounded border ${isAdmin ? 'admin-comment' : ''}">
                        <div class="d-flex align-items-start">
                            <div class="avatar me-2">
                                <div class="avatar-circle ${avatarClass} text-white">
                                    <i class="${avatarIcon}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 fw-bold">${authorName}</h6>
                                    <small class="text-muted">Just now</small>
                                </div>
                                <small class="text-muted d-block mb-2">${authorTitle}</small>
                                <div class="comment-text">${escapeHtml(response.comment.comment)}</div>
                            </div>
                        </div>
                    </div>
                `;
                
                var $commentsList = $('#comments-list-' + announcementId);
                if ($commentsList.find('.text-center').length > 0) {
                    $commentsList.html(newCommentHtml);
                } else {
                    $commentsList.append(newCommentHtml);
                }
                
                // Update comment count
                var currentCount = parseInt($('#comment-count-' + announcementId).text()) || 0;
                $('#comment-count-' + announcementId).text(currentCount + 1);
            }
        } else {
            alert('Error: ' + response.message);
        }
    }, 'json').fail(function() {
        alert('Failed to add comment. Please try again.');
    }).always(function() {
        // Re-enable the button
        $button.prop('disabled', false).html(originalHtml);
    });
}

function updateCommentCount(announcementId, count) {
    $('#comment-count-' + announcementId).text(count);
}

function shareAnnouncement(announcementId) {
    if (navigator.share) {
        navigator.share({
            title: 'Dormitory Announcement',
            text: 'Check out this announcement from the dormitory management.',
            url: window.location.href + '#announcement-' + announcementId
        });
    } else {
        // Fallback: copy to clipboard
        var url = window.location.href + '#announcement-' + announcementId;
        navigator.clipboard.writeText(url).then(function() {
            alert('Link copied to clipboard!');
        });
    }
}

function reportAnnouncement(announcementId) {
    var reason = prompt('Please provide a reason for reporting this announcement:');
    if (reason && reason.trim()) {
        // Here you would typically send the report to the server
        alert('Thank you for your report. We will review it shortly.');
    }
}

function getTimeAgo(timestamp) {
    var date = new Date(timestamp);
    var now = new Date();
    var diff = now - date;
    
    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        return Math.floor(diff / 60000) + 'm ago';
    } else if (diff < 86400000) { // Less than 1 day
        return Math.floor(diff / 3600000) + 'h ago';
    } else if (diff < 604800000) { // Less than 1 week
        return Math.floor(diff / 86400000) + 'd ago';
    } else {
        return date.toLocaleDateString();
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
</script>

<style>
/* Social Media Style Post Cards */
.post-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.post-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.post-card .card-header {
    border: none;
    padding: 1rem 1.5rem 0.5rem;
}

.post-card .card-body {
    padding: 0 1.5rem 1rem;
}

.post-card .card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f0f0f0;
}

/* Avatar Styles */
.avatar {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.avatar-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: bold;
}

/* Pinned Header */
.card-header.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%) !important;
    color: #000 !important;
    font-weight: bold;
    padding: 0.75rem 1.5rem;
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    border-radius: 20px;
    font-weight: 500;
}

/* Post Content */
.post-content {
    font-size: 1rem;
    line-height: 1.6;
    color: #333;
}

/* Action Buttons */
.btn-group .btn {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-group .btn:active {
    transform: translateY(0);
}

.btn-group .btn.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.btn-group .btn.btn-outline-primary:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.btn-group .btn.btn-outline-info:hover {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
    color: white;
}

.btn-group .btn.btn-outline-success:hover {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

/* Comments Section */
.comments-section {
    border-top: 1px solid #f0f0f0;
    padding-top: 1rem;
    margin-top: 1rem;
}

.comment-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.comment-item:hover {
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* Admin comment styling */
.comment-item.admin-comment {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    border-left: 4px solid #28a745;
    position: relative;
}

.comment-item.admin-comment::before {
    content: 'ADMIN';
    position: absolute;
    top: 8px;
    right: 12px;
    background: #28a745;
    color: white;
    font-size: 0.6rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    letter-spacing: 0.5px;
}

.comment-item.admin-comment:hover {
    background: linear-gradient(135deg, #d4edda 0%, #e8f5e8 100%);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
}

.comment-text {
    font-size: 0.95rem;
    line-height: 1.5;
    color: #333;
}

/* Comment Form */
.comment-form .form-control {
    border-radius: 25px;
    border: 1px solid #ddd;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
}

.comment-form .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.comment-form .btn {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Stats */
.card-footer .text-muted {
    font-size: 0.85rem;
}

/* Dropdown Menu */
.dropdown-menu {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 10px;
    padding: 0.5rem 0;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #333;
}

/* Alert Styles */
.alert {
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
}

/* Loading Animation */
.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .post-card .card-header,
    .post-card .card-body,
    .post-card .card-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .btn-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    .avatar {
        width: 35px;
        height: 35px;
    }
    
    .avatar-circle {
        font-size: 1rem;
    }
    
    .post-content {
        font-size: 0.95rem;
    }
    
    .comment-item {
        padding: 0.75rem !important;
    }
}

@media (max-width: 576px) {
    .btn-group {
        grid-template-columns: 1fr;
    }
    
    .btn-group .btn {
        font-size: 0.75rem;
        padding: 0.4rem;
    }
    
    .post-card .card-header,
    .post-card .card-body,
    .post-card .card-footer {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.4em 0.6em;
    }
}

/* Animation for new comments */
.comment-item {
    animation: slideInUp 0.3s ease;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Like animation */
.btn-outline-danger .fa-heart {
    transition: all 0.2s ease;
}

.btn-outline-danger:hover .fa-heart,
.btn-outline-danger .fa-heart {
    animation: heartbeat 0.6s ease-in-out;
}

@keyframes heartbeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Smooth transitions */
* {
    transition: all 0.2s ease;
}

/* Custom scrollbar for comments */
.comments-list {
    max-height: 400px;
    overflow-y: auto;
}

.comments-list::-webkit-scrollbar {
    width: 4px;
}

.comments-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.comments-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.comments-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<?php include 'includes/footer.php'; ?>