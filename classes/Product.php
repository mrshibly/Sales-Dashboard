<?php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $price;
    public $stock;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, price=:price, stock=:stock";

        $stmt = $this->conn->prepare($query);

        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->price=htmlspecialchars(strip_tags($this->price));
        $this->stock=htmlspecialchars(strip_tags($this->stock));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function readOne() {
        $query = "SELECT id, name, price, stock FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->name = $row['name'];
        $this->price = $row['price'];
        $this->stock = $row['stock'];

        return $row;
    }

    function read($search_term = '', $min_stock = '') {
        $query = "SELECT id, name, price, stock FROM " . $this->table_name . " ";
        $where_clauses = [];
        $bind_params = [];

        // Apply search term
        if (!empty($search_term)) {
            $where_clauses[] = "name LIKE :search_term";
            $bind_params[':search_term'] = '%' . $search_term . '%';
        }

        // Apply min stock filter
        if (!empty($min_stock)) {
            $where_clauses[] = "stock >= :min_stock";
            $bind_params[':min_stock'] = $min_stock;
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);

        foreach ($bind_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt;
    }

    function update() {
        $query = "UPDATE " . $this->table_name . " SET name=:name, price=:price, stock=:stock WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->price=htmlspecialchars(strip_tags($this->price));
        $this->stock=htmlspecialchars(strip_tags($this->stock));
        $this->id=htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $this->id=htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }
}
?>