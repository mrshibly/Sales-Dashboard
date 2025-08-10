<?php
class Territory {
    private $conn;
    private $table_name = "territories";

    public $id;
    public $name;
    public $upazila_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    function read() {
        $query = "SELECT t.id, t.name, t.upazila_id, u.name as upazila_name FROM " . $this->table_name . " t LEFT JOIN upazilas u ON t.upazila_id = u.id ORDER BY t.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    function readByUpazila($upazila_id) {
        $query = "SELECT id, name FROM " . $this->table_name . " WHERE upazila_id = ? ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $upazila_id);
        $stmt->execute();

        return $stmt;
    }
}
?>