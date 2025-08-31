<?php
require_once 'config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

echo "<!DOCTYPE html>";
echo "<html><head><title>System Status Check</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container my-5'>";
echo "<h1 class='mb-4'><i class='fas fa-cogs'></i> ISU Dorm System Status Check</h1>";

$issues = [];
$successes = [];

// Check database connection
try {
    $pdo = getDBConnection();
    $successes[] = "Database connection successful";
} catch (Exception $e) {
    $issues[] = "Database connection failed: " . $e->getMessage();
}

// Check required directories
$required_dirs = [
    'uploads',
    'uploads/biometrics',
    'uploads/maintenance',
    'uploads/complaints',
    'uploads/policies',
    'uploads/student_documents',
    'uploads/visitors',
    'uploads/announcements',
    'config',
    'admin',
    'admin/includes',
    'student',
    'student/includes',
    'database',
    'assets',
    'assets/css'
];

foreach ($required_dirs as $dir) {
    if (is_dir($dir)) {
        $successes[] = "Directory exists: $dir";
    } else {
        $issues[] = "Missing directory: $dir";
    }
}

// Check required admin files
$admin_files = [
    'admin/dashboard.php',
    'admin/buildings.php',
    'admin/reservations.php',
    'admin/students.php',
    'admin/visitors.php',
    'admin/biometrics.php',
    'admin/maintenance.php',
    'admin/offenses.php',
    'admin/complaints.php',
    'admin/policies.php',
    'admin/announcements.php',
    'admin/settings.php',
    'admin/includes/header.php',
    'admin/includes/footer.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        $successes[] = "Admin file exists: $file";
    } else {
        $issues[] = "Missing admin file: $file";
    }
}

// Check required student files
$student_files = [
    'student/dashboard.php',
    'student/profile.php',
    'student/change_password.php',
    'student/settings.php',
    'student/announcements.php',
    'student/reserve_room.php',
    'student/my_reservations.php',
    'student/maintenance-request.php',
    'student/complaints.php',
    'student/visitor-log.php',
    'student/biometrics.php',
    'student/offense-records.php',
    'student/policies.php',
    'student/register.php',
    'student/includes/header.php',
    'student/includes/footer.php'
];

foreach ($student_files as $file) {
    if (file_exists($file)) {
        $successes[] = "Student file exists: $file";
    } else {
        $issues[] = "Missing student file: $file";
    }
}

// Check helper files
$helper_files = [
    'admin/get_room_occupants.php',
    'admin/get_visitor_details.php',
    'admin/get_biometric_details.php',
    'admin/get_maintenance_details.php',
    'admin/get_offense_details.php',
    'admin/get_complaint_details.php',
    'admin/get_policy_details.php',
    'admin/export_visitors.php',
    'admin/export_maintenance.php',
    'admin/export_complaints.php',
    'student/get_complaint_details.php',
    'student/get_visitor_details.php',
    'student/get_biometric_details.php'
];

foreach ($helper_files as $file) {
    if (file_exists($file)) {
        $successes[] = "Helper file exists: $file";
    } else {
        $issues[] = "Missing helper file: $file";
    }
}

// Check database tables
try {
    $pdo = getDBConnection();
    
    $required_tables = [
        'users', 'admin_accounts', 'students', 'buildings', 'rooms', 'bedspaces',
        'reservations', 'visitors', 'biometrics', 'maintenance_requests', 'offenses',
        'announcements', 'complaints', 'policies', 'room_transfers', 'student_locations',
        'system_backups', 'activity_logs'
    ];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $successes[] = "Database table exists: $table";
        } else {
            $issues[] = "Missing database table: $table";
        }
    }
} catch (Exception $e) {
    $issues[] = "Database table check failed: " . $e->getMessage();
}

// Check configuration files
$config_files = [
    'config/config.php',
    'config/database.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        $successes[] = "Config file exists: $file";
    } else {
        $issues[] = "Missing config file: $file";
    }
}

// Check core files
$core_files = [
    'index.php',
    'logout.php',
    'install.php',
    'README.md'
];

foreach ($core_files as $file) {
    if (file_exists($file)) {
        $successes[] = "Core file exists: $file";
    } else {
        $issues[] = "Missing core file: $file";
    }
}

// Check CSS and assets
$asset_files = [
    'assets/css/style.css'
];

foreach ($asset_files as $file) {
    if (file_exists($file)) {
        $successes[] = "Asset file exists: $file";
    } else {
        $issues[] = "Missing asset file: $file";
    }
}

// Display results
echo "<div class='row'>";

// Issues
echo "<div class='col-md-6'>";
echo "<div class='card border-danger'>";
echo "<div class='card-header bg-danger text-white'>";
echo "<h5 class='mb-0'><i class='fas fa-exclamation-triangle'></i> Issues Found (" . count($issues) . ")</h5>";
echo "</div>";
echo "<div class='card-body'>";

