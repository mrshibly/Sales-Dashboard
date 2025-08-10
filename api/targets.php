<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Target.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/User.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$target = new Target($db);
$auth = new Auth($db);
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Permission check for GET
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to view targets.']);
            exit;
        }

        if (isset($_GET['id'])) {
            $target->id = $_GET['id'];
            $target->readOne();
            echo json_encode($target);
        } else {
            $search_term = isset($_GET['search']) ? $_GET['search'] : '';
            $filter_month = isset($_GET['month']) ? $_GET['month'] : '';
            $filter_division_id = isset($_GET['division_id']) ? $_GET['division_id'] : '';

            $stmt = $target->read(
                $auth->get_current_user_role(),
                $auth->get_current_user_geographic_scope(),
                $auth->get_current_user_id(),
                $search_term,
                $filter_month,
                $filter_division_id
            );
            $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($targets);
        }
        break;
    case 'POST':
        // Permission check for POST
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM'])) { // SR cannot assign targets
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create targets.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $target->user_id = $data->user_id;
        $target->month = $data->month;
        $target->target_amount = $data->target_amount;
        $target->achieved_amount = $data->achieved_amount;

        // Enforce geographic scope for creation
        $user_role = $auth->get_current_user_role();
        $user_scope = $auth->get_current_user_geographic_scope();

        $can_create = false;
        $target_user = new User($db);
        $target_user->id = $target->user_id;
        $target_user->readOne(); // Get target user's geographic info

        if (!$target_user->id) {
            http_response_code(404);
            echo json_encode(['message' => 'Target user not found.']);
            exit;
        }

        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_create = true;
                break;
            case 'DSM':
                // Check if target user is within DSM's division
                if ($target_user->division_id == $user_scope['division_id']) {
                    $can_create = true;
                }
                break;
            case 'ASM':
                // Check if target user is within ASM's district
                if ($target_user->district_id == $user_scope['district_id']) {
                    $can_create = true;
                }
                break;
            case 'TSM':
                // Check if target user is within TSM's upazila
                if ($target_user->upazila_id == $user_scope['upazila_id']) {
                    $can_create = true;
                }
                break;
        }

        if (!$can_create) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to create targets for this user.']);
            exit;
        }

        if ($target->create()) {
            echo json_encode(['message' => 'Target created successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create target.']);
        }
        break;
    case 'PUT':
        // Permission check for PUT
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) { // SR can update their own targets
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update targets.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $target->id = $data->id;
        $target->user_id = $data->user_id;
        $target->month = $data->month;
        $target->target_amount = $data->target_amount;
        $target->achieved_amount = $data->achieved_amount;

        // Enforce SR can only update their own targets
        if ($auth->get_current_user_role() === 'SR' && $target->user_id != $auth->get_current_user_id()) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: Sales Representatives can only update their own targets.']);
            exit;
        }

        // Enforce geographic scope for update
        $user_role = $auth->get_current_user_role();
        $user_scope = $auth->get_current_user_geographic_scope();

        $can_update = false;
        $target_user = new User($db);
        $target_user->id = $target->user_id;
        $target_user->readOne(); // Get target user's geographic info

        if (!$target_user->id) {
            http_response_code(404);
            echo json_encode(['message' => 'Target user not found.']);
            exit;
        }

        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_update = true;
                break;
            case 'DSM':
                // Check if target user is within DSM's division
                if ($target_user->division_id == $user_scope['division_id']) {
                    $can_update = true;
                }
                break;
            case 'ASM':
                // Check if target user is within ASM's district
                if ($target_user->district_id == $user_scope['district_id']) {
                    $can_update = true;
                }
                break;
            case 'TSM':
                // Check if target user is within TSM's upazila
                if ($target_user->upazila_id == $user_scope['upazila_id']) {
                    $can_update = true;
                }
                break;
            case 'SR':
                // SRs can only update their own targets, already handled above
                $can_update = true;
                break;
        }

        if (!$can_update) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to update targets for this user.']);
            exit;
        }

        if ($target->update()) {
            echo json_encode(['message' => 'Target updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update target.']);
        }
        break;
    case 'DELETE':
        // Permission check for DELETE
        if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM'])) { // SR cannot delete
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete targets.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'));
        $target->id = $data->id;

        // Enforce geographic scope for deletion
        $target->readOne(); // Get target details for geographic check
        if ($target->id === null) { // Target not found
            http_response_code(404);
            echo json_encode(['message' => 'Target not found.']);
            exit;
        }

        $user_role = $auth->get_current_user_role();
        $user_scope = $auth->get_current_user_geographic_scope();

        $can_delete = false;
        $target_user = new User($db);
        $target_user->id = $target->user_id;
        $target_user->readOne(); // Get target user's geographic info

        if (!$target_user->id) {
            http_response_code(404);
            echo json_encode(['message' => 'Target user not found.']);
            exit;
        }

        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_delete = true;
                break;
            case 'DSM':
                // Check if target user is within DSM's division
                if ($target_user->division_id == $user_scope['division_id']) {
                    $can_delete = true;
                }
                break;
            case 'ASM':
                // Check if target user is within ASM's district
                if ($target_user->district_id == $user_scope['district_id']) {
                    $can_delete = true;
                }
                break;
            case 'TSM':
                // Check if target user is within TSM's upazila
                if ($target_user->upazila_id == $user_scope['upazila_id']) {
                    $can_delete = true;
                }
                break;
        }

        if (!$can_delete) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden: You do not have permission to delete targets for this user.']);
            exit;
        }

        if ($target->delete()) {
            echo json_encode(['message' => 'Target deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete target.']);
        }
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}
?>