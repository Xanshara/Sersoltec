<?php
/**
 * SERSOLTEC - ADMIN PRODUCTS LIST
 * Lista produktów z możliwością edycji/usuwania
 */

require_once 'admin-auth.php';

$page_title = 'Zarządzanie Produktami';

$success = '';
$error = '';

// Obsługa usuwania
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $success = 'Produkt został usunięty';
    } catch (Exception $e) {
        $error = 'Błąd podczas usuwania produktu';
    }
}

// Filtry
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Query builder
$query = "SELECT p.*, c.name_pl as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($category_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $query .= " AND (p.name_pl LIKE ? OR p.name_en LIKE ? OR p.name_es LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter === 'active') {
    $query .= " AND p.active = 1";
} elseif ($status_filter === 'inactive') {
    $query .= " AND p.active = 0";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Pobierz kategorie dla filtra
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY `order`");
$categories = $stmt->fetchAll();

include 'admin-header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success">
        ✓ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        ✗ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0;">Produkty (<?php echo count($products); ?>)</h2>
        <a href="product-edit.php" class="btn btn-primary">
            ➕ Dodaj Produkt
        </a>
    </div>
    
    <!-- Filtry -->
    <form method="get" style="background: var(--color-light-gray); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Szukaj</label>
                <input type="text" name="search" class="form-input" placeholder="Nazwa lub SKU..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Kategoria</label>
                <select name="category" class="form-select">
                    <option value="">Wszystkie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name_pl']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktywne</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Nieaktywne</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                Filtruj
            </button>
        </div>
    </form>
    
    <!-- Tabela produktów -->
    <?php if ($products): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Zdjęcie</th>
                    <th>SKU</th>
                    <th>Nazwa</th>
                    <th>Kategoria</th>
                    <th>Cena</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: var(--color-gray); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                    📦
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($product['name_pl'], 0, 40)); ?><?php echo strlen($product['name_pl']) > 40 ? '...' : ''; ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                        <td>
                            <?php if ($product['price_base']): ?>
                                <?php echo formatPrice($product['price_base']); ?>
                            <?php else: ?>
                                <span style="color: #999;">Brak ceny</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($product['active']): ?>
                                <span class="badge badge-success">Aktywny</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Nieaktywny</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline btn-sm btn-icon" 
                                   title="Edytuj">
                                    ✏️
                                </a>
                                <a href="../pages/product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline btn-sm btn-icon" 
                                   title="Zobacz" 
                                   target="_blank">
                                    👁️
                                </a>
                                <a href="?delete=1&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-delete btn-sm btn-icon delete-btn" 
                                   title="Usuń">
                                    🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Brak produktów spełniających kryteria</p>
            <a href="product-edit.php" class="btn btn-primary" style="margin-top: 1rem;">
                ➕ Dodaj pierwszy produkt
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'admin-footer.php'; ?>
