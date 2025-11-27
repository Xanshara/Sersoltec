<?php
/**
 * Wishlist Page - Fixed Paths Version
 * Wersja z naprawionymi ścieżkami dla header/footer
 */

// Debug mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Określ ścieżkę bazową
$isInPages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
$basePath = $isInPages ? '../' : '';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
if (file_exists($basePath . 'config.php')) {
    require_once $basePath . 'config.php';
} else {
    die('❌ Nie można załadować config.php - sprawdź ścieżkę!');
}

// Load translations
if (file_exists($basePath . 'includes/translations.php')) {
    require_once $basePath . 'includes/translations.php';
}
if (file_exists($basePath . 'includes/wishlist-translations.php')) {
    require_once $basePath . 'includes/wishlist-translations.php';
}

// Get language
$lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'pl';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $redirectUrl = $basePath . 'auth.php?action=login&redirect=' . urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . $redirectUrl);
    exit;
}

$userId = $_SESSION['user_id'];

// Connect to database
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// AUTO-DETECT: Sprawdź strukturę tabeli products
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Mapowanie możliwych nazw kolumn
    $columnMap = [
        'name' => in_array('name_' . $lang, $columns) ? 'name_' . $lang : (in_array('name', $columns) ? 'name' : 'name_pl'),
        'price' => in_array('price_base', $columns) ? 'price_base' : (in_array('price', $columns) ? 'price' : 'price_base'),
        'image' => in_array('image', $columns) ? 'image' : (in_array('image_url', $columns) ? 'image_url' : 'image'),
        'stock' => in_array('stock_quantity', $columns) ? 'stock_quantity' : (in_array('stock', $columns) ? 'stock' : null),
        'active' => in_array('is_active', $columns) ? 'is_active' : (in_array('active', $columns) ? 'active' : 'active')
    ];
    
    // Buduj zapytanie dynamicznie
    $selectFields = "
        w.id as wishlist_id,
        w.product_id,
        w.added_at,
        p.{$columnMap['name']} as name,
        p.{$columnMap['price']} as price,
        p.{$columnMap['image']} as image_url,
        p.sku";
    
    if ($columnMap['stock']) {
        $selectFields .= ",\n        p.{$columnMap['stock']} as stock_quantity";
    }
    
    $selectFields .= ",\n        p.{$columnMap['active']} as is_active";
    
    $query = "
        SELECT {$selectFields}
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $wishlistItems = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('Query failed: ' . $e->getMessage());
}

// Helper functions
function formatPrice($price) {
    if (!$price) return 'N/A';
    // Użycie formatowania z config.php, jeśli jest dostępne, w przeciwnym razie domyślne.
    return number_format($price, 2, ',', ' ') . ' €';
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'przed chwilą';
    if ($diff < 3600) return floor($diff / 60) . ' min temu';
    if ($diff < 86400) return floor($diff / 3600) . ' godz. temu';
    if ($diff < 604800) return floor($diff / 86400) . ' dni temu';
    
    return date('d.m.Y', $timestamp);
}

