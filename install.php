<?php
// Installation script for ISU Dormitory Management System
// Run this file once to set up the database and initial configuration

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('System is already installed. Remove config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Step 1: Check requirements
if ($step == 1) {
    $requirements = [
        'PHP Version (>= 8.0)' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'Fileinfo Extension' => extension_loaded('fileinfo'),
        'GD Extension' => extension_loaded('gd'),
        'Config Directory Writable' => is_writable('config') || mkdir('config', 0755, true),
        'Uploads Directory Writable' => is_writable('uploads') || mkdir('uploads', 0755, true),
    ];
    
    $all_met = true;
    foreach ($requirements as $requirement => $met) {
        if (!$met) $all_met = false;
    }
    
    if ($all_met) {
        $success = 'All requirements are met!';
    } else {
        $error = 'Some requirements are not met. Please fix them before proceeding.';
    }
}

// Step 2: Database configuration
if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'isu_dorm';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Test connection to the specific database
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create config file
        $config_content = "<?php
// Database configuration
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Create database connection
function getDBConnection() {
    try {
        \$pdo = new PDO(
            \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return \$pdo;
    } catch (PDOException \$e) {
        die(\"Connection failed: \" . \$e->getMessage());
    }
}

// Helper function to execute queries safely
function executeQuery(\$sql, \$params = []) {
    \$pdo = getDBConnection();
    \$stmt = \$pdo->prepare(\$sql);
    \$stmt->execute(\$params);
    return \$stmt;
}

// Helper function to fetch single row
function fetchOne(\$sql, \$params = []) {
    \$stmt = executeQuery(\$sql, \$params);
    return \$stmt->fetch();
}

// Helper function to fetch all rows
function fetchAll(\$sql, \$params = []) {
    \$stmt = executeQuery(\$sql, \$params);
    return \$stmt->fetchAll();
}

// Helper function to get last insert ID
function getLastInsertId() {
    \$pdo = getDBConnection();
    return \$pdo->lastInsertId();
}

// Helper function to check if table exists
function tableExists(\$tableName) {
    \$sql = \"SHOW TABLES LIKE ?\";
    \$result = fetchOne(\$sql, [\$tableName]);
    return !empty(\$result);
}
?>";
        
        if (file_put_contents('config/database.php', $config_content)) {
            $success = 'Database configuration created successfully!';
            $step = 3;
        } else {
            $error = 'Failed to create database configuration file.';
        }
        
    } catch (Exception $e) {
        $error = 'Database connection failed: ' . $e->getMessage();
    }
}

// Step 3: Import database schema
if ($step == 3) {
    try {
        require_once 'config/database.php';
        
        // Import main schema
        $sql_file = 'database/isu_dorm.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo = getDBConnection();
            $pdo->exec($sql);
        }
        
        // Import additional tables
        $additional_sql_file = 'database/additional_tables.sql';
        if (file_exists($additional_sql_file)) {
            $sql = file_get_contents($additional_sql_file);
            $pdo = getDBConnection();
            $pdo->exec($sql);
        }
        
        $success = 'Database schema imported successfully!';
        $step = 4;
        
    } catch (Exception $e) {
        $error = 'Failed to import database schema: ' . $e->getMessage();
    }
}

// Step 4: Create admin account
if ($step == 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'config/database.php';
        
        $admin_username = $_POST['admin_username'] ?? 'Dorm_admin';
        $admin_password = $_POST['admin_password'] ?? 'Dorm_admin';
        $admin_email = $_POST['admin_email'] ?? 'admin@isudorm.com';
        $admin_full_name = $_POST['admin_full_name'] ?? 'Dormitory Administrator';
        
        // Hash password
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        // Create admin user
        $sql = "INSERT INTO users (username, password, role, status) VALUES (?, ?, 'admin', 'active')";
        executeQuery($sql, [$admin_username, $hashed_password]);
        $user_id = getLastInsertId();
        
        // Create admin account
        $sql = "INSERT INTO admin_accounts (user_id, full_name, email) VALUES (?, ?, ?)";
        executeQuery($sql, [$user_id, $admin_full_name, $admin_email]);
        
        $success = 'Admin account created successfully!';
        $step = 5;
        
    } catch (Exception $e) {
        $error = 'Failed to create admin account: ' . $e->getMessage();
    }
}

// Step 5: Finalize installation
if ($step == 5) {
    // Create installation lock file
    file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
    
    $success = 'Installation completed successfully! You can now log in with your admin credentials.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - ISU Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 25%, #FFC107 75%, #FFD54F 100%); min-height: 100vh; }
        .install-container { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 2rem; margin: 2rem auto; max-width: 800px; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 2rem; }
        .step { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 0.5rem; }
        .step.active { background: #28a745; color: white; }
        .step.completed { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="text-center mb-4">
                <h1 class="display-4 text-success">
                    <i class="fas fa-building me-3"></i>ISU Dormitory Management System
                </h1>
                <p class="lead">Installation Wizard</p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'completed' : ($step == 2 ? 'active' : ''); ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'completed' : ($step == 3 ? 'active' : ''); ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'completed' : ($step == 4 ? 'active' : ''); ?>">4</div>
                <div class="step <?php echo $step >= 5 ? 'completed' : ''; ?>">5</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Requirements Check -->
            <?php if ($step == 1): ?>
                <h3>Step 1: System Requirements</h3>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Requirement</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $requirement => $met): ?>
                            <tr>
                                <td><?php echo $requirement; ?></td>
                                <td>
                                    <?php if ($met): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($all_met): ?>
                    <div class="text-center">
                        <a href="?step=2" class="btn btn-success btn-lg">
                            <i class="fas fa-arrow-right me-2"></i>Continue to Database Setup
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please fix the failed requirements before proceeding with the installation.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Step 2: Database Configuration -->
            <?php if ($step == 2): ?>
                <h3>Step 2: Database Configuration</h3>
                <form method="POST" action="?step=2">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="isu_dorm" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_user" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass">
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-database me-2"></i>Test Connection & Continue
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Step 3: Database Schema -->
            <?php if ($step == 3): ?>
                <h3>Step 3: Database Schema Import</h3>
                <p>Importing database tables and structure...</p>
                <div class="text-center">
                    <a href="?step=4" class="btn btn-success btn-lg">
                        <i class="fas fa-arrow-right me-2"></i>Continue to Admin Setup
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Step 4: Admin Account -->
            <?php if ($step == 4): ?>
                <h3>Step 4: Create Admin Account</h3>
                <form method="POST" action="?step=4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_username" class="form-label">Admin Username</label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" value="Dorm_admin" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_password" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" value="Dorm_admin" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="admin_full_name" name="admin_full_name" value="Dormitory Administrator" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@isudorm.com" required>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-shield me-2"></i>Create Admin Account
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Step 5: Installation Complete -->
            <?php if ($step == 5): ?>
                <h3>Step 5: Installation Complete!</h3>
                <div class="text-center">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <p class="lead">Your ISU Dormitory Management System has been installed successfully!</p>
                    <div class="alert alert-info">
                        <strong>Default Admin Credentials:</strong><br>
                        Username: <code>Dorm_admin</code><br>
                        Password: <code>Dorm_admin</code>
                    </div>
                    <a href="index.php" class="btn btn-success btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login Page
                    </a>
                </div>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <div class="text-center text-muted">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    For support, please refer to the README.md file or contact the development team.
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
