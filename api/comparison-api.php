<?php
/**
 * SERSOLTEC v2.5 - Product Comparison API (ULTRA SAFE v2.6.3)
 * 
 * Ultra-safe version to avoid HTTP 500 errors
 * - Bezpieczne pobieranie config
 * - Silne error handling
 * - Fallback wartości na wszystko
 * 
 * @version 2.6.3
 * @date 2025-11-30
 */

// ============================================
// ERROR HANDLING - ZAWSZE NA POCZĄTKU
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log errors
if (!defined('LOG_FILE')) {
    define('LOG_FILE', __DIR__ . '/../logs/api-error.log');
    @mkdir(dirname(LOG_FILE), 0777, true);
}

function apiLog($message, $level = 'INFO') {
    $timestamp = date('[Y-m-d H:i:s]');
    $logLine = "$timestamp [$level] $message\n";
    @error_log($logLine, 3, LOG_FILE);
    if (php_sapi_name() !== 'cli') {
        error_log($message);
    }
}

// Global try-catch
set_exception_handler(function($e) {
    apiLog('Exception: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => php_sapi_name() === 'cli' ? $e->getMessage() : null
    ]);
    exit;
});

// ============================================
// HEADERS
// ============================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ============================================
// SESSION & TIMEZONE
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
date_default_timezone_set('Europe/Warsaw');

apiLog('Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . ($_GET['action'] ?? $_POST['action'] ?? 'none'));

// ============================================
// CONFIG - SAFE LOADING
// ============================================

$configFile = null;
$configPaths = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../../config.php',
    dirname(dirname(__FILE__)) . '/config.php',
    '/var/www/html/sersoltec/config.php',
    '/var/www/sersoltec/config.php',
];

foreach ($configPaths as $path) {
    if (@file_exists($path)) {
        $configFile = $path;
        break;
    }
}

if (!$configFile) {
    apiLog('No config file found', 'WARN');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sersoltec_db');
    define('DB_USER', 'sersoltec');
    define('DB_PASS', 'm1vg!M2Zj*3BY.QX');
} else {
    apiLog('Using config: ' . $configFile);
    require_once $configFile;
}

// ============================================
// HELPERS
// ============================================

function getDB() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbname = defined('DB_NAME') ? DB_NAME : 'sersoltec_db';
        $user = defined('DB_USER') ? DB_USER : 'sersoltec';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        
        apiLog("Connecting to: $host / $dbname");
        
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]
        );
        
        apiLog('Database connected OK');
    } catch (PDOException $e) {
        apiLog('DB Error: ' . $e->getMessage(), 'ERROR');
        throw new Exception('Database connection failed');
    }
    
    return $pdo;
}

function respond($success, $data = [], $message = '') {
    $response = ['success' => $success];
    if ($data) $response['data'] = $data;
    if ($message) $response['message'] = $message;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_response($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function detectColumns($pdo) {
    static $cols = null;
    if ($cols) return $cols;
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products");
        $available = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        apiLog('Columns: ' . implode(',', $available));
        
        $cols = [
            'name' => in_array('name_pl', $available) ? 'name_pl' : 
                     (in_array('name', $available) ? 'name' : 'name_pl'),
            'desc' => in_array('description_pl', $available) ? 'description_pl' :
                     (in_array('description', $available) ? 'description' : 'description_pl'),
            'price' => in_array('price_base', $available) ? 'price_base' :
                      (in_array('price', $available) ? 'price' : 'price_base'),
            'image' => in_array('image', $available) ? 'image' :
                      (in_array('image_url', $available) ? 'image_url' : 'image'),
            'stock' => in_array('stock', $available) ? 'stock' :
                      (in_array('stock_quantity', $available) ? 'stock_quantity' : null),
            'active' => in_array('active', $available) ? 'active' :
                       (in_array('is_active', $available) ? 'is_active' : 'active'),
        ];
    } catch (Exception $e) {
        apiLog('Column detection failed: ' . $e->getMessage(), 'WARN');
        $cols = [
            'name' => 'name_pl',
            'desc' => 'description_pl',
            'price' => 'price_base',
            'image' => 'image',
            'stock' => null,
            'active' => 'active',
        ];
    }
    
    return $cols;
}

function getItems() {
    try {
        $pdo = getDB();
        $sid = session_id();
        $uid = $_SESSION['user_id'] ?? null;
        
        $query = "SELECT product_ids FROM product_comparisons 
                  WHERE (session_id = ? OR user_id = ?) 
                  ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$sid, $uid]);
        $row = $stmt->fetch();
        
        if ($row && $row['product_ids']) {
            $ids = json_decode($row['product_ids'], true);
            return is_array($ids) ? $ids : [];
        }
    } catch (Exception $e) {
        apiLog('getItems error: ' . $e->getMessage(), 'WARN');
    }
    
    return $_SESSION['comparison_items'] ?? [];
}

