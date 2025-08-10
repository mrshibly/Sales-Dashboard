<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../vendor/autoload.php';
include_once '../config/database.php';
include_once '../classes/User.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (isset($data->idToken)) {
    $idToken = $data->idToken;
    // TODO: Replace with your actual Firebase Project ID
    $firebaseProjectId = 'srms-87697';

    try {
        $publicKeyUrl = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
        $publicKeys = json_decode(file_get_contents($publicKeyUrl), true);

        $decodedToken = null;
        foreach ($publicKeys as $key => $value) {
            try {
                $decodedToken = JWT::decode($idToken, new Key($value, 'RS256'));
                break; // Exit loop if decoding is successful
            } catch (Exception $e) {
                // Continue to the next key
            }
        }

        if (!$decodedToken) {
            throw new Exception('Invalid Firebase token');
        }

        $firebase_uid = $decodedToken->uid;
        $email = $decodedToken->email;
        $name = $decodedToken->name;

        $user->firebase_uid = $firebase_uid;
        if ($user->userExistsByFirebaseUid()) {
            // User exists, log them in
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $user->id;
            $_SESSION['name'] = $user->name;
            $_SESSION['role'] = $user->role;

            // Fetch user details including geographic IDs
            $user->id = $_SESSION['id'];
            $user->readOne();

            $_SESSION['division_id'] = $user->division_id;
            $_SESSION['district_id'] = $user->district_id;
            $_SESSION['upazila_id'] = $user->upazila_id;
            $_SESSION['territory_id'] = $user->territory_id;

            http_response_code(200);
            echo json_encode(array("message" => "Successful Firebase login.", "id" => $user->id, "name" => $user->name, "role" => $user->role));
        } else {
            // New user, create account
            $user->name = $name;
            $user->email = $email;
            $user->role = 'SR'; // Default role for new Firebase users
            // For new Firebase users, geographic info will be null initially, can be updated later
            if ($user->createFirebaseUser()) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user->id;
                $_SESSION['name'] = $user->name;
                $_SESSION['role'] = $user->role;

                // Fetch user details including geographic IDs for newly created user
                $user->id = $_SESSION['id'];
                $user->readOne();

                $_SESSION['division_id'] = $user->division_id;
                $_SESSION['district_id'] = $user->district_id;
                $_SESSION['upazila_id'] = $user->upazila_id;
                $_SESSION['territory_id'] = $user->territory_id;

                http_response_code(200);
                echo json_encode(array("message" => "Successful Firebase registration and login.", "id" => $user->id, "name" => $user->name, "role" => $user->role));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create Firebase user."));
            }
        }
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(array("message" => "Invalid Firebase token: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "ID token not provided."));
}
?>