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
    <h1>User Management</h1>
    <?php if ($current_user_role === 'HOM'): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" aria-label="Add New User">Add User</button>
    <?php endif; ?>
    <button class="btn btn-info" id="exportUsersBtn" aria-label="Export Users to CSV">Export Users</button>
</header>

<div class="filter-section mb-3">
    <div class="row g-3">
        <div class="col-md-3">
            <input type="text" id="searchUser" class="form-control" placeholder="Search by Name, Email...">
        </div>
        <div class="col-md-3">
            <select id="filterUserRole" class="form-select">
                <option value="">Filter by Role</option>
                <option value="SR">Sales Representative (SR)</option>
                <option value="TSM">Territory Sales Manager (TSM)</option>
                <option value="ASM">Area Sales Manager (ASM)</option>
                <option value="DSM">Divisional Sales Manager (DSM)</option>
                <option value="NSM">National Sales Manager (NSM)</option>
                <option value="HOM">Head of Marketing (HOM)</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterUserDivision" class="form-select">
                <option value="">Filter by Division</option>
                <?php foreach ($divisions as $div): ?>
                    <option value="<?php echo $div['id']; ?>"><?php echo $div['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterUserDistrict" class="form-select">
                <option value="">Filter by District</option>
            </select>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="userTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Reports To</th>
                <th>Division</th>
                <th>District</th>
                <th>Upazila</th>
                <th>Territory</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="userTableBody"></tbody>
    </table>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Enter user's full name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Enter user's email address">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password (leave blank to keep current)">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="SR">Sales Representative (SR)</option>
                            <option value="TSM">Territory Sales Manager (TSM)</option>
                            <option value="ASM">Area Sales Manager (ASM)</option>
                            <option value="DSM">Divisional Sales Manager (DSM)</option>
                            <option value="NSM">National Sales Manager (NSM)</option>
                            <option value="HOM">Head of Marketing (HOM)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reports_to" class="form-label">Reports To (User ID)</label>
                        <input type="number" class="form-control" id="reports_to" name="reports_to" placeholder="Enter ID of manager (optional)">
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

                    <button type="submit" class="btn btn-primary">Save User</button>
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
</script>