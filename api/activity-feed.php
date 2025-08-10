<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

// Fetch recent activities (e.g., last 10)
// In a real application, you would have a dedicated 'activities' table.
// For this example, we'll generate some sample data from existing tables.

$query = "
    (SELECT u.name, 'placed a new order' as action, o.created_at as time FROM orders o JOIN users u ON o.sales_rep_id = u.id ORDER BY o.created_at DESC LIMIT 5)
    UNION
    (SELECT u.name, 'added a new customer' as action, c.created_at as time FROM customers c JOIN users u ON c.assigned_sales_rep_id = u.id ORDER BY c.created_at DESC LIMIT 5)
    UNION
    (SELECT u.name, 'updated a sales target' as action, t.updated_at as time FROM targets t JOIN users u ON t.user_id = u.id ORDER BY t.updated_at DESC LIMIT 5)
    ORDER BY time DESC LIMIT 10
";

$stmt = $db->prepare($query);
$stmt->execute();

$activities = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $activities[] = [
        'user' => $row['name'],
        'action' => $row['action'],
        'time' => date("M d, H:i", strtotime($row['time']))
    ];
}

echo json_encode($activities);
?>