if (empty($issues)) {
    echo "<div class='alert alert-success'>";
    echo "<i class='fas fa-check-circle'></i> No issues found! System appears to be complete.";
    echo "</div>";
} else {
    echo "<ul class='list-group list-group-flush'>";
    foreach ($issues as $issue) {
        echo "<li class='list-group-item text-danger'><i class='fas fa-times'></i> $issue</li>";
    }
    echo "</ul>";
}

echo "</div></div></div>";

// Successes
echo "<div class='col-md-6'>";
echo "<div class='card border-success'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h5 class='mb-0'><i class='fas fa-check-circle'></i> System Components (" . count($successes) . ")</h5>";
echo "</div>";
echo "<div class='card-body' style='max-height: 400px; overflow-y: auto;'>";

echo "<ul class='list-group list-group-flush'>";
foreach ($successes as $success) {
    echo "<li class='list-group-item text-success'><i class='fas fa-check'></i> $success</li>";
}
echo "</ul>";

echo "</div></div></div>";

echo "</div>";

// Missing Features Summary
echo "<div class='card mt-4'>";
echo "<div class='card-header bg-warning text-dark'>";
echo "<h5 class='mb-0'><i class='fas fa-list'></i> Implementation Status</h5>";
echo "</div>";
echo "<div class='card-body'>";

$features_status = [
    '✅ Admin Dashboard' => 'Completed',
    '✅ Buildings & Room Management' => 'Completed',
    '✅ Reservations Management' => 'Completed',
    '✅ Students Management' => 'Completed',
    '✅ Visitors Logs' => 'Completed',
    '✅ Biometrics Uploads' => 'Completed',
    '✅ Maintenance Requests' => 'Completed',
    '✅ Offense Log' => 'Completed',
    '✅ Announcements' => 'Completed',
    '✅ Complaints Management' => 'Completed',
    '✅ Policies Management' => 'Completed',
    '✅ Admin Settings' => 'Completed',
    '❌ Room Transfer Requests' => 'Missing',
    '❌ Student Locator & Logs' => 'Missing',
    '❌ Reports / Analytics' => 'Missing',
    '❌ System Backup' => 'Missing',
    '✅ Student Registration' => 'Completed',
    '✅ Student Dashboard' => 'Completed',
    '✅ Student Profile' => 'Completed',
    '✅ Student Announcements' => 'Completed',
    '✅ Student Room Reservation' => 'Completed',
    '✅ Student My Reservations' => 'Completed',
    '✅ Student Maintenance Request' => 'Completed',
    '✅ Student Complaints' => 'Completed',
    '✅ Student Visitor Log' => 'Completed',
    '✅ Student Biometrics' => 'Completed',
    '✅ Student Offense Records' => 'Completed',
    '✅ Student Dorm Policies' => 'Completed'
];

echo "<div class='row'>";
foreach ($features_status as $feature => $status) {
    $color = strpos($feature, '✅') !== false ? 'success' : 'warning';
    echo "<div class='col-md-6 mb-2'>";
    echo "<div class='d-flex justify-content-between'>";
    echo "<span>$feature</span>";
    echo "<span class='badge bg-$color'>$status</span>";
    echo "</div></div>";
}
echo "</div>";

echo "</div></div>";

// System Health Check
echo "<div class='card mt-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h5 class='mb-0'><i class='fas fa-heartbeat'></i> System Health Check</h5>";
echo "</div>";
echo "<div class='card-body'>";

$health_checks = [];

// PHP Version
$php_version = phpversion();
if (version_compare($php_version, '8.0.0', '>=')) {
    $health_checks[] = ['✅ PHP Version', $php_version, 'success'];
} else {
    $health_checks[] = ['❌ PHP Version', $php_version . ' (Requires 8.0+)', 'danger'];
}

// Extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'fileinfo'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $health_checks[] = ["✅ Extension: $ext", 'Loaded', 'success'];
    } else {
        $health_checks[] = ["❌ Extension: $ext", 'Missing', 'danger'];
    }
}

// File permissions
$upload_dir = 'uploads';
if (is_writable($upload_dir)) {
    $health_checks[] = ['✅ Upload Directory', 'Writable', 'success'];
} else {
    $health_checks[] = ['❌ Upload Directory', 'Not writable', 'danger'];
}

echo "<div class='table-responsive'>";
echo "<table class='table table-sm'>";
echo "<thead><tr><th>Check</th><th>Status</th></tr></thead>";
echo "<tbody>";
foreach ($health_checks as $check) {
    echo "<tr class='table-{$check[2]}'>";
    echo "<td>{$check[0]}</td>";
    echo "<td>{$check[1]}</td>";
    echo "</tr>";
}
echo "</tbody></table>";
echo "</div>";

echo "</div></div>";

echo "<div class='text-center mt-4'>";
echo "<a href='admin/dashboard.php' class='btn btn-primary'>";
echo "<i class='fas fa-arrow-left'></i> Back to Admin Dashboard";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
