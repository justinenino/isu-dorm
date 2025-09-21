<?php
/**
 * HOSTINGER DEPLOYMENT SCRIPT
 * This script helps you deploy your local database to Hostinger
 * Run this script to export your local data and prepare for Hostinger deployment
 */

// Include database configuration
require_once 'config/database.php';

// Set content type for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="hostinger_database_export.sql"');

try {
    // Get database connection
    $pdo = getConnection();
    
    echo "-- =====================================================\n";
    echo "-- ISU DORMITORY MANAGEMENT SYSTEM - HOSTINGER EXPORT\n";
    echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    echo "-- =====================================================\n\n";
    
    // Disable foreign key checks for import
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "-- =====================================================\n";
        echo "-- Table structure for table `$table`\n";
        echo "-- =====================================================\n";
        
        // Get table structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        echo $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        echo "-- Dumping data for table `$table`\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            echo "INSERT INTO `$table` ($columnList) VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = $pdo->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            echo implode(",\n", $values) . ";\n\n";
        } else {
            echo "-- No data in table `$table`\n\n";
        }
    }
    
    // Re-enable foreign key checks
    echo "SET FOREIGN_KEY_CHECKS = 1;\n\n";
    
    echo "-- =====================================================\n";
    echo "-- Export completed successfully!\n";
    echo "-- =====================================================\n";
    
} catch (Exception $e) {
    echo "-- ERROR: " . $e->getMessage() . "\n";
    echo "-- Please check your database connection and try again.\n";
}
?>
