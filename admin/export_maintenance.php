<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="maintenance_requests_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'ID',
    'Title',
    'Description',
    'Student Name',
    'Student ID',
    'Room',
    'Building',
    'Priority',
    'Status',
    'Assigned To',
    'Contact Preference',
    'Created Date',
    'Updated Date'
];

fputcsv($output, $headers);

try {
    $pdo = getDBConnection();
    
    // Build the query based on filters
    $where_conditions = [];
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = sanitizeInput($_GET['search']);
        $where_conditions[] = "(mr.title LIKE ? OR mr.description LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = sanitizeInput($_GET['status']);
        $where_conditions[] = "mr.status = ?";
        $params[] = $status;
    }
    
    if (isset($_GET['priority']) && !empty($_GET['priority'])) {
        $priority = sanitizeInput($_GET['priority']);
        $where_conditions[] = "mr.priority = ?";
        $params[] = $priority;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $sql = "
        SELECT 
            mr.id,
            mr.title,
            mr.description,
            s.first_name,
            s.last_name,
            s.middle_name,
            s.student_id,
            r.room_number,
            b.building_name,
            mr.priority,
            mr.status,
            u.username as assigned_to,
            mr.contact_preference,
            mr.created_at,
            mr.updated_at
        FROM maintenance_requests mr
        LEFT JOIN students s ON mr.student_id = s.id
        LEFT JOIN rooms r ON mr.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN users u ON mr.assigned_to = u.id
        $where_clause
        ORDER BY mr.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $student_name = $row['first_name'] . ' ' . $row['last_name'];
        if ($row['middle_name']) {
            $student_name = $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
        }
        
        $room_info = $row['building_name'] . ' - Room ' . $row['room_number'];
        $assigned_to = $row['assigned_to'] ?: 'Not Assigned';
        
        $csv_row = [
            $row['id'],
            $row['title'],
            $row['description'],
            $student_name,
            $row['student_id'],
            $room_info,
            $row['building_name'],
            ucfirst($row['priority']),
            ucfirst($row['status']),
            $assigned_to,
            ucfirst($row['contact_preference']),
            date('Y-m-d H:i:s', strtotime($row['created_at'])),
            $row['updated_at'] ? date('Y-m-d H:i:s', strtotime($row['updated_at'])) : 'Not updated'
        ];
        
        fputcsv($output, $csv_row);
    }
    
    // Log the export activity
    logActivity($_SESSION['user_id'], "Exported maintenance requests to CSV");
    
} catch (PDOException $e) {
    error_log("Database error in export_maintenance.php: " . $e->getMessage());
    fputcsv($output, ['Error: Database error occurred']);
} catch (Exception $e) {
    error_log("Error in export_maintenance.php: " . $e->getMessage());
    fputcsv($output, ['Error: Export failed']);
}

fclose($output);
exit;
?>
