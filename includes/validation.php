<?php
// Form validation functions

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password($password) {
    // Password must be at least 6 characters long
    return strlen($password) >= 6;
}

// Other validation functions can be added here

?>