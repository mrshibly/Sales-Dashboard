<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$auth = new Auth($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to view users.']);
            exit;
        }

        if (isset($_GET['id'])) {
            $user->id = $_GET['id'];
            $user->readOne();
            echo json_encode($user->user_data());
        } else {
            $search_term = isset($_GET['search']) ? $_GET['search'] : '';
            $filter_role = isset($_GET['role']) ? $_GET['role'] : '';
            $filter_division = isset($_GET['division_id']) ? $_GET['division_id'] : '';
            $filter_district = isset($_GET['district_id']) ? $_GET['district_id'] : '';

            $stmt = $user->read(
                $auth->get_current_user_role(),
                $auth->get_current_user_geographic_scope(),
                $search_term,
                $filter_role,
                $filter_division,
                $filter_district
            );
            $users_arr = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users_arr[] = $row;
            }
            echo json_encode($users_arr);
        }
        break;

    case 'POST':
        if (!$auth->has_permission(['HOM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create users.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));

        $user->name = $data->name;
        $user->email = $data->email;
        $user->password = $data->password ?? null;
        $user->role = $data->role;
        $user->reports_to = $data->reports_to ?? null;
        $user->division_id = $data->division_id ?? null;
        $user->district_id = $data->district_id ?? null;
        $user->upazila_id = $data->upazila_id ?? null;
        $user->territory_id = $data->territory_id ?? null;

        if ($user->create()) {
            echo json_encode(['message' => 'User created successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create user.']);
        }
        break;

    case 'PUT':
        if (!$auth->has_permission(['HOM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update users.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));

        $user->id = $_GET['id'];
        $user->name = $data->name;
        $user->email = $data->email;
        $user->role = $data->role;
        $user->reports_to = $data->reports_to ?? null;
        $user->division_id = $data->division_id ?? null;
        $user->district_id = $data->district_id ?? null;
        $user->upazila_id = $data->upazila_id ?? null;
        $user->territory_id = $data->territory_id ?? null;

        // Handle password update if provided
        if (isset($data->password) && !empty($data->password)) {
            $user->password = $data->password;
            if (!$user->updatePassword()) {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update password.']);
                exit;
            }
        }

        if ($user->update()) {
            echo json_encode(['message' => 'User updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update user details.']);
        }
        break;

    case 'DELETE':
        if (!$auth->has_permission(['HOM'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete users.']);
            exit;
        }

        $user->id = $_GET['id'];

        if ($user->delete()) {
            echo json_encode(['message' => 'User deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete user.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}
?>