<?php
require_once '../config/database.php';
requireAdmin();

$pdo = getConnection();

function tableExists($pdo, $table) {
	try {
		$table = str_replace('`', '', $table);
		$stmt = $pdo->query("SHOW TABLES LIKE '" . addslashes($table) . "'");
		return $stmt && $stmt->rowCount() > 0;
	} catch (Exception $e) {
		return false;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	try {
		switch ($_POST['action']) {
			case 'flush_offenses':
				if (tableExists($pdo, 'offenses')) { $pdo->exec("DELETE FROM offenses"); }
				if (tableExists($pdo, 'offense_logs')) { $pdo->exec("DELETE FROM offense_logs"); }
				$_SESSION['success'] = 'Offense data cleared.';
				break;
			case 'flush_complaints':
				if (tableExists($pdo, 'offenses')) { $pdo->exec("UPDATE offenses SET complaint_id = NULL"); }
				if (tableExists($pdo, 'complaints')) { $pdo->exec("DELETE FROM complaints"); }
				$_SESSION['success'] = 'Complaints data cleared.';
				break;
			case 'flush_announcements':
				$aux = ['announcement_comment_likes','announcement_comment_replies','announcement_comments','announcement_interactions','announcement_likes','announcement_views'];
				foreach ($aux as $t) { if (tableExists($pdo, $t)) { $pdo->exec("DELETE FROM `{$t}`"); } }
				if (tableExists($pdo, 'announcements')) { $pdo->exec("DELETE FROM announcements"); }
				$_SESSION['success'] = 'Announcements and related data cleared.';
				break;
			case 'flush_visitor_logs':
				if (tableExists($pdo, 'visitor_logs')) { $pdo->exec("DELETE FROM visitor_logs"); }
				$_SESSION['success'] = 'Visitor logs cleared.';
				break;
			case 'flush_maintenance':
				if (tableExists($pdo, 'maintenance_requests')) { $pdo->exec("DELETE FROM maintenance_requests"); }
				$_SESSION['success'] = 'Maintenance requests cleared.';
				break;
			case 'flush_room_requests':
				if (tableExists($pdo, 'room_change_requests')) { $pdo->exec("DELETE FROM room_change_requests"); }
				$_SESSION['success'] = 'Room change requests cleared.';
				break;
			case 'flush_location_logs':
				if (tableExists($pdo, 'student_location_logs')) { $pdo->exec("DELETE FROM student_location_logs"); }
				$_SESSION['success'] = 'Student location logs cleared.';
				break;
			case 'flush_biometric_files':
				if (tableExists($pdo, 'biometric_files')) { $pdo->exec("DELETE FROM biometric_files"); }
				$_SESSION['success'] = 'Biometric files records cleared.';
				break;
		}
	} catch (Exception $e) {
		$_SESSION['error'] = 'Error: ' . $e->getMessage();
	}
	header('Location: data_flush_tools.php');
	exit;
}

$page_title = 'Data Flush Tools';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h2><i class="fas fa-broom"></i> Data Flush Tools</h2>
	<div class="text-muted">Admin-only, permanently deletes selected module data</div>
</div>

<?php if (isset($_SESSION['success'])): ?>
	<div class="alert alert-success alert-dismissible fade show" role="alert">
		<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
	</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
	<div class="alert alert-danger alert-dismissible fade show" role="alert">
		<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
	</div>
<?php endif; ?>

<div class="row g-4">
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Offense Logs</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all rows from <code>offenses</code> and legacy <code>offense_logs</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all offense data?');">
					<input type="hidden" name="action" value="flush_offenses">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Offenses</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Complaints</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Nulls offense links, then deletes all <code>complaints</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all complaints?');">
					<input type="hidden" name="action" value="flush_complaints">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Complaints</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Announcements</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all <code>announcements</code> and related analytics/comments tables.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all announcements and related data?');">
					<input type="hidden" name="action" value="flush_announcements">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Announcements</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Visitor Logs</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all rows from <code>visitor_logs</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all visitor logs?');">
					<input type="hidden" name="action" value="flush_visitor_logs">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Visitor Logs</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Maintenance Requests</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all rows from <code>maintenance_requests</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all maintenance requests?');">
					<input type="hidden" name="action" value="flush_maintenance">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Maintenance</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Room Change Requests</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all rows from <code>room_change_requests</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all room change requests?');">
					<input type="hidden" name="action" value="flush_room_requests">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Room Requests</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Student Location Logs</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all rows from <code>student_location_logs</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all student location logs?');">
					<input type="hidden" name="action" value="flush_location_logs">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Location Logs</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card h-100">
			<div class="card-header"><strong>Biometric Files</strong></div>
			<div class="card-body">
				<p class="text-muted mb-3">Deletes all rows from <code>biometric_files</code>.</p>
				<form method="POST" onsubmit="return confirm('Permanently delete all biometric file records?');">
					<input type="hidden" name="action" value="flush_biometric_files">
					<button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt"></i> Flush Biometric Files</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include 'includes/footer.php'; ?>


