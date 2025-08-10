<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Division.php';
require_once 'classes/District.php';
require_once 'classes/Upazila.php';
require_once 'classes/Territory.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$current_user_role = $auth->get_current_user_role();
$current_user_id = $_SESSION['id'];

// Fetch all users for dropdown
$user = new User($db);
$query_users = "SELECT id, name FROM users";
$stmt_users = $db->prepare($query_users);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Fetch all geographic data
$division = new Division($db);
$district = new District($db);
$upazila = new Upazila($db);
$territory = new Territory($db);

$divisions = $division->read()->fetchAll(PDO::FETCH_ASSOC);
$districts = $district->read()->fetchAll(PDO::FETCH_ASSOC);
$upazilas = $upazila->read()->fetchAll(PDO::FETCH_ASSOC);
$territories = $territory->read()->fetchAll(PDO::FETCH_ASSOC);
?>

<header class="main-header">
    <h1>Target Management</h1>
    <?php if (in_array($current_user_role, ['HOM', 'NSM', 'DSM', 'ASM', 'TSM'])): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#targetModal" aria-label="Add New Target">Add Target</button>
    <?php endif; ?>
    <button class="btn btn-info" id="exportTargetsBtn" aria-label="Export Targets to CSV">Export Targets</button>
</header>

<div class="filter-section mb-3">
    <div class="row g-3">
        <div class="col-md-4">
            <input type="text" id="searchTargetUser" class="form-control" placeholder="Search by User Name...">
        </div>
        <div class="col-md-4">
            <input type="month" id="filterTargetMonth" class="form-control" title="Filter by Month">
        </div>
        <div class="col-md-4">
            <select id="filterTargetDivision" class="form-select">
                <option value="">Filter by Division</option>
                <?php foreach ($divisions as $div): ?>
                    <option value="<?php echo $div['id']; ?>"><?php echo $div['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="targetTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Month</th>
                <th>Target Amount</th>
                <th>Achieved Amount</th>
                <th>Geographic Scope</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="targetTableBody"></tbody>
    </table>
</div>

<!-- Target Modal -->
<div class="modal fade" id="targetModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="targetForm">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo $u['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="month" class="form-label">Month</label>
                        <input type="month" class="form-control" id="month" name="month" required placeholder="Select target month">
                    </div>
                    <div class="mb-3">
                        <label for="target_amount" class="form-label">Target Amount</label>
                        <input type="number" class="form-control" id="target_amount" name="target_amount" step="0.01" required placeholder="Enter target amount">
                    </div>
                    <div class="mb-3">
                        <label for="achieved_amount" class="form-label">Achieved Amount</label>
                        <input type="number" class="form-control" id="achieved_amount" name="achieved_amount" step="0.01" required placeholder="Enter achieved amount">
                    </div>

                    <div class="mb-3">
                        <label for="division_id" class="form-label">Division</label>
                        <select class="form-select" id="division_id" name="division_id">
                            <option value="">Select Division</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['id']; ?>"><?php echo $div['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="district_id" class="form-label">District</label>
                        <select class="form-select" id="district_id" name="district_id">
                            <option value="">Select District</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="upazila_id" class="form-label">Upazila</label>
                        <select class="form-select" id="upazila_id" name="upazila_id">
                            <option value="">Select Upazila</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="territory_id" class="form-label">Territory</label>
                        <select class="form-select" id="territory_id" name="territory_id">
                            <option value="">Select Territory</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Target</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
    const allDistricts = <?php echo json_encode($districts); ?>;
    const allUpazilas = <?php echo json_encode($upazilas); ?>;
    const allTerritories = <?php echo json_encode($territories); ?>;
    const currentUserRole = "<?php echo $current_user_role; ?>";
    const currentUserId = <?php echo $current_user_id; ?>;
</script>