function wt($key, $lang = 'pl') {
    global $translations;
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Page title
$pageTitle = wt('wishlist_page_title', $lang) . ' (' . count($wishlistItems) . ')';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php 
// Include header
if (file_exists($basePath . 'includes/header.php')) {
    include $basePath . 'includes/header.php';
} else {
    echo '<div style="padding: 20px; background: #f44336; color: white;">❌ Nie można załadować header.php z: ' . $basePath . 'includes/header.php</div>';
}
?>

<div class="wishlist-page">
    <div class="container">
        
        <div class="page-header">
            <h1>
                <i class="fa fa-heart"></i> 
                <?php echo wt('wishlist_page_title', $lang); ?>
                <span class="wishlist-count">(<?php echo count($wishlistItems); ?>)</span>
            </h1>
            <p class="page-subtitle">
                <?php echo wt('wishlist_page_subtitle', $lang); ?>
            </p>
        </div>
        
        <?php if (empty($wishlistItems)): ?>
            
            <div class="empty-wishlist">
                <div class="empty-icon">
                    <i class="fa fa-heart-o"></i>
                </div>
                <h2>
                    <?php echo wt('wishlist_empty_title', $lang); ?>
                </h2>
                <p>
                    <?php echo wt('wishlist_empty_message', $lang); ?>
                </p>
                <a href="<?php echo $basePath; ?>pages/products.php" class="btn btn-primary btn-lg empty-cta-btn">
                    <i class="fa fa-shopping-bag"></i> 
                    <?php echo wt('wishlist_empty_cta', $lang); ?>
                </a>
            </div>
            
        <?php else: ?>
            
            <div class="wishlist-grid">
                
                <?php foreach ($wishlistItems as $item): ?>
                    
                    <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                        
                        <div class="wishlist-item-image">
                            <a href="<?php echo $basePath; ?>pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img 
                                        src="<?php echo $basePath; ?>assets/images/products/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        📦
                                    </div>
                                <?php endif; ?>
                            </a>
                            
                            <?php if (isset($item['stock_quantity'])): ?>
                                <?php if ($item['stock_quantity'] <= 0): ?>
                                    <span class="stock-badge out-of-stock">
                                        <?php echo wt('wishlist_out_of_stock', $lang); ?>
                                    </span>
                                <? elseif ($item['stock_quantity'] < 5): ?>
                                    <span class="stock-badge low-stock">
                                        <?php echo wt('wishlist_low_stock', $lang); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="wishlist-item-info">
                            
                            <h3 class="wishlist-item-title">
                                <a href="<?php echo $basePath; ?>pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </h3>
                            
                            <p class="wishlist-item-sku">
                                SKU: <?php echo htmlspecialchars($item['sku']); ?>
                            </p>
                            
                            <div class="wishlist-item-price">
                                <span class="price">
                                    <?php echo formatPrice($item['price']); ?>
                                </span>
                            </div>
                            
                            <p class="wishlist-item-date">
                                <i class="fa fa-clock-o"></i>
                                <?php echo wt('wishlist_added', $lang); ?>: <?php echo timeAgo($item['added_at']); ?>
                            </p>
                            
                        </div>
                        
                        <div class="wishlist-item-actions">
                            
                            <?php 
                            $inStock = !isset($item['stock_quantity']) || $item['stock_quantity'] > 0;
                            $isActive = !isset($item['is_active']) || $item['is_active'];
                            ?>
                            
                            <?php if ($inStock && $isActive): ?>
                                <form method="POST" action="<?php echo $basePath; ?>cart.php" class="add-to-cart-form">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="add-to-cart-btn">
                                        <i class="fa fa-shopping-cart"></i> 
                                        <?php echo wt('wishlist_add_to_cart', $lang); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="unavailable-btn" disabled>
                                    <i class="fa fa-ban"></i> 
                                    <?php echo wt('wishlist_unavailable', $lang); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button 
                                class="btn btn-outline remove-from-wishlist remove-btn" 
                                data-product-id="<?php echo $item['product_id']; ?>"
                                title="<?php echo wt('wishlist_remove', $lang); ?>"
                            >
                                <i class="fa fa-trash"></i> 
                                <span class="btn-text-only"><?php echo wt('wishlist_remove', $lang); ?></span>
                            </button>
                            
                        </div>
                        
                    </div>
                    
                <?php endforeach; ?>
                
            </div>
            
            <div class="wishlist-footer">
                <a href="<?php echo $basePath; ?>pages/products.php" class="btn btn-secondary continue-shopping-btn">
                    <i class="fa fa-arrow-left"></i> 
                    <?php echo wt('wishlist_continue_shopping', $lang); ?>
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<style>
/* ===================================
   WISHLIST PAGE STYLES (ZINTEGROWANE Z styles.css)
   =================================== */

/* Definicje zmiennych ze styles.css dla kontekstu (zmienne są już globalne w head, ale ułatwia to czytelność) */
/*
:root {
    --color-primary: #1a4d2e;
    --color-primary-dark: #0f3d25;
    --color-primary-light: #2d7a4a;
    --color-accent: #8b9467;
    --color-white: #ffffff;
    --color-light-gray: #f8f8f8;
    --color-text: #2c2c2c;
    --radius-md: 8px;
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
}
*/

.wishlist-page {
    min-height: 60vh;
    padding: 40px 0;
    /* Używamy koloru tła ze styles.css */
    background: var(--color-light-gray); 
}

.wishlist-page .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Page Header */
.wishlist-page .page-header {
    text-align: center; 
    margin-bottom: 40px;
}

.wishlist-page .page-header h1 {
    font-size: 32px; 
    /* Używamy koloru nagłówka ze styles.css */
    color: var(--color-primary-dark); 
    margin-bottom: 10px;
}

.wishlist-page .page-header h1 i.fa-heart {
    /* Akcentujemy ikonę kolorem akcentującym */
    color: var(--color-accent);
}

.wishlist-page .page-header .wishlist-count {
    font-size: 24px; 
    color: #666;
}

.wishlist-page .page-subtitle {
    color: var(--color-text); 
    font-size: 16px;
    opacity: 0.8;
}

/* Empty State */
.empty-wishlist {
    text-align: center;
    padding: 80px 20px;
    /* Używamy jasnego tła dla kontrastu na ciemnym tle strony */
    background: var(--color-white); 
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
}

.empty-wishlist .empty-icon i {
    font-size: 64px;
    /* Widoczny, neutralny kolor ikony */
    color: var(--color-gray); 
    margin-bottom: 20px;
}

.empty-wishlist h2 {
    font-size: 24px;
    color: var(--color-primary-dark);
    margin-bottom: 10px;
}

.empty-wishlist p {
    color: var(--color-text);
    margin-bottom: 30px;
}

.empty-cta-btn {
    /* Dopasowanie do stylu .btn-lg w styles.css */
    /* Poniższe inline style zostały usunięte i zastąpione klasami: */
    /* display: inline-block; padding: 15px 30px; background: linear-gradient(...); color: white; text-decoration: none; border-radius: 4px; */
}


/* Wishlist Grid */
.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 30px;
    margin-bottom: 40px;
}

