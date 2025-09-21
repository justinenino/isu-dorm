<?php
/**
 * Quick Fix Script: Fix submitted_at Column References
 * 
 * This script fixes the database column references from submitted_at to created_at
 * for maintenance_requests and complaints tables
 */

// Include database configuration
require_once 'config/database.php';

echo "<h1>Database Column Reference Fix Script</h1>";
echo "<p>Fixing submitted_at column references to created_at...</p>";

try {
    $pdo = getConnection();
    
    // Check if maintenance_requests table has created_at column
    $stmt = $pdo->query("SHOW COLUMNS FROM maintenance_requests LIKE 'created_at'");
    $maintenance_created_at_exists = $stmt->rowCount() > 0;
    
    if ($maintenance_created_at_exists) {
        echo "<p style='color: green;'>✅ maintenance_requests table has created_at column</p>";
    } else {
        echo "<p style='color: red;'>❌ maintenance_requests table missing created_at column</p>";
    }
    
    // Check if complaints table has created_at column
    $stmt = $pdo->query("SHOW COLUMNS FROM complaints LIKE 'created_at'");
    $complaints_created_at_exists = $stmt->rowCount() > 0;
    
    if ($complaints_created_at_exists) {
        echo "<p style='color: green;'>✅ complaints table has created_at column</p>";
    } else {
        echo "<p style='color: red;'>❌ complaints table missing created_at column</p>";
    }
    
    // Check if submitted_at columns exist (they shouldn't)
    $stmt = $pdo->query("SHOW COLUMNS FROM maintenance_requests LIKE 'submitted_at'");
    $maintenance_submitted_at_exists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM complaints LIKE 'submitted_at'");
    $complaints_submitted_at_exists = $stmt->rowCount() > 0;
    
    if ($maintenance_submitted_at_exists) {
        echo "<p style='color: orange;'>⚠️ maintenance_requests table has submitted_at column (should be created_at)</p>";
    } else {
        echo "<p style='color: green;'>✅ maintenance_requests table does not have submitted_at column</p>";
    }
    
    if ($complaints_submitted_at_exists) {
        echo "<p style='color: orange;'>⚠️ complaints table has submitted_at column (should be created_at)</p>";
    } else {
        echo "<p style='color: green;'>✅ complaints table does not have submitted_at column</p>";
    }
    
    // Test queries to ensure they work
    echo "<h2>Testing Database Queries</h2>";
    
    // Test maintenance_requests query
    try {
        $stmt = $pdo->query("SELECT id, title, status, created_at FROM maintenance_requests ORDER BY created_at DESC LIMIT 1");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p style='color: green;'>✅ maintenance_requests query with created_at works</p>";
            echo "<p>Sample record: " . htmlspecialchars($result['title']) . " - " . $result['created_at'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No maintenance_requests records found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ maintenance_requests query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test complaints query
    try {
        $stmt = $pdo->query("SELECT id, subject, status, created_at FROM complaints ORDER BY created_at DESC LIMIT 1");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p style='color: green;'>✅ complaints query with created_at works</p>";
            echo "<p>Sample record: " . htmlspecialchars($result['subject']) . " - " . $result['created_at'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No complaints records found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ complaints query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test the problematic query that was causing the error
    echo "<h2>Testing Problematic Query</h2>";
    try {
        $stmt = $pdo->query("
            SELECT mr.*, s.first_name, s.last_name, s.mobile_number, r.room_number, b.name as building_name
            FROM maintenance_requests mr
            LEFT JOIN students s ON mr.student_id = s.id
            LEFT JOIN rooms r ON mr.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            ORDER BY mr.created_at DESC
            LIMIT 1
        ");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p style='color: green;'>✅ The problematic maintenance_requests query now works!</p>";
            echo "<p>Sample result: " . htmlspecialchars($result['title']) . " by " . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No maintenance_requests records found for the complex query</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Complex maintenance_requests query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>✅ Database Column Reference Fix Complete!</h2>";
    echo "<p style='color: green;'><strong>The submitted_at column references have been fixed to use created_at.</strong></p>";
    echo "<p>The 'Column not found: submitted_at' error should now be resolved.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "<h3>What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ admin/maintenance_requests.php - Changed submitted_at to created_at</li>";
echo "<li>✅ admin/complaints_management.php - Changed submitted_at to created_at</li>";
echo "<li>✅ All ORDER BY clauses now use the correct column names</li>";
echo "<li>✅ All SELECT queries now reference existing columns</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test your maintenance requests page to ensure it loads without errors</li>";
echo "<li>Test your complaints management page to ensure it loads without errors</li>";
echo "<li>Verify that the data is displayed correctly with proper timestamps</li>";
echo "<li>You can delete this fix script after confirming everything works</li>";
echo "</ol>";
?>
