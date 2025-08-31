<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$error = '';

// Handle building operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_building') {
            $building_name = sanitizeInput($_POST['building_name']);
            $building_address = sanitizeInput($_POST['building_address']);
            $total_floors = (int)$_POST['total_floors'];
            $description = sanitizeInput($_POST['description']);
            
            if (empty($building_name) || empty($building_address)) {
                $error = 'Building name and address are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("INSERT INTO buildings (building_name, building_address, total_floors, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$building_name, $building_address, $total_floors, $description]);
                    
                    $message = 'Building added successfully!';
                    logActivity($_SESSION['user_id'], 'Building added: ' . $building_name);
                } catch (PDOException $e) {
                    $error = 'Error adding building: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit_building') {
            $building_id = (int)$_POST['building_id'];
            $building_name = sanitizeInput($_POST['building_name']);
            $building_address = sanitizeInput($_POST['building_address']);
            $total_floors = (int)$_POST['total_floors'];
            $description = sanitizeInput($_POST['description']);
            
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("UPDATE buildings SET building_name = ?, building_address = ?, total_floors = ?, description = ?, updated_at = NOW() WHERE building_id = ?");
                $stmt->execute([$building_name, $building_address, $total_floors, $description, $building_id]);
                
                                    $message = 'Building updated successfully!';
                    logActivity($_SESSION['user_id'], 'Building updated: ' . $building_name);
            } catch (PDOException $e) {
                $error = 'Error updating building: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_building') {
            $building_id = (int)$_POST['building_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Check if building has rooms
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE building_id = ?");
                $stmt->execute([$building_id]);
                $roomCount = $stmt->fetchColumn();
                
                if ($roomCount > 0) {
                    $error = 'Cannot delete building with existing rooms. Please delete all rooms first.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM buildings WHERE building_id = ?");
                    $stmt->execute([$building_id]);
                    
                    $message = 'Building deleted successfully!';
                    logActivity($_SESSION['user_id'], 'Building deleted: ID ' . $building_id);
                }
            } catch (PDOException $e) {
                $error = 'Error deleting building: ' . $e->getMessage();
            }
        } elseif ($action === 'add_room') {
            $building_id = (int)$_POST['building_id'];
            $room_number = sanitizeInput($_POST['room_number']);
            $floor = (int)$_POST['floor'];
            $capacity = (int)$_POST['capacity'];
            $room_type = sanitizeInput($_POST['room_type']);
            $description = sanitizeInput($_POST['description']);
            
            if (empty($room_number) || $capacity <= 0) {
                $error = 'Room number and capacity are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if room number already exists in this building
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE building_id = ? AND room_number = ?");
                    $stmt->execute([$building_id, $room_number]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Room number already exists in this building.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO rooms (building_id, room_number, floor, capacity, room_type, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$building_id, $room_number, $floor, $capacity, $room_type, $description]);
                        
                        $room_id = $pdo->lastInsertId();
                        
                        // Auto-create bedspaces
                        for ($i = 1; $i <= $capacity; $i++) {
                            $bedspace_label = "Bed " . $i;
                            $stmt = $pdo->prepare("INSERT INTO bedspaces (room_id, bedspace_label, status, created_at) VALUES (?, ?, 'available', NOW())");
                            $stmt->execute([$room_id, $bedspace_label]);
                        }
                        
                        $message = 'Room added successfully with ' . $capacity . ' bedspaces!';
                        logActivity($_SESSION['user_id'], 'Room added: ' . $room_number . ' in Building ID ' . $building_id);
                    }
                } catch (PDOException $e) {
                    $error = 'Error adding room: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit_room') {
            $room_id = (int)$_POST['room_id'];
            $room_number = sanitizeInput($_POST['room_number']);
            $floor = (int)$_POST['floor'];
            $capacity = (int)$_POST['capacity'];
            $room_type = sanitizeInput($_POST['room_type']);
            $description = sanitizeInput($_POST['description']);
            
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, floor = ?, capacity = ?, room_type = ?, description = ?, updated_at = NOW() WHERE room_id = ?");
                $stmt->execute([$room_number, $floor, $capacity, $room_type, $description, $room_id]);
                
                                    $message = 'Room updated successfully!';
                    logActivity($_SESSION['user_id'], 'Room updated: ' . $room_number);
            } catch (PDOException $e) {
                $error = 'Error updating room: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_room') {
            $room_id = (int)$_POST['room_id'];
            
            try {
                $pdo = getDBConnection();
                
                // Check if room has occupied bedspaces
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bedspaces WHERE room_id = ? AND status = 'occupied'");
                $stmt->execute([$room_id]);
                $occupiedCount = $stmt->fetchColumn();
                
                if ($occupiedCount > 0) {
                    $error = 'Cannot delete room with occupied bedspaces. Please check out all students first.';
                } else {
                    // Delete bedspaces first
                    $stmt = $pdo->prepare("DELETE FROM bedspaces WHERE room_id = ?");
                    $stmt->execute([$room_id]);
                    
                    // Delete room
                    $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
                    $stmt->execute([$room_id]);
                    
                    $message = 'Room deleted successfully!';
                    logActivity($_SESSION['user_id'], 'Room deleted: ID ' . $room_id);
                }
            } catch (PDOException $e) {
                $error = 'Error deleting room: ' . $e->getMessage();
            }
        }
    }
}

