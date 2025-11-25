<?php
/**
 * SERSOLTEC - ADMIN DASHBOARD
 * Panel główny z statystykami
 */

require_once 'admin-auth.php';

$page_title = 'Dashboard';

// Pobierz statystyki
$stats = [];

// Produkty
$stmt = $pdo->query("SELECT COUNT(*) as total, 
                     SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active,
                     SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) as inactive
                     FROM products");
$stats['products'] = $stmt->fetch();

// Kategorie
$stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE active = 1");
$stats['categories'] = $stmt->fetchColumn();

// Zamówienia
$stmt = $pdo->query("SELECT COUNT(*) as total,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                     FROM orders");
$stats['orders'] = $stmt->fetch();

// Zapytania
$stmt = $pdo->query("SELECT COUNT(*) as total,
                     SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
                     SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as unread,
                     SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied
                     FROM inquiries");
$stats['inquiries'] = $stmt->fetch();

// Kalkulacje okien (ostatnie 30 dni)
$stmt = $pdo->query("SELECT COUNT(*) FROM window_calculations 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['calculations'] = $stmt->fetchColumn();

// Ostatnie zapytania (5 najnowszych)
$stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
$recent_inquiries = $stmt->fetchAll();

// Ostatnie zamówienia (5 najnowszych)
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

// Najpopularniejsze produkty (top 5)
$stmt = $pdo->query("SELECT p.*, COUNT(o.id) as order_count 
                     FROM products p 
                     LEFT JOIN orders o ON JSON_CONTAINS(o.products, JSON_QUOTE(CAST(p.id AS CHAR)))
                     WHERE p.active = 1
                     GROUP BY p.id 
                     ORDER BY order_count DESC 
                     LIMIT 5");
$top_products = $stmt->fetchAll();

include 'admin-header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-number"><?php echo $stats['products']['total']; ?></div>
        <div class="stat-label">Wszystkie Produkty</div>
        <small style="color: #4caf50;"><?php echo $stats['products']['active']; ?> aktywnych</small>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📂</div>
        <div class="stat-number"><?php echo $stats['categories']; ?></div>
        <div class="stat-label">Kategorie</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-number"><?php echo $stats['orders']['total']; ?></div>
        <div class="stat-label">Zamówienia</div>
        <small style="color: #ff9800;"><?php echo $stats['orders']['pending']; ?> oczekujących</small>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-number"><?php echo $stats['inquiries']['total']; ?></div>
        <div class="stat-label">Zapytania</div>
        <small style="color: #f44336;"><?php echo $stats['inquiries']['new']; ?> nowych</small>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🧮</div>
        <div class="stat-number"><?php echo $stats['calculations']; ?></div>
        <div class="stat-label">Kalkulacje (30 dni)</div>
    </div>
</div>

<!-- Recent Activity -->
<div class="admin-card">
    <h2>Ostatnie Zapytania</h2>
    
    <?php if ($recent_inquiries): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Imię</th>
                    <th>Email</th>
                    <th>Temat</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_inquiries as $inquiry): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($inquiry['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['email']); ?></td>
                        <td><?php echo htmlspecialchars(substr($inquiry['subject'] ?: 'Brak tematu', 0, 40)); ?></td>
                        <td>
                            <?php
                            $badge_class = 'badge-info';
                            $status_text = 'Nieznany';
                            
                            switch ($inquiry['status']) {
                                case 'new':
                                    $badge_class = 'badge-danger';
                                    $status_text = 'Nowe';
                                    break;
                                case 'read':
                                    $badge_class = 'badge-warning';
                                    $status_text = 'Przeczytane';
                                    break;
                                case 'replied':
                                    $badge_class = 'badge-success';
                                    $status_text = 'Odpowiedziano';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <a href="inquiries.php?view=<?php echo $inquiry['id']; ?>" class="btn btn-outline btn-sm">
                                Zobacz
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="inquiries.php" class="btn btn-primary">
                Zobacz wszystkie zapytania
            </a>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>Brak zapytań</p>
        </div>
    <?php endif; ?>
</div>

<!-- Top Products -->
<div class="admin-card">
    <h2>Najpopularniejsze Produkty</h2>
    
    <?php if ($top_products): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nazwa</th>
                    <th>Kategoria</th>
                    <th>Cena</th>
                    <th>Zamówień</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                        <td><?php echo htmlspecialchars(substr($product['name_pl'], 0, 40)); ?></td>
                        <td>-</td>
                        <td><?php echo $product['price_base'] ? formatPrice($product['price_base']) : '-'; ?></td>
                        <td><?php echo $product['order_count']; ?></td>
                        <td>
                            <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">
                                Edytuj
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Brak produktów</p>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <h2>Szybkie Akcje</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <a href="product-edit.php" class="btn btn-primary" style="text-align: center;">
            ➕ Dodaj Produkt
        </a>
        <a href="products.php" class="btn btn-outline" style="text-align: center;">
            📦 Zarządzaj Produktami
        </a>
        <a href="inquiries.php" class="btn btn-outline" style="text-align: center;">
            💬 Zobacz Zapytania
        </a>
        <a href="settings.php" class="btn btn-outline" style="text-align: center;">
            ⚙️ Ustawienia
        </a>
    </div>
</div>

<?php include 'admin-footer.php'; ?>
