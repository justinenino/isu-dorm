<?php
require_once 'config/config.php';

// Log the logout activity if user is logged in
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'user_logout', 'User logged out successfully');
}

// Destroy the session
session_unset();
session_destroy();

// Clear any session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
redirect('index.php?msg=logged_out');
?>
