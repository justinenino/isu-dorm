<?php
// Test dashboard fix - check database connection and table existence
require_once 'config/database.php';

echo "<h1>Dashboard Fix Test</h1>";

try {
    $pdo = getConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['admins', 'students', 'rooms', 'buildings', 'offenses', 'complaints', 'maintenance_requests', 'announcements'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p style='color: green;'>✅ Table '$table' exists with $count records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table' error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test admin dashboard queries
    echo "<h2>Testing Admin Dashboard Queries</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
        $room_count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Room count query: $room_count</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Room count error: " . $e->getMessage() . "</p>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
        $student_count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Student count query: $student_count</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Student count error: " . $e->getMessage() . "</p>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as pending_offenses FROM offenses WHERE status = 'pending'");
        $offense_count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Offense count query: $offense_count</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Offense count error: " . $e->getMessage() . "</p>";
    }
    
    // Test student dashboard queries
    echo "<h2>Testing Student Dashboard Queries</h2>";
    
    if (isset($_SESSION['user_id'])) {
        $student_id = $_SESSION['user_id'];
        echo "<p>Testing with student ID: $student_id</p>";
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            if ($student) {
                echo "<p style='color: green;'>✅ Student data query successful</p>";
            } else {
                echo "<p style='color: red;'>❌ No student found with ID: $student_id</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Student data error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No student session found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Session Information</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>PHP Error Log</h2>";
echo "<pre>";
echo "Error reporting: " . error_reporting() . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
echo "Log errors: " . ini_get('log_errors') . "\n";
echo "</pre>";
?>
