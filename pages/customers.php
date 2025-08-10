<?php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Database.php';
require_once 'classes/Division.php';
require_once 'classes/District.php';
require_once 'classes/Upazila.php';
require_once 'classes/Territory.php';

$database = new Database();
$db = $database->getConnection();

$query_all_users = "SELECT id, name FROM users ORDER BY name ASC";
$stmt_all_users = $db->prepare($query_all_users);
$stmt_all_users->execute();
$all_users = $stmt_all_users->fetchAll(PDO::FETCH_ASSOC);

$query_sales_reps = "SELECT id, name FROM users WHERE role = 'SR'";
$stmt_sales_reps = $db->prepare($query_sales_reps);
$stmt_sales_reps->execute();
$sales_reps = $stmt_sales_reps->fetchAll(PDO::FETCH_ASSOC);

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
    <h1>Customer Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">Add Customer</button>
</header>

<div class="filter-section mb-3">
    <div class="row g-3">
        <div class="col-md-4">
            <input type="text" id="searchCustomer" class="form-control" placeholder="Search by Name, Email, Phone...">
        </div>
        <div class="col-md-4">
            <select id="filterSalesRep" class="form-select">
                <option value="">Filter by Sales Rep</option>
                <?php foreach ($all_users as $user_option): ?>
                    <option value="<?php echo $user_option['id']; ?>"><?php echo $user_option['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select id="filterTerritory" class="form-select">
                <option value="">Filter by Territory</option>
                <?php foreach ($territories as $terr): ?>
                    <option value="<?php echo $terr['id']; ?>"><?php echo $terr['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="customerTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Assigned Sales Rep</th>
                <th>Territory</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="customerTableBody"></tbody>
    </table>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <input type="hidden" id="customerId" name="customerId">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_sales_rep_id" class="form-label">Assigned Sales Representative</label>
                        <select class="form-select" id="assigned_sales_rep_id" name="assigned_sales_rep_id" required>
                            <?php foreach ($sales_reps as $rep): ?>
                                <option value="<?php echo $rep['id']; ?>"><?php echo $rep['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const allDistricts = <?php echo json_encode($districts); ?>;
    const allUpazilas = <?php echo json_encode($upazilas); ?>;
    const allTerritories = <?php echo json_encode($territories); ?>;
</script>
<script type="module" src="/sales_dashboard_php/assets/js/utils.js"></script>
<script src="/sales_dashboard_php/assets/js/customers.js" type="module"></script>