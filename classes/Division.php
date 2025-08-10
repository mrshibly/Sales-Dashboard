<?php
class Division {
    private $conn;
    private $table_name = "divisions";

    public $id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    function read() {
        $query = "SELECT id, name FROM " . $this->table_name . " ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
?>