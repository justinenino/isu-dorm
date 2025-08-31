<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_locations_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Student ID',
    'Student Name',
    'Mobile Number',
    'Building',
    'Room Number',
    'Bedspace',
    'Current Location',
    'Last Update',
    'Updated By',
    'Notes'
];

fputcsv($output, $headers);

try {
    $pdo = getDBConnection();
    
    // Get filter parameters
    $search = sanitizeInput($_GET['search'] ?? '');
    $location_filter = sanitizeInput($_GET['location'] ?? '');
    $building_filter = sanitizeInput($_GET['building'] ?? '');
    
    // Build query with same filters as main page
    $where_conditions = ["s.status = 'approved'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }
    
    if (!empty($location_filter)) {
        $where_conditions[] = "latest_location.location = ?";
        $params[] = $location_filter;
    }
    
    if (!empty($building_filter)) {
        $where_conditions[] = "b.id = ?";
        $params[] = $building_filter;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT 
            s.id, s.first_name, s.last_name, s.middle_name, s.student_id, s.mobile_number,
            r.room_number, b.building_name, bs.bedspace_number,
            latest_location.location, latest_location.notes, latest_location.created_at as last_update,
            u.username as updated_by
        FROM students s
        LEFT JOIN bedspaces bs ON s.id = bs.student_id
        LEFT JOIN rooms r ON bs.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN (
            SELECT sl.*, u.username,
                   ROW_NUMBER() OVER (PARTITION BY student_id ORDER BY created_at DESC) as rn
            FROM student_locations sl
            LEFT JOIN users u ON sl.created_by = u.id
            WHERE sl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) latest_location ON s.id = latest_location.student_id AND latest_location.rn = 1
        LEFT JOIN users u ON latest_location.created_by = u.id
        $where_clause
        ORDER BY latest_location.created_at DESC, s.last_name, s.first_name
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $full_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        
        $csv_row = [
            $row['student_id'],
            $full_name,
            $row['mobile_number'],
            $row['building_name'],
            $row['room_number'],
            $row['bedspace_number'],
            $row['location'] ? ucwords(str_replace('_', ' ', $row['location'])) : 'Unknown',
            $row['last_update'],
            $row['updated_by'],
            $row['notes']
        ];
        
        fputcsv($output, $csv_row);
    }
    
    // Log the export activity
    logActivity($_SESSION['user_id'], "Exported student locations to CSV");
    
} catch (PDOException $e) {
    error_log("Database error in export_student_locations.php: " . $e->getMessage());
    fputcsv($output, ['Error: Database error occurred']);
}

fclose($output);
exit;
?>
