<?php
// Test script to verify database fixes
session_start();

// Mock session data
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';
$_SESSION['username'] = 'admin';

echo "<h1>Testing Database Fixes</h1>";

try {
    require_once 'config/database.php';
    $pdo = getConnection();
    
    echo "<h2>✅ Database Connection: SUCCESS</h2>";
    
    // Test if tables exist
    $tables = ['admins', 'students', 'rooms', 'buildings', 'offenses', 'complaints', 'maintenance_requests'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p>✅ Table '$table': EXISTS ($count records)</p>";
        } catch (Exception $e) {
            echo "<p>❌ Table '$table': MISSING - " . $e->getMessage() . "</p>";
        }
    }
    
    // Test admin dashboard
    echo "<h2>Testing Admin Dashboard</h2>";
    try {
        include 'admin/dashboard.php';
        echo "<p>✅ Admin Dashboard: LOADS SUCCESSFULLY</p>";
    } catch (Exception $e) {
        echo "<p>❌ Admin Dashboard: ERROR - " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Connection: FAILED</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
