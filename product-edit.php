<?php
/**
 * SERSOLTEC - ADMIN PRODUCT EDIT
 * Dodawanie/edycja produktu
 */

require_once 'admin-auth.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $product_id > 0;

$page_title = $is_edit ? 'Edytuj Produkt' : 'Dodaj Produkt';

$success = '';
$error = '';
$product = null;

// Jeśli edycja - pobierz produkt
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
}

// Pobierz kategorie
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY `order`");
$categories = $stmt->fetchAll();

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = sanitize($_POST['sku']);
    $category_id = (int)$_POST['category_id'];
    $name_pl = sanitize($_POST['name_pl']);
    $name_en = sanitize($_POST['name_en']);
    $name_es = sanitize($_POST['name_es']);
    $description_pl = sanitize($_POST['description_pl']);
    $description_en = sanitize($_POST['description_en']);
    $description_es = sanitize($_POST['description_es']);
    $price_base = !empty($_POST['price_base']) ? (float)$_POST['price_base'] : null;
    $price_min = !empty($_POST['price_min']) ? (float)$_POST['price_min'] : null;
    $price_max = !empty($_POST['price_max']) ? (float)$_POST['price_max'] : null;
    $unit = sanitize($_POST['unit']);
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $active = isset($_POST['active']) ? 1 : 0;
    $b2b_only = isset($_POST['b2b_only']) ? 1 : 0;
    
    // Walidacja
    if (empty($sku) || empty($name_pl) || !$category_id) {
        $error = 'Proszę wypełnić wymagane pola (SKU, Nazwa PL, Kategoria)';
    } else {
        try {
            if ($is_edit) {
                // UPDATE
                $stmt = $pdo->prepare("
                    UPDATE products SET 
                        category_id = ?,
                        sku = ?,
                        name_pl = ?,
                        name_en = ?,
                        name_es = ?,
                        description_pl = ?,
                        description_en = ?,
                        description_es = ?,
                        price_base = ?,
                        price_min = ?,
                        price_max = ?,
                        unit = ?,
                        weight = ?,
                        active = ?,
                        b2b_only = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $category_id, $sku, $name_pl, $name_en, $name_es,
                    $description_pl, $description_en, $description_es,
                    $price_base, $price_min, $price_max,
                    $unit, $weight, $active, $b2b_only,
                    $product_id
                ]);
                
                $success = 'Produkt został zaktualizowany';
                
                // Refresh product data
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_id, sku, name_pl, name_en, name_es,
                        description_pl, description_en, description_es,
                        price_base, price_min, price_max,
                        unit, weight, active, b2b_only
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $category_id, $sku, $name_pl, $name_en, $name_es,
                    $description_pl, $description_en, $description_es,
                    $price_base, $price_min, $price_max,
                    $unit, $weight, $active, $b2b_only
                ]);
                
                $product_id = $pdo->lastInsertId();
                $success = 'Produkt został dodany';
                
                // Redirect to edit mode
                header('Location: product-edit.php?id=' . $product_id . '&success=1');
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'SKU już istnieje w bazie';
            } else {
                $error = 'Błąd zapisu: ' . $e->getMessage();
            }
        }
    }
}

// Success message z URL
if (isset($_GET['success'])) {
    $success = 'Produkt został dodany pomyślnie';
}

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
    <h2><?php echo $is_edit ? 'Edytuj Produkt' : 'Dodaj Nowy Produkt'; ?></h2>
    
    <form method="POST" enctype="multipart/form-data">
        <!-- Podstawowe info -->
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">SKU <span class="required">*</span></label>
                <input type="text" name="sku" class="form-input" required 
                       value="<?php echo $product ? htmlspecialchars($product['sku']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategoria <span class="required">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">--- Wybierz ---</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo ($product && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name_pl']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Nazwy w różnych językach -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--color-primary);">Nazwy</h3>
        
        <div class="form-group">
            <label class="form-label">Nazwa PL <span class="required">*</span></label>
            <input type="text" name="name_pl" class="form-input" required 
                   value="<?php echo $product ? htmlspecialchars($product['name_pl']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Nazwa EN</label>
            <input type="text" name="name_en" class="form-input" 
                   value="<?php echo $product ? htmlspecialchars($product['name_en']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Nazwa ES</label>
            <input type="text" name="name_es" class="form-input" 
                   value="<?php echo $product ? htmlspecialchars($product['name_es']) : ''; ?>">
        </div>
        
        <!-- Opisy -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--color-primary);">Opisy</h3>
        
        <div class="form-group">
            <label class="form-label">Opis PL</label>
            <textarea name="description_pl" class="form-textarea" rows="4"><?php echo $product ? htmlspecialchars($product['description_pl']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Opis EN</label>
            <textarea name="description_en" class="form-textarea" rows="4"><?php echo $product ? htmlspecialchars($product['description_en']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Opis ES</label>
            <textarea name="description_es" class="form-textarea" rows="4"><?php echo $product ? htmlspecialchars($product['description_es']) : ''; ?></textarea>
        </div>
        
        <!-- Ceny -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--color-primary);">Ceny</h3>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Cena bazowa (€)</label>
                <input type="number" name="price_base" class="form-input" step="0.01" 
                       value="<?php echo $product ? $product['price_base'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Cena min (€)</label>
                <input type="number" name="price_min" class="form-input" step="0.01" 
                       value="<?php echo $product ? $product['price_min'] : ''; ?>">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Cena max (€)</label>
                <input type="number" name="price_max" class="form-input" step="0.01" 
                       value="<?php echo $product ? $product['price_max'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Jednostka</label>
                <input type="text" name="unit" class="form-input" placeholder="szt, m2, mb..." 
                       value="<?php echo $product ? htmlspecialchars($product['unit']) : 'szt'; ?>">
            </div>
        </div>
        
        <!-- Dodatkowe -->
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--color-primary);">Dodatkowe</h3>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Waga (kg)</label>
                <input type="number" name="weight" class="form-input" step="0.001" 
                       value="<?php echo $product ? $product['weight'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 1rem;">Opcje</label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="active" value="1" 
                               <?php echo (!$product || $product['active']) ? 'checked' : ''; ?>>
                        <span>Aktywny (widoczny w katalogu)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="b2b_only" value="1" 
                               <?php echo ($product && $product['b2b_only']) ? 'checked' : ''; ?>>
                        <span>Tylko B2B</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Akcje -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <?php echo $is_edit ? 'Zapisz zmiany' : 'Dodaj produkt'; ?>
            </button>
            <a href="products.php" class="btn btn-outline btn-lg">
                Anuluj
            </a>
            <?php if ($is_edit): ?>
                <a href="../pages/product-detail.php?id=<?php echo $product_id; ?>" 
                   class="btn btn-outline btn-lg" 
                   target="_blank">
                    👁️ Zobacz na stronie
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include 'admin-footer.php'; ?>
