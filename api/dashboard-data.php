<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';
require_once '../classes/Target.php';
require_once '../classes/User.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);
$target = new Target($db);
$user = new User($db);
$auth = new Auth($db);

// Permission check for GET (all roles can view dashboard data)
if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: You do not have permission to view dashboard data.']);
    exit;
}

$current_user_id = $auth->get_current_user_id();
$current_user_role = $auth->get_current_user_role();
$current_user_scope = $auth->get_current_user_geographic_scope();

$where_clause_sales = '';
$where_clause_targets = '';
$bind_params_sales = [];
$bind_params_targets = [];

switch ($current_user_role) {
    case 'SR':
        $where_clause_sales = " WHERE o.sales_rep_id = :user_id";
        $bind_params_sales[':user_id'] = $current_user_id;
        $where_clause_targets = " WHERE user_id = :user_id";
        $bind_params_targets[':user_id'] = $current_user_id;
        break;
    case 'TSM':
        $where_clause_sales = " WHERE c.territory_id IN (SELECT id FROM territories WHERE upazila_id = :upazila_id)";
        $bind_params_sales[':upazila_id'] = $current_user_scope['upazila_id'];
        $where_clause_targets = " WHERE user_id IN (SELECT id FROM users WHERE upazila_id = :upazila_id)";
        $bind_params_targets[':upazila_id'] = $current_user_scope['upazila_id'];
        break;
    case 'ASM':
        $where_clause_sales = " WHERE c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE up.district_id = :district_id)";
        $bind_params_sales[':district_id'] = $current_user_scope['district_id'];
        $where_clause_targets = " WHERE user_id IN (SELECT id FROM users WHERE district_id = :district_id)";
        $bind_params_targets[':district_id'] = $current_user_scope['district_id'];
        break;
    case 'DSM':
        $where_clause_sales = " WHERE c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE d.division_id = :division_id)";
        $bind_params_sales[':division_id'] = $current_user_scope['division_id'];
        $where_clause_targets = " WHERE user_id IN (SELECT id FROM users WHERE division_id = :division_id)";
        $bind_params_targets[':division_id'] = $current_user_scope['division_id'];
        break;
    case 'NSM':
    case 'HOM':
        // No geographic restrictions
        break;
}

// Get Total Sales
$query_total_sales = "SELECT SUM(oi.quantity * oi.price) as total_sales FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN customers c ON o.customer_id = c.id" . $where_clause_sales;
$stmt_total_sales = $db->prepare($query_total_sales);
foreach ($bind_params_sales as $key => $value) {
    $stmt_total_sales->bindValue($key, $value);
}
$stmt_total_sales->execute();
$total_sales = $stmt_total_sales->fetch(PDO::FETCH_ASSOC)['total_sales'];

// Get Total Orders
$query_total_orders = "SELECT COUNT(o.id) as total_orders FROM orders o JOIN customers c ON o.customer_id = c.id" . $where_clause_sales;
$stmt_total_orders = $db->prepare($query_total_orders);
foreach ($bind_params_sales as $key => $value) {
    $stmt_total_orders->bindValue($key, $value);
}
$stmt_total_orders->execute();
$total_orders = $stmt_total_orders->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Get Target Progress
$query_target_progress = "SELECT SUM(target_amount) as total_target, SUM(achieved_amount) as total_achieved FROM targets" . $where_clause_targets;
$stmt_target_progress = $db->prepare($query_target_progress);
foreach ($bind_params_targets as $key => $value) {
    $stmt_target_progress->bindValue($key, $value);
}
$stmt_target_progress->execute();
$target_data = $stmt_target_progress->fetch(PDO::FETCH_ASSOC);
$total_target = $target_data['total_target'];
$total_achieved = $target_data['total_achieved'];
$target_progress = ($total_target > 0) ? ($total_achieved / $total_target) * 100 : 0;

echo json_encode([
    'total_sales' => $total_sales,
    'total_orders' => $total_orders,
    'target_progress' => $target_progress
]);
?>