// Fetch buildings with room counts
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT b.*, 
               COUNT(r.room_id) as room_count,
               SUM(CASE WHEN r.room_id IS NOT NULL THEN r.capacity ELSE 0 END) as total_capacity
        FROM buildings b 
        LEFT JOIN rooms r ON b.building_id = r.building_id 
        GROUP BY b.building_id 
        ORDER BY b.building_name
    ");
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching buildings: ' . $e->getMessage();
    $buildings = [];
}

// Fetch rooms with building info and occupancy
try {
    $stmt = $pdo->query("
        SELECT r.*, b.building_name,
               COUNT(bs.bedspace_id) as total_bedspaces,
               SUM(CASE WHEN bs.status = 'occupied' THEN 1 ELSE 0 END) as occupied_bedspaces,
               SUM(CASE WHEN bs.status = 'reserved' THEN 1 ELSE 0 END) as reserved_bedspaces,
               SUM(CASE WHEN bs.status = 'available' THEN 1 ELSE 0 END) as available_bedspaces
        FROM rooms r 
        JOIN buildings b ON r.building_id = b.building_id
        LEFT JOIN bedspaces bs ON r.room_id = bs.room_id
        GROUP BY r.room_id 
        ORDER BY b.building_name, r.room_number
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching rooms: ' . $e->getMessage();
    $rooms = [];
}

$page_title = "Buildings & Room Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-building me-2"></i>Buildings & Room Management
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                    <i class="fas fa-plus me-2"></i>Add Building
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Buildings Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building me-2"></i>Buildings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="buildingsTable">
                            <thead>
                                <tr>
                                    <th>Building Name</th>
                                    <th>Address</th>
                                    <th>Floors</th>
                                    <th>Rooms</th>
                                    <th>Total Capacity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buildings as $building): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($building['building_name']); ?></strong>
                                            <?php if ($building['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($building['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($building['building_address']); ?></td>
                                        <td><?php echo $building['total_floors']; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $building['room_count']; ?> rooms</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $building['total_capacity']; ?> beds</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal" 
                                                        data-building-id="<?php echo $building['building_id']; ?>"
                                                        data-building-name="<?php echo htmlspecialchars($building['building_name']); ?>">
                                                    <i class="fas fa-plus"></i> Add Room
                                                </button>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBuildingModal"
                                                        data-building='<?php echo json_encode($building); ?>'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteBuilding(<?php echo $building['building_id']; ?>, '<?php echo htmlspecialchars($building['building_name']); ?>')">
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
            </div>

            <!-- Rooms Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-door-open me-2"></i>Rooms
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="roomsTable">
                            <thead>
                                <tr>
                                    <th>Building</th>
                                    <th>Room Number</th>
                                    <th>Floor</th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th>Occupancy</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['building_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($room['room_number']); ?></strong>
                                            <?php if ($room['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($room['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $room['floor']; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($room['room_type']); ?></span>
                                        </td>
                                        <td><?php echo $room['total_capacity']; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="badge bg-success mb-1"><?php echo $room['available_bedspaces']; ?> Available</span>
                                                <span class="badge bg-warning mb-1"><?php echo $room['reserved_bedspaces']; ?> Reserved</span>
                                                <span class="badge bg-danger"><?php echo $room['occupied_bedspaces']; ?> Occupied</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-info" onclick="viewOccupants(<?php echo $room['room_id']; ?>)">
                                                    <i class="fas fa-users"></i> View Occupants
                                                </button>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editRoomModal"
                                                        data-room='<?php echo json_encode($room); ?>'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteRoom(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')">
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
            </div>
        </div>
    </div>
</div>

<!-- Add Building Modal -->
<div class="modal fade" id="addBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_building">
                    
                    <div class="mb-3">
                        <label for="building_name" class="form-label">Building Name *</label>
                        <input type="text" class="form-control" id="building_name" name="building_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="building_address" class="form-label">Address *</label>
                        <textarea class="form-control" id="building_address" name="building_address" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="total_floors" class="form-label">Total Floors</label>
                        <input type="number" class="form-control" id="total_floors" name="total_floors" value="1" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Building Modal -->
<div class="modal fade" id="editBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_building">
                    <input type="hidden" name="building_id" id="edit_building_id">
                    
                    <div class="mb-3">
                        <label for="edit_building_name" class="form-label">Building Name *</label>
                        <input type="text" class="form-control" id="edit_building_name" name="building_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_building_address" class="form-label">Address *</label>
                        <textarea class="form-control" id="edit_building_address" name="building_address" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_total_floors" class="form-label">Total Floors</label>
                        <input type="number" class="form-control" id="edit_total_floors" name="total_floors" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
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

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_room">
                    <input type="hidden" name="building_id" id="add_room_building_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Building</label>
                        <input type="text" class="form-control" id="add_room_building_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number *</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="floor" class="form-label">Floor</label>
                        <input type="number" class="form-control" id="floor" name="floor" value="1" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity (Bedspaces) *</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" value="4" min="1" max="8">
                        <small class="form-text text-muted">Default is 4 bedspaces per room</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="room_type" class="form-label">Room Type</label>
                        <select class="form-control" id="room_type" name="room_type">
                            <option value="Standard">Standard</option>
                            <option value="Premium">Premium</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_room">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    
                    <div class="mb-3">
                        <label for="edit_room_number" class="form-label">Room Number *</label>
                        <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_floor" class="form-label">Floor</label>
                        <input type="number" class="form-control" id="edit_floor" name="floor" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacity (Bedspaces) *</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" max="8">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_room_type" class="form-label">Room Type</label>
                        <select class="form-control" id="edit_room_type" name="room_type">
                            <option value="Standard">Standard</option>
                            <option value="Premium">Premium</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Occupants Modal -->
<div class="modal fade" id="viewOccupantsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Occupants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="occupantsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Forms -->
<form id="deleteBuildingForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_building">
    <input type="hidden" name="building_id" id="delete_building_id">
</form>

<form id="deleteRoomForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_room">
    <input type="hidden" name="room_id" id="delete_room_id">
</form>

<?php include 'includes/footer.php'; ?>

<script>
// Initialize DataTables
$(document).ready(function() {
    $('#buildingsTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 10,
        responsive: true
    });
    
    $('#roomsTable').DataTable({
        order: [[0, 'asc'], [1, 'asc']],
        pageLength: 15,
        responsive: true
    });
});

// Handle Add Room Modal
$('#addRoomModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const buildingId = button.data('building-id');
    const buildingName = button.data('building-name');
    
    $('#add_room_building_id').val(buildingId);
    $('#add_room_building_name').val(buildingName);
});

// Handle Edit Building Modal
$('#editBuildingModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const building = button.data('building');
    
    $('#edit_building_id').val(building.building_id);
    $('#edit_building_name').val(building.building_name);
    $('#edit_building_address').val(building.building_address);
    $('#edit_total_floors').val(building.total_floors);
    $('#edit_description').val(building.description);
});

// Handle Edit Room Modal
$('#editRoomModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const room = button.data('room');
    
    $('#edit_room_id').val(room.room_id);
    $('#edit_room_number').val(room.room_number);
    $('#edit_floor').val(room.floor);
    $('#edit_capacity').val(room.total_capacity);
    $('#edit_room_type').val(room.room_type);
    $('#edit_description').val(room.description);
});

// Delete Building
function deleteBuilding(buildingId, buildingName) {
    if (confirm(`Are you sure you want to delete the building "${buildingName}"? This action cannot be undone.`)) {
        $('#delete_building_id').val(buildingId);
        $('#deleteBuildingForm').submit();
    }
}

// Delete Room
function deleteRoom(roomId, roomNumber) {
    if (confirm(`Are you sure you want to delete room "${roomNumber}"? This action cannot be undone.`)) {
        $('#delete_room_id').val(roomId);
        $('#deleteRoomForm').submit();
    }
}

// View Occupants
function viewOccupants(roomId) {
    // Load occupants data via AJAX
    $.get(`get_room_occupants.php?room_id=${roomId}`, function(data) {
        $('#occupantsContent').html(data);
        $('#viewOccupantsModal').modal('show');
    });
}
</script>
