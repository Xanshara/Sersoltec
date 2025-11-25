<?php
// ===== SZCZEGÓŁY PRODUKTU =====

require_once '../config.php';
// UWAGA: Usunięto require_once '../includes/translations.php'; 
// Ponieważ plik about.php dowiódł, że translations.php jest już dołączany przez config.php!

$current_lang = getCurrentLanguage();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// Pobierz produkt
$stmt = $pdo->prepare(
    "SELECT p.*, c.name_pl, c.name_en, c.name_es, c.slug 
     FROM products p 
     JOIN categories c ON p.category_id = c.id 
     WHERE p.id = ? AND p.active = 1"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Sprawdź czy produkt jest już w wishliście
$inWishlist = false;
if (isset($_SESSION['user_id'])) {
    $stmt_wish = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt_wish->execute([$_SESSION['user_id'], $product_id]);
    $inWishlist = $stmt_wish->fetch() ? true : false;
}

// Pobierz podobne produkty (ta sama kategoria)
$stmt = $pdo->prepare(
    "SELECT * FROM products WHERE category_id = ? AND id != ? AND active = 1 ORDER BY RAND() LIMIT 4"
);
$stmt->execute([$product['category_id'], $product_id]);
$similar_products = $stmt->fetchAll();

// Rozpakuj specyfikacje
$specs = json_decode($product['specifications'], true) ?? [];

// Rozpakuj zdjęcia
$images = json_decode($product['images'], true) ?? [];
if ($product['image'] && !in_array($product['image'], $images)) {
    array_unshift($images, $product['image']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
    <meta name="description" content="<?php echo htmlspecialchars($product['description_' . $current_lang]); ?>">
    <title><?php echo htmlspecialchars($product['name_' . $current_lang]); ?> - <?php echo t('nav_products'); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/wishlist.css">
    <link rel="stylesheet" href="../assets/css/chatbot-widget.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<nav style="padding: 1rem; background: var(--color-light-gray); border-bottom: 1px solid var(--color-gray);">
    <div class="container" style="font-size: 0.9rem;">
        <a href="../index.php"><?php echo t('nav_home'); ?></a> / 
        <a href="products.php"><?php echo t('nav_products'); ?></a> / 
        <a href="products.php?category=<?php echo htmlspecialchars($product['slug']); ?>">
            <?php echo htmlspecialchars($product['name_' . $current_lang]); ?>
        </a> / 
        <strong><?php echo htmlspecialchars(substr($product['name_' . $current_lang], 0, 50)); ?></strong>
    </div>
</nav>

<section class="product-detail">
    <div class="container" style="grid-template-columns: 1fr 1fr; gap: 3rem;">
        <div class="product-detail-image">
            <?php if ($product['image']): ?>
                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name_' . $current_lang]); ?>">
            <?php else: ?>
                <span style="font-size: 4rem;">📦</span>
            <?php endif; ?>
        </div>
        
        <div class="product-detail-info">
            <div style="font-size: 0.85rem; color: var(--color-primary); font-weight: 600; margin-bottom: 0.5rem;">
                <?php echo t('product_sku'); ?>: <?php echo htmlspecialchars($product['sku']); ?>
            </div>
            
            <h1><?php echo htmlspecialchars($product['name_' . $current_lang]); ?></h1>
            
            <div style="margin: 1.5rem 0;">
                <?php if ($product['price_base']): ?>
                    <div class="product-price" style="margin: 0;">
                        <?php echo formatPrice($product['price_base']); ?>
                    </div>
                    <?php if ($product['price_min'] || $product['price_max']): ?>
                        <div style="font-size: 0.9rem; color: var(--color-text); margin-top: 0.5rem;">
                            <?php if ($product['price_min']): ?>
                                <?php echo t('price_from'); ?>: <?php echo formatPrice($product['price_min']); ?>
                            <?php endif; ?>
                            <?php if ($product['price_max']): ?>
                                <?php echo t('price_to'); ?>: <?php echo formatPrice($product['price_max']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="color: var(--color-text); font-size: 0.95rem;">
                        <?php echo t('product_contact_for_price'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin: 1.5rem 0; line-height: 1.8; color: var(--color-text);">
                <?php echo nl2br(htmlspecialchars($product['description_' . $current_lang])); ?>
            </div>
            
            <div class="product-meta">
                <dt><?php echo t('product_unit_label'); ?>:</dt>
                <dd><?php echo htmlspecialchars($product['unit'] ?? t('product_unit_default')); ?></dd>
                
                <dt><?php echo t('product_weight_label'); ?>:</dt>
                <dd><?php echo $product['weight'] ? htmlspecialchars($product['weight']) . ' kg' : t('no_data'); ?></dd>
                
                <dt><?php echo t('b2b_label'); ?>:</dt>
                <dd><?php echo $product['b2b_only'] ? '✓ ' . t('b2b_only') : t('available_for_all'); ?></dd>
            </div>
            
            <?php if ($specs): ?>
                <div style="margin: 1.5rem 0; padding: 1rem; background: var(--color-light-gray); border-radius: 4px;">
                    <h3 style="margin-bottom: 1rem; color: var(--color-primary);"><?php echo t('specifications_title'); ?></h3>
                    <dl style="display: grid; grid-template-columns: auto 1fr; gap: 1rem;">
                        <?php foreach ($specs as $key => $value): ?>
                            <dt style="font-weight: 600; color: var(--color-primary);">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:
                            </dt>
                            <dd style="margin: 0;">
                                <?php echo htmlspecialchars($value); ?>
                            </dd>
                        <?php endforeach; ?>
                    </dl>
                </div>
            <?php endif; ?>
            
<div style="margin-top: 2rem;">
                <?php if ($product['price_base']): ?>
                    <form method="POST" action="../cart.php" style="margin-bottom: 1rem;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                            <label for="quantity" style="font-weight: 600; color: var(--color-primary);">
                                <?php echo t('quantity_label'); ?>:
                            </label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   value="1" 
                                   min="1" 
                                   max="999"
                                   style="width: 100px; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; text-align: center;">
                            <span style="color: var(--color-text);">
                                <?php echo htmlspecialchars($product['unit'] ?? t('product_unit_default')); ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                            <button type="submit" class="btn btn-success btn-lg" style="flex: 2; text-align: center; font-size: 18px;">
                                🛒 <?php echo t('add_to_cart_btn'); ?>
                            </button>
                            
                            <!-- WISHLIST BUTTON - TYLKO DLA ZALOGOWANYCH -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button 
                                    type="button"
                                    class="btn btn-wishlist add-to-wishlist <?php echo $inWishlist ? 'in-wishlist' : ''; ?>" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    style="flex: 1;"
                                    <?php echo $inWishlist ? 'disabled' : ''; ?>
                                >
                                    <i class="fa fa-heart<?php echo $inWishlist ? '' : '-o'; ?>"></i>
                                    <?php echo $inWishlist ? t('wishlist_in_wishlist', $current_lang) : t('wishlist_add_to_wishlist', $current_lang); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div style="display: flex; gap: 1rem; flex-direction: column;">
                    <a href="contact.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary btn-lg" style="text-align: center;">
                        <?php echo t('inquire_about_offer_btn'); ?>
                    </a>
                    <a href="calculator.php" class="btn btn-secondary btn-lg" style="text-align: center;">
                        <?php echo t('use_calculator_btn'); ?>
                    </a>
                    <a href="products.php?category=<?php echo htmlspecialchars($product['slug']); ?>" class="btn btn-outline" style="text-align: center;">
                        ← <?php echo t('back_to_category_btn'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($similar_products): ?>
    <section style="padding: 3rem 1rem; background: var(--color-light-gray);">
        <div class="container">
            <div class="section-header">
                <h2><?php echo t('product_similar'); ?></h2>
            </div>
            
            <div class="products-grid">
                <?php foreach ($similar_products as $sim_prod): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($sim_prod['image']): ?>
                                <img src="../assets/images/products/<?php echo htmlspecialchars($sim_prod['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($sim_prod['name_' . $current_lang]); ?>">
                            <?php else: ?>
                                <span>📦</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-category">
                                <?php echo t('product_sku'); ?>: <?php echo htmlspecialchars($sim_prod['sku']); ?>
                            </div>
                            <h3 class="product-name">
                                <?php echo htmlspecialchars(substr($sim_prod['name_' . $current_lang], 0, 40)); ?>
                            </h3>
                            <p class="product-desc">
                                <?php echo htmlspecialchars(substr($sim_prod['description_' . $current_lang], 0, 80)); ?>...
                            </p>
                            
                            <?php if ($sim_prod['price_base']): ?>
                                <div class="product-price">
                                    <?php echo formatPrice($sim_prod['price_base']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $sim_prod['id']; ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                                    <?php echo t('details_btn'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/wishlist.js"></script>
<script src="../assets/js/chatbot-widget.js"></script>

<style>
/* Wishlist Button Styles */
.btn-wishlist {
    background: white;
    color: #e91e63;
    border: 2px solid #e91e63 !important;
    padding: 15px 30px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.btn-wishlist:hover:not(:disabled) {
    background: #e91e63;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(233, 30, 99, 0.4);
}

.btn-wishlist.in-wishlist {
    background: #ffebee;
    border-color: #e91e63 !important;
    color: #e91e63;
    cursor: not-allowed;
}

.btn-wishlist i {
    font-size: 18px;
}
</style>
</body>
</html>