<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['id'];
$user->readOne();

?>

<header class="main-header">
    <div>
        <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
        <p>Your role: <?php echo $_SESSION['role']; ?></p>
    </div>
    <div class="header-avatar">
        <?php if ($user->avatar): ?>
            <img src="uploads/avatars/<?php echo htmlspecialchars($user->avatar); ?>" alt="User Avatar">
        <?php else: ?>
            <img src="uploads/avatars/image.png" alt="Default Avatar">
        <?php endif; ?>
    </div>
</header>

<section class="dashboard-metrics">
    <div class="row">
        <div class="col-md-4">
            <div class="metric-card">
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="info">
                    <h3>Total Sales</h3>
                    <p id="total-sales">$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="info">
                    <h3>Total Orders</h3>
                    <p id="total-orders">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card">
                <div class="icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="info">
                    <h3>Target Progress</h3>
                    <p id="target-progress">0%</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Sales Trends (Last 30 Days)
            </div>
            <div class="card-body">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                Real-Time Activity Feed
            </div>
            <div class="card-body activity-feed">
                <!-- Activity items will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div>

<script type="module" src="/sales_dashboard_php/assets/js/utils.js?v=<?php echo time(); ?>"></script>
<script type="module" src="/sales_dashboard_php/assets/js/dashboard.js?v=<?php echo time(); ?>"></script>