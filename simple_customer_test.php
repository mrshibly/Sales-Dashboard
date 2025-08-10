<?php
require_once 'config/database.php';
require_once 'classes/Customer.php';
require_once 'classes/Database.php';

$database = new Database();
$db = $database->getConnection();
$customer = new Customer($db);

$message = '';

// Handle form submission for creating a customer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer->name = $_POST['name'];
    $customer->phone = $_POST['phone'];
    $customer->email = $_POST['email'];
    $customer->address = $_POST['address'];
    $customer->assigned_sales_rep_id = $_POST['assigned_sales_rep_id'];

    if ($customer->create()) {
        $message = 'Customer created successfully!';
    } else {
        $message = 'Failed to create customer. Error: ' . implode(', ', $db->errorInfo());
    }
}

// Fetch all customers to display
$stmt = $customer->read();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Customer Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin-bottom: 30px; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        form div { margin-bottom: 10px; }
        label { display: inline-block; width: 120px; }
        input[type="text"], input[type="email"] { width: 250px; padding: 5px; }
        button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { margin-bottom: 20px; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <h1>Add New Customer</h1>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'Failed') !== false) ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone">
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email">
        </div>
        <div>
            <label for="address">Address:</label>
            <input type="text" id="address" name="address">
        </div>
        <div>
            <label for="assigned_sales_rep_id">Sales Rep ID:</label>
            <input type="text" id="assigned_sales_rep_id" name="assigned_sales_rep_id" required>
        </div>
        <button type="submit">Add Customer</button>
    </form>

    <h1>Existing Customers</h1>
    <?php if (count($customers) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Sales Rep Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer_data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer_data['id']); ?></td>
                        <td><?php echo htmlspecialchars($customer_data['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer_data['phone']); ?></td>
                        <td><?php echo htmlspecialchars($customer_data['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer_data['address']); ?></td>
                        <td><?php echo htmlspecialchars($customer_data['sales_rep_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No customers found.</p>
    <?php endif; ?>
</body>
</html>
