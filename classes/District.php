<?php
class District {
    private $conn;
    private $table_name = "districts";

    public $id;
    public $name;
    public $division_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    function read() {
        $query = "SELECT d.id, d.name, d.division_id, division_table.name as division_name FROM " . $this->table_name . " d LEFT JOIN divisions division_table ON d.division_id = division_table.id ORDER BY d.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    function readByDivision($division_id) {
        $query = "SELECT id, name FROM " . $this->table_name . " WHERE division_id = ? ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $division_id);
        $stmt->execute();

        return $stmt;
    }
}
?>