<?php
session_start();

require_once 'config/constants.php';
require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if the user is logged in, otherwise redirect to login page
$auth->require_login();

// Simple router
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url_parts = explode('/', $url);

// Define accessible pages
$pages = ['dashboard', 'customers', 'orders', 'products', 'targets', 'analytics', 'leaderboard', 'settings', 'user_management'];

$page = !empty($url_parts[0]) ? $url_parts[0] : 'dashboard';

// Check if the requested page is valid
if (!in_array($page, $pages)) {
    http_response_code(404);
    include('pages/404.php'); // You can create a 404 page
    exit();
}

// Include the main layout
include('layouts/header.php');
include('layouts/sidebar.php');

// Include the requested page content
echo '<main class="main-content">';
include('pages/' . $page . '.php');
echo '</main>';

include('layouts/footer.php');
