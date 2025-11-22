<?php
// ===== POTWIERDZENIE ZAMÓWIENIA =====
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
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE order_number = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("s", $order_number);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit;
}

$products = json_decode($order['products'], true);

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
            <!-- Sukces -->
            <div class="success-icon">✅</div>
            
            <h1><?php echo t('order_placed_successfully'); ?></h1>
            
            <p class="confirmation-text">
                <?php echo t('order_confirmation_text'); ?>
            </p>
            
            <!-- Numer zamówienia -->
            <div class="order-number-box">
                <span><?php echo t('order_number'); ?>:</span>
                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
            </div>
            
            <!-- Szczegóły zamówienia -->
            <div class="order-details-card">
                <h2><?php echo t('order_details'); ?></h2>
                
                <div class="customer-info">
                    <h3>👤 <?php echo t('customer_data'); ?></h3>
                    <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                    <p>📧 <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p>📱 <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    
                    <?php if ($order['customer_address']): ?>
                        <p>📍 <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($order['customer_company']): ?>
                        <p>🏢 <?php echo htmlspecialchars($order['customer_company']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($order['customer_tax_id']): ?>
                        <p>💼 NIP: <?php echo htmlspecialchars($order['customer_tax_id']); ?></p>
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
                                    <td><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?></td>
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
                
                <?php if ($order['message']): ?>
                    <div class="order-message">
                        <h3>💬 <?php echo t('additional_notes'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($order['message'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Informacja o dalszych krokach -->
            <div class="next-steps">
                <h3><?php echo t('what_next'); ?></h3>
                <ul>
                    <li>✉️ <?php echo t('confirmation_step_1'); ?></li>
                    <li>📞 <?php echo t('confirmation_step_2'); ?></li>
                    <li>🚚 <?php echo t('confirmation_step_3'); ?></li>
                </ul>
            </div>
            
            <!-- Akcje -->
            <div class="confirmation-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="order-history.php" class="btn btn-primary">
                        📋 <?php echo t('view_order_history'); ?>
                    </a>
                <?php endif; ?>
                
                <a href="pages/products.php" class="btn btn-secondary">
                    🛒 <?php echo t('continue_shopping'); ?>
                </a>
                
                <a href="index.php" class="btn btn-secondary">
                    🏠 <?php echo t('back_to_home'); ?>
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.confirmation-page {
    padding: 60px 20px;
    min-height: calc(100vh - 200px);
}

.confirmation-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.success-icon {
    font-size: 120px;
    margin-bottom: 20px;
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
    }
    to {
        transform: scale(1);
    }
}

.confirmation-content h1 {
    color: #27ae60;
    font-size: 2.5em;
    margin-bottom: 20px;
}

.confirmation-text {
    font-size: 1.2em;
    color: #666;
    margin-bottom: 30px;
}

.order-number-box {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50px;
    font-size: 1.2em;
    margin-bottom: 40px;
}

/* Szczegóły zamówienia */
.order-details-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: left;
    margin-bottom: 30px;
}

.order-details-card h2 {
    color: #2c3e50;
    font-size: 1.5em;
    margin-bottom: 20px;
    text-align: center;
}

.order-details-card h3 {
    color: #555;
    font-size: 1.2em;
    margin: 20px 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.customer-info p {
    margin: 8px 0;
    color: #666;
}

/* Tabela produktów */
.products-table {
    width: 100%;
    margin-top: 15px;
    border-collapse: collapse;
}

.products-table th,
.products-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.products-table th {
    background: #f8f9fa;
    color: #555;
    font-weight: 600;
}

.products-table tbody tr:hover {
    background: #f8f9fa;
}

.products-table tfoot td {
    font-size: 1.2em;
    padding-top: 20px;
    border-bottom: none;
}

.total-price {
    color: #27ae60;
    font-size: 1.3em;
}

.order-message {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.order-message p {
    color: #555;
    margin: 0;
}

/* Następne kroki */
.next-steps {
    background: #e8f4f8;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: left;
}

.next-steps h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.next-steps ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.next-steps li {
    padding: 10px 0;
    color: #555;
    font-size: 1.05em;
}

/* Akcje */
.confirmation-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Przyciski */
.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-size: 16px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: white;
    color: #3498db;
    border: 2px solid #3498db;
}

.btn-secondary:hover {
    background: #3498db;
    color: white;
}

/* Responsywność */
@media (max-width: 768px) {
    .confirmation-content h1 {
        font-size: 1.8em;
    }
    
    .order-number-box {
        flex-direction: column;
        text-align: center;
    }
    
    .products-table {
        font-size: 0.9em;
    }
    
    .products-table th,
    .products-table td {
        padding: 8px;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
</body>
</html>
