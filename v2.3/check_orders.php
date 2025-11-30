<?php
// DEBUG - check what orders exist in database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Orders Debug</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#667eea;color:white;}</style>";
echo "</head><body><h1>Orders in Database</h1>";

try {
    $stmt = $pdo->query("SELECT id, order_number, full_name, email, created_at, status FROM orders ORDER BY id DESC LIMIT 20");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orders) > 0) {
        echo "<table><thead><tr><th>ID</th><th>Order Number</th><th>Customer</th><th>Email</th><th>Date</th><th>Status</th><th>Link</th></tr></thead><tbody>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id'] . "</td>";
            echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($order['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($order['email']) . "</td>";
            echo "<td>" . $order['created_at'] . "</td>";
            echo "<td>" . $order['status'] . "</td>";
            echo "<td><a href='order_success.php?id=" . $order['id'] . "'>Test Link</a> | <a href='order-confirmation.php?order=" . urlencode($order['order_number']) . "'>Direct</a></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No orders found in database.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>