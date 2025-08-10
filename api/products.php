<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Product.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$auth = new Auth($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Permission check for GET (all roles can view)
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to view products.']);
            exit;
        }

        if (isset($_GET['id'])) {
            $product->id = $_GET['id'];
            $product->readOne();
            echo json_encode($product);
        } else {
            $search_term = isset($_GET['search']) ? $_GET['search'] : '';
            $min_stock = isset($_GET['min_stock']) ? $_GET['min_stock'] : '';

            $stmt = $product->read($search_term, $min_stock);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($products);
        }
        break;
    case 'POST':
        // Permission check for POST (only HOM can create)
        if (!$auth->has_permission(['HOM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create products.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $product->name = $data->name;
        $product->price = $data->price;
        $product->stock = $data->stock;

        if ($product->create()) {
            echo json_encode(['message' => 'Product created successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create product.']);
        }
        break;
    case 'PUT':
        // Permission check for PUT (only HOM can update)
        if (!$auth->has_permission(['HOM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update products.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $product->id = $data->id;
        $product->name = $data->name;
        $product->price = $data->price;
        $product->stock = $data->stock;

        if ($product->update()) {
            echo json_encode(['message' => 'Product updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update product.']);
        }
        break;
    case 'DELETE':
        // Permission check for DELETE (only HOM can delete)
        if (!$auth->has_permission(['HOM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete products.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $product->id = $data->id;

        if ($product->delete()) {
            echo json_encode(['message' => 'Product deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete product.']);
        }
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}
?>