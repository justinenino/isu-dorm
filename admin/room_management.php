<?php
require_once '../config/database.php';

// Handle form submissions BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo = getConnection();
            
            switch ($_POST['action']) {
                case 'add_building':
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $total_floors = (int)$_POST['total_floors'];
                    $rooms_per_floor = (int)$_POST['rooms_per_floor'];
                    $capacity_per_room = (int)$_POST['capacity_per_room'];
                    
                    // Validate inputs
                    if (empty($name)) {
                        throw new Exception("Building name is required.");
                    }
                    
                    if ($total_floors < 1 || $rooms_per_floor < 1 || $capacity_per_room < 1) {
                        throw new Exception("All numeric values must be greater than 0.");
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Insert building
                    $stmt = $pdo->prepare("INSERT INTO buildings (name, description, total_floors) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $description, $total_floors]);
                    $building_id = $pdo->lastInsertId();
                    
                    // Create rooms for each floor
                    for ($floor = 1; $floor <= $total_floors; $floor++) {
                        for ($room_num = 1; $room_num <= $rooms_per_floor; $room_num++) {
                            $room_number = $floor . str_pad($room_num, 2, '0', STR_PAD_LEFT);
                            
                            // Insert room
                            $stmt = $pdo->prepare("INSERT INTO rooms (building_id, room_number, floor_number, capacity) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$building_id, $room_number, $floor, $capacity_per_room]);
                            $room_id = $pdo->lastInsertId();
                            
                            // Create bed spaces for the room
                            for ($bed = 1; $bed <= $capacity_per_room; $bed++) {
                                $stmt = $pdo->prepare("INSERT INTO bed_spaces (room_id, bed_number) VALUES (?, ?)");
                                $stmt->execute([$room_id, $bed]);
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Building '$name' created successfully with " . ($total_floors * $rooms_per_floor) . " rooms.";
                    header("Location: room_management.php");
                    exit;
                    break;
                    
                case 'edit_building':
                    $building_id = (int)$_POST['building_id'];
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $total_floors = (int)$_POST['total_floors'];
                    
                    // Validate inputs
                    if (empty($name)) {
                        throw new Exception("Building name is required.");
                    }
                    
                    if ($total_floors < 1) {
                        throw new Exception("Total floors must be greater than 0.");
                    }
                    
                    // Check if building has students
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students s 
                                         JOIN rooms r ON s.room_id = r.id 
                                         WHERE r.building_id = ? AND s.application_status = 'approved' AND s.is_deleted = 0 AND s.is_active = 1");
                    $stmt->execute([$building_id]);
                    $student_count = $stmt->fetch()['count'];
                    
                    if ($student_count > 0) {
                        throw new Exception("Cannot edit building with assigned students. Please reassign students first.");
                    }
                    
                    // Update building
                    $stmt = $pdo->prepare("UPDATE buildings SET name = ?, description = ?, total_floors = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $total_floors, $building_id]);
                    
                    $_SESSION['success'] = "Building '$name' updated successfully.";
                    header("Location: room_management.php");
                    exit;
                    break;
                    
                case 'delete_building':
                    $building_id = (int)$_POST['building_id'];
                    
                    // Check if building has students
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students s 
                                         JOIN rooms r ON s.room_id = r.id 
                                         WHERE r.building_id = ? AND s.application_status = 'approved' AND s.is_deleted = 0 AND s.is_active = 1");
                    $stmt->execute([$building_id]);
                    $student_count = $stmt->fetch()['count'];
                    
                    if ($student_count > 0) {
                        throw new Exception("Cannot delete building with assigned students. Please reassign students first.");
                    }
                    
                    // Get building name for success message
                    $stmt = $pdo->prepare("SELECT name FROM buildings WHERE id = ?");
                    $stmt->execute([$building_id]);
                    $building_name = $stmt->fetch()['name'];
                    
                    // Delete building (cascade will handle rooms and bed spaces)
                    $stmt = $pdo->prepare("DELETE FROM buildings WHERE id = ?");
                    $stmt->execute([$building_id]);
                    
                    $_SESSION['success'] = "Building '$building_name' deleted successfully.";
                    header("Location: room_management.php");
                    exit;
                    break;
                    
                case 'add_room':
                    $building_id = (int)$_POST['building_id'];
                    $room_number = trim($_POST['room_number']);
                    $floor_number = (int)$_POST['floor_number'];
                    $capacity = (int)$_POST['capacity'];
                    
                    // Validate inputs
                    if (empty($room_number) || $building_id <= 0 || $floor_number < 1 || $capacity < 1) {
                        throw new Exception("All fields are required and must be valid.");
                    }
                    
                    // Check if room already exists
                    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE building_id = ? AND room_number = ?");
                    $stmt->execute([$building_id, $room_number]);
                    if ($stmt->fetch()) {
                        throw new Exception("Room number already exists in this building.");
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Insert room
                    $stmt = $pdo->prepare("INSERT INTO rooms (building_id, room_number, floor_number, capacity) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$building_id, $room_number, $floor_number, $capacity]);
                    $room_id = $pdo->lastInsertId();
                    
                    // Create bed spaces for the room
                    for ($bed = 1; $bed <= $capacity; $bed++) {
                        $stmt = $pdo->prepare("INSERT INTO bed_spaces (room_id, bed_number) VALUES (?, ?)");
                        $stmt->execute([$room_id, $bed]);
                    }
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Room $room_number added successfully.";
                    header("Location: room_management.php");
                    exit;
                    break;
                    
                case 'update_room_capacity':
                    $room_id = (int)$_POST['room_id'];
                    $new_capacity = (int)$_POST['new_capacity'];
                    
                    if ($room_id <= 0 || $new_capacity < 1) {
                        throw new Exception("Invalid room ID or capacity.");
                    }
                    
                    // Get current room info
                    $stmt = $pdo->prepare("SELECT capacity, occupied FROM rooms WHERE id = ?");
                    $stmt->execute([$room_id]);
                    $room = $stmt->fetch();
                    
                    if (!$room) {
                        throw new Exception("Room not found.");
                    }
                    
                    if ($new_capacity < $room['occupied']) {
                        throw new Exception("New capacity cannot be less than current occupancy.");
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Update room capacity
                    $stmt = $pdo->prepare("UPDATE rooms SET capacity = ? WHERE id = ?");
                    $stmt->execute([$new_capacity, $room_id]);
                    
                    // Delete excess bed spaces if reducing capacity
                    if ($new_capacity < $room['capacity']) {
                        $stmt = $pdo->prepare("DELETE FROM bed_spaces WHERE room_id = ? AND bed_number > ?");
                        $stmt->execute([$room_id, $new_capacity]);
                    }
                    
                    // Add new bed spaces if increasing capacity
                    if ($new_capacity > $room['capacity']) {
                        for ($bed = $room['capacity'] + 1; $bed <= $new_capacity; $bed++) {
                            $stmt = $pdo->prepare("INSERT INTO bed_spaces (room_id, bed_number) VALUES (?, ?)");
                            $stmt->execute([$room_id, $bed]);
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Room capacity updated successfully.";
                    header("Location: room_management.php");
                    exit;
                    break;
                    
                case 'update_room_status':
                    $room_id = (int)$_POST['room_id'];
                    $status = $_POST['status'];
                    
                    $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $room_id]);
                    
                    $_SESSION['success'] = "Room status updated successfully.";
                    header("Location: room_management.php");
                    exit;
                    break;
                    
                case 'delete_room':
                    $room_id = (int)$_POST['room_id'];
                    
                    // Check if room has students
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE room_id = ? AND is_deleted = 0 AND is_active = 1");
                    $stmt->execute([$room_id]);
                    $student_count = $stmt->fetchColumn();
                    
                    if ($student_count > 0) {
                        throw new Exception("Cannot delete room with assigned students. Please reassign students first.");
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Delete bed spaces
                    $stmt = $pdo->prepare("DELETE FROM bed_spaces WHERE room_id = ?");
                    $stmt->execute([$room_id]);
                    
                    // Delete room
                    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                    $stmt->execute([$room_id]);
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Room deleted successfully.";
                    header("Location: room_management.php");
                    exit;
                    break;
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: room_management.php");
            exit;
        }
    }
}

$page_title = 'Room Management';
include 'includes/header.php';

try {
    $pdo = getConnection();
    
    // Get rooms with building details and occupancy
    $stmt = $pdo->query("SELECT r.*, 
        b.name as building_name,
        b.total_floors,
        COUNT(bs.id) as total_beds,
        SUM(CASE WHEN bs.is_occupied = 1 THEN 1 ELSE 0 END) as occupied_beds
        FROM rooms r
        JOIN buildings b ON r.building_id = b.id
        LEFT JOIN bed_spaces bs ON r.id = bs.room_id
        GROUP BY r.id
        ORDER BY b.name, r.floor_number, r.room_number");
    $rooms = $stmt->fetchAll();
    
    // Get students in each room
    $stmt = $pdo->query("SELECT s.id, s.first_name, s.last_name, s.school_id, s.room_id, bs.bed_number
        FROM students s
        LEFT JOIN bed_spaces bs ON s.bed_space_id = bs.id
        WHERE s.application_status = 'approved' AND s.room_id IS NOT NULL AND s.is_deleted = 0 AND s.is_active = 1");
    $students_in_rooms = $stmt->fetchAll();
    
    // Group students by room
    $students_by_room = [];
    foreach ($students_in_rooms as $student) {
        $room_id = $student['room_id'];
        if (!isset($students_by_room[$room_id])) {
            $students_by_room[$room_id] = [];
        }
        $students_by_room[$room_id][] = $student;
    }
    
    // Update room occupancy based on active students only
    $stmt = $pdo->query("SELECT r.id, COUNT(s.id) as active_students
        FROM rooms r
        LEFT JOIN students s ON r.id = s.room_id AND s.application_status = 'approved' AND s.is_deleted = 0 AND s.is_active = 1
        GROUP BY r.id");
    $room_occupancy = $stmt->fetchAll();
    
    // Update the occupied count for each room
    foreach ($room_occupancy as $occupancy) {
        $stmt = $pdo->prepare("UPDATE rooms SET occupied = ? WHERE id = ?");
        $stmt->execute([$occupancy['active_students'], $occupancy['id']]);
    }
    
    // Recalculate building statistics with updated occupancy
    $stmt = $pdo->query("SELECT b.*, 
        COUNT(r.id) as total_rooms,
        SUM(r.capacity) as total_capacity,
        SUM(r.occupied) as total_occupied
        FROM buildings b
        LEFT JOIN rooms r ON b.id = r.building_id
        GROUP BY b.id
        ORDER BY b.name");
    $buildings = $stmt->fetchAll();
    
} catch (Exception $e) {
    $buildings = [];
    $rooms = [];
    $students_by_room = [];
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-bed"></i> Room Management</h2>
    <div>
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
            <i class="fas fa-building"></i> Add Building
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="fas fa-plus"></i> Add Room
        </button>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3><?php echo count($buildings); ?></h3>
            <p>Buildings</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php echo count($rooms); ?></h3>
            <p>Total Rooms</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
            <h3><?php echo array_sum(array_column($rooms, 'occupied_beds')); ?></h3>
            <p>Occupied Beds</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
            <h3><?php echo array_sum(array_column($rooms, 'total_beds')); ?></h3>
            <p>Total Beds</p>
        </div>
    </div>
</div>

<!-- Building Tabs -->
<?php if (!empty($buildings)): ?>
<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="buildingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i> Overview
                </button>
            </li>
            <?php foreach ($buildings as $index => $building): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="building-<?php echo $building['id']; ?>-tab" data-bs-toggle="tab" data-bs-target="#building-<?php echo $building['id']; ?>" type="button" role="tab">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($building['name']); ?>
                    <span class="badge bg-primary ms-1"><?php echo $building['total_rooms']; ?></span>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="buildingTabsContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row">
                    <?php foreach ($buildings as $building): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-building"></i> <?php echo htmlspecialchars($building['name']); ?></h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted"><?php echo htmlspecialchars($building['description']); ?></p>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <h5><?php echo $building['total_floors']; ?></h5>
                                            <small>Floors</small>
                                        </div>
                                        <div class="col-4">
                                            <h5><?php echo $building['total_rooms']; ?></h5>
                                            <small>Rooms</small>
                                        </div>
                                        <div class="col-4">
                                            <h5><?php echo $building['total_capacity']; ?></h5>
                                            <small>Capacity</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $building['total_capacity'] > 0 ? round(($building['total_occupied'] / $building['total_capacity']) * 100) : 0; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $building['total_occupied']; ?> / <?php echo $building['total_capacity']; ?> occupied</small>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editBuildingModal" 
                                                data-building='<?php echo json_encode($building); ?>'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBuilding(<?php echo $building['id']; ?>, '<?php echo htmlspecialchars($building['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Individual Building Tabs -->
            <?php foreach ($buildings as $building): ?>
            <div class="tab-pane fade" id="building-<?php echo $building['id']; ?>" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?php echo htmlspecialchars($building['name']); ?> - Room Details</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="filterRooms(<?php echo $building['id']; ?>, 'all')">All</button>
                        <button class="btn btn-sm btn-outline-success" onclick="filterRooms(<?php echo $building['id']; ?>, 'available')">Available</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="filterRooms(<?php echo $building['id']; ?>, 'full')">Full</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="filterRooms(<?php echo $building['id']; ?>, 'maintenance')">Maintenance</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped" id="roomsTable-<?php echo $building['id']; ?>">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Floor</th>
                                <th>Capacity</th>
                                <th>Occupied</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $building_rooms = array_filter($rooms, function($r) use ($building) { 
                                return $r['building_id'] == $building['id']; 
                            });
                            foreach ($building_rooms as $room): 
                            ?>
                                <tr data-room-id="<?php echo $room['id']; ?>" data-status="<?php echo $room['status']; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($room['room_number']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['floor_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['total_beds']); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($room['occupied_beds']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($room['total_beds'] - $room['occupied_beds']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($room['status']) {
                                            case 'available': $status_class = 'badge bg-success'; break;
                                            case 'full': $status_class = 'badge bg-danger'; break;
                                            case 'maintenance': $status_class = 'badge bg-warning'; break;
                                        }
                                        ?>
                                        <span class="<?php echo $status_class; ?>"><?php echo ucfirst($room['status']); ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewRoomStudents(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')">
                                            <i class="fas fa-users"></i> <?php echo count($students_by_room[$room['id']] ?? []); ?>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRoomModal" 
                                                    data-room='<?php echo json_encode($room); ?>' 
                                                    data-students='<?php echo json_encode(isset($students_by_room[$room['id']]) ? $students_by_room[$room['id']] : []); ?>'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#updateCapacityModal" 
                                                    data-room-id="<?php echo $room['id']; ?>" 
                                                    data-room-capacity="<?php echo $room['total_beds']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal" 
                                                    data-room-id="<?php echo $room['id']; ?>" 
                                                    data-room-status="<?php echo $room['status']; ?>">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Building Modal -->
<div class="modal fade" id="addBuildingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addBuildingForm">
                <input type="hidden" name="action" value="add_building">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Building Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Floors <span class="text-danger">*</span></label>
                                <input type="number" name="total_floors" class="form-control" min="1" value="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Rooms per Floor <span class="text-danger">*</span></label>
                                <input type="number" name="rooms_per_floor" class="form-control" min="1" value="10" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Capacity per Room <span class="text-danger">*</span></label>
                                <input type="number" name="capacity_per_room" class="form-control" min="1" max="10" value="4" required>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> This will create <span id="totalRooms">10</span> rooms with <span id="totalBeds">40</span> total beds.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Building Modal -->
<div class="modal fade" id="editBuildingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editBuildingForm">
                <input type="hidden" name="action" value="edit_building">
                <input type="hidden" name="building_id" id="editBuildingId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Building Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editBuildingName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editBuildingDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Floors <span class="text-danger">*</span></label>
                        <input type="number" name="total_floors" id="editBuildingFloors" class="form-control" min="1" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Editing building details will not affect existing rooms. Only the building information will be updated.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Building Confirmation Modal -->
<div class="modal fade" id="deleteBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Building Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> Warning!</h6>
                    <p class="mb-0">This action will permanently delete the building and all its rooms. This cannot be undone!</p>
                </div>
                <p>Are you sure you want to delete the building <strong id="deleteBuildingName"></strong>?</p>
                <p class="text-muted">This will also delete:</p>
                <ul class="text-muted">
                    <li>All rooms in this building</li>
                    <li>All bed spaces in those rooms</li>
                    <li>All room assignments (students will be unassigned)</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_building">
                    <input type="hidden" name="building_id" id="deleteBuildingId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Building
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_room">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Building <span class="text-danger">*</span></label>
                        <select name="building_id" class="form-select" required>
                            <option value="">Select Building</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?php echo $building['id']; ?>"><?php echo htmlspecialchars($building['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number <span class="text-danger">*</span></label>
                            <input type="text" name="room_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Floor Number <span class="text-danger">*</span></label>
                            <input type="number" name="floor_number" class="form-control" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity (Number of Beds) <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" class="form-control" min="1" max="10" value="4" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Room Modal -->
<div class="modal fade" id="viewRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="roomDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Update Capacity Modal -->
<div class="modal fade" id="updateCapacityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Room Capacity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_room_capacity">
                <input type="hidden" name="room_id" id="updateCapacityRoomId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="new_capacity" id="updateCapacityValue" class="form-control" min="1" max="10" required>
                        <small class="form-text text-muted">This will adjust the number of bed spaces in the room.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Capacity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Room Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_room_status">
                <input type="hidden" name="room_id" id="updateRoomId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="updateRoomStatus" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="full">Full</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Students in Room Modal -->
<div class="modal fade" id="studentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentsModalTitle">Students in Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentsModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Room Form -->
<form id="deleteRoomForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_room">
    <input type="hidden" name="room_id" id="deleteRoomId">
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables for each building
    <?php foreach ($buildings as $building): ?>
    $('#roomsTable-<?php echo $building['id']; ?>').DataTable({
        order: [[1, 'asc'], [0, 'asc']], // Sort by floor, then room number
        pageLength: 25,
        responsive: true
    });
    <?php endforeach; ?>
    
    // Calculate total rooms and beds for building creation
    function updateBuildingStats() {
        var floors = parseInt($('input[name="total_floors"]').val()) || 1;
        var roomsPerFloor = parseInt($('input[name="rooms_per_floor"]').val()) || 10;
        var capacityPerRoom = parseInt($('input[name="capacity_per_room"]').val()) || 4;
        
        var totalRooms = floors * roomsPerFloor;
        var totalBeds = totalRooms * capacityPerRoom;
        
        $('#totalRooms').text(totalRooms);
        $('#totalBeds').text(totalBeds);
    }
    
    $('input[name="total_floors"], input[name="rooms_per_floor"], input[name="capacity_per_room"]').on('input', updateBuildingStats);
    updateBuildingStats();
    
    // Handle view room modal
    $('#viewRoomModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var room = button.data('room');
        var students = button.data('students');
        var modal = $(this);
        
        var statusClass = '';
        switch (room.status) {
            case 'available': statusClass = 'badge bg-success'; break;
            case 'full': statusClass = 'badge bg-danger'; break;
            case 'maintenance': statusClass = 'badge bg-warning'; break;
        }
        
        var studentsHtml = '';
        if (students.length > 0) {
            students.forEach(function(student) {
                studentsHtml += `
                    <tr>
                        <td>${student.first_name} ${student.last_name}</td>
                        <td>${student.school_id}</td>
                        <td>${student.bed_number || 'Not assigned'}</td>
                    </tr>
                `;
            });
        } else {
            studentsHtml = '<tr><td colspan="3" class="text-center">No students assigned</td></tr>';
        }
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Room Information</h6>
                    <p><strong>Building:</strong> ${room.building_name}</p>
                    <p><strong>Room Number:</strong> ${room.room_number}</p>
                    <p><strong>Floor:</strong> ${room.floor_number}</p>
                    <p><strong>Status:</strong> <span class="${statusClass}">${room.status}</span></p>
                </div>
                <div class="col-md-6">
                    <h6>Capacity Information</h6>
                    <p><strong>Total Beds:</strong> ${room.total_beds}</p>
                    <p><strong>Occupied:</strong> ${room.occupied_beds}</p>
                    <p><strong>Available:</strong> ${room.total_beds - room.occupied_beds}</p>
                    <p><strong>Occupancy Rate:</strong> ${Math.round((room.occupied_beds / room.total_beds) * 100)}%</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Assigned Students</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>School ID</th>
                                    <th>Bed Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${studentsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        modal.find('#roomDetails').html(content);
    });
    
    // Handle update capacity modal
    $('#updateCapacityModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var roomId = button.data('room-id');
        var roomCapacity = button.data('room-capacity');
        
        $('#updateCapacityRoomId').val(roomId);
        $('#updateCapacityValue').val(roomCapacity);
    });
    
    // Handle update status modal
    $('#updateStatusModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var roomId = button.data('room-id');
        var roomStatus = button.data('room-status');
        
        $('#updateRoomId').val(roomId);
        $('#updateRoomStatus').val(roomStatus);
    });
});

function filterRooms(buildingId, status) {
    var table = $('#roomsTable-' + buildingId).DataTable();
    
    if (status === 'all') {
        table.search('').draw();
    } else {
        table.column(5).search(status).draw();
    }
}

function viewRoomStudents(roomId, roomNumber) {
    // This would typically load students via AJAX
    // For now, we'll show a placeholder
    $('#studentsModalTitle').text('Students in Room ' + roomNumber);
    $('#studentsModalBody').html('<p class="text-center">Loading students...</p>');
    $('#studentsModal').modal('show');
}

function deleteRoom(roomId, roomNumber) {
    if (confirm('Are you sure you want to delete room ' + roomNumber + '? This action cannot be undone.')) {
        $('#deleteRoomId').val(roomId);
        $('#deleteRoomForm').submit();
    }
}

// Handle edit building modal
$('#editBuildingModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var building = button.data('building');
    var modal = $(this);
    
    modal.find('#editBuildingId').val(building.id);
    modal.find('#editBuildingName').val(building.name);
    modal.find('#editBuildingDescription').val(building.description);
    modal.find('#editBuildingFloors').val(building.total_floors);
});

// Handle delete building
function deleteBuilding(buildingId, buildingName) {
    $('#deleteBuildingId').val(buildingId);
    $('#deleteBuildingName').text(buildingName);
    $('#deleteBuildingModal').modal('show');
}
</script>

<style>
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card h3 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stats-card p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.9;
}

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    border-color: #e9ecef;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
}

.modal-header .btn-close {
    filter: invert(1);
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
    border-radius: 20px;
}

.nav-tabs .nav-link {
    border-radius: 10px 10px 0 0;
    margin-right: 5px;
}

.nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

@media (max-width: 768px) {
    .stats-card h3 {
        font-size: 2rem;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}

@media (max-width: 576px) {
    .col-md-2, .col-md-3, .col-md-4 {
        margin-bottom: 0.5rem;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
}
</style>

<?php include 'includes/footer.php'; ?>