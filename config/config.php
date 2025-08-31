 
 
 <?php
// Main configuration file
session_start();

// Include database configuration
require_once __DIR__ . '/database.php';

// System constants
define('SITE_NAME', 'ISU Dormitory Management System');
define('SITE_URL', 'http://localhost/isu-dorm');
define('ADMIN_EMAIL', 'admin@isudorm.com');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('UPLOAD_PATH', 'uploads/');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Backup settings
define('BACKUP_RETENTION_DAYS', 7);
define('BACKUP_LOG_PATH', '/var/log/drive_backup.log');

// Google Drive API settings (for backup)
define('GOOGLE_DRIVE_CLIENT_ID', '');
define('GOOGLE_DRIVE_CLIENT_SECRET', '');
define('GOOGLE_DRIVE_REDIRECT_URI', SITE_URL . '/admin/backup/google-drive-callback.php');

// Email settings (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Room settings
define('DEFAULT_ROOM_CAPACITY', 4);
define('DEFAULT_BEDSPACE_LABELS', ['Bed 1', 'Bed 2', 'Bed 3', 'Bed 4']);

// Status constants
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_CANCELLED', 'cancelled');
define('STATUS_OCCUPIED', 'occupied');
define('STATUS_AVAILABLE', 'available');
define('STATUS_RESERVED', 'reserved');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STUDENT', 'student');

// Offense severity levels
define('SEVERITY_LOW', 'low');
define('SEVERITY_MEDIUM', 'medium');
define('SEVERITY_HIGH', 'high');

// Maintenance status
define('MAINTENANCE_PENDING', 'pending');
define('MAINTENANCE_ASSIGNED', 'assigned');
define('MAINTENANCE_IN_PROGRESS', 'in_progress');
define('MAINTENANCE_RESOLVED', 'resolved');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN;
}

// Check if user is student
function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_STUDENT;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// Format file size in human readable format
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

// Format date
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// Check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        redirect('login.php?msg=timeout');
    }
    $_SESSION['last_activity'] = time();
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    executeQuery($sql, [
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Initialize session timeout check
checkSessionTimeout();
?>
