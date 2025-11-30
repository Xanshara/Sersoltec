<?php
// ===== POTWIERDZENIE ZAMÓWIENIA =====

// 1. POPRAWKA KODOWANIA: Dodanie nagłówka dla wymuszenia kodowania UTF-8
header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'includes/translations.php';

$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    header('Location: index.php');
    exit;
}

// Pobierz szczegóły zamówienia
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Order fetch error: " . $e->getMessage());
    $order = false;
}

if (!$order) {
    header('Location: index.php');
    exit;
}

// Pobierz produkty z order_items
try {
    $stmt = $pdo->prepare("SELECT product_name as name, quantity, price_per_unit as price FROM order_items WHERE order_id = ?");
    $stmt->execute([$order['id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

$page_title = t('order_confirmation');
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>

<main class="confirmation-page">
    <div class="container">
        <div class="confirmation-content">
            <div class="success-icon">✅</div>
            
            <h1><?php echo t('order_placed_successfully'); ?></h1>
            
            <p class="confirmation-text">
                <?php echo t('order_confirmation_text'); ?>
            </p>
                   
            <div class="confirmation-actions">
                <a class="btn btn-primary"><?php echo t('order_number'); ?>: <?php echo htmlspecialchars($order['order_number']); ?></a>
            </div>
			<br>
            
            <div class="order-details-card">
                <h2><?php echo t('order_details'); ?></h2>
                
                <div class="customer-info">
                    <h3>👤 <?php echo t('customer_data'); ?></h3>
                    <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                    <p>📧 <?php echo htmlspecialchars($order['email']); ?></p>
                    <p>📞 <?php echo htmlspecialchars($order['phone']); ?></p>
                    
                    <?php if ($order['address']): ?>
                        <p>📍 <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($order['company']): ?>
                        <p>🏢 <?php echo htmlspecialchars($order['company']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($order['tax_id']): ?>
                        <p>💳 NIP: <?php echo htmlspecialchars($order['tax_id']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="order-products">
                    <h3>📦 <?php echo t('ordered_products'); ?></h3>
                    
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th><?php echo t('product'); ?></th>
                                <th><?php echo t('quantity'); ?></th>
                                <th><?php echo t('price'); ?></th>
                                <th><?php echo t('total'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($products as $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $total += $item_total;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo number_format($item['price'], 2); ?> €</td>
                                    <td><strong><?php echo number_format($item_total, 2); ?> €</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong><?php echo t('total'); ?>:</strong></td>
                                <td><strong class="total-price"><?php echo number_format($total, 2); ?> €</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if ($order['notes']): ?>
                    <div class="order-message">
                        <h3>💬 <?php echo t('additional_notes'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="next-steps">
                <h3><?php echo t('what_next'); ?></h3>
                <ul>
                    <li>✉️  <?php echo t('confirmation_step_1'); ?></li>
                    <li>📞 <?php echo t('confirmation_step_2'); ?></li>
                    <li>🚚 <?php echo t('confirmation_step_3'); ?></li>
                </ul>
            </div>
            
            <div class="confirmation-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="order-history.php" class="btn btn-primary">
                        📑 <?php echo t('view_order_history'); ?>
                    </a>
                <?php endif; ?>
                
                <a href="pages/products.php" class="btn btn-secondary">
                        🛍️ <?php echo t('continue_shopping'); ?>
                </a>
                
                <a href="index.php" class="btn btn-secondary">
                        🏠 <?php echo t('back_to_home'); ?>
                </a>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>