<?php
// WŁĄCZENIE WYŚWIETLANIA BŁĘDÓW (dla celów diagnostycznych, usuń po wdrożeniu)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// POPRAWKA KODOWANIA
header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
// Zakładamy, że translations.php definiuje $translations i funkcję getCurrentLanguage()
require_once 'includes/translations.php'; 

// Funkcja tłumaczenia z wbudowanymi domyślnymi kluczami (w tym nowymi dla detali)
function ut($key) {
    global $translations;
    
    // Tłumaczenia awaryjne, w tym te dla logiki anulowania i detali
    $default_translations = [
        // Nowe klucze dla detali
        'orders_details_toggle' => [
            'pl' => 'Szczegóły zamówienia',
            'en' => 'Order details',
            'es' => 'Detalles del pedido',
        ],
        'orders_product' => [
            'pl' => 'Produkt',
            'en' => 'Product',
            'es' => 'Producto',
        ],
        'orders_quantity' => [
            'pl' => 'Ilość',
            'en' => 'Quantity',
            'es' => 'Cantidad',
        ],
        'orders_price_unit' => [
            'pl' => 'Cena jedn.',
            'en' => 'Unit Price',
            'es' => 'Precio unitario',
        ],
        'orders_subtotal' => [
            'pl' => 'Suma',
            'en' => 'Subtotal',
            'es' => 'Subtotal',
        ],
        // Klucze dla anulowania (z poprzedniej iteracji)
        'orders_cancel' => [
            'pl' => 'Anuluj',
            'en' => 'Cancel',
            'es' => 'Cancelar',
        ],
        'orders_confirm_cancel' => [
            'pl' => 'Czy na pewno chcesz anulować to zamówienie?',
            'en' => 'Are you sure you want to cancel this order?',
            'es' => '¿Está seguro de que desea cancelar este pedido?',
        ],
        'orders_cancellation_success' => [
            'pl' => 'Zamówienie zostało pomyślnie anulowane.',
            'en' => 'The order has been successfully cancelled.',
            'es' => 'El pedido ha sido cancelado con éxito.',
        ],
        'orders_cancellation_error' => [
            'pl' => 'Wystąpił błąd podczas anulowania zamówienia. Spróbuj ponownie.',
            'en' => 'An error occurred while cancelling the order. Please try again.',
            'es' => 'Se produjo un error al cancelar el pedido. Por favor, inténtelo de nuevo.',
        ],
        'orders_not_pending' => [
            'pl' => 'Zamówienie nie może być anulowane, ponieważ nie jest już w statusie oczekującym (pending).',
            'en' => 'The order cannot be cancelled because it is no longer in pending status.',
            'es' => 'El pedido no puede ser cancelado porque ya no se encuentra en estado pendiente.',
        ]
    ];
    
    $lang = getCurrentLanguage();
    
    // 1. Sprawdzenie klucza w globalnym pliku tłumaczeń
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    } 
    
    // 2. Sprawdzenie klucza w awaryjnych tłumaczeniach (z uwzględnieniem języka)
    elseif (isset($default_translations[$key][$lang])) {
        return $default_translations[$key][$lang];
    } 
    
    // 3. Awaryjnie użycie PL, jeśli język docelowy nie ma definicji
    elseif (isset($default_translations[$key]['pl'])) {
        return $default_translations[$key]['pl'];
    }
    
    // 4. Ostatecznie zwrócenie klucza, jeśli tłumaczenie nie istnieje
    else {
        return $key;
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?action=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// ===================================================
// LOGIKA ANULOWANIA ZAMÓWIENIA
// ===================================================
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['order_id'])) {
    $order_id_to_cancel = (int)$_GET['order_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND status = 'pending' AND (user_id = ? OR customer_email = ?)");
        $stmt->execute([$order_id_to_cancel, $user_id, $user_email]);
        $order_to_cancel = $stmt->fetch();

        if ($order_to_cancel) {
            $update_stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $update_stmt->execute([$order_id_to_cancel]);
            
            header('Location: order-history.php?cancellation=success');
            exit;
        } else {
            header('Location: order-history.php?cancellation=error&reason=not_pending');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Order cancellation error: " . $e->getMessage());
        header('Location: order-history.php?cancellation=error&reason=db_error');
        exit;
    }
}
// ===================================================

$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

$sql = "SELECT * FROM orders WHERE (user_id = ? OR customer_email = ?)";
$params = [$user_id, $user_email];

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

switch ($sort) {
    case 'date_asc':
        $sql .= " ORDER BY created_at ASC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Pobieranie statystyk (zachowane z poprzednich wersji)
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent,
            AVG(total_amount) as avg_order
        FROM orders 
        WHERE user_id = ? OR customer_email = ?
    ");
    $stats_stmt->execute([$user_id, $user_email]);
    $stats = $stats_stmt->fetch();

    // ===================================================
    // NOWA LOGIKA: POBIERANIE SZCZEGÓŁÓW PRODUKTÓW
    // ===================================================
    $order_items = [];
    $order_ids = array_column($orders, 'id'); // Zbieramy wszystkie ID zamówień

    if (!empty($order_ids)) {
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        
        // Pobieramy wszystkie pozycje w jednym zapytaniu dla optymalizacji
        $items_sql = "SELECT order_id, product_name, quantity, price_per_unit FROM order_items WHERE order_id IN ($placeholders)";
        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute($order_ids);
        $all_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Reorganizacja: grupujemy pozycje pod kluczem order_id
        foreach ($all_items as $item) {
            $order_items[$item['order_id']][] = $item;
        }
    }
    // ===================================================

} catch (PDOException $e) {
    $orders = [];
    $stats = ['total_orders' => 0, 'total_spent' => 0, 'avg_order' => 0];
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ut('orders_title'); ?> - <?php echo SITE_NAME; ?></title>
    <style>
        /* CSS dla podstawowego układu i stylu (zachowane z poprzedniej wersji) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #f8f8f8;
            color: #2c2c2c;
            line-height: 1.6;
        }
        
        .top-nav {
            background: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-nav h2 {
            color: #1a4d2e;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 24px;
        }
        
        .nav-links a {
            color: #2c2c2c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-links a:hover {
            color: #1a4d2e;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        /* KOMUNIKATY */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1a4d2e 0%, #0f3d25 100%);
            padding: 24px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card h3 {
            margin: 0 0 12px 0;
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .stat-card p {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .filters {
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #2c2c2c;
        }
        
        .filter-group select {
            padding: 10px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #1a4d2e;
        }
        
        .order-card {
            background: #ffffff;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-id {
            font-weight: 700;
            color: #1a4d2e;
            font-size: 1.25rem;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9rem;
            margin-top: 6px;
        }
        
        /* OGRANICZENIE PRZESTRZENI DLA STATUSU I PRZYCISKU */
        .order-actions-wrapper { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* STYL PRZYCISKU ANULOWANIA */
        .btn-cancel {
            padding: 6px 12px;
            background: #e74c3c; /* Czerwony */
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
        }
        
        .btn-cancel:hover {
            background: #c0392b;
        }
        
        .order-content {
            margin: 16px 0;
        }
        
        .order-content p {
            margin: 8px 0;
            color: #666;
        }
        
        .order-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-label {
            font-weight: 600;
            color: #2c2c2c;
        }
        
        .total-amount {
            font-size: 1.75rem;
            font-weight: bold;
            color: #1a4d2e;
        }
        
        /* ============================== */
        /* NOWE STYLE DLA DETALI ZAMÓWIENIA */
        /* ============================== */
        .details-toggle {
            display: block;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            color: #3498db;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            transition: color 0.2s;
        }

        .details-toggle:hover {
            color: #2980b9;
        }
        
        .details-content {
            max-height: 0; /* Ukryj domyślnie */
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
            padding: 0 10px;
            margin-top: 15px;
        }
        
        .details-content.show {
            max-height: 1000px; /* Duża wartość dla płynnej animacji */
        }

        .details-content h4 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #1a4d2e;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .items-table th, .items-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
        }

        .items-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #555;
        }
        /* ============================== */


        .empty-state {
            background: #ffffff;
            padding: 80px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #1a4d2e;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0f3d25;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }
        
        @media (max-width: 768px) {
            .order-actions-wrapper {
                justify-content: space-between;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="top-nav-content">
            <h2>📑 <?php echo ut('orders_title'); ?></h2>
            <div class="nav-links">
                <a href="index.php">🏠 <?php echo ut('nav_home'); ?></a>
                <a href="profile.php">👤 <?php echo ut('nav_profile'); ?></a>
                <a href="auth.php?action=logout">🚪 <?php echo ut('nav_logout'); ?></a>
            </div>
        </div>
    </div>

    <div class="container">
        
        <?php if (isset($_GET['cancellation'])): ?>
            <div class="alert alert-<?php echo $_GET['cancellation'] === 'success' ? 'success' : 'danger'; ?>">
                <?php 
                    if ($_GET['cancellation'] === 'success') {
                        echo ut('orders_cancellation_success');
                    } else {
                        if (isset($_GET['reason']) && $_GET['reason'] === 'not_pending') {
                             echo ut('orders_not_pending');
                        } else {
                            echo ut('orders_cancellation_error');
                        }
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo ut('orders_all'); ?></h3>
                <p><?php echo number_format($stats['total_orders'] ?? 0); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo ut('orders_total_value'); ?></h3>
                <p><?php echo number_format($stats['total_spent'] ?? 0, 2); ?> €</p>
            </div>
            <div class="stat-card">
                <h3><?php echo ut('orders_avg_value'); ?></h3>
                <p><?php echo number_format($stats['avg_order'] ?? 0, 2); ?> €</p>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label><?php echo ut('orders_status'); ?>:</label>
                <select onchange="window.location.href='order-history.php?status=' + this.value">
                    <option value=""><?php echo ut('orders_all_status'); ?></option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>><?php echo ut('orders_pending'); ?></option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>><?php echo ut('orders_confirmed'); ?></option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>><?php echo ut('orders_processing'); ?></option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>><?php echo ut('orders_completed'); ?></option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>><?php echo ut('orders_cancelled'); ?></option>
                </select>
            </div>
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h2><?php echo ut('orders_empty'); ?></h2>
                <p><?php echo ut('orders_empty_text'); ?></p>
                <a href="index.php" class="btn"><?php echo ut('orders_browse'); ?></a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id"><?php echo ut('orders_number'); ?> <?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        
                        <div class="order-actions-wrapper"> 
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ut('orders_' . $order['status']); ?>
                            </span>
                            
                            <?php if ($order['status'] === 'pending'): ?>
                                <a href="order-history.php?action=cancel&order_id=<?php echo $order['id']; ?>" 
                                   class="btn-cancel" 
                                   onclick="return confirm('<?php echo ut('orders_confirm_cancel'); ?>');">
                                    <?php echo ut('orders_cancel'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['message'])): ?>
                        <div class="order-content">
                            <p><strong><?php echo ut('orders_message'); ?>:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($order['message'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($order['total_items'])): ?>
                        <div class="order-content">
                            <p><strong><?php echo ut('orders_products_count'); ?>:</strong> <?php echo $order['total_items']; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($order['total_amount'])): ?>
                        <div class="order-total">
                            <span class="total-label"><?php echo ut('orders_total'); ?>:</span>
                            <span class="total-amount"><?php echo number_format($order['total_amount'], 2); ?> €</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order_items[$order['id']]) && !empty($order_items[$order['id']])): ?>
                        
                        <div class="details-toggle" onclick="toggleDetails(<?php echo $order['id']; ?>)">
                            <?php echo ut('orders_details_toggle'); ?> (<?php echo count($order_items[$order['id']]); ?>)
                        </div>

                        <div class="details-content" id="details-<?php echo $order['id']; ?>">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th><?php echo ut('orders_product'); ?></th>
                                        <th><?php echo ut('orders_quantity'); ?></th>
                                        <th><?php echo ut('orders_price_unit'); ?></th>
                                        <th><?php echo ut('orders_subtotal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($order_items[$order['id']] as $item): 
                                        $subtotal = $item['price_per_unit'] * $item['quantity'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($item['price_per_unit'], 2); ?> €</td>
                                            <td><strong><?php echo number_format($subtotal, 2); ?> €</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleDetails(orderId) {
            const detailsDiv = document.getElementById('details-' + orderId);
            detailsDiv.classList.toggle('show');
            
            // Opcjonalnie zmiana tekstu na przycisku/linku
            const toggleButton = detailsDiv.previousElementSibling;
            if (detailsDiv.classList.contains('show')) {
                toggleButton.innerHTML = '<?php echo ut('orders_details_toggle'); ?> (<?php echo ut('orders_product'); ?>) ▴';
            } else {
                toggleButton.innerHTML = '<?php echo ut('orders_details_toggle'); ?> (<?php echo ut('orders_product'); ?>) ▾';
            }
        }
        
        // Ustawienie początkowego tekstu (można zignorować, działa bez tego)
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.details-toggle').forEach(btn => {
                const count = btn.textContent.match(/\((.*?)\)/)[1]; // Wyciągnij liczbę z nawiasu
                btn.innerHTML = `<?php echo ut('orders_details_toggle'); ?> (${count}) ▾`;
            });
        });
    </script>
</body>
</html>