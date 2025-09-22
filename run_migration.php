<?php
require_once 'config/database.php';

echo "Running database migration to add bed space columns...\n";

try {
    $pdo = getConnection();
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM room_change_requests LIKE 'requested_bed_space_id'");
    if ($stmt->rowCount() > 0) {
        echo "Columns already exist. Migration not needed.\n";
        exit;
    }
    
    // Add the columns
    $pdo->exec("ALTER TABLE room_change_requests
        ADD COLUMN requested_bed_space_id INT(11) DEFAULT NULL AFTER requested_room_id,
        ADD COLUMN current_bed_space_id INT(11) DEFAULT NULL AFTER current_room_id");
    
    echo "Added requested_bed_space_id column.\n";
    echo "Added current_bed_space_id column.\n";
    
    // Add foreign key constraints
    $pdo->exec("ALTER TABLE room_change_requests
        ADD CONSTRAINT fk_requested_bed_space
        FOREIGN KEY (requested_bed_space_id) REFERENCES bed_spaces(id) ON DELETE SET NULL");
    
    $pdo->exec("ALTER TABLE room_change_requests
        ADD CONSTRAINT fk_current_bed_space
        FOREIGN KEY (current_bed_space_id) REFERENCES bed_spaces(id) ON DELETE SET NULL");
    
    echo "Added foreign key constraints.\n";
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
