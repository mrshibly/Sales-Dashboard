<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/Database.php';
include_once '../classes/Auth.php';
include_once '../classes/Customer.php'; // Needed for sales/order queries
include_once '../classes/User.php';     // Needed for target queries

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// General permission check for dashboard data
if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
    http_response_code(403);
    echo json_encode(array("message" => "Forbidden: You do not have permission to view dashboard data."));
    exit;
}

$current_user_id = $auth->get_current_user_id();
$current_user_role = $auth->get_current_user_role();
$current_user_scope = $auth->get_current_user_geographic_scope();

$data = json_decode(file_get_contents("php://input"));

$action = isset($_GET['action']) ? $_GET['action'] : '';

$where_clause_sales_orders = '';
$where_clause_targets = '';
$bind_params_sales_orders = [];
$bind_params_targets = [];

switch ($current_user_role) {
    case 'SR':
        $where_clause_sales_orders = " AND o.sales_rep_id = :user_id";
        $bind_params_sales_orders[':user_id'] = $current_user_id;
        $where_clause_targets = " AND t.user_id = :user_id";
        $bind_params_targets[':user_id'] = $current_user_id;
        break;
    case 'TSM':
        $where_clause_sales_orders = " AND c.territory_id IN (SELECT id FROM territories WHERE upazila_id = :upazila_id)";
        $bind_params_sales_orders[':upazila_id'] = $current_user_scope['upazila_id'];
        $where_clause_targets = " AND t.user_id IN (SELECT id FROM users WHERE upazila_id = :upazila_id)";
        $bind_params_targets[':upazila_id'] = $current_user_scope['upazila_id'];
        break;
    case 'ASM':
        $where_clause_sales_orders = " AND c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE up.district_id = :district_id)";
        $bind_params_sales_orders[':district_id'] = $current_user_scope['district_id'];
        $where_clause_targets = " AND t.user_id IN (SELECT id FROM users WHERE district_id = :district_id)";
        $bind_params_targets[':district_id'] = $current_user_scope['district_id'];
        break;
    case 'DSM':
        $where_clause_sales_orders = " AND c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE d.division_id = :division_id)";
        $bind_params_sales_orders[':division_id'] = $current_user_scope['division_id'];
        $where_clause_targets = " AND t.user_id IN (SELECT id FROM users WHERE division_id = :division_id)";
        $bind_params_targets[':division_id'] = $current_user_scope['division_id'];
        break;
    case 'NSM':
    case 'HOM':
        // No geographic restrictions
        break;
}

switch ($action) {
    case 'sales_summary':
        $query = "SELECT SUM(oi.quantity * oi.price) as total_sales FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN customers c ON o.customer_id = c.id WHERE 1=1 " . $where_clause_sales_orders;
        $stmt = $db->prepare($query);
        foreach ($bind_params_sales_orders as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(array("total_sales" => $row['total_sales']));
        break;

    case 'order_count':
        $query = "SELECT COUNT(o.id) as total_orders FROM orders o JOIN customers c ON o.customer_id = c.id WHERE 1=1 " . $where_clause_sales_orders;
        $stmt = $db->prepare($query);
        foreach ($bind_params_sales_orders as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(array("total_orders" => $row['total_orders']));
        break;

    case 'target_progress':
        $query = "SELECT SUM(target_amount) as total_target, SUM(achieved_amount) as total_achieved FROM targets t JOIN users u ON t.user_id = u.id WHERE 1=1 " . $where_clause_targets;
        $stmt = $db->prepare($query);
        foreach ($bind_params_targets as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $progress = ($row['total_target'] > 0) ? ($row['total_achieved'] / $row['total_target']) * 100 : 0;
        echo json_encode(array("target_progress" => round($progress, 2)));
        break;

    default:
        http_response_code(400);
        echo json_encode(array("message" => "Invalid action."));
        break;
}
?>