<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}

require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$current_user_role = $auth->get_current_user_role();

?>

<header class="main-header">
    <h1>Product Management</h1>
    <?php if ($current_user_role === 'HOM'): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" aria-label="Add New Product">Add Product</button>
    <?php endif; ?>
    <button class="btn btn-info" id="exportProductsBtn" aria-label="Export Products to CSV">Export Products</button>
</header>

<div class="filter-section mb-3">
    <div class="row g-3">
        <div class="col-md-6">
            <input type="text" id="searchProduct" class="form-control" placeholder="Search by Product Name...">
        </div>
        <div class="col-md-6">
            <input type="number" id="filterProductStock" class="form-control" placeholder="Filter by Stock (min)">
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="productTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="productTableBody"></tbody>
    </table>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Enter product name">
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required placeholder="Enter price">
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required placeholder="Enter stock quantity">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
    const currentUserRole = "<?php echo $current_user_role; ?>";
</script>