function saveItems($items) {
    try {
        $_SESSION['comparison_items'] = $items;
        
        $pdo = getDB();
        $sid = session_id();
        $uid = $_SESSION['user_id'] ?? null;
        $json = json_encode(array_values($items));
        
        $query = "INSERT INTO product_comparisons (user_id, session_id, product_ids, created_at)
                  VALUES (?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE product_ids = VALUES(product_ids), created_at = NOW()";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$uid, $sid, $json]);
    } catch (Exception $e) {
        apiLog('saveItems error: ' . $e->getMessage(), 'WARN');
    }
}

// ============================================
// MAIN LOGIC
// ============================================

try {
    $pdo = getDB();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    apiLog("Action: $action");
    
    switch ($action) {
        case 'count':
            $items = getItems();
            respond(true, ['count' => count($items)]);
            break;
        
        case 'list':
            $items = getItems();
            
            if (empty($items)) {
                respond(true, ['products' => []]);
            }
            
            $cols = detectColumns($pdo);
            $ph = implode(',', array_fill(0, count($items), '?'));
            
            $select = "
                id, sku,
                {$cols['name']} as name,
                {$cols['desc']} as description,
                {$cols['price']} as price,
                {$cols['image']} as image_url,
                " . ($cols['stock'] ? "{$cols['stock']}" : "0") . " as stock_quantity
            ";
            
            $query = "SELECT $select FROM products WHERE id IN ($ph) AND {$cols['active']} = 1";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($items);
            $products = $stmt->fetchAll();
            
            // Preserve order
            $ordered = [];
            foreach ($items as $id) {
                foreach ($products as $p) {
                    if ($p['id'] == $id) {
                        $ordered[] = $p;
                        break;
                    }
                }
            }
            
            respond(true, ['products' => $ordered]);
            break;
        
        case 'add':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $pid = (int)($input['product_id'] ?? 0);
            
            if (!$pid) error_response('Missing product_id');
            
            $cols = detectColumns($pdo);
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND {$cols['active']} = 1");
            $stmt->execute([$pid]);
            if (!$stmt->fetch()) error_response('Product not found');
            
            $items = getItems();
            if (in_array($pid, $items)) error_response('Already in comparison');
            if (count($items) >= 4) error_response('Maximum 4 items');
            
            $items[] = $pid;
            saveItems($items);
            
            respond(true, ['count' => count($items)], 'Added');
            break;
        
        case 'remove':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $pid = (int)($input['product_id'] ?? 0);
            
            if (!$pid) error_response('Missing product_id');
            
            $items = getItems();
            $items = array_filter($items, fn($id) => $id != $pid);
            saveItems(array_values($items));
            
            respond(true, ['count' => count($items)], 'Removed');
            break;
        
        case 'clear':
            saveItems([]);
            respond(true, ['count' => 0], 'Cleared');
            break;
        
        case 'check':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $pid = (int)($input['product_id'] ?? 0);
            
            if (!$pid) error_response('Missing product_id');
            
            $items = getItems();
            respond(true, [
                'in_comparison' => in_array($pid, $items),
                'count' => count($items)
            ]);
            break;
        
        default:
            error_response('Invalid action');
    }
    
} catch (Exception $e) {
    apiLog('Exception: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    respond(false, [], 'Server error');
}