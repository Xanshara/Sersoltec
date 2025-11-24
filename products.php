<?php
// ===== KATALOG PRODUKTÓW =====

require_once '../config.php';

$current_lang = getCurrentLanguage();
$selected_category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Pobierz wszystkie kategorie
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY `order`");
$all_categories = $stmt->fetchAll();

// Buduj zapytanie produktów
$query = "SELECT p.*, c.slug as category_slug, c.name_pl, c.name_en, c.name_es 
          FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE p.active = 1";
$params = [];

if ($selected_category) {
    $query .= " AND c.slug = ?";
    $params[] = $selected_category;
}

if ($search_query) {
    $query .= " AND (p.name_" . $current_lang . " LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$query .= " ORDER BY p.name_" . $current_lang . " ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Pobierz kategorię info jeśli wybrana
$selected_cat_info = null;
if ($selected_category) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$selected_category]);
    $selected_cat_info = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('nav_products'); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/chatbot-widget.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<section class="page-header" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); color: white; padding: 4rem 1rem; text-align: center;">
    <div class="container">
        <h1><?php echo t('nav_products'); ?></h1>
        <p><?php 
            if ($selected_cat_info) {
                echo htmlspecialchars($selected_cat_info['name_' . $current_lang]);
            } else {
                echo t('hero_subtitle');
            }
        ?></p>
    </div>
</section>

<section class="products-section">
    <div class="container">
        <div class="filters">
            <form method="get" class="filters-form">
                <div class="filter-group" style="flex: 1; min-width: 200px;">
                    <label class="filter-label"><?php echo t('cat_pvc_windows'); ?> / <?php echo t('nav_products'); ?></label>
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">--- <?php echo t('nav_products'); ?> ---</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>" 
                                <?php if ($cat['slug'] === $selected_category) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['name_' . $current_lang]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group" style="flex: 2; min-width: 250px;">
                    <label class="filter-label"><?php echo t('product_search_label'); ?></label>
                    <input type="text" name="search" class="filter-input" placeholder="<?php echo t('product_search_placeholder'); ?>" 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary" style="margin-top: 1.75rem;">
                        <?php echo t('product_search_label'); ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($products): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name_' . $current_lang]); ?>">
                            <?php else: ?>
                                <span>📦</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-category">
                                <?php echo htmlspecialchars($product['name_' . $current_lang]); ?>
                            </div>
                            
                            <h3 class="product-name">
                                <?php echo htmlspecialchars(substr($product['name_' . $current_lang], 0, 50)); ?>
                            </h3>
                            
                            <p class="product-desc">
                                <?php echo htmlspecialchars(substr($product['description_' . $current_lang], 0, 100)); ?>...
                            </p>
                            
                            <div class="product-specs">
                                <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?><br>
                                <strong><?php echo t('product_unit'); ?>:</strong> <?php echo htmlspecialchars($product['unit'] ?? t('product_unit_default')); ?>
                            </div>
                            
                            <?php if ($product['price_base']): ?>
                                <div class="product-price">
                                    <?php echo formatPrice($product['price_base']); ?>
                                </div>
                            <?php else: ?>
                                <div class="product-price" style="color: var(--color-text); font-size: 0.9rem;">
                                    <?php echo t('product_contact_for_price'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                    <?php echo t('product_details'); ?>
                                </a>
                                
                                <?php if ($product['price_base']): ?>
                                    <form method="POST" action="../cart.php" style="display: inline; flex: 1;">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-success btn-sm" style="width: 100%;">
                                            🛒 <?php echo t('product_add'); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="contact.php?product_id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">
                                        <?php echo t('product_inquire_btn'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: var(--color-light-gray); border-radius: 8px;">
                <p><?php echo t('no_products'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Dodatkowe style dla przycisku dodawania do koszyka */
.btn-success {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    color: white;
    border: none;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.4);
}

.product-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.product-actions .btn {
    flex: 1;
}

.product-actions form {
    flex: 1;
}
</style>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/chatbot-widget.js"></script>
</body>
</html>