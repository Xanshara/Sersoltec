<?php
/**
 * DEBUG SCRIPT - Sprawdź co jest w bazie i w session
 */

session_start();
require_once '/var/www/lastchance/sersoltec/config.php';

echo "<pre>";
echo "=== COMPARISON DEBUG ===\n\n";

// 1. Session info
echo "1. SESSION INFO:\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NULL') . "\n";
echo "Comparison Session ID: " . ($_SESSION['comparison_session_id'] ?? 'NULL') . "\n\n";

// 2. Check database
echo "2. DATABASE:\n";
$stmt = $pdo->query("SELECT * FROM product_comparisons ORDER BY created_at DESC LIMIT 5");
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "❌ BRAK wpisów w tabeli product_comparisons!\n\n";
} else {
    foreach ($rows as $row) {
        echo "ID: {$row['id']}\n";
        echo "User ID: " . ($row['user_id'] ?? 'NULL') . "\n";
        echo "Session ID: {$row['session_id']}\n";
        echo "Product IDs: {$row['product_ids']}\n";
        echo "Created: {$row['created_at']}\n";
        echo "---\n";
    }
}

// 3. Test API call
echo "3. TEST API CALL:\n";
$testUrl = 'http://localhost/sersoltec/api/comparison-api.php?action=count';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

echo "Response: " . $response . "\n\n";

// 4. Test getComparisonItems
echo "4. TEST FUNCTION:\n";

function getComparisonItems() {
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = $_SESSION['comparison_session_id'] ?? session_id();
    
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

$items = getComparisonItems();
echo "Found items: " . count($items) . "\n";
echo "IDs: " . json_encode($items) . "\n\n";

echo "=== END DEBUG ===\n";
echo "</pre>";
