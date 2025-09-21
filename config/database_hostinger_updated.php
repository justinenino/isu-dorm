<?php
/**
 * UPDATED HOSTINGER DATABASE CONFIGURATION
 * Replace the placeholder values with your actual Hostinger database credentials
 * 
 * To get your Hostinger database credentials:
 * 1. Log into your Hostinger control panel
 * 2. Go to "Databases" section
 * 3. Find your database and note down the credentials
 */

// Hostinger database configuration
// IMPORTANT: Replace these with your actual Hostinger database credentials
define('DB_HOST', 'localhost'); // Usually localhost for Hostinger shared hosting
define('DB_USERNAME', 'u123456789_admin'); // Your Hostinger database username
define('DB_PASSWORD', 'your_strong_password_here'); // Your Hostinger database password
define('DB_NAME', 'u123456789_dormitory'); // Your Hostinger database name

// Alternative configuration if above doesn't work
// Some Hostinger plans use different host names
// define('DB_HOST', 'mysql.hostinger.com');
// define('DB_USERNAME', 'your_username');
// define('DB_PASSWORD', 'your_password');
// define('DB_NAME', 'your_database_name');

// Create connection with enhanced error handling for Hostinger
function getConnection() {
    try {
        // Use charset=utf8mb4 for better Unicode support
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
            DB_USERNAME, 
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Better security
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        // Log error for debugging
        error_log("Database connection failed: " . $e->getMessage());
        
        // Show user-friendly error
        die("Database connection failed. Please check your Hostinger database configuration.");
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

// Hostinger-specific optimizations
function optimizeForHostinger() {
    try {
        $pdo = getConnection();
        
        // Set session timeout
        $pdo->exec("SET SESSION wait_timeout = 28800");
        $pdo->exec("SET SESSION interactive_timeout = 28800");
        
        // Optimize for shared hosting
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        
        return true;
    } catch (Exception $e) {
        logError("Hostinger optimization failed", ['error' => $e->getMessage()]);
        return false;
    }
}

// Initialize Hostinger optimizations
optimizeForHostinger();
?>
