<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Permission check for GET (all roles can view leaderboard data)
if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: You do not have permission to view leaderboard data.']);
    exit;
}

$current_user_id = $auth->get_current_user_id();
$current_user_role = $auth->get_current_user_role();
$current_user_scope = $auth->get_current_user_geographic_scope();

$period = isset($_GET['period']) ? $_GET['period'] : 'all-time';

$where_clause_orders = '';
$where_clause_targets = '';
$bind_params_orders = [];
$bind_params_targets = [];

switch ($period) {
    case 'weekly':
        $where_clause_orders = 'AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
        $where_clause_targets = 'AND t.month >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
        break;
    case 'monthly':
        $where_clause_orders = 'AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
        $where_clause_targets = 'AND t.month >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
        break;
}

// Add geographic filtering based on user role
switch ($current_user_role) {
    case 'SR':
        $where_clause_orders .= " AND o.sales_rep_id = :user_id";
        $bind_params_orders[':user_id'] = $current_user_id;
        $where_clause_targets .= " AND t.user_id = :user_id";
        $bind_params_targets[':user_id'] = $current_user_id;
        break;
    case 'TSM':
        $where_clause_orders .= " AND c.territory_id IN (SELECT id FROM territories WHERE upazila_id = :upazila_id)";
        $bind_params_orders[':upazila_id'] = $current_user_scope['upazila_id'];
        $where_clause_targets .= " AND t.user_id IN (SELECT id FROM users WHERE upazila_id = :upazila_id)";
        $bind_params_targets[':upazila_id'] = $current_user_scope['upazila_id'];
        break;
    case 'ASM':
        $where_clause_orders .= " AND c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE up.district_id = :district_id)";
        $bind_params_orders[':district_id'] = $current_user_scope['district_id'];
        $where_clause_targets .= " AND t.user_id IN (SELECT id FROM users WHERE district_id = :district_id)";
        $bind_params_targets[':district_id'] = $current_user_scope['district_id'];
        break;
    case 'DSM':
        $where_clause_orders .= " AND c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE d.division_id = :division_id)";
        $bind_params_orders[':division_id'] = $current_user_scope['division_id'];
        $where_clause_targets .= " AND t.user_id IN (SELECT id FROM users WHERE division_id = :division_id)";
        $bind_params_targets[':division_id'] = $current_user_scope['division_id'];
        break;
    case 'NSM':
    case 'HOM':
        // No geographic restrictions
        break;
}

// Fetch data for sales leaderboard
$query_sales_leaderboard = "SELECT u.name, SUM(oi.quantity * oi.price) as total_sales FROM users u JOIN orders o ON u.id = o.sales_rep_id JOIN order_items oi ON o.id = oi.order_id JOIN customers c ON o.customer_id = c.id WHERE 1=1 " . $where_clause_orders . " GROUP BY u.name ORDER BY total_sales DESC LIMIT 10";
$stmt_sales_leaderboard = $db->prepare($query_sales_leaderboard);
foreach ($bind_params_orders as $key => $value) {
    $stmt_sales_leaderboard->bindValue($key, $value);
}
$stmt_sales_leaderboard->execute();
$sales_leaderboard = $stmt_sales_leaderboard->fetchAll(PDO::FETCH_ASSOC);

// Fetch data for target leaderboard
$query_target_leaderboard = "SELECT u.name, (SUM(t.achieved_amount) / SUM(t.target_amount)) * 100 as target_completion FROM users u JOIN targets t ON u.id = t.user_id WHERE 1=1 " . $where_clause_targets . " GROUP BY u.name ORDER BY target_completion DESC LIMIT 10";
$stmt_target_leaderboard = $db->prepare($query_target_leaderboard);
foreach ($bind_params_targets as $key => $value) {
    $stmt_target_leaderboard->bindValue($key, $value);
}
$stmt_target_leaderboard->execute();
$target_leaderboard = $stmt_target_leaderboard->fetchAll(PDO::FETCH_ASSOC);

// Fetch data for order count leaderboard
$query_order_leaderboard = "SELECT u.name, COUNT(o.id) as order_count FROM users u JOIN orders o ON u.id = o.sales_rep_id JOIN customers c ON o.customer_id = c.id WHERE 1=1 " . $where_clause_orders . " GROUP BY u.name ORDER BY order_count DESC LIMIT 10";
$stmt_order_leaderboard = $db->prepare($query_order_leaderboard);
foreach ($bind_params_orders as $key => $value) {
    $stmt_order_leaderboard->bindValue($key, $value);
}
$stmt_order_leaderboard->execute();
$order_leaderboard = $stmt_order_leaderboard->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'sales_leaderboard' => $sales_leaderboard,
    'target_leaderboard' => $target_leaderboard,
    'order_leaderboard' => $order_leaderboard
]);
?>