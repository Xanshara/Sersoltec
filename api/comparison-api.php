<?php
/**
 * SERSOLTEC v2.5 - Comparison API (ABSOLUTE FINAL)
 * 
 * NAPRAWIONE:
 * - Ścieżki ABSOLUTE /var/www/lastchance/sersoltec/
 * - Kategorie z JOIN
 * - Wszystko działa
 * 
 * @version 2.5.10
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ABSOLUTE PATH
require_once '/var/www/lastchance/sersoltec/config.php';

$current_lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'pl';
if (!in_array($current_lang, ['pl', 'en', 'es'])) {
    $current_lang = 'pl';
}

function getDB() {
    global $pdo;
    return $pdo;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getSessionId() {
    if (!isset($_SESSION['comparison_session_id'])) {
        $_SESSION['comparison_session_id'] = session_id() ?: bin2hex(random_bytes(16));
    }
    return $_SESSION['comparison_session_id'];
}

function sendResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message) {
    sendResponse(false, [], $message);
}

function getComparisonItems() {
    $pdo = getDB();
    $userId = getUserId();
    $sessionId = getSessionId();
    
    if ($userId) {
        $stmt = $pdo->prepare("SELECT product_ids FROM product_comparisons WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        
        if ($row && $row['product_ids']) {
            $ids = json_decode($row['product_ids'], true);
            return is_array($ids) ? array_map('intval', $ids) : [];
        }
    }
    
    if ($sessionId) {
        $stmt = $pdo->prepare("SELECT product_ids FROM product_comparisons WHERE session_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        
        if ($row && $row['product_ids']) {
            $ids = json_decode($row['product_ids'], true);
            return is_array($ids) ? array_map('intval', $ids) : [];
        }
    }
    
    return [];
}

function saveComparisonItems($items) {
    $pdo = getDB();
    $userId = getUserId();
    $sessionId = getSessionId();
    
    $productIds = json_encode(array_values($items));
    
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id FROM product_comparisons WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE product_comparisons SET product_ids = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$productIds, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO product_comparisons (user_id, product_ids, session_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$userId, $productIds, $sessionId]);
        }
    } elseif ($sessionId) {
        $stmt = $pdo->prepare("SELECT id FROM product_comparisons WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE product_comparisons SET product_ids = ?, updated_at = NOW() WHERE session_id = ?");
            $stmt->execute([$productIds, $sessionId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO product_comparisons (user_id, product_ids, session_id, created_at, updated_at) VALUES (NULL, ?, ?, NOW(), NOW())");
            $stmt->execute([$productIds, $sessionId]);
        }
    }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'count');

try {
    $pdo = getDB();
    
    switch ($action) {
        case 'count':
            $items = getComparisonItems();
            sendResponse(true, ['count' => count($items)]);
            break;
        
        case 'list':
            global $current_lang;
            $items = getComparisonItems();
            
            if (empty($items)) {
                sendResponse(true, ['products' => []]);
            }
            
            $placeholders = implode(',', array_fill(0, count($items), '?'));
            $nameCol = "name_{$current_lang}";
            $descCol = "description_{$current_lang}";
            $categoryCol = "c.name_{$current_lang}";
            
            $query = "
                SELECT 
                    p.id,
                    p.sku,
                    p.$nameCol as name,
                    p.$descCol as description,
                    p.price_base as price,
                    p.image as image_url,
                    p.stock_quantity,
                    $categoryCol as category
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id IN ($placeholders) AND p.active = 1
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($items);
            $products = $stmt->fetchAll();
            
            $orderedProducts = [];
            foreach ($items as $id) {
                foreach ($products as $product) {
                    if ($product['id'] == $id) {
                        if (!empty($product['image_url'])) {
                            if (!preg_match('/^(https?:\/\/|\/)/i', $product['image_url'])) {
                                $product['image_url'] = '/sersoltec/assets/images/products/' . $product['image_url'];
                            }
                        } else {
                            $product['image_url'] = '/sersoltec/assets/images/no-image.png';
                        }
                        
                        $product['description'] = $product['description'] ?: '';
                        $product['name'] = $product['name'] ?: 'Produkt #' . $product['id'];
                        $product['category'] = $product['category'] ?: 'Bez kategorii';
                        $product['price'] = $product['price'] ?: 0;
                        $product['stock_quantity'] = $product['stock_quantity'] ?: 0;
                        
                        $orderedProducts[] = $product;
                        break;
                    }
                }
            }
            
            sendResponse(true, ['products' => $orderedProducts]);
            break;
        
        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = (int)($input['product_id'] ?? $_POST['product_id'] ?? 0);
            
            if (!$productId) {
                sendError('Brak ID produktu');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND active = 1");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                sendError('Produkt nie istnieje');
            }
            
            $items = getComparisonItems();
            
            if (in_array($productId, $items)) {
                sendError('Produkt już jest w porównaniu');
            }
            
            if (count($items) >= 4) {
                sendError('Możesz porównać maksymalnie 4 produkty');
            }
            
            $items[] = $productId;
            saveComparisonItems($items);
            
            sendResponse(true, ['count' => count($items)], 'Dodano do porównania');
            break;
        
        case 'remove':
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = (int)($input['product_id'] ?? $_POST['product_id'] ?? 0);
            
            if (!$productId) {
                sendError('Brak ID produktu');
            }
            
            $items = getComparisonItems();
            $items = array_values(array_filter($items, fn($id) => $id != $productId));
            
            saveComparisonItems($items);
            
            sendResponse(true, ['count' => count($items)], 'Usunięto');
            break;
        
        case 'clear':
            saveComparisonItems([]);
            sendResponse(true, ['count' => 0], 'Wyczyszczono');
            break;
        
        case 'check':
            $productId = (int)($_GET['product_id'] ?? 0);
            
            if (!$productId) {
                sendError('Brak ID produktu');
            }
            
            $items = getComparisonItems();
            $inComparison = in_array($productId, $items);
            
            sendResponse(true, [
                'in_comparison' => $inComparison,
                'count' => count($items)
            ]);
            break;
        
        default:
            sendError('Nieznana akcja');
    }
    
} catch (Exception $e) {
    error_log("Comparison API Error: " . $e->getMessage());
    sendError('Server error: ' . $e->getMessage());
}