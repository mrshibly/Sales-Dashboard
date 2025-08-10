<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Customer.php';
require_once 'classes/User.php';
require_once 'classes/Division.php';
require_once 'classes/District.php';
require_once 'classes/Upazila.php';
require_once 'classes/Territory.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all customers for dropdown
$customer = new Customer($db);
$stmt_customers = $customer->read();
$customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sales representatives for dropdown
$user = new User($db);
$query_sales_reps = "SELECT id, name FROM users WHERE role = 'SR'";
$stmt_sales_reps = $db->prepare($query_sales_reps);
$stmt_sales_reps->execute();
$sales_reps = $stmt_sales_reps->fetchAll(PDO::FETCH_ASSOC);

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
    <h1>Order Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal" aria-label="Add New Order">Add Order</button>
    <button class="btn btn-info" id="exportOrdersBtn" aria-label="Export Orders to CSV">Export Orders</button>
</header>

<div class="filter-section mb-3">
    <div class="row g-3">
        <div class="col-md-3">
            <input type="text" id="searchOrder" class="form-control" placeholder="Search by Customer/Sales Rep...">
        </div>
        <div class="col-md-3">
            <select id="filterOrderStatus" class="form-select">
                <option value="">Filter by Status</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Delivered">Delivered</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" id="filterOrderDateStart" class="form-control" title="Order Date Start">
        </div>
        <div class="col-md-3">
            <input type="date" id="filterOrderDateEnd" class="form-control" title="Order Date End">
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="orderTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Sales Rep</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Territory</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="orderTableBody"></tbody>
    </table>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="orderForm">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <?php foreach ($customers as $cust): ?>
                                <option value="<?php echo $cust['id']; ?>"><?php echo $cust['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sales_rep_id" class="form-label">Sales Representative</label>
                        <select class="form-select" id="sales_rep_id" name="sales_rep_id" required>
                            <?php foreach ($sales_reps as $rep): ?>
                                <option value="<?php echo $rep['id']; ?>"><?php echo $rep['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="order_date" class="form-label">Order Date</label>
                        <input type="datetime-local" class="form-control" id="order_date" name="order_date" required placeholder="Select order date and time">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Delivered">Delivered</option>
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

                    <button type="submit" class="btn btn-primary">Save Order</button>
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
</script>

<script type="module" src="/sales_dashboard_php/assets/js/utils.js"></script>
<script type="module" src="/sales_dashboard_php/assets/js/orders.js"></script>
<script type="module" src="/sales_dashboard_php/assets/js/dashboard.js"></script>