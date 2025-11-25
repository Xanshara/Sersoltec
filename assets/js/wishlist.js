/**
 * Wishlist Manager - UNIVERSAL VERSION
 * Działa w KAŻDEJ konfiguracji:
 * - Root domain: example.com/
 * - Subdirectory: example.com/shop/
 * - Deep subdirectory: example.com/sites/myshop/
 * - Localhost: localhost/mysite/
 */

class WishlistManager {
    constructor() {
        // UNIWERSALNE WYKRYWANIE ŚCIEŻKI
        this.API_URL = this.detectApiUrl();
        this.badgeSelector = '.wishlist-badge';
        this.lang = document.documentElement.lang || 'pl';
        this.translations = this.getTranslations();
        
        console.log('🔍 WishlistManager initialized');
        console.log('📍 Current URL:', window.location.href);
        console.log('🎯 API URL:', this.API_URL);
        console.log('🌍 Language:', this.lang);
        
        this.init();
    }

    /**
     * Uniwersalne wykrywanie ścieżki do API
     * Działa dla KAŻDEJ struktury katalogów
     */
    detectApiUrl() {
        // Metoda 1: Sprawdź czy jest tag <base> w HTML
        const baseTag = document.querySelector('base');
        if (baseTag && baseTag.href) {
            const baseUrl = baseTag.href.replace(/\/$/, '');
            console.log('✅ Found <base> tag:', baseUrl);
            return baseUrl + '/api/wishlist-api.php';
        }

        // Metoda 2: Sprawdź czy jest data-api-url w <body> lub <html>
        const bodyApiUrl = document.body.dataset.apiUrl;
        if (bodyApiUrl) {
            console.log('✅ Found data-api-url in <body>:', bodyApiUrl);
            return bodyApiUrl;
        }

        const htmlApiUrl = document.documentElement.dataset.apiUrl;
        if (htmlApiUrl) {
            console.log('✅ Found data-api-url in <html>:', htmlApiUrl);
            return htmlApiUrl;
        }

        // Metoda 3: Automatyczne wykrywanie na podstawie obecnego URL
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/').filter(p => p);
        
        console.log('📂 Path parts:', pathParts);

        // Jeśli jesteśmy w /pages/, /includes/, itp. - idź level wyżej
        const knownDirs = ['pages', 'includes', 'assets', 'admin'];
        let basePath = '';

        if (pathParts.length > 0) {
            // Sprawdź czy jesteśmy w znanym podkatalogu
            const lastPart = pathParts[pathParts.length - 1];
            const isInKnownDir = knownDirs.some(dir => currentPath.includes('/' + dir + '/'));

            if (isInKnownDir) {
                // Jesteśmy w podkatalogu - base path to wszystko przed tym katalogiem
                const index = pathParts.findIndex(p => knownDirs.includes(p));
                if (index > 0) {
                    basePath = '/' + pathParts.slice(0, index).join('/');
                } else if (index === 0) {
                    basePath = '';
                }
            } else {
                // Możemy być w głównym katalogu lub subdirectory
                // Sprawdź czy pierwszy segment to subdirectory (nie index.php ani plik)
                if (pathParts[0] && !pathParts[0].includes('.')) {
                    basePath = '/' + pathParts[0];
                }
            }
        }

        console.log('📍 Detected base path:', basePath || '(root)');

        // Metoda 4: Spróbuj znaleźć relatywnie
        // Jeśli jesteśmy w /pages/ lub głębiej, użyj ../
        if (currentPath.includes('/pages/')) {
            const apiPath = '../api/wishlist-api.php';
            console.log('📍 Using relative path (from /pages/):', apiPath);
            return apiPath;
        }

        // Domyślnie: base path + /api/
        const finalUrl = (basePath || '') + '/api/wishlist-api.php';
        console.log('📍 Final API URL:', finalUrl);
        return finalUrl;
    }

    /**
     * Tłumaczenia dla różnych języków
     */
    getTranslations() {
        return {
            pl: {
                added: 'Dodano do wishlisty',
                removed: 'Usunięto z wishlisty',
                error: 'Wystąpił błąd',
                confirm_remove: 'Czy na pewno chcesz usunąć ten produkt z wishlisty?',
                in_wishlist: 'W wishliście',
                add_to_wishlist: 'Dodaj do wishlisty'
            },
            en: {
                added: 'Added to wishlist',
                removed: 'Removed from wishlist',
                error: 'An error occurred',
                confirm_remove: 'Are you sure you want to remove this product from wishlist?',
                in_wishlist: 'In wishlist',
                add_to_wishlist: 'Add to wishlist'
            },
            es: {
                added: 'Añadido a la lista de deseos',
                removed: 'Eliminado de la lista de deseos',
                error: 'Ocurrió un error',
                confirm_remove: '¿Estás seguro de que quieres eliminar este producto de la lista de deseos?',
                in_wishlist: 'En lista de deseos',
                add_to_wishlist: 'Añadir a lista de deseos'
            }
        };
    }

