# 📋 SERSOLTEC v2.0 - NEXT STEPS (FAZA 2)

## 🎯 FAZA 2: E-commerce Features

**Czas trwania:** 2-3 tygodnie  
**Status:** Gotowe do rozpoczęcia 🚀

---

## 📅 Sprint 2.1: Wishlist (Tydzień 1, dni 1-2)

### 1. Wishlist Frontend (`wishlist.php`)

**Lokalizacja:** `/wishlist.php` (główny katalog)

**Funkcjonalności:**
- ✅ Tabela z produktami na wishliście
- ✅ Usuń z wishlisty (X button)
- ✅ Dodaj do koszyka (z wishlisty)
- ✅ Komunikat "Pusta wishlista"
- ✅ Licznik produktów
- ✅ Multi-language support

**Struktura:**
```php
<?php
require_once 'config.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!is_authenticated()) {
    Helpers::redirect('/auth.php?action=login&redirect=/wishlist.php');
}

$userId = auth()->id();

// Get wishlist items
$wishlistItems = db()->fetchAll('
    SELECT w.*, p.name, p.price, p.image_url, p.sku
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
', [$userId]);

?>

<div class="wishlist-container">
    <h1><?php echo t('my_wishlist'); ?></h1>
    
    <?php if (empty($wishlistItems)): ?>
        <div class="empty-wishlist">
            <p><?php echo t('wishlist_empty'); ?></p>
            <a href="/pages/products.php" class="btn"><?php echo t('browse_products'); ?></a>
        </div>
    <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="price"><?php echo Helpers::formatPrice($item['price']); ?></p>
                    <p class="added-date"><?php echo Helpers::timeAgo($item['added_at']); ?></p>
                    
                    <div class="actions">
                        <button class="btn add-to-cart" data-product-id="<?php echo $item['product_id']; ?>">
                            <?php echo t('add_to_cart'); ?>
                        </button>
                        <button class="btn-outline remove-from-wishlist" data-product-id="<?php echo $item['product_id']; ?>">
                            <?php echo t('remove'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/assets/js/wishlist.js"></script>

<?php require_once 'includes/footer.php'; ?>
```

### 2. Wishlist API (`api/wishlist-api.php`)

**Lokalizacja:** `/api/wishlist-api.php`

**Endpoints:**
- `GET /api/wishlist-api.php?action=get` - Pobierz wishlistę
- `POST /api/wishlist-api.php?action=add` - Dodaj do wishlisty
- `POST /api/wishlist-api.php?action=remove` - Usuń z wishlisty
- `GET /api/wishlist-api.php?action=count` - Liczba produktów

**Struktura:**
```php
<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check authentication
if (!is_authenticated()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Verify CSRF
if (!security()->verifyCsrfToken()) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$userId = auth()->id();
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    switch ($action) {
        case 'add':
            $productId = (int)$_POST['product_id'];
            
            // Check if already in wishlist
            if (db()->exists('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId])) {
                echo json_encode(['success' => false, 'error' => 'Already in wishlist']);
                exit;
            }
            
            db()->insert('wishlist', [
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            
            logger()->info('Product added to wishlist', ['user_id' => $userId, 'product_id' => $productId]);
            
            echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
            break;
            
        case 'remove':
            $productId = (int)$_POST['product_id'];
            
            db()->delete('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
            
            logger()->info('Product removed from wishlist', ['user_id' => $userId, 'product_id' => $productId]);
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        case 'count':
            $count = db()->count('wishlist', 'user_id = ?', [$userId]);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'get':
        default:
            $items = db()->fetchAll('
                SELECT w.*, p.name, p.price, p.image_url, p.sku
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ?
                ORDER BY w.added_at DESC
            ', [$userId]);
            
            echo json_encode(['success' => true, 'items' => $items]);
            break;
    }
    
} catch (Exception $e) {
    logger()->error('Wishlist API error', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
```

### 3. Wishlist JavaScript (`assets/js/wishlist.js`)

**Lokalizacja:** `/assets/js/wishlist.js`

**Funkcje:**
- Add to wishlist (product pages)
- Remove from wishlist
- Update wishlist badge count
- Add to cart from wishlist

