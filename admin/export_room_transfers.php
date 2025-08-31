<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="room_transfers_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'ID',
    'Student Name',
    'Student ID',
    'Mobile Number',
    'Email',
    'Current Building',
    'Current Room',
    'Current Bedspace',
    'Target Building', 
    'Target Room',
    'Target Bedspace',
    'Reason',
    'Contact Preference',
    'Status',
    'Admin Notes',
    'Processed By',
    'Created Date',
    'Processed Date'
];

fputcsv($output, $headers);

try {
    $pdo = getDBConnection();
    
    // Get filter parameters
    $search = sanitizeInput($_GET['search'] ?? '');
    $status_filter = sanitizeInput($_GET['status'] ?? '');
    $building_filter = sanitizeInput($_GET['building'] ?? '');
    
    // Build query with same filters as main page
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR rt.reason LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "rt.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($building_filter)) {
        $where_conditions[] = "(cb.id = ? OR tb.id = ?)";
        $params[] = $building_filter;
        $params[] = $building_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT 
            rt.*,
            s.first_name, s.last_name, s.middle_name, s.student_id, s.mobile_number, s.email,
            cr.room_number as current_room, cb.building_name as current_building,
            tr.room_number as target_room, tb.building_name as target_building,
            cbs.bedspace_number as current_bedspace, tbs.bedspace_number as target_bedspace,
            u.username as processed_by_name
        FROM room_transfers rt
        LEFT JOIN students s ON rt.student_id = s.id
        LEFT JOIN bedspaces cbs ON rt.current_bedspace_id = cbs.id
        LEFT JOIN rooms cr ON cbs.room_id = cr.id
        LEFT JOIN buildings cb ON cr.building_id = cb.id
        LEFT JOIN bedspaces tbs ON rt.target_bedspace_id = tbs.id
        LEFT JOIN rooms tr ON tbs.room_id = tr.id
        LEFT JOIN buildings tb ON tr.building_id = tb.id
        LEFT JOIN users u ON rt.processed_by = u.id
        $where_clause
        ORDER BY rt.created_at DESC
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $full_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        
        $csv_row = [
            $row['id'],
            $full_name,
            $row['student_id'],
            $row['mobile_number'],
            $row['email'],
            $row['current_building'],
            $row['current_room'],
            $row['current_bedspace'],
            $row['target_building'],
            $row['target_room'],
            $row['target_bedspace'],
            $row['reason'],
            ucfirst($row['contact_preference']),
            ucfirst($row['status']),
            $row['admin_notes'],
            $row['processed_by_name'],
            $row['created_at'],
            $row['processed_at']
        ];
        
        fputcsv($output, $csv_row);
    }
    
    // Log the export activity
    logActivity($_SESSION['user_id'], "Exported room transfers to CSV");
    
} catch (PDOException $e) {
    error_log("Database error in export_room_transfers.php: " . $e->getMessage());
    fputcsv($output, ['Error: Database error occurred']);
}

fclose($output);
exit;
?>
