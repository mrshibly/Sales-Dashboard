<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $reports_to;
    public $avatar;
    public $firebase_uid;
    public $division_id;
    public $district_id;
    public $upazila_id;
    public $territory_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, email, password, role, reports_to, division_id, district_id, upazila_id, territory_id) VALUES (:name, :email, :password, :role, :reports_to, :division_id, :district_id, :upazila_id, :territory_id)";

        $stmt = $this->conn->prepare($query);

        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->password=htmlspecialchars(strip_tags($this->password));
        $this->role=htmlspecialchars(strip_tags($this->role));

        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':reports_to', $this->reports_to);
        $stmt->bindParam(':division_id', $this->division_id);
        $stmt->bindParam(':district_id', $this->district_id);
        $stmt->bindParam(':upazila_id', $this->upazila_id);
        $stmt->bindParam(':territory_id', $this->territory_id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function createFirebaseUser() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, email=:email, firebase_uid=:firebase_uid, role=:role, division_id=:division_id, district_id=:district_id, upazila_id=:upazila_id, territory_id=:territory_id";

        $stmt = $this->conn->prepare($query);

        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->firebase_uid=htmlspecialchars(strip_tags($this->firebase_uid));
        $this->role=htmlspecialchars(strip_tags($this->role));

        // Handle geographic IDs: ensure they are NULL if empty, otherwise cast to int
        $this->division_id = empty($this->division_id) ? NULL : (int) $this->division_id;
        $this->district_id = empty($this->district_id) ? NULL : (int) $this->district_id;
        $this->upazila_id = empty($this->upazila_id) ? NULL : (int) $this->upazila_id;
        $this->territory_id = empty($this->territory_id) ? NULL : (int) $this->territory_id;

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":firebase_uid", $this->firebase_uid);
        $stmt->bindParam(":role", $this->role);

        if ($this->division_id === NULL) {
            $stmt->bindValue(":division_id", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":division_id", $this->division_id, PDO::PARAM_INT);
        }
        if ($this->district_id === NULL) {
            $stmt->bindValue(":district_id", NULL, PDO::PARAM_NULL);
        }
        if ($this->upazila_id === NULL) {
            $stmt->bindValue(":upazila_id", NULL, PDO::PARAM_NULL);
        }
        if ($this->territory_id === NULL) {
            $stmt->bindValue(":territory_id", NULL, PDO::PARAM_NULL);
        }
        else {
            $stmt->bindParam(":territory_id", $this->territory_id, PDO::PARAM_INT);
        }

        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    function emailExists(){
        $query = "SELECT id, name, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->email=htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num>0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->role = $row['role'];

            return true;
        }

        return false;
    }

    function userExistsByFirebaseUid(){
        $query = "SELECT id, name, email, role FROM " . $this->table_name . " WHERE firebase_uid = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->firebase_uid=htmlspecialchars(strip_tags($this->firebase_uid));
        $stmt->bindParam(1, $this->firebase_uid);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num>0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];

            return true;
        }

        return false;
    }

    function read($user_role = null, $user_geographic_scope = []) {
        $query = "SELECT id, name, email, role, reports_to, avatar, division_id, district_id, upazila_id, territory_id FROM " . $this->table_name . " ";
        $where_clauses = [];
        $bind_params = [];

        if ($user_role) {
            switch ($user_role) {
                case 'HOM':
                    // No restrictions
                    break;
                case 'NSM':
                    // NSM can view all users below them (all roles except HOM)
                    $where_clauses[] = "role != 'HOM'";
                    break;
                case 'DSM':
                    // DSM can view users in their division and below
                    $where_clauses[] = "division_id = :division_id";
                    $bind_params[':division_id'] = $user_geographic_scope['division_id'];
                    break;
                case 'ASM':
                    // ASM can view users in their district and below
                    $where_clauses[] = "district_id = :district_id";
                    $bind_params[':district_id'] = $user_geographic_scope['district_id'];
                    break;
                case 'TSM':
                    // TSM can view users in their upazila and below
                    $where_clauses[] = "upazila_id = :upazila_id";
                    $bind_params[':upazila_id'] = $user_geographic_scope['upazila_id'];
                    break;
                default:
                    // SR and other roles cannot view other users
                    return false; // Or throw an exception
            }
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

    function readOne() {
        $query = "SELECT name, email, role, reports_to, avatar, division_id, district_id, upazila_id, territory_id FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->reports_to = $row['reports_to'];
            $this->avatar = $row['avatar'];
            $this->division_id = $row['division_id'];
            $this->district_id = $row['district_id'];
            $this->upazila_id = $row['upazila_id'];
            $this->territory_id = $row['territory_id'];
            return true;
        }
        return false;
    }

    function user_data() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'reports_to' => $this->reports_to,
            'avatar' => $this->avatar,
            'firebase_uid' => $this->firebase_uid,
            'division_id' => $this->division_id,
            'district_id' => $this->district_id,
            'upazila_id' => $this->upazila_id,
            'territory_id' => $this->territory_id
        ];
    }

    function update() {
        // First, check if the email is already taken by another user
        $email_check_query = "SELECT id FROM " . $this->table_name . " WHERE email = :email AND id != :id";
        $email_stmt = $this->conn->prepare($email_check_query);

        $email_to_check = htmlspecialchars(strip_tags($this->email));
        $email_stmt->bindParam(':email', $email_to_check);
        $email_stmt->bindParam(':id', $this->id); // id from session is safe
        $email_stmt->execute();

        if($email_stmt->rowCount() > 0) {
            return false; // Email is already in use
        }

        $query = "UPDATE " . $this->table_name . " SET name=:name, email=:email, role=:role, reports_to=:reports_to, division_id=:division_id, district_id=:district_id, upazila_id=:upazila_id, territory_id=:territory_id WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->role=htmlspecialchars(strip_tags($this->role));

        if (empty($this->reports_to)) {
            $this->reports_to = NULL;
        } else {
            $this->reports_to = (int) $this->reports_to;
        }

        // Handle geographic IDs: ensure they are NULL if empty, otherwise cast to int
        $this->division_id = empty($this->division_id) ? NULL : (int) $this->division_id;
        $this->district_id = empty($this->district_id) ? NULL : (int) $this->district_id;
        $this->upazila_id = empty($this->upazila_id) ? NULL : (int) $this->upazila_id;
        $this->territory_id = empty($this->territory_id) ? NULL : (int) $this->territory_id;

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id', $this->id);

        if ($this->reports_to === NULL) {
            $stmt->bindValue(":reports_to", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":reports_to", $this->reports_to, PDO::PARAM_INT);
        }

        if ($this->division_id === NULL) {
            $stmt->bindValue(":division_id", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":division_id", $this->division_id, PDO::PARAM_INT);
        }
        if ($this->district_id === NULL) {
            $stmt->bindValue(":district_id", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":district_id", $this->district_id, PDO::PARAM_INT);
        }
        if ($this->upazila_id === NULL) {
            $stmt->bindValue(":upazila_id", NULL, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":upazila_id", $this->upazila_id, PDO::PARAM_INT);
        }
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

    function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password=:password WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->password=htmlspecialchars(strip_tags($this->password));

        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function updateAvatar($avatar_path) {
        $query = "UPDATE " . $this->table_name . " SET avatar=:avatar WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->avatar=htmlspecialchars(strip_tags($avatar_path));

        $stmt->bindParam(':avatar', $this->avatar);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    function userExistsById(){
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num>0){
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