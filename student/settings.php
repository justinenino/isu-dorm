<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    redirect('../index.php');
}

// Fetch student account info
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.created_at, u.last_login
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $error = 'Student profile not found.';
    }
} catch (PDOException $e) {
    $error = 'Error fetching profile: ' . $e->getMessage();
    $student = [];
}

$page_title = "Settings";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-cog me-2"></i>Settings
                </h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($student): ?>
                <div class="row">
                    <!-- Account Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-user me-2"></i>Account Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Student ID:</strong></label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Username:</strong></label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($student['username']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Full Name:</strong></label>
                                    <p class="form-control-plaintext">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Email:</strong></label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($student['email']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Status:</strong></label>
                                    <p class="form-control-plaintext">
                                        <span class="badge bg-<?php echo $student['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Account Created:</strong></label>
                                    <p class="form-control-plaintext"><?php echo formatDate($student['created_at'], 'M d, Y h:i A'); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Last Login:</strong></label>
                                    <p class="form-control-plaintext"><?php echo $student['last_login'] ? formatDate($student['last_login'], 'M d, Y h:i A') : 'Never'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tools me-2"></i>Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <a href="profile.php" class="btn btn-primary">
                                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                                    </a>
                                    
                                    <a href="change_password.php" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </a>
                                    
                                    <a href="reserve_room.php" class="btn btn-success">
                                        <i class="fas fa-bed me-2"></i>Reserve Room
                                    </a>
                                    
                                    <a href="my_reservations.php" class="btn btn-info">
                                        <i class="fas fa-calendar-check me-2"></i>My Reservations
                                    </a>
                                    
                                    <a href="announcements.php" class="btn btn-secondary">
                                        <i class="fas fa-bullhorn me-2"></i>View Announcements
                                    </a>
                                </div>
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
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <i class="fas fa-server fa-2x text-primary mb-2"></i>
                                            <h6>Server</h6>
                                            <p class="small text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <i class="fas fa-code fa-2x text-success mb-2"></i>
                                            <h6>PHP Version</h6>
                                            <p class="small text-muted"><?php echo PHP_VERSION; ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <i class="fas fa-database fa-2x text-warning mb-2"></i>
                                            <h6>Database</h6>
                                            <p class="small text-muted">MySQL</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
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

                <!-- Privacy & Security -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-shield-alt me-2"></i>Privacy & Security
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-lock me-2"></i>Account Security</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Strong password requirements</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Session timeout protection</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Secure database connections</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Input sanitization</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-user-shield me-2"></i>Data Privacy</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Personal data encryption</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Access control restrictions</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Activity logging</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Secure file uploads</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> Your personal information is protected and will only be used for dormitory management purposes. 
                                    You can update your profile information at any time through the Edit Profile section.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
