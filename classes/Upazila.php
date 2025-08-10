<?php
class Upazila {
    private $conn;
    private $table_name = "upazilas";

    public $id;
    public $name;
    public $district_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    function read() {
        $query = "SELECT u.id, u.name, u.district_id, d.name as district_name FROM " . $this->table_name . " u LEFT JOIN districts d ON u.district_id = d.id ORDER BY u.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    function readByDistrict($district_id) {
        $query = "SELECT id, name FROM " . $this->table_name . " WHERE district_id = ? ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $district_id);
        $stmt->execute();

        return $stmt;
    }
}
?>