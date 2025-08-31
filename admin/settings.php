<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and confirmation password do not match.';
            } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_hash = $stmt->fetchColumn();
                    
                    if (!password_verify($current_password, $current_hash)) {
                        $error = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                        $stmt->execute([$new_hash, $_SESSION['user_id']]);
                        
                        $message = 'Password changed successfully!';
                        logActivity($_SESSION['user_id'], 'Password changed');
                    }
                } catch (PDOException $e) {
                    $error = 'Error changing password: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'change_username') {
            $current_password = $_POST['current_password'];
            $new_username = sanitizeInput($_POST['new_username']);
            
            if (empty($current_password) || empty($new_username)) {
                $error = 'All fields are required.';
            } elseif (strlen($new_username) < 3) {
                $error = 'Username must be at least 3 characters long.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_hash = $stmt->fetchColumn();
                    
                    if (!password_verify($current_password, $current_hash)) {
                        $error = 'Current password is incorrect.';
                    } else {
                        // Check if username already exists
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
                        $stmt->execute([$new_username, $_SESSION['user_id']]);
                        if ($stmt->fetchColumn() > 0) {
                            $error = 'Username already exists. Please choose a different one.';
                        } else {
                            // Update username
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE user_id = ?");
                            $stmt->execute([$new_username, $_SESSION['user_id']]);
                            
                            $message = 'Username changed successfully!';
                            logActivity($_SESSION['user_id'], 'Username changed to: ' . $new_username);
                            
                            // Update session username
                            $_SESSION['username'] = $new_username;
                        }
                    }
                } catch (PDOException $e) {
                    $error = 'Error changing username: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch current user info
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT username, email, created_at, last_login FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching user information: ' . $e->getMessage();
    $user_info = [];
}

$page_title = "Admin Settings";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-cog me-2"></i>Admin Settings
                </h1>
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

            <div class="row">
                <!-- Account Information -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user me-2"></i>Account Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><strong>Username:</strong></label>
                                <p class="form-control-plaintext"><?php echo htmlspecialchars($user_info['username'] ?? ''); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Email:</strong></label>
                                <p class="form-control-plaintext"><?php echo htmlspecialchars($user_info['email'] ?? ''); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Account Created:</strong></label>
                                <p class="form-control-plaintext"><?php echo $user_info['created_at'] ? formatDate($user_info['created_at'], 'M d, Y h:i A') : 'N/A'; ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Last Login:</strong></label>
                                <p class="form-control-plaintext"><?php echo $user_info['last_login'] ? formatDate($user_info['last_login'], 'M d, Y h:i A') : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Username -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user-edit me-2"></i>Change Username
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_username">
                                
                                <div class="mb-3">
                                    <label for="current_password_username" class="form-label">Current Password *</label>
                                    <input type="password" class="form-control" id="current_password_username" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_username" class="form-label">New Username *</label>
                                    <input type="text" class="form-control" id="new_username" name="new_username" 
                                           value="<?php echo htmlspecialchars($user_info['username'] ?? ''); ?>" required>
                                    <small class="form-text text-muted">Username must be at least 3 characters long.</small>
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-save me-2"></i>Update Username
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-key me-2"></i>Change Password
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                    <small class="form-text text-muted">Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-save me-2"></i>Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>System Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-server fa-2x text-primary mb-2"></i>
                                        <h6>Server</h6>
                                        <p class="small text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-code fa-2x text-success mb-2"></i>
                                        <h6>PHP Version</h6>
                                        <p class="small text-muted"><?php echo PHP_VERSION; ?></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-database fa-2x text-warning mb-2"></i>
                                        <h6>Database</h6>
                                        <p class="small text-muted">MySQL</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                        <h6>Session Timeout</h6>
                                        <p class="small text-muted"><?php echo round(SESSION_TIMEOUT / 60, 1); ?> minutes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Username validation
document.getElementById('new_username').addEventListener('input', function() {
    const username = this.value;
    
    if (username.length < 3) {
        this.setCustomValidity('Username must be at least 3 characters long');
    } else {
        this.setCustomValidity('');
    }
});
</script>