    t(key) {
        return this.translations[this.lang]?.[key] || this.translations['pl'][key] || key;
    }

    init() {
        // Update badge count on page load
        this.updateBadge();
        
        // Attach event listeners
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Add to wishlist buttons
        document.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.add-to-wishlist');
            if (addBtn && !addBtn.disabled) {
                e.preventDefault();
                const productId = addBtn.dataset.productId;
                this.addToWishlist(productId, addBtn);
            }
        });

        // Remove from wishlist buttons
        document.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-from-wishlist');
            if (removeBtn) {
                e.preventDefault();
                const productId = removeBtn.dataset.productId;
                this.removeFromWishlist(productId, removeBtn);
            }
        });
    }

    async addToWishlist(productId, button) {
        try {
            console.log('➕ Adding product to wishlist:', productId);
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);

            const response = await fetch(this.API_URL, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ Add response:', data);

            if (data.success) {
                this.showNotification(data.message || this.t('added'), 'success');
                this.updateBadge(data.count);
                this.updateButtonState(button, true);
            } else {
                this.showNotification(data.error || this.t('error'), 'error');
            }
        } catch (error) {
            console.error('❌ Error adding to wishlist:', error);
            this.showNotification(this.t('error'), 'error');
        }
    }

    async removeFromWishlist(productId, button) {
        if (!confirm(this.t('confirm_remove'))) {
            return;
        }

        try {
            console.log('➖ Removing product from wishlist:', productId);
            
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);

            const response = await fetch(this.API_URL, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ Remove response:', data);

            if (data.success) {
                this.showNotification(data.message || this.t('removed'), 'success');
                this.updateBadge(data.count);
                
                // Remove item from page if on wishlist page
                const item = button.closest('.wishlist-item');
                if (item) {
                    item.style.opacity = '0';
                    setTimeout(() => item.remove(), 300);
                }
                
                // Update button state if on product page
                this.updateButtonState(button, false);
            } else {
                this.showNotification(data.error || this.t('error'), 'error');
            }
        } catch (error) {
            console.error('❌ Error removing from wishlist:', error);
            this.showNotification(this.t('error'), 'error');
        }
    }

    async updateBadge(count = null) {
        try {
            if (count === null) {
                const response = await fetch(this.API_URL + '?action=count');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                count = data.count;
            }

            const badge = document.querySelector(this.badgeSelector);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
                console.log('🔢 Badge updated:', count);
            }
        } catch (error) {
            console.error('❌ Error updating badge:', error);
        }
    }

    updateButtonState(button, inWishlist) {
        if (!button) return;

        const icon = button.querySelector('i');
        
        if (inWishlist) {
            button.classList.add('in-wishlist');
            button.disabled = true;
            if (icon) {
                icon.classList.remove('fa-heart-o');
                icon.classList.add('fa-heart');
            }
            
            // Update text if exists
            const textContent = button.textContent.trim();
            if (textContent && !textContent.match(/^\s*$/)) {
                button.innerHTML = `<i class="fa fa-heart"></i> ${this.t('in_wishlist')}`;
            }
        } else {
            button.classList.remove('in-wishlist');
            button.disabled = false;
            if (icon) {
                icon.classList.remove('fa-heart');
                icon.classList.add('fa-heart-o');
            }
            
            // Update text if exists
            const textContent = button.textContent.trim();
            if (textContent && !textContent.match(/^\s*$/)) {
                button.innerHTML = `<i class="fa fa-heart-o"></i> ${this.t('add_to_wishlist')}`;
            }
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelector('.wishlist-notification');
        if (existing) {
            existing.remove();
        }

        // Create notification
        const notification = document.createElement('div');
        notification.className = `wishlist-notification wishlist-notification-${type}`;
        notification.innerHTML = `
            <i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Show with animation
        setTimeout(() => notification.classList.add('show'), 10);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.wishlistManager = new WishlistManager();
    });
} else {
    window.wishlistManager = new WishlistManager();
}

// CSS for notifications
const style = document.createElement('style');
style.textContent = `
.wishlist-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 10000;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.wishlist-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.wishlist-notification-success {
    border-left: 4px solid #28a745;
}

.wishlist-notification-success i {
    color: #28a745;
}

.wishlist-notification-error {
    border-left: 4px solid #dc3545;
}

.wishlist-notification-error i {
    color: #dc3545;
}

.wishlist-notification i {
    font-size: 20px;
}

.wishlist-notification span {
    color: #333;
    font-weight: 500;
}

.wishlist-item {
    transition: opacity 0.3s ease;
}
`;
document.head.appendChild(style);