/* Wishlist Item */
.wishlist-item {
    background: var(--color-white);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid var(--color-gray-light);
}

.wishlist-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
    border-color: var(--color-primary);
}

/* Item Image */
.wishlist-item-image {
    position: relative;
    padding-top: 100%; /* Ratio 1:1 */
    overflow: hidden;
    background: var(--color-light-gray);
}

.wishlist-item-image a img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.wishlist-item:hover .wishlist-item-image a img {
    transform: scale(1.05);
}

.no-image-placeholder {
    position: absolute; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%); 
    font-size: 48px;
}

/* Stock Badge */
.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    color: var(--color-white);
    font-size: 12px;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 4px;
    z-index: 5;
}

.stock-badge.out-of-stock {
    background: #d32f2f; /* Standard Red */
}

.stock-badge.low-stock {
    background: #fbc02d; /* Standard Yellow */
}

/* Item Info */
.wishlist-item-info {
    padding: 20px;
}

.wishlist-item-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.wishlist-item-title a {
    color: var(--color-text); 
    text-decoration: none;
    transition: color 0.2s;
}

.wishlist-item-title a:hover {
    color: var(--color-primary);
}

.wishlist-item-sku {
    color: #999;
    font-size: 13px;
    margin-bottom: 10px;
}

.wishlist-item-price .price {
    font-size: 24px;
    font-weight: bold;
    /* Używamy koloru akcentu */
    color: var(--color-accent); 
}

.wishlist-item-date {
    color: #999;
    font-size: 13px;
}

/* Actions */
.wishlist-item-actions {
    padding: 15px 20px;
    border-top: 1px solid var(--color-gray-light);
    display: flex;
    gap: 10px;
}

.add-to-cart-form {
    flex: 1;
}

.add-to-cart-btn {
    width: 100%; 
    padding: 10px 15px; 
    /* Używamy .btn-primary */
    background: var(--color-primary); 
    color: var(--color-white); 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
    font-size: 14px;
    transition: all var(--transition-normal);
}

.add-to-cart-btn:hover {
    background: var(--color-primary-light); 
    transform: translateY(-1px);
}

.unavailable-btn {
    flex: 1; 
    padding: 10px 15px; 
    background: #ccc; 
    color: #666; 
    border: none; 
    border-radius: 4px; 
    font-size: 14px;
    cursor: not-allowed;
}

