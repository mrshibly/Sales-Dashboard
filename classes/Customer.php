<?php
class Customer {
    private $conn;
    private $table_name = "customers";

    public $id;
    public $name;
    public $phone;
    public $email;
    public $address;
    public $assigned_sales_rep_id;
    public $territory_id;
    public $sales_rep_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, phone=:phone, email=:email, address=:address, assigned_sales_rep_id=:assigned_sales_rep_id, territory_id=:territory_id";
        $stmt = $this->conn->prepare($query);

        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->phone=htmlspecialchars(strip_tags($this->phone));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->address=htmlspecialchars(strip_tags($this->address));
        $this->assigned_sales_rep_id=htmlspecialchars(strip_tags($this->assigned_sales_rep_id));
        $this->territory_id=($this->territory_id === '' || $this->territory_id === null) ? NULL : htmlspecialchars(strip_tags($this->territory_id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":assigned_sales_rep_id", $this->assigned_sales_rep_id);
        
        if ($this->territory_id === NULL) {
            $stmt->bindValue(":territory_id", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":territory_id", $this->territory_id, PDO::PARAM_INT);
        }

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    function readOne() {
        $query = "SELECT c.id, c.name, c.phone, c.email, c.address, c.assigned_sales_rep_id, c.territory_id, u.name as sales_rep_name FROM " . $this->table_name . " c LEFT JOIN users u ON c.assigned_sales_rep_id = u.id WHERE c.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->name = $row['name'];
            $this->phone = $row['phone'];
            $this->email = $row['email'];
            $this->address = $row['address'];
            $this->assigned_sales_rep_id = $row['assigned_sales_rep_id'];
            $this->territory_id = $row['territory_id'];
            $this->sales_rep_name = $row['sales_rep_name'];
        }
    }

    function customer_data() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'assigned_sales_rep_id' => $this->assigned_sales_rep_id,
            'territory_id' => $this->territory_id,
            'sales_rep_name' => $this->sales_rep_name
        ];
    }

    function read($user_role = null, $user_geographic_scope = [], $user_id = null, $search_term = '', $filter_sales_rep_id = '', $filter_territory_id = '') {
        $query = "SELECT c.id, c.name, c.phone, c.email, c.address, c.territory_id, COALESCE(u.name, '') as sales_rep_name FROM " . $this->table_name . " c LEFT JOIN users u ON c.assigned_sales_rep_id = u.id ";
        $where_clauses = [];
        $bind_params = [];

        if ($user_role) {
            switch ($user_role) {
                case 'SR':
                    $where_clauses[] = "c.assigned_sales_rep_id = :user_id";
                    $bind_params[':user_id'] = $user_id;
                    break;
                case 'TSM':
                    $where_clauses[] = "c.territory_id IN (SELECT id FROM territories WHERE upazila_id = :upazila_id)";
                    $bind_params[':upazila_id'] = $user_geographic_scope['upazila_id'];
                    break;
                case 'ASM':
                    $where_clauses[] = "c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE up.district_id = :district_id)";
                    $bind_params[':district_id'] = $user_geographic_scope['district_id'];
                    break;
                case 'DSM':
                    $where_clauses[] = "c.territory_id IN (SELECT t.id FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE d.division_id = :division_id)";
                    $bind_params[':division_id'] = $user_geographic_scope['division_id'];
                    break;
            }
        }

        if (!empty($search_term)) {
            $where_clauses[] = "(c.name LIKE :search_term OR c.email LIKE :search_term OR c.phone LIKE :search_term)";
            $bind_params[':search_term'] = '%' . $search_term . '%';
        }

        if (!empty($filter_sales_rep_id)) {
            $where_clauses[] = "c.assigned_sales_rep_id = :filter_sales_rep_id";
            $bind_params[':filter_sales_rep_id'] = $filter_sales_rep_id;
        }

        if (!empty($filter_territory_id)) {
            $where_clauses[] = "c.territory_id = :filter_territory_id";
            $bind_params[':filter_territory_id'] = $filter_territory_id;
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY c.name ASC";
        $stmt = $this->conn->prepare($query);

        foreach ($bind_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    function update() {
        $query = "UPDATE " . $this->table_name . " SET name=:name, phone=:phone, email=:email, address=:address, assigned_sales_rep_id=:assigned_sales_rep_id, territory_id=:territory_id WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->phone=htmlspecialchars(strip_tags($this->phone));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->address=htmlspecialchars(strip_tags($this->address));
        $this->assigned_sales_rep_id=htmlspecialchars(strip_tags($this->assigned_sales_rep_id));
        $this->territory_id=($this->territory_id === '' || $this->territory_id === null) ? NULL : htmlspecialchars(strip_tags($this->territory_id));
        $this->id=htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':assigned_sales_rep_id', $this->assigned_sales_rep_id);
        if ($this->territory_id === NULL) {
            $stmt->bindValue(":territory_id", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":territory_id", $this->territory_id, PDO::PARAM_INT);
        }
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