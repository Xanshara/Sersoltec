<?php
// Redirect from old order_success.php to new order-confirmation.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    require_once 'config.php';
    
    $order_id = intval($_GET['id']);
    
    try {
        $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$order_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            header('Location: order-confirmation.php?order=' . urlencode($result['order_number']));
            exit;
        }
    } catch (PDOException $e) {
        // Silent error
    }
}

// Redirect to home if no order found
header('Location: index.php');
exit;