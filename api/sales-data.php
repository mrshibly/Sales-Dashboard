<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/Customer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Permission check for GET (all roles can view sales data)
if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: You do not have permission to view sales data.']);
    exit;
}

$current_user_id = $auth->get_current_user_id();
$current_user_role = $auth->get_current_user_role();
$current_user_scope = $auth->get_current_user_geographic_scope();

$where_clause = '';
$bind_params = [];

switch ($current_user_role) {
    case 'SR':
        $where_clause = " AND o.sales_rep_id = :user_id";
        $bind_params[':user_id'] = $current_user_id;
        break;
    case 'TSM':
        $where_clause = " AND c.territory_id IN (SELECT id FROM territories WHERE upazila_id = :upazila_id)";
        $bind_params[':upazila_id'] = $current_user_scope['upazila_id'];
        break;
    case 'ASM':
        $where_clause = " AND c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE up.district_id = :district_id)";
        $bind_params[':district_id'] = $current_user_scope['district_id'];
        break;
    case 'DSM':
        $where_clause = " AND c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE d.division_id = :division_id)";
        $bind_params[':division_id'] = $current_user_scope['division_id'];
        break;
    case 'NSM':
    case 'HOM':
        // No geographic restrictions
        break;
}

$query = "SELECT DATE(order_date) as date, SUM(oi.quantity * oi.price) as total_sales 
          FROM orders o 
          JOIN order_items oi ON o.id = oi.order_id 
          JOIN customers c ON o.customer_id = c.id 
          WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) " . $where_clause . " 
          GROUP BY DATE(order_date) 
          ORDER BY DATE(order_date) ASC";

$stmt = $db->prepare($query);
foreach ($bind_params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>