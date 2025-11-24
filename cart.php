<?php
// ===== KOSZYK ZAKUPOWY - FINALNA WERSJA =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'includes/translations.php';

// INICJALIZACJA JĘZYKA (dodane na podstawie products.php)
$current_lang = getCurrentLanguage();

// Wyświetl info o sesji na górze strony (usuń to potem)
echo "";

// Inicjalizacja koszyka
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Obsługa dodawania produktu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id > 0 && $quantity > 0) {
    // Pobierz produkt
		try {
            // POPRAWIONE: użycie $current_lang do pobrania nazwy produktu
			$stmt = $pdo->prepare("SELECT id, name_" . $current_lang . " as name, price_base, unit, image FROM products WHERE id = ? AND active = 1");
			$stmt->execute([$product_id]);
			$product = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			error_log("Cart error: " . $e->getMessage());
			$product = false;
		}
    
    if ($product) {
            // Dodaj do koszyka
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price_base'],
                    'unit' => $product['unit'],
                    'image' => $product['image'],
                    'quantity' => $quantity
                ];
            }
            
            // Debug: Wyświetl co zostało dodane
            file_put_contents('/tmp/cart_debug.txt', 
                date('Y-m-d H:i:s') . " - Added product $product_id, Cart now: " . print_r($_SESSION['cart'], true) . "\n", 
                FILE_APPEND
            );
        }
    }
    
    // Redirect - NIE rób session_write_close, PHP zrobi to automatycznie
    header('Location: cart.php?added=1');
    exit;
}

// Obsługa update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if ($product_id > 0) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    
    header('Location: cart.php');
    exit;
}

// Obsługa remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $product_id = intval($_POST['product_id'] ?? 0);
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    
    header('Location: cart.php');
    exit;
}

// Obsługa clear
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

// Oblicz sumę koszyka
$cart_total = 0;
$cart_items = count($_SESSION['cart']);

foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

// Użycie funkcji t()
$page_title = t('cart_title');
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>

<main class="cart-page">
    <div class="container">
        <h1 class="page-title">🛒 <?php echo t('cart_title'); ?></h1>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">
                ✓ <?php echo t('cart_item_added'); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2><?php echo t('cart_empty'); ?></h2>
                <p><?php echo t('cart_empty_description'); ?></p>
                <a href="pages/products.php" class="btn btn-primary">
                    <?php echo t('orders_browse'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">📦</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="cart-item-price">
                                    <?php echo number_format($item['price'], 2); ?> € / <?php echo htmlspecialchars($item['unit']); ?>
                                </p>
                            </div>
                            
                            <div class="cart-item-quantity">
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <button type="button" class="qty-btn" onclick="changeQuantity(this, -1)">-</button>
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="0" class="qty-input" onchange="this.form.submit()">
                                    <button type="button" class="qty-btn" onclick="changeQuantity(this, 1)">+</button>
                                </form>
                            </div>
                            
                            <div class="cart-item-total">
                                <strong><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</strong>
                            </div>
                            
                            <div class="cart-item-remove">
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <button type="submit" class="btn-remove" title="<?php echo t('remove'); ?>">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3><?php echo t('order_summary'); ?></h3>
                        
                        <div class="summary-row">
                            <span><?php echo t('form_products'); ?>:</span>
                            <span><?php echo $cart_items; ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-row total">
                            <span><?php echo t('total'); ?>:</span>
                            <strong><?php echo number_format($cart_total, 2); ?> €</strong>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-primary btn-block">
                            <?php echo t('proceed_to_checkout'); ?>
                        </a>
                        
                        <a href="pages/products.php" class="btn btn-secondary btn-block">
                            <?php echo t('continue_shopping'); ?>
                        </a>
                        
                        <form method="POST" class="clear-cart-form">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-text btn-block" 
                                    onclick="return confirm('<?php echo t('cart_clear_confirm'); ?>')">
                                <?php echo t('clear_cart'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.cart-page { padding: 40px 20px; min-height: calc(100vh - 200px); }
.page-title { text-align: center; margin-bottom: 40px; color: #2c3e50; font-size: 2.5em; }
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.empty-cart { text-align: center; padding: 60px 20px; }
.empty-cart-icon { font-size: 120px; margin-bottom: 20px; opacity: 0.3; }
.empty-cart h2 { color: #666; margin-bottom: 10px; }
.empty-cart p { color: #999; margin-bottom: 30px; }
.cart-content { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }
.cart-items { display: flex; flex-direction: column; gap: 15px; }
.cart-item { display: grid; grid-template-columns: 100px 1fr 150px 120px 50px; gap: 20px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); align-items: center; }
.cart-item-image { width: 100px; height: 100px; border-radius: 8px; overflow: hidden; background: #f5f5f5; }
.cart-item-image img { width: 100%; height: 100%; object-fit: cover; }
.no-image { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc; }
.cart-item-details h3 { margin: 0 0 10px 0; color: #2c3e50; font-size: 1.1em; }
.cart-item-price { color: #7f8c8d; margin: 0; }
.quantity-form { display: flex; align-items: center; gap: 5px; }
.qty-btn { width: 32px; height: 32px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; font-size: 18px; transition: all 0.2s; }
.qty-btn:hover { background: #f8f9fa; border-color: #3498db; }
.qty-input { width: 60px; height: 32px; text-align: center; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
.cart-item-total { text-align: right; font-size: 1.2em; color: #27ae60; }
.btn-remove { background: none; border: none; font-size: 24px; cursor: pointer; opacity: 0.6; transition: opacity 0.2s; }
.btn-remove:hover { opacity: 1; }
.summary-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: sticky; top: 100px; }
.summary-card h3 { margin: 0 0 20px 0; color: #2c3e50; font-size: 1.3em; }
.summary-row { display: flex; justify-content: space-between; padding: 10px 0; color: #666; }
.summary-row.total { font-size: 1.3em; color: #2c3e50; padding: 15px 0; }
.summary-card hr { border: none; border-top: 1px solid #eee; margin: 15px 0; }
.btn { display: inline-block; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; text-align: center; cursor: pointer; transition: all 0.3s; border: none; font-size: 16px; }
.btn-primary {
    background-color: var(--color-primary);
    color: var(--color-white);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
.btn-secondary { background: white; color: #3498db; border: 2px solid #3498db; }
.btn-secondary:hover { background: #3498db; color: white; }
.btn-text { background: none; color: #e74c3c; padding: 10px; }
.btn-text:hover { background: #fee; }
.btn-block { display: block; width: 100%; margin-bottom: 10px; }
.clear-cart-form { margin-top: 15px; }
@media (max-width: 1024px) {
    .cart-content { grid-template-columns: 1fr; }
    .summary-card { position: static; }
}
@media (max-width: 768px) {
    .cart-item { grid-template-columns: 1fr; gap: 15px; text-align: center; }
    .cart-item-image { margin: 0 auto; }
    .cart-item-total { text-align: center; }
    .quantity-form { justify-content: center; }
}
</style>

<script>
function changeQuantity(btn, delta) {
    const form = btn.closest('.quantity-form');
    const input = form.querySelector('.qty-input');
    const newValue = Math.max(0, parseInt(input.value) + delta);
    input.value = newValue;
    form.submit();
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>