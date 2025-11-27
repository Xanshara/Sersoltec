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

// Sprawdź wishlist dla wszystkich produktów NARAZ (wydajne!)
$wishlistProducts = [];
if (isset($_SESSION['user_id']) && !empty($products)) {
    $productIds = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt_wish = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)");
    $stmt_wish->execute(array_merge([$_SESSION['user_id']], $productIds));
    $wishlistItems = $stmt_wish->fetchAll();
    $wishlistProducts = array_column($wishlistItems, 'product_id');
}

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
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
    <title><?php echo t('nav_products'); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
   <!-- <link rel="stylesheet" href="../assets/css/wishlist.css"> -->
    <link rel="stylesheet" href="../assets/css/chatbot-widget.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
                    <?php $inWishlist = in_array($product['id'], $wishlistProducts); ?>
                    <div class="product-card">
                        
                        <!-- WISHLIST HEART ICON - TYLKO DLA ZALOGOWANYCH -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button 
                                class="wishlist-icon-btn add-to-wishlist <?php echo $inWishlist ? 'in-wishlist' : ''; ?>" 
                                data-product-id="<?php echo $product['id']; ?>"
                                title="<?php echo $inWishlist ? t('wishlist_in_wishlist', $current_lang) : t('wishlist_add_to_wishlist', $current_lang); ?>"
                                <?php echo $inWishlist ? 'disabled' : ''; ?>
                            >
                                <i class="fa fa-heart<?php echo $inWishlist ? '' : '-o'; ?>"></i>
                            </button>
                        <?php endif; ?>
                        
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
                        <button 
                            class="btn-compare-list" 
                            onclick="addToComparisonList(<?php echo $product['id']; ?>, this)"
                            style="
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                gap: 6px;
                                background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
                                color: var(--color-white);
                                border: none;
                                padding: 10px 16px;
                                border-radius: var(--radius-md);
                                font-size: 0.9rem;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all var(--transition-normal);
                                width: 100%;
                                margin-top: 8px;
                            ">
                            ⚖️ Porównaj
                        </button>
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

/* Wishlist Icon Button Styles */
.product-card { 
    position: relative; 
    background: white; 
    border-radius: 8px; 
    overflow: hidden; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
}

.wishlist-icon-btn { 
    position: absolute; 
    top: 10px; 
    right: 10px; 
    width: 40px; 
    height: 40px; 
    background: white; 
    border: none; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    cursor: pointer; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
    z-index: 10; 
    color: #666; 
    transition: all 0.3s; 
}

.wishlist-icon-btn:hover:not(:disabled) { 
    background: #e91e63; 
    color: white; 
    transform: scale(1.15); 
}

.wishlist-icon-btn.in-wishlist { 
    background: #e91e63; 
    color: white; 
}

.wishlist-icon-btn i { 
    font-size: 18px; 
}
</style>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/wishlist.js"></script>
<script src="../assets/js/chatbot-widget.js"></script>
</body>
</html>
<script>
// Comparison function for products list page
function addToComparisonList(productId, button) {
    if (button.disabled) return;
    
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '⏳...';
    
    fetch('/sersoltec/api/comparison-api.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '✓';
            button.style.background = '#4CAF50';
            
            // Reload to update comparison bar
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('❌ ' + data.message);
            button.disabled = false;
            button.innerHTML = originalText;
            button.style.background = 'linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%)';
        }
    })
    .catch(err => {
        console.error('Comparison error:', err);
        alert('❌ Błąd połączenia');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>