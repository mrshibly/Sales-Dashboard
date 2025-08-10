<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: ../login.php');
    exit;
}

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Division.php';
require_once 'classes/District.php';
require_once 'classes/Upazila.php';
require_once 'classes/Territory.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all users for reports_to dropdown
$user = new User($db);
$all_users_query = "SELECT id, name FROM users WHERE id != :current_user_id";
$stmt_all_users = $db->prepare($all_users_query);
$stmt_all_users->bindParam(':current_user_id', $_SESSION['id']);
$stmt_all_users->execute();
$all_users = $stmt_all_users->fetchAll(PDO::FETCH_ASSOC);

// Fetch all geographic data
$division = new Division($db);
$district = new District($db);
$upazila = new Upazila($db);
$territory = new Territory($db);

$divisions = $division->read()->fetchAll(PDO::FETCH_ASSOC);
$districts = $district->read()->fetchAll(PDO::FETCH_ASSOC);
$upazilas = $upazila->read()->fetchAll(PDO::FETCH_ASSOC);
$territories = $territory->read()->fetchAll(PDO::FETCH_ASSOC);
?>

<header class="main-header">
    <h1>User Profile & Settings</h1>
</header>

<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>

<section class="profile-section">
    <h2>Your Profile</h2>
    <div class="profile-avatar">
        <img src="uploads/avatars/image.png" alt="User Avatar" id="avatar-img">
    </div>

    <form id="profileForm">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
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
        <div class="form-group">
            <label>Reports To (User ID)</label>
            <select name="reports_to" id="reports_to" class="form-control">
                <option value="">None</option>
                <?php foreach ($all_users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']) . " (ID: " . $u['id'] . ")"; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="division-group">
            <label for="division_id" class="form-label">Division</label>
            <select class="form-select" id="division_id" name="division_id">
                <option value="">Select Division</option>
                <?php foreach ($divisions as $div): ?>
                    <option value="<?php echo $div['id']; ?>"><?php echo $div['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="district-group">
            <label for="district_id" class="form-label">District</label>
            <select class="form-select" id="district_id" name="district_id">
                <option value="">Select District</option>
            </select>
        </div>

        <div class="form-group" id="upazila-group">
            <label for="upazila_id" class="form-label">Upazila</label>
            <select class="form-select" id="upazila_id" name="upazila_id">
                <option value="">Select Upazila</option>
            </select>
        </div>

        <div class="form-group" id="territory-group">
            <label for="territory_id" class="form-label">Territory</label>
            <select class="form-select" id="territory_id" name="territory_id">
                <option value="">Select Territory</option>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>

    <hr>

    <h2>Change Password</h2>
    <form id="passwordForm">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Change Password</button>
        </div>
    </form>

    <hr>

    <h2>Upload Avatar</h2>
    <form id="avatarForm" enctype="multipart/form-data">
        <div class="form-group">
            <label>Select Image to upload:</label>
            <input type="file" name="avatar" class="form-control">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Upload Image</button>
        </div>
    </form>
</section>

<script>
    const allDistricts = <?php echo json_encode($districts); ?>;
    const allUpazilas = <?php echo json_encode($upazilas); ?>;
    const allTerritories = <?php echo json_encode($territories); ?>;
</script>