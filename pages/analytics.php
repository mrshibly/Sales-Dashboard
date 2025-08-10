<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}
?>

<header class="main-header">
    <h1>Sales Analytics</h1>
</header>

<div class="chart-container table-responsive">
    <canvas id="salesChart"></canvas>
</div>