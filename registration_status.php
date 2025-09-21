<?php
require_once 'config/database.php';

// Check if user is trying to access this page directly without registering
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'register.php') === false) {
    // Check if there's a school_id in the URL to check status
    if (!isset($_GET['school_id'])) {
        header('Location: login.php');
        exit();
    }
}

$application_status = 'pending';
$student_data = null;

// If school_id is provided, check the status
if (isset($_GET['school_id'])) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
    $stmt->execute([$_GET['school_id']]);
    $student_data = $stmt->fetch();
    
    if ($student_data) {
        $application_status = $student_data['application_status'];
    } else {
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .status-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .status-header {
            padding: 2rem;
            text-align: center;
        }
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
            color: white;
        }
        .status-approved {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .status-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);
            color: white;
        }
        .status-body {
            padding: 2rem;
            text-align: center;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .btn-refresh {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            color: white;
        }
        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="status-container">
                    <?php if ($application_status === 'pending'): ?>
                        <div class="status-header status-pending">
                            <div class="status-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h2>Application Submitted</h2>
                            <p class="mb-0">Your application is under review</p>
                        </div>
                        <div class="status-body">
                            <h4>WAITING TO APPROVE YOUR APPLICATION</h4>
                            <p class="lead">Please refresh this page to update the status of your application.</p>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> 
                                Your application has been successfully submitted and is currently being reviewed by our administrators. 
                                You will be notified once a decision has been made.
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button onclick="location.reload()" class="btn btn-refresh me-md-2">
                                    <i class="fas fa-sync-alt"></i> Refresh Status
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-sign-in-alt"></i> Go to Login
                                </a>
                            </div>
                        </div>
                    <?php elseif ($application_status === 'approved'): ?>
                        <div class="status-header status-approved">
                            <div class="status-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2>Application Approved!</h2>
                            <p class="mb-0">Congratulations! You can now access the system</p>
                        </div>
                        <div class="status-body">
                            <h4>Welcome to the Dormitory Management System</h4>
                            <p class="lead">Your application has been approved. You can now log in using your credentials.</p>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i> 
                                <strong>Login Credentials:</strong><br>
                                Username: Your School ID<br>
                                Password: Your Learner Reference Number
                            </div>
                            <div class="d-grid">
                                <a href="login.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Login Now
                                </a>
                            </div>
                        </div>
                    <?php elseif ($application_status === 'rejected'): ?>
                        <div class="status-header status-rejected">
                            <div class="status-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h2>Application Not Approved</h2>
                            <p class="mb-0">We regret to inform you about this decision</p>
                        </div>
                        <div class="status-body">
                            <h4>Application Status: Not Approved</h4>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Thank you for your application. After careful review, we regret to inform you that it was not approved this time. 
                                We sincerely apologize for any disappointment this may cause and appreciate your interest.
                            </div>
                            <p>If you believe this decision was made in error or if you have additional information to provide, 
                               please contact the dormitory administration.</p>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="register.php" class="btn btn-primary me-md-2">
                                    <i class="fas fa-redo"></i> Apply Again
                                </a>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home"></i> Back to Home
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds for pending applications
        <?php if ($application_status === 'pending'): ?>
        setInterval(function() {
            location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>