.remove-btn {
    padding: 10px 15px; 
    background: var(--color-white); 
    color: #d32f2f; /* Red */
    border: 1px solid #d32f2f; /* Red */
    border-radius: 4px; 
    cursor: pointer; 
    font-size: 14px;
    transition: background 0.3s, color 0.3s;
}

.remove-btn:hover {
    background: #d32f2f;
    color: var(--color-white);
}

/* Footer Actions */
.wishlist-footer {
    text-align: center; 
    padding-top: 20px;
}

.continue-shopping-btn {
    /* Używamy .btn-secondary z styles.css */
    /* Zapewniamy większą widoczność dla responsywności */
    min-width: 250px;
}

/* ===================================
   RESPONSIVE
   =================================== */

@media (max-width: 768px) {
    .wishlist-grid {
        /* Przechodzimy na dwie kolumny na tablecie */
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .wishlist-page .container {
        padding: 0 10px;
    }
    
    .wishlist-page {
        padding: 20px 0;
    }
    
    /* Na mniejszych ekranach usuwamy tekst z przycisku usuwania, zostawiamy tylko ikonę */
    .remove-btn .btn-text-only {
        display: none;
    }
}

/* ===================================
   DARK MODE SUPPORT (ZINTEGROWANE Z styles.css)
   =================================== */

@media (prefers-color-scheme: dark) {
    
    /* Globalne tło strony (zgodnie z styles.css) */
    .wishlist-page {
        background: var(--color-primary-dark);
    }
    
    /* Nagłówek */
    .wishlist-page .page-header h1 {
        color: var(--color-white);
    }

    .wishlist-page .page-header .wishlist-count,
    .wishlist-page .page-subtitle {
        color: rgba(255, 255, 255, 0.85);
    }
    
    /* Empty State - musi się wyróżniać na ciemnym tle strony */
    .empty-wishlist {
        background: var(--color-white); /* Utrzymujemy jasne tło dla kontrastu */
        box-shadow: 0 2px 10px rgba(255,255,255,0.08);
    }
    
    .empty-wishlist h2 {
        color: var(--color-primary-dark); /* Ciemny tekst na jasnym tle */
    }
    
    .empty-wishlist p {
        color: var(--color-text); /* Ciemny tekst na jasnym tle */
    }
    
    .empty-wishlist .empty-icon i {
        color: var(--color-gray);
    }

    /* Elementy Listy Życzeń */
    .wishlist-item {
        background: #2d2d2d; /* Ciemniejszy kolor dla kart */
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        border: 1px solid #3a3a3a;
    }
    
    .wishlist-item:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        border-color: var(--color-accent);
    }
    
    .wishlist-item-title a {
        color: var(--color-white);
    }

    .wishlist-item-sku,
    .wishlist-item-date {
        color: #bbb;
    }
    
    .wishlist-item-price .price {
        color: var(--color-accent-light);
    }

    .wishlist-item-actions {
        border-top: 1px solid #3a3a3a;
    }

    .unavailable-btn {
        background: #444;
        color: #999;
    }

    .remove-btn {
        background: #2d2d2d;
        color: #d32f2f; 
        border: 1px solid #d32f2f; 
    }

    .remove-btn:hover {
        background: #d32f2f;
        color: var(--color-white);
    }
    
    .continue-shopping-btn {
        /* Nadpisanie .btn-secondary dla Dark Mode */
        background: var(--color-primary-dark);
        color: var(--color-accent);
        border: 2px solid var(--color-accent);
    }

    .continue-shopping-btn:hover {
        background: var(--color-accent);
        color: var(--color-primary-dark);
    }
}
</style>

<?php 
// Include footer
if (file_exists($basePath . 'includes/footer.php')) {
    include $basePath . 'includes/footer.php';
} else {
    echo '<div style="padding: 20px; background: #f44336; color: white;">❌ Nie można załadować footer.php z: ' . $basePath . 'includes/footer.php</div>';
}
?>

<script src="<?php echo $basePath; ?>assets/js/wishlist.js"></script>

</body>
</html>