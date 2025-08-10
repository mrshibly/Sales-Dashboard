<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Order.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);
$auth = new Auth($db);

$method = $_SERVER['REQUEST_METHOD'];

// DEBUG: Output current user role and geographic scope
error_log("Current User Role: " . $auth->get_current_user_role());
error_log("Current User ID: " . $auth->get_current_user_id());
error_log("Current User Geographic Scope: " . print_r($auth->get_current_user_geographic_scope(), true));

switch ($method) {
    case 'GET':
        // Permission check for GET
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to view orders.']);
            exit;
        }

        if (isset($_GET['id'])) {
            $order->id = $_GET['id'];
            $order->readOne();
            echo json_encode($order);
        } else {
            $search_term = isset($_GET['search']) ? $_GET['search'] : '';
            $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
            $filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

            $stmt = $order->read(
                $auth->get_current_user_role(),
                $auth->get_current_user_geographic_scope(),
                $auth->get_current_user_id(),
                $search_term,
                $filter_status,
                $filter_start_date,
                $filter_end_date
            );
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($orders);
        }
        break;
    case 'POST':
        // Permission check for POST
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create orders.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $order->customer_id = $data->customer_id;
        $order->sales_rep_id = $data->sales_rep_id;
        $order->order_date = date('Y-m-d H:i:s');
        $order->status = 'Pending';

        // Enforce SR can only create orders for customers assigned to them
        if ($auth->get_current_user_role() === 'SR') {
            require_once '../classes/Customer.php';
            $customer = new Customer($db);
            $customer->id = $order->customer_id;
            $customer->readOne();
            if ($customer->assigned_sales_rep_id != $auth->get_current_user_id()) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden: Sales Representatives can only create orders for their assigned customers.']);
                exit;
            }
            // Ensure SR can only assign order to themselves
            if ($order->sales_rep_id != $auth->get_current_user_id()) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden: Sales Representatives can only assign orders to themselves.']);
                exit;
            }
        }

        // Enforce geographic scope for creation (similar to customers)
        $user_role = $auth->get_current_user_role();
        $user_scope = $auth->get_current_user_geographic_scope();

        $can_create = false;
        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_create = true;
                break;
            case 'DSM':
                // Check if customer's territory is within DSM's division
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE t.id = :territory_id AND d.division_id = :division_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':division_id', $user_scope['division_id']);
                $stmt->execute();
                $can_create = ($stmt->fetchColumn() > 0);
                break;
            case 'ASM':
                // Check if customer's territory is within ASM's district
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer->id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE t.id = :territory_id AND up.district_id = :district_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':district_id', $user_scope['district_id']);
                $stmt->execute();
                $can_create = ($stmt->fetchColumn() > 0);
                break;
            case 'TSM':
                // Check if customer's territory is within TSM's upazila
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories WHERE id = :territory_id AND upazila_id = :upazila_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':upazila_id', $user_scope['upazila_id']);
                $stmt->execute();
                $can_create = ($stmt->fetchColumn() > 0);
                break;
            case 'SR':
                // Check if customer's territory is within SR's territory
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                if ($customer->territory_id != $user_scope['territory_id']) {
                    $can_create = false;
                } else {
                    $can_create = true;
                }
                break;
        }

        if (!$can_create) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create orders for customers in this scope.']);
            exit;
        }

        if ($order->create()) {
            echo json_encode(['message' => 'Order created successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create order.']);
        }
        break;
    case 'PUT':
        // Permission check for PUT
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update orders.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $order->id = $data->id;
        $order->customer_id = $data->customer_id;
        $order->sales_rep_id = $data->sales_rep_id;
        $order->order_date = $data->order_date;
        $order->status = $data->status;

        // Enforce SR can only update their own orders
        if ($auth->get_current_user_role() === 'SR') {
            $order->readOne(); // Get current sales_rep_id
            if ($order->sales_rep_id != $auth->get_current_user_id()) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden: Sales Representatives can only update their own orders.']);
                exit;
            }
        }

        // Enforce geographic scope for update (similar to customers)
        $user_role = $auth->get_current_user_role();
        $user_scope = $auth->get_current_user_geographic_scope();

        $can_update = false;
        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_update = true;
                break;
            case 'DSM':
                // Check if order's customer's territory is within DSM's division
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE t.id = :territory_id AND d.division_id = :division_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':division_id', $user_scope['division_id']);
                $stmt->execute();
                $can_update = ($stmt->fetchColumn() > 0);
                break;
            case 'ASM':
                // Check if order's customer's territory is within ASM's district
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE t.id = :territory_id AND up.district_id = :district_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':district_id', $user_scope['district_id']);
                $stmt->execute();
                $can_update = ($stmt->fetchColumn() > 0);
                break;
            case 'TSM':
                // Check if order's customer's territory is within TSM's upazila
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories WHERE id = :territory_id AND upazila_id = :upazila_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':upazila_id', $user_scope['upazila_id']);
                $stmt->execute();
                $can_update = ($stmt->fetchColumn() > 0);
                break;
            case 'SR':
                // SRs can only update their own assigned orders, already handled above
                $can_update = true;
                break;
        }

        if (!$can_update) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update orders in this scope.']);
            exit;
        }

        if ($order->update()) {
            echo json_encode(['message' => 'Order updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update order.', 'error' => $order->conn->errorInfo()]);
        }
        break;
    case 'DELETE':
        // Permission check for DELETE
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM'])) { // SR cannot delete
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete orders.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $order->id = $data->id;

        // Enforce geographic scope for deletion
        $order->readOne(); // Get order details for geographic check
        if ($order->id === null) { // Order not found
            http_response_code(404);
            echo json_encode(['message' => 'Order not found.']);
            exit;
        }

        $user_role = $auth->get_current_user_role();
        $user_scope = $auth->get_current_user_geographic_scope();

        $can_delete = false;
        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_delete = true;
                break;
            case 'DSM':
                // Check if order's customer's territory is within DSM's division
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE t.id = :territory_id AND d.division_id = :division_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':division_id', $user_scope['division_id']);
                $stmt->execute();
                $can_delete = ($stmt->fetchColumn() > 0);
                break;
            case 'ASM':
                // Check if order's customer's territory is within ASM's district
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE t.id = :territory_id AND up.district_id = :district_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':district_id', $user_scope['district_id']);
                $stmt->execute();
                $can_delete = ($stmt->fetchColumn() > 0);
                break;
            case 'TSM':
                // Check if order's customer's territory is within TSM's upazila
                require_once '../classes/Customer.php';
                $customer = new Customer($db);
                $customer->id = $order->customer_id;
                $customer->readOne();
                $query = "SELECT COUNT(*) FROM territories WHERE id = :territory_id AND upazila_id = :upazila_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':upazila_id', $user_scope['upazila_id']);
                $stmt->execute();
                $can_delete = ($stmt->fetchColumn() > 0);
                break;
        }

        if (!$can_delete) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete orders in this scope.']);
            exit;
        }

        if ($order->delete()) {
            echo json_encode(['message' => 'Order deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete order.', 'error' => $order->conn->errorInfo()]);
        }
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}
?>