```javascript
// wishlist.js
class Wishlist {
    constructor() {
        this.apiUrl = '/api/wishlist-api.php';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.init();
    }
    
    init() {
        this.updateBadge();
        this.bindEvents();
    }
    
    bindEvents() {
        // Add to wishlist buttons
        document.querySelectorAll('.add-to-wishlist').forEach(btn => {
            btn.addEventListener('click', (e) => this.addToWishlist(e));
        });
        
        // Remove from wishlist buttons
        document.querySelectorAll('.remove-from-wishlist').forEach(btn => {
            btn.addEventListener('click', (e) => this.removeFromWishlist(e));
        });
    }
    
    async addToWishlist(event) {
        const button = event.target;
        const productId = button.dataset.productId;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&_token=${this.csrfToken}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Dodano do wishlisty', 'success');
                this.updateBadge();
                button.textContent = '❤️ W wishliście';
                button.disabled = true;
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            this.showNotification('Błąd serwera', 'error');
        }
    }
    
    async removeFromWishlist(event) {
        const button = event.target;
        const productId = button.dataset.productId;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&product_id=${productId}&_token=${this.csrfToken}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Usunięto z wishlisty', 'success');
                this.updateBadge();
                
                // Remove item from DOM
                const item = button.closest('.wishlist-item');
                item.remove();
                
                // Check if wishlist is now empty
                if (document.querySelectorAll('.wishlist-item').length === 0) {
                    location.reload();
                }
            }
        } catch (error) {
            this.showNotification('Błąd serwera', 'error');
        }
    }
    
    async updateBadge() {
        try {
            const response = await fetch(`${this.apiUrl}?action=count`);
            const data = await response.json();
            
            if (data.success) {
                const badge = document.querySelector('.wishlist-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'block' : 'none';
                }
            }
        } catch (error) {
            console.error('Failed to update wishlist badge', error);
        }
    }
    
    showNotification(message, type) {
        // Simple notification (can be replaced with better UI)
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 3000);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    new Wishlist();
});
```

### 4. Update Header (`includes/header.php`)

Dodaj wishlist badge obok cart badge:

```php
<!-- Add after cart link -->
<a href="/wishlist.php" class="wishlist-link">
    ❤️
    <span class="wishlist-badge" style="display: none;">0</span>
</a>
```

### 5. CSS Styles (`assets/css/styles.css`)

```css
/* Wishlist Badge */
.wishlist-link {
    position: relative;
    padding: 8px 16px;
    font-size: 24px;
}

.wishlist-badge {
    position: absolute;
    top: 0;
    right: 6px;
    background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

/* Wishlist Page */
.wishlist-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.wishlist-item {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.wishlist-item:hover {
    transform: translateY(-5px);
}

.empty-wishlist {
    text-align: center;
    padding: 60px 20px;
}
```

---

## 📅 Sprint 2.2: Password Reset (Tydzień 1, dni 3-4)

### Files to create:
1. `forgot-password.php` - Request reset form
2. `reset-password.php` - Set new password
3. `verify.php` - Email verification
4. Email templates (3 files)

### Detailed specifications in separate document...

---

## 📅 Sprint 2.3: Product Comparison (Tydzień 2, dni 1-3)

### Files to create:
1. `pages/compare.php` - Comparison table
2. `api/compare-api.php` - AJAX endpoint
3. JavaScript for comparison

---

## 📅 Sprint 2.4: Reviews System (Tydzień 2, dni 4-5)

### Files to create:
1. `api/reviews-api.php` - Add/get reviews
2. `admin/reviews.php` - Moderation panel
3. Update `product-detail.php` - Show reviews

---

## 🎯 PRIORYTET PIERWSZEGO ZADANIA

**START HERE:** Wishlist Implementation

**Pliki do stworzenia (w tej kolejności):**
1. ✅ SQL migration (already done in MIGRATION-v2.0.sql)
2. `api/wishlist-api.php` (backend first)
3. `assets/js/wishlist.js` (JavaScript)
4. `wishlist.php` (frontend page)
5. Update `header.php` (add badge)
6. CSS styles

**Czas realizacji:** 3-4 godziny

---

## 📝 CHECKLIST - Wishlist Implementation

- [ ] Backend API utworzone (`api/wishlist-api.php`)
- [ ] JavaScript handler (`assets/js/wishlist.js`)
- [ ] Frontend page (`wishlist.php`)
- [ ] Header updated (badge dodany)
- [ ] CSS styles dodane
- [ ] Testy:
  - [ ] Dodaj do wishlisty
  - [ ] Usuń z wishlisty
  - [ ] Badge count update
  - [ ] Empty state
  - [ ] Multi-language
- [ ] Dokumentacja

---

**Gotowe do rozpoczęcia! 🚀**

W nowym czacie powiedz Claude:
```
Zaczynamy FAZĘ 2 - implementacja wishlist.
Zobacz NEXT-STEPS.md w project knowledge.
Zacznij od utworzenia api/wishlist-api.php.
```
