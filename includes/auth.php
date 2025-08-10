<?php
// Authentication functions



function require_login() {
    global $auth;
    $auth->require_login();
}

function get_current_user_id() {
    return $_SESSION['id'] ?? null;
}

function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

// Other authentication helpers can be added here

?>