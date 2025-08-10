<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}
?>

<header class="main-header">
    <h1>Leaderboard</h1>
</header>

<div class="d-flex justify-content-end mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary active" data-period="weekly">Weekly</button>
        <button type="button" class="btn btn-primary" data-period="monthly">Monthly</button>
        <button type="button" class="btn btn-primary" data-period="all-time">All-Time</button>
    </div>
</div>

<div class="leaderboard-grid">
    <div id="sales-leaderboard" class="leaderboard-card">
        <div class="card-header">Top Sales Representatives by Revenue</div>
        <div class="card-body">
            <!-- Sales leaderboard content will be loaded here via AJAX -->
        </div>
    </div>

    <div id="target-leaderboard" class="leaderboard-card">
        <div class="card-header">Top Sales Representatives by Target Completion</div>
        <div class="card-body">
            <!-- Target leaderboard content will be loaded here via AJAX -->
        </div>
    </div>

    <div id="order-leaderboard" class="leaderboard-card">
        <div class="card-header">Top Sales Representatives by Order Count</div>
        <div class="card-body">
            <!-- Order leaderboard content will be loaded here via AJAX -->
        </div>
    </div>
</div>