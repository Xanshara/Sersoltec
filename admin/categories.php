<?php
/**
 * SERSOLTEC - ADMIN CATEGORIES
 * Zarządzanie kategoriami produktów
 */

require_once 'admin-auth.php';

$page_title = 'Zarządzanie Kategoriami';

$success = '';
$error = '';

// Obsługa usuwania
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    try {
        // Sprawdź czy kategoria ma produkty
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $product_count = $stmt->fetchColumn();
        
        if ($product_count > 0) {
            $error = "Nie można usunąć kategorii - zawiera $product_count produktów";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $success = 'Kategoria została usunięta';
        }
    } catch (Exception $e) {
        $error = 'Błąd podczas usuwania kategorii';
    }
}

// Obsługa dodawania/edycji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $slug = sanitize($_POST['slug']);
    $name_pl = sanitize($_POST['name_pl']);
    $name_en = sanitize($_POST['name_en']);
    $name_es = sanitize($_POST['name_es']);
    $description_pl = sanitize($_POST['description_pl'] ?? '');
    $description_en = sanitize($_POST['description_en'] ?? '');
    $description_es = sanitize($_POST['description_es'] ?? '');
    $icon = sanitize($_POST['icon'] ?? '');
    $order = (int)($_POST['order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Walidacja
    if (empty($slug) || empty($name_pl)) {
        $error = 'Proszę wypełnić wymagane pola (Slug, Nazwa PL)';
    } else {
        try {
            if ($category_id > 0) {
                // UPDATE
                $stmt = $pdo->prepare("
                    UPDATE categories SET 
                        slug = ?, 
                        name_pl = ?, 
                        name_en = ?, 
                        name_es = ?,
                        description_pl = ?,
                        description_en = ?,
                        description_es = ?,
                        icon = ?,
                        `order` = ?,
                        active = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $slug, $name_pl, $name_en, $name_es,
                    $description_pl, $description_en, $description_es,
                    $icon, $order, $active, $category_id
                ]);
                
                $success = 'Kategoria została zaktualizowana';
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO categories (
                        slug, name_pl, name_en, name_es,
                        description_pl, description_en, description_es,
                        icon, `order`, active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $slug, $name_pl, $name_en, $name_es,
                    $description_pl, $description_en, $description_es,
                    $icon, $order, $active
                ]);
                
                $success = 'Kategoria została dodana';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Slug już istnieje w bazie';
            } else {
                $error = 'Błąd zapisu: ' . $e->getMessage();
            }
        }
    }
}

// Pobierz kategorie
$stmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.`order`, c.name_pl");
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

<!-- Formularz dodawania kategorii -->
<div class="admin-card">
    <h2>Dodaj Nową Kategorię</h2>
    
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Slug <span class="required">*</span></label>
                <input type="text" name="slug" class="form-input" required placeholder="np. okna-pvc">
                <small>Używany w URL, tylko małe litery i myślniki</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Ikona (emoji)</label>
                <input type="text" name="icon" class="form-input" placeholder="🪟">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nazwa PL <span class="required">*</span></label>
                <input type="text" name="name_pl" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nazwa EN</label>
                <input type="text" name="name_en" class="form-input">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nazwa ES</label>
                <input type="text" name="name_es" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">Kolejność</label>
                <input type="number" name="order" class="form-input" value="0">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Opis PL</label>
            <textarea name="description_pl" class="form-textarea" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="active" value="1" checked>
                <span>Aktywna (widoczna w katalogu)</span>
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary">
            ➕ Dodaj Kategorię
        </button>
    </form>
</div>

<!-- Lista kategorii -->
<div class="admin-card">
    <h2>Kategorie (<?php echo count($categories); ?>)</h2>
    
    <?php if ($categories): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ikona</th>
                    <th>Nazwa</th>
                    <th>Slug</th>
                    <th>Kolejność</th>
                    <th>Produktów</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td style="font-size: 2rem;"><?php echo $category['icon'] ?: '📁'; ?></td>
                        <td><strong><?php echo htmlspecialchars($category['name_pl']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                        <td><?php echo $category['order']; ?></td>
                        <td><?php echo $category['product_count']; ?></td>
                        <td>
                            <?php if ($category['active']): ?>
                                <span class="badge badge-success">Aktywna</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Nieaktywna</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button class="btn btn-outline btn-sm btn-icon" 
                                        onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                        title="Edytuj">
                                    ✏️
                                </button>
                                <?php if ($category['product_count'] == 0): ?>
                                    <a href="?delete=1&id=<?php echo $category['id']; ?>" 
                                       class="btn btn-delete btn-sm btn-icon delete-btn" 
                                       title="Usuń">
                                        🗑️
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📂</div>
            <p>Brak kategorii</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal edycji -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-top: 0;">Edytuj Kategorię</h2>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="category_id" id="edit_category_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="edit_slug" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ikona</label>
                    <input type="text" name="icon" id="edit_icon" class="form-input">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nazwa PL</label>
                    <input type="text" name="name_pl" id="edit_name_pl" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nazwa EN</label>
                    <input type="text" name="name_en" id="edit_name_en" class="form-input">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nazwa ES</label>
                    <input type="text" name="name_es" id="edit_name_es" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kolejność</label>
                    <input type="number" name="order" id="edit_order" class="form-input">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Opis PL</label>
                <textarea name="description_pl" id="edit_description_pl" class="form-textarea" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="active" id="edit_active" value="1">
                    <span>Aktywna</span>
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    Zapisz
                </button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">
                    Anuluj
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_slug').value = category.slug;
    document.getElementById('edit_name_pl').value = category.name_pl;
    document.getElementById('edit_name_en').value = category.name_en || '';
    document.getElementById('edit_name_es').value = category.name_es || '';
    document.getElementById('edit_description_pl').value = category.description_pl || '';
    document.getElementById('edit_icon').value = category.icon || '';
    document.getElementById('edit_order').value = category.order;
    document.getElementById('edit_active').checked = category.active == 1;
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Zamknij modal klikając poza nim
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include 'admin-footer.php'; ?>
