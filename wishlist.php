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
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/wishlist.css">
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
        
        <!-- Page Header -->
        <div class="page-header" style="text-align: center; margin-bottom: 40px;">
            <h1 style="font-size: 32px; color: #333; margin-bottom: 10px;">
                <i class="fa fa-heart" style="color: #e91e63;"></i> 
                <?php echo wt('wishlist_page_title', $lang); ?>
                <span class="wishlist-count" style="font-size: 24px; color: #666;">(<?php echo count($wishlistItems); ?>)</span>
            </h1>
            <p class="page-subtitle" style="color: #666; font-size: 16px;">
                <?php echo wt('wishlist_page_subtitle', $lang); ?>
            </p>
        </div>
        
        <?php if (empty($wishlistItems)): ?>
            
            <!-- Empty State -->
            <div class="empty-wishlist" style="text-align: center; padding: 80px 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div class="empty-icon" style="font-size: 80px; color: #ddd; margin-bottom: 20px;">
                    <i class="fa fa-heart-o"></i>
                </div>
                <h2 style="font-size: 24px; color: #333; margin-bottom: 10px;">
                    <?php echo wt('wishlist_empty_title', $lang); ?>
                </h2>
                <p style="color: #666; margin-bottom: 30px;">
                    <?php echo wt('wishlist_empty_message', $lang); ?>
                </p>
                <a href="<?php echo $basePath; ?>pages/products.php" class="btn btn-primary btn-lg" style="display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 4px;">
                    <i class="fa fa-shopping-bag"></i> 
                    <?php echo wt('wishlist_empty_cta', $lang); ?>
                </a>
            </div>
            
        <?php else: ?>
            
            <!-- Wishlist Grid -->
            <div class="wishlist-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; margin-bottom: 40px;">
                
                <?php foreach ($wishlistItems as $item): ?>
                    
                    <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s;">
                        
                        <!-- Product Image -->
                        <div class="wishlist-item-image" style="position: relative; padding-top: 100%; overflow: hidden; background: #f5f5f5;">
                            <a href="<?php echo $basePath; ?>pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img 
                                        src="<?php echo $basePath; ?>assets/images/products/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        loading="lazy"
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 48px;">
                                        📦
                                    </div>
                                <?php endif; ?>
                            </a>
                            
                            <!-- Stock badge -->
                            <?php if (isset($item['stock_quantity'])): ?>
                                <?php if ($item['stock_quantity'] <= 0): ?>
                                    <span style="position: absolute; top: 10px; right: 10px; background: #f44336; color: white; font-size: 12px; font-weight: bold; padding: 5px 10px; border-radius: 4px;">
                                        <?php echo wt('wishlist_out_of_stock', $lang); ?>
                                    </span>
                                <?php elseif ($item['stock_quantity'] < 5): ?>
                                    <span style="position: absolute; top: 10px; right: 10px; background: #ff9800; color: white; font-size: 12px; font-weight: bold; padding: 5px 10px; border-radius: 4px;">
                                        <?php echo wt('wishlist_low_stock', $lang); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Product Info -->
                        <div class="wishlist-item-info" style="padding: 20px;">
                            
                            <h3 class="wishlist-item-title" style="font-size: 18px; margin-bottom: 8px;">
                                <a href="<?php echo $basePath; ?>pages/product-detail.php?id=<?php echo $item['product_id']; ?>" style="color: #333; text-decoration: none;">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </h3>
                            
                            <p class="wishlist-item-sku" style="color: #999; font-size: 13px; margin-bottom: 10px;">
                                SKU: <?php echo htmlspecialchars($item['sku']); ?>
                            </p>
                            
                            <!-- Price -->
                            <div class="wishlist-item-price" style="margin-bottom: 10px;">
                                <span class="price" style="font-size: 24px; font-weight: bold; color: #e91e63;">
                                    <?php echo formatPrice($item['price']); ?>
                                </span>
                            </div>
                            
                            <!-- Added date -->
                            <p class="wishlist-item-date" style="color: #999; font-size: 13px;">
                                <i class="fa fa-clock-o"></i>
                                <?php echo wt('wishlist_added', $lang); ?>: <?php echo timeAgo($item['added_at']); ?>
                            </p>
                            
                        </div>
                        
                        <!-- Actions -->
                        <div class="wishlist-item-actions" style="padding: 15px 20px; border-top: 1px solid #eee; display: flex; gap: 10px;">
                            
                            <?php 
                            $inStock = !isset($item['stock_quantity']) || $item['stock_quantity'] > 0;
                            $isActive = !isset($item['is_active']) || $item['is_active'];
                            ?>
                            
                            <?php if ($inStock && $isActive): ?>
                                <form method="POST" action="<?php echo $basePath; ?>cart.php" style="flex: 1;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" style="width: 100%; padding: 10px 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                                        <i class="fa fa-shopping-cart"></i> 
                                        <?php echo wt('wishlist_add_to_cart', $lang); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button style="flex: 1; padding: 10px 15px; background: #ccc; color: #666; border: none; border-radius: 4px; font-size: 14px;" disabled>
                                    <i class="fa fa-ban"></i> 
                                    <?php echo wt('wishlist_unavailable', $lang); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button 
                                class="btn btn-outline remove-from-wishlist" 
                                data-product-id="<?php echo $item['product_id']; ?>"
                                title="<?php echo wt('wishlist_remove', $lang); ?>"
                                style="padding: 10px 15px; background: white; color: #f44336; border: 1px solid #f44336; border-radius: 4px; cursor: pointer; font-size: 14px;"
                            >
                                <i class="fa fa-trash"></i> 
                                <?php echo wt('wishlist_remove', $lang); ?>
                            </button>
                            
                        </div>
                        
                    </div>
                    
                <?php endforeach; ?>
                
            </div>
            
            <!-- Actions Footer -->
            <div class="wishlist-footer" style="text-align: center; padding-top: 20px;">
                <a href="<?php echo $basePath; ?>pages/products.php" class="btn btn-outline-primary" style="display: inline-block; padding: 12px 30px; background: white; color: #667eea; border: 2px solid #667eea; text-decoration: none; border-radius: 4px;">
                    <i class="fa fa-arrow-left"></i> 
                    <?php echo wt('wishlist_continue_shopping', $lang); ?>
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<style>
.wishlist-page {
    min-height: 60vh;
    padding: 40px 0;
    background: #f8f9fa;
}

.wishlist-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.wishlist-item-image a img {
    transition: transform 0.3s;
}

.wishlist-item:hover .wishlist-item-image a img {
    transform: scale(1.05);
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

<!-- Wishlist JavaScript -->
<script src="<?php echo $basePath; ?>assets/js/wishlist.js"></script>

</body>
</html>