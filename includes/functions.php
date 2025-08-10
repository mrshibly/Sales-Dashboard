<?php
// General utility functions

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Other common functions can be added here

?>