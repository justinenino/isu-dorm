<?php
/**
 * Quick Fix Script: Add Missing mobile_number Column
 * 
 * This script adds the missing mobile_number column and other required columns
 * to the students table to fix the "Column not found" error
 */

// Include database configuration
require_once 'config/database.php';

echo "<h1>Database Column Fix Script</h1>";
echo "<p>Fixing missing columns in students table...</p>";

try {
    $pdo = getConnection();
    
    // Check if mobile_number column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'mobile_number'");
    $mobile_number_exists = $stmt->rowCount() > 0;
    
    if (!$mobile_number_exists) {
        echo "<p style='color: orange;'>⚠️ mobile_number column not found. Adding it...</p>";
        
        // Add mobile_number column
        $pdo->exec("ALTER TABLE students ADD COLUMN mobile_number varchar(15) NOT NULL DEFAULT '' AFTER contact_number");
        echo "<p style='color: green;'>✅ mobile_number column added successfully!</p>";
    } else {
        echo "<p style='color: green;'>✅ mobile_number column already exists.</p>";
    }
    
    // Check and add other missing columns
    $missing_columns = [
        'province' => "varchar(50) NOT NULL DEFAULT ''",
        'municipality' => "varchar(50) NOT NULL DEFAULT ''",
        'barangay' => "varchar(50) NOT NULL DEFAULT ''",
        'street_purok' => "varchar(100) NOT NULL DEFAULT ''",
        'facebook_link' => "varchar(255) DEFAULT NULL",
        'attachment_file' => "varchar(255) DEFAULT NULL",
        'guardian_name' => "varchar(100) NOT NULL DEFAULT ''",
        'guardian_mobile' => "varchar(15) NOT NULL DEFAULT ''",
        'guardian_relationship' => "varchar(50) NOT NULL DEFAULT ''"
    ];
    
    foreach ($missing_columns as $column => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE '$column'");
        $column_exists = $stmt->rowCount() > 0;
        
        if (!$column_exists) {
            echo "<p style='color: orange;'>⚠️ $column column not found. Adding it...</p>";
            $pdo->exec("ALTER TABLE students ADD COLUMN $column $definition");
            echo "<p style='color: green;'>✅ $column column added successfully!</p>";
        } else {
            echo "<p style='color: green;'>✅ $column column already exists.</p>";
        }
    }
    
    // Update existing records to copy contact_number to mobile_number if needed
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE mobile_number = '' OR mobile_number IS NULL");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ Updating existing records...</p>";
        $pdo->exec("UPDATE students SET mobile_number = contact_number WHERE mobile_number = '' OR mobile_number IS NULL");
        echo "<p style='color: green;'>✅ Updated " . $result['count'] . " records with mobile_number data.</p>";
    }
    
    // Update other required fields with default values
    $update_fields = [
        'province' => 'Isabela',
        'municipality' => 'Echague',
        'barangay' => 'Unknown',
        'street_purok' => 'Unknown',
        'guardian_name' => 'Emergency Contact',
        'guardian_relationship' => 'Guardian'
    ];
    
    foreach ($update_fields as $field => $default_value) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE $field = '' OR $field IS NULL");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $pdo->exec("UPDATE students SET $field = '$default_value' WHERE $field = '' OR $field IS NULL");
            echo "<p style='color: green;'>✅ Updated $field for " . $result['count'] . " records.</p>";
        }
    }
    
    // Update guardian_mobile to use contact_number if empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE guardian_mobile = '' OR guardian_mobile IS NULL");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $pdo->exec("UPDATE students SET guardian_mobile = contact_number WHERE guardian_mobile = '' OR guardian_mobile IS NULL");
        echo "<p style='color: green;'>✅ Updated guardian_mobile for " . $result['count'] . " records.</p>";
    }
    
    echo "<h2>✅ Database Fix Complete!</h2>";
    echo "<p style='color: green;'><strong>All missing columns have been added to the students table.</strong></p>";
    echo "<p>The 'Column not found: mobile_number' error should now be resolved.</p>";
    
    // Test the fix by running a sample query
    echo "<h3>Testing the fix...</h3>";
    $stmt = $pdo->query("SELECT id, first_name, last_name, mobile_number, contact_number FROM students LIMIT 1");
    $test_result = $stmt->fetch();
    
    if ($test_result) {
        echo "<p style='color: green;'>✅ Test query successful! Sample data:</p>";
        echo "<ul>";
        echo "<li>Name: " . htmlspecialchars($test_result['first_name'] . ' ' . $test_result['last_name']) . "</li>";
        echo "<li>Mobile: " . htmlspecialchars($test_result['mobile_number']) . "</li>";
        echo "<li>Contact: " . htmlspecialchars($test_result['contact_number']) . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No student records found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test your application to ensure the error is resolved</li>";
echo "<li>If you have existing student data, verify that mobile_number is populated</li>";
echo "<li>You can delete this fix script after confirming everything works</li>";
echo "</ol>";
?>
