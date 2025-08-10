<?php
class Target {
    private $conn;
    private $table_name = "targets";

    public $id;
    public $user_id;
    public $month;
    public $target_amount;
    public $achieved_amount;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, month=:month, target_amount=:target_amount, achieved_amount=:achieved_amount";

        $stmt = $this->conn->prepare($query);

        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->month=htmlspecialchars(strip_tags($this->month));
        $this->target_amount=htmlspecialchars(strip_tags($this->target_amount));
        $this->achieved_amount=htmlspecialchars(strip_tags($this->achieved_amount));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":month", $this->month);
        $stmt->bindParam(":target_amount", $this->target_amount);
        $stmt->bindParam(":achieved_amount", $this->achieved_amount);

        if($stmt->execute()){
            return true;
        }

        // Print error if execution fails
        printf("Error: %s.\n", $stmt->errorInfo()[2]);
        return false;
    }

    function readOne() {
        $query = "SELECT t.id, t.user_id, t.month, t.target_amount, t.achieved_amount, u.name as user_name FROM " . $this->table_name . " t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->user_id = $row['user_id'];
        $this->month = $row['month'];
        $this->target_amount = $row['target_amount'];
        $this->achieved_amount = $row['achieved_amount'];
        $this->user_name = $row['user_name'];

        return $row;
    }

    function read($user_role = null, $user_geographic_scope = [], $user_id = null, $search_term = '', $filter_month = '', $filter_division_id = '') {
        $query = "SELECT t.id, u.name as user_name, t.month, t.target_amount, t.achieved_amount FROM " . $this->table_name . " t LEFT JOIN users u ON t.user_id = u.id ";
        $where_clauses = [];
        $bind_params = [];

        // Apply RBAC filters
        switch ($user_role) {
            case 'SR':
                $where_clauses[] = "t.user_id = :user_id";
                $bind_params[':user_id'] = $user_id;
                break;
            case 'TSM':
                $where_clauses[] = "u.upazila_id = :upazila_id";
                $bind_params[':upazila_id'] = $user_geographic_scope['upazila_id'];
                break;
            case 'ASM':
                $where_clauses[] = "u.district_id = :district_id";
                $bind_params[':district_id'] = $user_geographic_scope['district_id'];
                break;
            case 'DSM':
                $where_clauses[] = "u.division_id = :division_id";
                $bind_params[':division_id'] = $user_geographic_scope['division_id'];
                break;
            case 'NSM':
            case 'HOM':
                // No geographic restrictions for NSM and HOM
                break;
        }

        // Apply search term
        if (!empty($search_term)) {
            $where_clauses[] = "u.name LIKE :search_term";
            $bind_params[':search_term'] = '%' . $search_term . '%';
        }

        // Apply month filter
        if (!empty($filter_month)) {
            $where_clauses[] = "DATE_FORMAT(t.month, '%Y-%m') = :filter_month";
            $bind_params[':filter_month'] = $filter_month;
        }

        // Apply division filter (only if not already restricted by role)
        if (!empty($filter_division_id) && !in_array($user_role, ['DSM', 'ASM', 'TSM', 'SR'])) {
            $where_clauses[] = "u.division_id = :filter_division_id";
            $bind_params[':filter_division_id'] = $filter_division_id;
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY t.month DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($bind_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt;
    }

    function update() {
        $query = "UPDATE " . $this->table_name . " SET user_id=:user_id, month=:month, target_amount=:target_amount, achieved_amount=:achieved_amount WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->month=htmlspecialchars(strip_tags($this->month));
        $this->target_amount=htmlspecialchars(strip_tags($this->target_amount));
        $this->achieved_amount=htmlspecialchars(strip_tags($this->achieved_amount));
        $this->id=htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':month', $this->month);
        $stmt->bindParam(':target_amount', $this->target_amount);
        $stmt->bindParam(':achieved_amount', $this->achieved_amount);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()){
            return true;
        }

        // Print error if execution fails
        printf("Error: %s.\n", $stmt->errorInfo()[2]);
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

        // Print error if execution fails
        printf("Error: %s.\n", $stmt->errorInfo()[2]);
        return false;
    }
}
?>