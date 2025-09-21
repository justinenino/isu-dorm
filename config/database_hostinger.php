<?php
// Database configuration for Hostinger
// Replace these values with your actual Hostinger database credentials

// Hostinger database configuration
define('DB_HOST', 'localhost'); // Usually localhost for Hostinger
define('DB_USERNAME', 'u123456789_admin'); // Your Hostinger database username (replace with actual)
define('DB_PASSWORD', 'your_strong_password_here'); // Your Hostinger database password (replace with actual)
define('DB_NAME', 'u123456789_dormitory'); // Your Hostinger database name (replace with actual)

// Alternative configuration if above doesn't work
// define('DB_HOST', 'mysql.hostinger.com'); // Some Hostinger plans use this
// define('DB_USERNAME', 'your_username');
// define('DB_PASSWORD', 'your_password');
// define('DB_NAME', 'your_database_name');

// Create connection with enhanced error handling
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Better security
        return $pdo;
    } catch(PDOException $e) {
        // Log error for debugging
        error_log("Database connection failed: " . $e->getMessage());
        
        // Show user-friendly error
        die("Database connection failed. Please check your configuration.");
    }
}

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isStudent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: login.php');
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: login.php');
        exit();
    }
}

// Hostinger-specific error handling
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

// Database health check
function checkDatabaseHealth() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        logError("Database health check failed", ['error' => $e->getMessage()]);
        return false;
    }
}

// Test database connection and return status
function testDatabaseConnection() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetchColumn();
        
        return [
            'status' => 'success',
            'message' => 'Database connected successfully',
            'version' => $version
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'version' => null
        ];
    }
}
?>
