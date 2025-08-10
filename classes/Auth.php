<?php
class Auth {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function is_logged_in() {
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }

    public function require_login() {
        if (!$this->is_logged_in()) {
            header('location: ' . BASE_URL . 'login.php');
            exit;
        }
    }

    public function get_current_user_id() {
        return $_SESSION['id'] ?? null;
    }

    public function get_current_user_role() {
        return $_SESSION['role'] ?? null;
    }

    public function get_current_user_geographic_scope() {
        return [
            'division_id' => $_SESSION['division_id'] ?? null,
            'district_id' => $_SESSION['district_id'] ?? null,
            'upazila_id' => $_SESSION['upazila_id'] ?? null,
            'territory_id' => $_SESSION['territory_id'] ?? null,
        ];
    }

    public function has_permission($required_roles, $geographic_scope_level = null, $geographic_id = null) {
        $user_role = $this->get_current_user_role();
        $user_scope = $this->get_current_user_geographic_scope();

        if (!in_array($user_role, $required_roles)) {
            return false;
        }

        // Implement geographic scope checks based on role and hierarchy
        switch ($user_role) {
            case 'HOM': // Head of Marketing - Full system access
                return true;
            case 'NSM': // National Sales Manager - Country-wide
                return true;
            case 'DSM': // Divisional Sales Manager
                if ($geographic_scope_level === 'division' && $geographic_id !== null) {
                    return $user_scope['division_id'] == $geographic_id;
                }
                return true; // Can view all below their division
            case 'ASM': // Area Sales Manager
                if ($geographic_scope_level === 'district' && $geographic_id !== null) {
                    return $user_scope['district_id'] == $geographic_id;
                }
                return true; // Can view all below their district
            case 'TSM': // Territory Sales Manager
                if ($geographic_scope_level === 'upazila' && $geographic_id !== null) {
                    return $user_scope['upazila_id'] == $geographic_id;
                }
                return true; // Can view all below their upazila
            case 'SR': // Sales Representative
                if ($geographic_scope_level === 'territory' && $geographic_id !== null) {
                    return $user_scope['territory_id'] == $geographic_id;
                }
                return true; // Can view only their own territory
            default:
                return false;
        }
    }
}
?>