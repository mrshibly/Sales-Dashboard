<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $customer_id;
    public $sales_rep_id;
    public $order_date;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create($user_role = null, $user_geographic_scope = []) {
        // First, get the customer's geographic information
        require_once 'Customer.php'; // Include Customer class
        $customer = new Customer($this->conn);
        $customer->id = $this->customer_id;
        $customer->readOne(); // This populates $customer->territory_id

        if ($customer->id === null) {
            // Customer not found
            return false;
        }

        $can_create = false;
        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_create = true;
                break;
            case 'DSM':
                // Check if customer's territory is within DSM's division
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE t.id = :territory_id AND d.division_id = :division_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':division_id', $user_geographic_scope['division_id']);
                $stmt->execute();
                $can_create = ($stmt->fetchColumn() > 0);
                break;
            case 'ASM':
                // Check if customer's territory is within ASM's district
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE t.id = :territory_id AND up.district_id = :district_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':district_id', $user_geographic_scope['district_id']);
                $stmt->execute();
                $can_create = ($stmt->fetchColumn() > 0);
                break;
            case 'TSM':
                // Check if customer's territory is within TSM's upazila
                $query = "SELECT COUNT(*) FROM territories WHERE id = :territory_id AND upazila_id = :upazila_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':upazila_id', $user_geographic_scope['upazila_id']);
                $stmt->execute();
                $can_create = ($stmt->fetchColumn() > 0);
                break;
            case 'SR':
                // Check if customer's territory is within SR's territory
                if ($customer->territory_id != $user_geographic_scope['territory_id']) {
                    $can_create = false;
                } else {
                    $can_create = true;
                }
                break;
        }

        if (!$can_create) {
            return false; // Not authorized to create order for this customer
        }

        $query = "INSERT INTO " . $this->table_name . " SET customer_id=:customer_id, sales_rep_id=:sales_rep_id, order_date=:order_date, status=:status";

        $stmt = $this->conn->prepare($query);

        $this->customer_id=htmlspecialchars(strip_tags($this->customer_id));
        $this->sales_rep_id=htmlspecialchars(strip_tags($this->sales_rep_id));
        $this->order_date=htmlspecialchars(strip_tags($this->order_date));
        $this->status=htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":sales_rep_id", $this->sales_rep_id);
        $stmt->bindParam(":order_date", $this->order_date);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function readOne() {
        $query = "SELECT o.id, o.customer_id, o.sales_rep_id, o.order_date, o.status, c.name as customer_name, u.name as sales_rep_name FROM " . $this->table_name . " o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN users u ON o.sales_rep_id = u.id WHERE o.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->customer_id = $row['customer_id'];
        $this->sales_rep_id = $row['sales_rep_id'];
        $this->order_date = $row['order_date'];
        $this->status = $row['status'];
        $this->customer_name = $row['customer_name'];
        $this->sales_rep_name = $row['sales_rep_name'];

        return $row;
    }

    function read($user_role = null, $user_geographic_scope = [], $user_id = null, $search_term = '', $filter_status = '', $filter_start_date = '', $filter_end_date = '') {
        $query = "SELECT o.id, c.name as customer_name, u.name as sales_rep_name, o.order_date, o.status FROM " . $this->table_name . " o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN users u ON o.sales_rep_id = u.id ";
        $where_clauses = [];
        $bind_params = [];

        // Apply RBAC filters
        switch ($user_role) {
            case 'SR':
                $where_clauses[] = "o.sales_rep_id = :user_id";
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
            case 'NSM':
            case 'HOM':
                // No geographic restrictions for NSM and HOM
                break;
        }

        // Apply search term
        if (!empty($search_term)) {
            $where_clauses[] = "(c.name LIKE :search_term OR u.name LIKE :search_term)";
            $bind_params[':search_term'] = '%' . $search_term . '%';
        }

        // Apply status filter
        if (!empty($filter_status)) {
            $where_clauses[] = "o.status = :filter_status";
            $bind_params[':filter_status'] = $filter_status;
        }

        // Apply date range filter
        if (!empty($filter_start_date)) {
            $where_clauses[] = "o.order_date >= :start_date";
            $bind_params[':start_date'] = $filter_start_date . ' 00:00:00';
        }
        if (!empty($filter_end_date)) {
            $where_clauses[] = "o.order_date <= :end_date";
            $bind_params[':end_date'] = $filter_end_date . ' 23:59:59';
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY o.order_date DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($bind_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt;
    }

    function update($user_role = null, $user_geographic_scope = []) {
        // First, get the order's customer's geographic information
        $this->readOne(); // This populates $this->customer_id

        if ($this->id === null) {
            // Order not found, or readOne failed
            return false;
        }

        require_once 'Customer.php'; // Include Customer class
        $customer = new Customer($this->conn);
        $customer->id = $this->customer_id;
        $customer->readOne(); // This populates $customer->territory_id

        if ($customer->id === null) {
            // Customer not found for this order
            return false;
        }

        $can_update = false;
        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_update = true;
                break;
            case 'DSM':
                // Check if order's customer's territory is within DSM's division
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE t.id = :territory_id AND d.division_id = :division_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':division_id', $user_geographic_scope['division_id']);
                $stmt->execute();
                $can_update = ($stmt->fetchColumn() > 0);
                break;
            case 'ASM':
                // Check if order's customer's territory is within ASM's district
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE t.id = :territory_id AND up.district_id = :district_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':district_id', $user_geographic_scope['district_id']);
                $stmt->execute();
                $can_update = ($stmt->fetchColumn() > 0);
                break;
            case 'TSM':
                // Check if order's customer's territory is within TSM's upazila
                $query = "SELECT COUNT(*) FROM territories WHERE id = :territory_id AND upazila_id = :upazila_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':upazila_id', $user_geographic_scope['upazila_id']);
                $stmt->execute();
                $can_update = ($stmt->fetchColumn() > 0);
                break;
            case 'SR':
                // SRs can only update their own assigned orders, already handled by API
                $can_update = true;
                break;
        }

        if (!$can_update) {
            return false; // Not authorized to update this order
        }

        $query = "UPDATE " . $this->table_name . " SET customer_id=:customer_id, sales_rep_id=:sales_rep_id, order_date=:order_date, status=:status WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->customer_id=htmlspecialchars(strip_tags($this->customer_id));
        $this->sales_rep_id=htmlspecialchars(strip_tags($this->sales_rep_id));
        $this->order_date=htmlspecialchars(strip_tags($this->order_date));
        $this->status=htmlspecialchars(strip_tags($this->status));
        $this->id=htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':sales_rep_id', $this->sales_rep_id);
        $stmt->bindParam(':order_date', $this->order_date);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function delete($user_role = null, $user_geographic_scope = []) {
        // First, get the order's customer's geographic information
        $this->readOne(); // This populates $this->customer_id

        if ($this->id === null) {
            // Order not found, or readOne failed
            return false;
        }

        require_once 'Customer.php'; // Include Customer class
        $customer = new Customer($this->conn);
        $customer->id = $this->customer_id;
        $customer->readOne(); // This populates $customer->territory_id

        if ($customer->id === null) {
            // Customer not found for this order
            return false;
        }

        $can_delete = false;
        switch ($user_role) {
            case 'HOM':
            case 'NSM':
                $can_delete = true;
                break;
            case 'DSM':
                // Check if order's customer's territory is within DSM's division
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id JOIN districts d ON up.district_id = d.id WHERE t.id = :territory_id AND d.division_id = :division_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':division_id', $user_geographic_scope['division_id']);
                $stmt->execute();
                $can_delete = ($stmt->fetchColumn() > 0);
                break;
            case 'ASM':
                // Check if order's customer's territory is within ASM's district
                $query = "SELECT COUNT(*) FROM territories t JOIN upazilas up ON t.upazila_id = up.id WHERE t.id = :territory_id AND up.district_id = :district_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':district_id', $user_geographic_scope['district_id']);
                $stmt->execute();
                $can_delete = ($stmt->fetchColumn() > 0);
                break;
            case 'TSM':
                // Check if order's customer's territory is within TSM's upazila
                $query = "SELECT COUNT(*) FROM territories WHERE id = :territory_id AND upazila_id = :upazila_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':territory_id', $customer->territory_id);
                $stmt->bindParam(':upazila_id', $user_geographic_scope['upazila_id']);
                $stmt->execute();
                $can_delete = ($stmt->fetchColumn() > 0);
                break;
            case 'SR':
                // SRs cannot delete orders, this should be caught by API level check
                return false;
        }

        if (!$can_delete) {
            return false; // Not authorized to delete this order
        }

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