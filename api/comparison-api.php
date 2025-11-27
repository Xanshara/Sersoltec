<?php
/**
 * SERSOLTEC v2.5 - Product Comparison API
 * 
 * REST API for product comparison system
 * Supports both logged-in users and guest sessions
 * Max 4 products in comparison
 * 
 * Endpoints:
 * - GET  ?action=list          - Get comparison products
 * - GET  ?action=count         - Get count
 * - POST ?action=add           - Add product (product_id)
 * - POST ?action=remove        - Remove product (product_id)
 * - POST ?action=clear         - Clear all
 * - POST ?action=check         - Check if product in comparison (product_id)
 * 
 * @version 2.5.0
 * @date 2025-11-27
 */

// Error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Europe/Warsaw');

// ============================================
// CONFIG
// ============================================

// Try to load config.php
$configPaths = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../../config.php',
    dirname(dirname(__FILE__)) . '/config.php'
];

$configLoaded = false;
foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
}

// Fallback config if not loaded
if (!$configLoaded) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sersoltec_db');
    define('DB_USER', 'sersoltec');
    define('DB_PASS', 'm1vg!M2Zj*3BY.QX');
}

// Constants
define('MAX_COMPARISON_ITEMS', 4);

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get database connection
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME') ? DB_NAME : 'sersoltec_db';
            $user = defined('DB_USER') ? DB_USER : 'sersoltec';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set timezone
            $pdo->exec("SET time_zone = '+01:00'");
            
        } catch (PDOException $e) {
            sendError('Database connection failed', 500);
        }
    }
    
    return $pdo;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['id']);
}

/**
 * Get user ID
 */
function getUserId() {
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    if (isset($_SESSION['id'])) {
        return (int)$_SESSION['id'];
    }
    return null;
}

/**
 * Get or create session ID
 */
