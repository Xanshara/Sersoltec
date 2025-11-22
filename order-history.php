<?php
require_once 'config.php';

// Funkcja t?umaczenia
function ut($key) {
    global $translations;
    $lang = getCurrentLanguage();
    return $translations[$lang][$key] ?? $key;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?action=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

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
        
        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
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
        
        .empty-state {
            background: #ffffff;
            padding: 80px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 16px;
            font-size: 1.5rem;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 32px;
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
            .top-nav-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="top-nav-content">
            <h2>👤 <?php echo ut('orders_title'); ?></h2>
            <div class="nav-links">
                <a href="index.php">🏠  <?php echo ut('nav_home'); ?></a>
                <a href="profile.php">📦 <?php echo ut('nav_profile'); ?></a>
                <a href="auth.php?action=logout">🚪 <?php echo ut('nav_logout'); ?></a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo ut('orders_all'); ?></h3>
                <p><?php echo number_format($stats['total_orders'] ?? 0); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo ut('orders_total_value'); ?></h3>
                <p><?php echo number_format($stats['total_spent'] ?? 0, 2); ?> <?php echo CURRENCY_SYMBOL; ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo ut('orders_avg_value'); ?></h3>
                <p><?php echo number_format($stats['avg_order'] ?? 0, 2); ?> <?php echo CURRENCY_SYMBOL; ?></p>
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
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo ut('orders_' . $order['status']); ?>
                        </span>
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
                            <span class="total-amount"><?php echo number_format($order['total_amount'], 2); ?> <?php echo CURRENCY_SYMBOL; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>