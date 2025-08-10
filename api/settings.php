<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$auth = new Auth($db);

// Initial check for logged in status using Auth class
if (!$auth->is_logged_in()) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$user->id = $_SESSION['id']; // User can only modify their own settings

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // General permission check for POST actions
    if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden: You do not have permission to modify settings.']);
        exit;
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $user->name = $_POST['name'];
                $user->email = $_POST['email'];
                $user->role = $_POST['role'];
                $user->reports_to = empty($_POST['reports_to']) ? NULL : $_POST['reports_to'];

                // Additional check: HOM can change role, others cannot change their own role
                if ($auth->get_current_user_role() !== 'HOM' && $user->role !== $auth->get_current_user_role()) {
                    http_response_code(403);
                    echo json_encode(['message' => 'Forbidden: You cannot change your own role.']);
                    exit;
                }

                if ($user->update()) {
                    $_SESSION['name'] = $user->name;
                    $_SESSION['role'] = $user->role;
                    // Update session geographic info if changed
                    $user->readOne(); // Re-read to get updated geographic IDs
                    $_SESSION['division_id'] = $user->division_id;
                    $_SESSION['district_id'] = $user->district_id;
                    $_SESSION['upazila_id'] = $user->upazila_id;
                    $_SESSION['territory_id'] = $user->territory_id;

                    echo json_encode(['message' => 'Profile updated successfully!']);
                } else {
                    echo json_encode(['message' => 'Failed to update profile.']);
                }
                break;
            case 'change_password':
                $user->password = $_POST['new_password'];
                if ($user->updatePassword()) {
                    echo json_encode(['message' => 'Password updated successfully!']);
                } else {
                    echo json_encode(['message' => 'Failed to update password.']);
                }
                break;
            case 'upload_avatar':
                $target_dir = "../uploads/avatars/";
                $file_extension = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
                $new_file_name = $user->id . "." . $file_extension;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                    if ($user->updateAvatar($new_file_name)) {
                        $_SESSION['avatar'] = $new_file_name;
                        echo json_encode(['message' => 'Avatar uploaded successfully!', 'avatar' => $new_file_name]);
                    } else {
                        echo json_encode(['message' => 'Failed to update avatar in database.']);
                    }
                } else {
                    echo json_encode(['message' => 'Failed to upload avatar.']);
                }
                break;
        }
    }
} else if ($method === 'GET') {
    // Permission check for GET
    if (!$auth->has_permission(['HOM', 'NSM', 'DSM', 'ASM', 'TSM', 'SR'])) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden: You do not have permission to view settings.']);
        exit;
    }

    $user->readOne();
    echo json_encode($user);
}
?>