function getSessionId() {
    if (!isset($_SESSION['comparison_session_id'])) {
        $_SESSION['comparison_session_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['comparison_session_id'];
}

/**
 * Send JSON response
 */
function sendResponse($success, $data = [], $message = '', $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $httpCode = 400) {
    sendResponse(false, [], $message, $httpCode);
}

/**
 * Detect product columns (compatible with different DB schemas)
 */
function detectProductColumns($pdo) {
    static $columns = null;
    
    if ($columns === null) {
        $stmt = $pdo->query("SHOW COLUMNS FROM products");
        $columnNames = array_column($stmt->fetchAll(), 'Field');
        
        // Detect language columns
        $lang = $_GET['lang'] ?? 'pl';
        
        $columns = [
            'name' => in_array('name_' . $lang, $columnNames) ? 'name_' . $lang : 
                     (in_array('name', $columnNames) ? 'name' : 'name_pl'),
            'description' => in_array('description_' . $lang, $columnNames) ? 'description_' . $lang :
                            (in_array('description', $columnNames) ? 'description' : 'description_pl'),
            'price' => in_array('price_base', $columnNames) ? 'price_base' : 
                      (in_array('price', $columnNames) ? 'price' : 'price_base'),
            'image' => in_array('image', $columnNames) ? 'image' : 
                      (in_array('image_url', $columnNames) ? 'image_url' : 'image'),
            'stock' => in_array('stock_quantity', $columnNames) ? 'stock_quantity' : 
                      (in_array('stock', $columnNames) ? 'stock' : null),
            'active' => in_array('is_active', $columnNames) ? 'is_active' : 
                       (in_array('active', $columnNames) ? 'active' : 'active'),
            'category' => in_array('category_name', $columnNames) ? 'category_name' : 
                         (in_array('category', $columnNames) ? 'category' : null)
        ];
    }
    
    return $columns;
}

/**
 * Get comparison items from session or database
 */
function getComparisonItems() {
    $pdo = getDB();
    $userId = getUserId();
    $sessionId = getSessionId();
    
    // Try database first (for logged-in users or persistent sessions)
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT product_ids 
            FROM product_comparisons 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        
        if ($row && $row['product_ids']) {
            $ids = json_decode($row['product_ids'], true);
            return is_array($ids) ? $ids : [];
        }
    } else {
        // Guest - check by session_id
        $stmt = $pdo->prepare("
            SELECT product_ids 
            FROM product_comparisons 
            WHERE session_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        
        if ($row && $row['product_ids']) {
            $ids = json_decode($row['product_ids'], true);
            return is_array($ids) ? $ids : [];
        }
    }
    
    // Fallback to session storage
    return $_SESSION['comparison_items'] ?? [];
}

/**
 * Save comparison items
 */
function saveComparisonItems($items) {
    $pdo = getDB();
    $userId = getUserId();
    $sessionId = getSessionId();
    
    // Save to session
    $_SESSION['comparison_items'] = $items;
    
    // Save to database
    $productIdsJson = json_encode(array_values($items));
    
    if ($userId) {
        // Logged-in user
        $stmt = $pdo->prepare("
            INSERT INTO product_comparisons (user_id, product_ids, session_id, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                product_ids = VALUES(product_ids),
                created_at = NOW()
        ");
        $stmt->execute([$userId, $productIdsJson, $sessionId]);
    } else {
        // Guest
        $stmt = $pdo->prepare("
            INSERT INTO product_comparisons (user_id, product_ids, session_id, created_at)
            VALUES (NULL, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                product_ids = VALUES(product_ids),
                created_at = NOW()
        ");
        $stmt->execute([$productIdsJson, $sessionId]);
    }
}

// ============================================
// MAIN LOGIC
// ============================================

try {
    $pdo = getDB();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        
        // ==========================================
        // GET: Count comparison items
        // ==========================================
        case 'count':
            $items = getComparisonItems();
            sendResponse(true, ['count' => count($items)]);
            break;
        
        // ==========================================
        // GET: List comparison products
        // ==========================================
        case 'list':
            $items = getComparisonItems();
            
            if (empty($items)) {
                sendResponse(true, ['products' => []]);
            }
            
            // Get product details
            $columns = detectProductColumns($pdo);
            $placeholders = implode(',', array_fill(0, count($items), '?'));
            
            $query = "
                SELECT 
                    id,
                    sku,
                    {$columns['name']} as name,
                    {$columns['description']} as description,
                    {$columns['price']} as price,
                    {$columns['image']} as image_url,
                    " . ($columns['stock'] ? "{$columns['stock']} as stock_quantity," : "0 as stock_quantity,") . "
                    " . ($columns['category'] ? "{$columns['category']} as category" : "'Uncategorized' as category") . "
                FROM products
                WHERE id IN ($placeholders)
                  AND {$columns['active']} = 1
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($items);
            $products = $stmt->fetchAll();
            
            // Maintain order from $items
            $orderedProducts = [];
            foreach ($items as $id) {
                foreach ($products as $product) {
                    if ($product['id'] == $id) {
                        $orderedProducts[] = $product;
                        break;
                    }
                }
            }
            
            sendResponse(true, ['products' => $orderedProducts]);
            break;
        
        // ==========================================
        // POST: Add product to comparison
        // ==========================================
        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = (int)($input['product_id'] ?? $_POST['product_id'] ?? 0);
            
            if (!$productId) {
                sendError('Product ID is required');
            }
            
            // Check if product exists
            $columns = detectProductColumns($pdo);
            $stmt = $pdo->prepare("
                SELECT id FROM products 
                WHERE id = ? AND {$columns['active']} = 1
            ");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                sendError('Product not found or inactive');
            }
            
            // Get current items
            $items = getComparisonItems();
            
            // Check if already in comparison
            if (in_array($productId, $items)) {
                sendError('Product already in comparison');
            }
            
            // Check max limit
            if (count($items) >= MAX_COMPARISON_ITEMS) {
                sendError('Maximum ' . MAX_COMPARISON_ITEMS . ' products allowed in comparison');
            }
            
            // Add to comparison
            $items[] = $productId;
            saveComparisonItems($items);
            
            sendResponse(true, ['count' => count($items)], 'Product added to comparison');
            break;
        
        // ==========================================
        // POST: Remove product from comparison
        // ==========================================
        case 'remove':
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = (int)($input['product_id'] ?? $_POST['product_id'] ?? 0);
            
            if (!$productId) {
                sendError('Product ID is required');
            }
            
            $items = getComparisonItems();
            $items = array_filter($items, function($id) use ($productId) {
                return $id != $productId;
            });
            
            saveComparisonItems(array_values($items));
            
            sendResponse(true, ['count' => count($items)], 'Product removed from comparison');
            break;
        
        // ==========================================
        // POST: Clear all comparison
        // ==========================================
        case 'clear':
            saveComparisonItems([]);
            sendResponse(true, ['count' => 0], 'Comparison cleared');
            break;
        
        // ==========================================
        // POST: Check if product in comparison
        // ==========================================
        case 'check':
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = (int)($input['product_id'] ?? $_POST['product_id'] ?? 0);
            
            if (!$productId) {
                sendError('Product ID is required');
            }
            
            $items = getComparisonItems();
            $inComparison = in_array($productId, $items);
            
            sendResponse(true, [
                'in_comparison' => $inComparison,
                'count' => count($items)
            ]);
            break;
        
        // ==========================================
        // Invalid action
        // ==========================================
        default:
            sendError('Invalid action. Use: list, count, add, remove, clear, check');
    }
    
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
