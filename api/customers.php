<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Customer.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$customer = new Customer($db);
$auth = new Auth($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to view customers.']);
            exit;
        }

        if (isset($_GET['id'])) {
            $customer->id = $_GET['id'];
            $customer->readOne();
            echo json_encode($customer->customer_data());
        } else {
            $search_term = isset($_GET['search']) ? $_GET['search'] : '';
            $filter_sales_rep_id = isset($_GET['sales_rep_id']) ? $_GET['sales_rep_id'] : '';
            $filter_territory_id = isset($_GET['territory_id']) ? $_GET['territory_id'] : '';

            $stmt = $customer->read(
                $auth->get_current_user_role(),
                $auth->get_current_user_geographic_scope(),
                $auth->get_current_user_id(),
                $search_term,
                $filter_sales_rep_id,
                $filter_territory_id
            );
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($customers);
        }
        break;
    case 'POST':
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create customers.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));

        $customer->name = $data->name;
        $customer->phone = $data->phone;
        $customer->email = $data->email;
        $customer->address = $data->address;
        $customer->assigned_sales_rep_id = $data->assigned_sales_rep_id;
        $customer->territory_id = $data->territory_id ?? null;

        if ($customer->create()) {
            echo json_encode(['message' => 'Customer created successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create customer.']);
        }
        break;
    case 'PUT':
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update customers.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $customer->id = $_GET['id'];
        $customer->name = $data->name;
        $customer->phone = $data->phone;
        $customer->email = $data->email;
        $customer->address = $data->address;
        $customer->assigned_sales_rep_id = $data->assigned_sales_rep_id;
        $customer->territory_id = $data->territory_id ?? null;

        if ($customer->update()) {
            echo json_encode(['message' => 'Customer updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update customer.']);
        }
        break;
    case 'DELETE':
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete customers.']);
            exit;
        }

        $customer->id = $_GET['id'];

        if ($customer->delete()) {
            echo json_encode(['message' => 'Customer deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete customer.']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}
?>