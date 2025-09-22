<?php
/**
 * Database Schema Verification Script
 * 
 * This script checks if all required tables and columns exist
 * Run this after importing the database to Hostinger
 */

require_once 'config/database_hostinger_updated.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Schema Verification</title></head><body>";
echo "<h2>🔍 Database Schema Verification</h2>";

try {
    $pdo = getConnection();
    
    // Check required tables
    $required_tables = [
        'students', 'rooms', 'buildings', 'bed_spaces', 
        'admins', 'notifications', 'maintenance_requests'
    ];
    
    echo "<h3>📋 Table Verification</h3>";
    $tables_exist = true;
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' is missing</p>";
            $tables_exist = false;
        }
    }
    
    if (!$tables_exist) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Critical Error!</h3>";
        echo "<p>Some required tables are missing. Please re-import the database.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>🏗️ Column Verification</h3>";
    
    // Check students table columns
    $stmt = $pdo->query("DESCRIBE students");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_student_columns = ['id', 'first_name', 'last_name', 'email', 'application_status', 'room_id', 'bed_space_id'];
    foreach ($required_student_columns as $column) {
        if (in_array($column, $columns)) {
            echo "<p style='color: green;'>✅ students.$column exists</p>";
        } else {
            echo "<p style='color: red;'>❌ students.$column is missing</p>";
        }
    }
    
    // Check buildings table columns
    $stmt = $pdo->query("DESCRIBE buildings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('name', $columns)) {
        echo "<p style='color: green;'>✅ buildings.name exists</p>";
    } else {
        echo "<p style='color: red;'>❌ buildings.name is missing</p>";
    }
    
    // Check rooms table columns
    $stmt = $pdo->query("DESCRIBE rooms");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_room_columns = ['id', 'room_number', 'building_id', 'room_type', 'capacity', 'occupied'];
    foreach ($required_room_columns as $column) {
        if (in_array($column, $columns)) {
            echo "<p style='color: green;'>✅ rooms.$column exists</p>";
        } else {
            echo "<p style='color: red;'>❌ rooms.$column is missing</p>";
        }
    }
    
    // Check bed_spaces table columns
    $stmt = $pdo->query("DESCRIBE bed_spaces");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_bed_columns = ['id', 'room_id', 'bed_space_number', 'is_occupied', 'student_id'];
    foreach ($required_bed_columns as $column) {
        if (in_array($column, $columns)) {
            echo "<p style='color: green;'>✅ bed_spaces.$column exists</p>";
        } else {
            echo "<p style='color: red;'>❌ bed_spaces.$column is missing</p>";
        }
    }
    
    echo "<h3>🧪 Test Query</h3>";
    
    // Test the problematic query
    try {
        $stmt = $pdo->query("
            SELECT r.*, b.name as building_name, bs.bed_number as bed_space_number 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.id 
            JOIN bed_spaces bs ON bs.room_id = r.id 
            LIMIT 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Room query works correctly</p>";
            echo "<p><strong>Sample data:</strong> Building: " . $result['building_name'] . ", Room: " . $result['room_number'] . ", Bed: " . $result['bed_space_number'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Room query works but no data found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Room query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test email template data
    echo "<h3>📧 Email Template Test</h3>";
    try {
        $stmt = $pdo->query("
            SELECT r.*, b.name as building_name, bs.bed_number as bed_space_number 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.id 
            JOIN bed_spaces bs ON bs.room_id = r.id 
            WHERE bs.is_occupied = 0
            LIMIT 1
        ");
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            echo "<p style='color: green;'>✅ Email template data available</p>";
            echo "<p><strong>Room for email:</strong> " . $room['building_name'] . " - Room " . $room['room_number'] . " - Bed " . $room['bed_space_number'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No available rooms found for email testing</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Email template data query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Database Schema Verification Complete!</h3>";
    echo "<p>If all items show green checkmarks, your database is ready for the application.</p>";
    echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a> | <a href='index.php'>Go to Homepage</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Database Connection Failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
