<?php
/**
 * Wishlist API - Final Working Version
 * Fixed path to config.php
 */

// Debug mode
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config - FIXED PATH
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    // Try alternative paths
    $configPath = dirname(__DIR__) . '/config.php';
    if (!file_exists($configPath)) {
        $configPath = $_SERVER['DOCUMENT_ROOT'] . '/sersoltec/config.php';
    }
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Config not found. Tried: ' . $configPath,
        'error_code' => 'CONFIG_ERROR'
    ]);
    exit;
}

// Load translations
$translationsPath = dirname($configPath) . '/includes/translations.php';
if (file_exists($translationsPath)) {
    require_once $translationsPath;
}

$wishlistTransPath = dirname($configPath) . '/includes/wishlist-translations.php';
if (file_exists($wishlistTransPath)) {
    require_once $wishlistTransPath;
}

// Get language
$lang = $_GET['lang'] ?? $_POST['lang'] ?? $_SESSION['language'] ?? 'pl';

// Helper function for translations
if (!function_exists('wt')) {
    function wt($key, $lang = 'pl') {
        global $translations;
        return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
    }
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => wt('wishlist_login_required', $lang),
        'error_code' => 'NOT_AUTHENTICATED'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Process action
try {
    
    switch ($action) {
        
        case 'count':
            // Get wishlist count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'count' => (int)$result['count']
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'add':
            // Add to wishlist
            $productId = (int)($_POST['product_id'] ?? 0);
            
            if ($productId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => wt('wishlist_invalid_product', $lang),
                    'error_code' => 'INVALID_PRODUCT_ID'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Check if product exists
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => wt('wishlist_product_not_found', $lang),
                    'error_code' => 'PRODUCT_NOT_FOUND'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Check if already in wishlist
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'error' => wt('wishlist_already_added', $lang),
                    'error_code' => 'ALREADY_IN_WISHLIST'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Add to wishlist
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            
            // Get new count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => wt('wishlist_added_success', $lang),
                'count' => (int)$result['count']
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'remove':
            // Remove from wishlist
            $productId = (int)($_POST['product_id'] ?? 0);
            
            if ($productId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => wt('wishlist_invalid_product', $lang),
                    'error_code' => 'INVALID_PRODUCT_ID'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            
            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'error' => wt('wishlist_not_in_wishlist', $lang),
                    'error_code' => 'NOT_IN_WISHLIST'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Get new count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => wt('wishlist_removed_success', $lang),
                'count' => (int)$result['count']
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get':
        default:
            // Get all wishlist items
            $stmt = $pdo->query("SHOW COLUMNS FROM products");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Auto-detect column names
            $columnMap = [
                'name' => in_array('name_' . $lang, $columns) ? 'name_' . $lang : (in_array('name', $columns) ? 'name' : 'name_pl'),
                'price' => in_array('price_base', $columns) ? 'price_base' : (in_array('price', $columns) ? 'price' : 'price_base'),
                'image' => in_array('image', $columns) ? 'image' : (in_array('image_url', $columns) ? 'image_url' : 'image'),
                'stock' => in_array('stock_quantity', $columns) ? 'stock_quantity' : (in_array('stock', $columns) ? 'stock' : null),
                'active' => in_array('is_active', $columns) ? 'is_active' : (in_array('active', $columns) ? 'active' : 'active')
            ];
            
            $selectFields = "
                w.id as wishlist_id,
                w.product_id,
                w.added_at,
                p.{$columnMap['name']} as name,
                p.{$columnMap['price']} as price,
                p.{$columnMap['image']} as image_url,
                p.sku";
            
            if ($columnMap['stock']) {
                $selectFields .= ",\n                p.{$columnMap['stock']} as stock_quantity";
            }
            
            $selectFields .= ",\n                p.{$columnMap['active']} as is_active";
            
            $query = "
                SELECT {$selectFields}
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ?
                ORDER BY w.added_at DESC
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'count' => count($items),
                'items' => $items
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => wt('wishlist_server_error', $lang),
        'error_code' => 'SERVER_ERROR',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}