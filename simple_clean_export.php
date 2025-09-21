<?php
/**
 * SIMPLE CLEAN EXPORT SCRIPT
 * This script removes only the problematic DEFINER clauses
 * without adding any new functionality
 */

$inputFile = 'local_database_export.sql';
$outputFile = 'hostinger_simple_clean.sql';

if (!file_exists($inputFile)) {
    die("❌ Input file '$inputFile' not found!\n");
}

echo "🧹 Removing DEFINER clauses for Hostinger compatibility...\n";

// Read the file
$content = file_get_contents($inputFile);

// Remove only the problematic DEFINER clauses
$patterns = [
    // Remove DEFINER from CREATE PROCEDURE statements
    '/CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/',
    // Remove DEFINER from CREATE TRIGGER statements  
    '/CREATE DEFINER=`[^`]+`@`[^`]+` TRIGGER/',
    // Remove DEFINER from CREATE FUNCTION statements
    '/CREATE DEFINER=`[^`]+`@`[^`]+` FUNCTION/',
    // Remove DEFINER from CREATE EVENT statements
    '/CREATE DEFINER=`[^`]+`@`[^`]+` EVENT/',
    // Remove DEFINER from /*!50013 comments
    '/\/\*!50013 DEFINER=`[^`]+`@`[^`]+` SQL SECURITY DEFINER \*\/\s*/',
];

$replacements = [
    'CREATE PROCEDURE',
    'CREATE TRIGGER', 
    'CREATE FUNCTION',
    'CREATE EVENT',
    '',
];

// Apply replacements
$cleanedContent = preg_replace($patterns, $replacements, $content);

// Write the cleaned file
if (file_put_contents($outputFile, $cleanedContent)) {
    echo "✅ Successfully created simple clean export: $outputFile\n";
    echo "📊 Original file size: " . number_format(filesize($inputFile)) . " bytes\n";
    echo "📊 Cleaned file size: " . number_format(filesize($outputFile)) . " bytes\n";
    
    // Count DEFINER occurrences
    $originalDefiners = substr_count($content, 'DEFINER=');
    $cleanedDefiners = substr_count($cleanedContent, 'DEFINER=');
    
    echo "🔧 Removed $originalDefiners DEFINER clauses\n";
    echo "✅ Remaining DEFINER clauses: $cleanedDefiners\n";
    
    if ($cleanedDefiners == 0) {
        echo "🎉 Export is now Hostinger-compatible!\n";
    } else {
        echo "⚠️  Some DEFINER clauses may still remain\n";
    }
    
} else {
    echo "❌ Failed to create cleaned export file\n";
}

echo "\n📝 Use '$outputFile' for your Hostinger import\n";
?>
