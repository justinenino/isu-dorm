<?php
// Simple test to check if dashboard loads
session_start();

// Mock session data for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';
$_SESSION['username'] = 'admin';

// Include the dashboard
include 'admin/dashboard.php';
?>
