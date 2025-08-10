<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Division.php';
require_once 'classes/District.php';
require_once 'classes/Upazila.php';
require_once 'classes/Territory.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$division = new Division($db);
$district = new District($db);
$upazila = new Upazila($db);
$territory = new Territory($db);

$divisions = $division->read()->fetchAll(PDO::FETCH_ASSOC);
$districts = $district->read()->fetchAll(PDO::FETCH_ASSOC);
$upazilas = $upazila->read()->fetchAll(PDO::FETCH_ASSOC);
$territories = $territory->read()->fetchAll(PDO::FETCH_ASSOC);

$managers = $user->read()->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = $_POST['role'];
    $user->reports_to = !empty($_POST['reports_to']) ? $_POST['reports_to'] : null;
    $user->division_id = !empty($_POST['division_id']) ? $_POST['division_id'] : null;
    $user->district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $user->upazila_id = !empty($_POST['upazila_id']) ? $_POST['upazila_id'] : null;
    $user->territory_id = !empty($_POST['territory_id']) ? $_POST['territory_id'] : null;

    if ($user->create()) {
        header('location: login.php');
    } else {
        echo 'Something went wrong. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="SR">Sales Representative (SR)</option>
                    <option value="TSM">Territory Sales Manager (TSM)</option>
                    <option value="ASM">Area Sales Manager (ASM)</option>
                    <option value="DSM">Divisional Sales Manager (DSM)</option>
                    <option value="NSM">National Sales Manager (NSM)</option>
                    <option value="HOM">Head of Marketing (HOM)</option>
                </select>
            </div>

            <div class="form-group" id="reports-to-group">
                <label>Reports To</label>
                <select name="reports_to" id="reports_to" class="form-control">
                    <option value="">Select Manager</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?php echo $manager['id']; ?>" data-role="<?php echo $manager['role']; ?>"><?php echo $manager['name']; ?> (<?php echo $manager['role']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="division-group" style="display: none;">
                <label>Division</label>
                <select name="division_id" id="division_id" class="form-control">
                    <option value="">Select Division</option>
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?php echo $div['id']; ?>"><?php echo $div['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="district-group" style="display: none;">
                <label>District</label>
                <select name="district_id" id="district_id" class="form-control">
                    <option value="">Select District</option>
                </select>
            </div>

            <div class="form-group" id="upazila-group" style="display: none;">
                <label>Upazila</label>
                <select name="upazila_id" id="upazila_id" class="form-control">
                    <option value="">Select Upazila</option>
                </select>
            </div>

            <div class="form-group" id="territory-group" style="display: none;">
                <label>Territory</label>
                <select name="territory_id" id="territory_id" class="form-control">
                    <option value="">Select Territory</option>
                </select>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Register">
            </div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>
    <script>
        const allDistricts = <?php echo json_encode($districts); ?>;
        const allUpazilas = <?php echo json_encode($upazilas); ?>;
        const allTerritories = <?php echo json_encode($territories); ?>;
        const managers = <?php echo json_encode($managers); ?>;
    </script>
    <script src="assets/js/registration.js"></script>
</body>
</html>