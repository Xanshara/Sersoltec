/**
 * SERSOLTEC v2.5 - Product Comparison System
 * 
 * Features:
 * - Add/remove products from comparison
 * - Max 4 products
 * - Sticky comparison bar
 * - localStorage sync
 * - AJAX API calls
 * - Auto-detect API path (root/subdirectory)
 * 
 * @version 2.5.0
 * @date 2025-11-27
 */

class ComparisonManager {
    constructor() {
        this.apiUrl = this.detectApiUrl();
        this.maxItems = 4;
        this.items = [];
        this.bar = null;
        
        console.log('🔍 ComparisonManager initialized');
        console.log('🎯 API URL:', this.apiUrl);
        
        this.init();
    }
    
    /**
     * Auto-detect API URL (works in root and subdirectories)
     */
    detectApiUrl() {
        // Method 1: Check for <base> tag
        const baseTag = document.querySelector('base');
        if (baseTag && baseTag.href) {
            const baseUrl = baseTag.href.replace(/\/$/, '');
            return baseUrl + '/api/comparison-api.php';
        }
        
        // Method 2: Check for data-api-url attribute
        const apiUrlElement = document.querySelector('[data-api-url]');
        if (apiUrlElement) {
            return apiUrlElement.getAttribute('data-api-url');
        }
        
        // Method 3: Auto-detect from current path
        const path = window.location.pathname;
        const pathParts = path.split('/').filter(p => p);
        
        // If we're in /pages/ subdirectory, go up one level
        if (pathParts.includes('pages')) {
            const index = pathParts.indexOf('pages');
            const basePath = '/' + pathParts.slice(0, index).join('/');
            return basePath + '/api/comparison-api.php';
        }
        
        // Method 4: Check if we're in a subdirectory
        if (pathParts.length > 0 && !path.includes('.php')) {
            // Likely in subdirectory like /sersoltec/
            return '/' + pathParts[0] + '/api/comparison-api.php';
        }
        
        // Default: root
        return '/api/comparison-api.php';
    }
    
    /**
     * Initialize
     */
    async init() {
        // Load from API
        await this.loadFromAPI();
        
        // Sync with localStorage
        this.syncWithLocalStorage();
        
        // Create comparison bar
        this.createComparisonBar();
        
        // Attach event listeners
        this.attachEventListeners();
        
        // Update UI
        this.updateUI();
        
        console.log('✅ Comparison system ready. Items:', this.items.length);
    }
    
    /**
     * Load items from API
     */
    async loadFromAPI() {
        try {
            const response = await fetch(`${this.apiUrl}?action=list`);
            const data = await response.json();
            
            if (data.success && data.data.products) {
                this.items = data.data.products.map(p => ({
                    id: p.id,
                    name: p.name,
                    price: p.price,
                    image: p.image_url,
                    category: p.category
                }));
            }
        } catch (error) {
            console.warn('⚠️ Could not load from API:', error);
            // Fallback to localStorage
            const stored = localStorage.getItem('comparison_items');
            if (stored) {
                this.items = JSON.parse(stored);
            }
        }
    }
    
    /**
     * Sync with localStorage
     */
    syncWithLocalStorage() {
        // Save current items
        localStorage.setItem('comparison_items', JSON.stringify(this.items));
        
        // Listen for changes from other tabs
        window.addEventListener('storage', (e) => {
            if (e.key === 'comparison_items') {
                this.items = JSON.parse(e.newValue || '[]');
                this.updateUI();
            }
        });
    }
    
    /**
     * Create sticky comparison bar
     */
    createComparisonBar() {
        // Check if already exists
        if (document.getElementById('comparison-bar')) {
            this.bar = document.getElementById('comparison-bar');
            return;
        }
        
        const bar = document.createElement('div');
        bar.id = 'comparison-bar';
        bar.className = 'comparison-bar';
        bar.style.display = 'none';
        
        bar.innerHTML = `
            <div class="comparison-bar-content">
                <div class="comparison-bar-left">
                    <strong>Porównanie produktów</strong>
                    <span class="comparison-count">0</span>
                </div>
                <div class="comparison-bar-items" id="comparison-items-container"></div>
                <div class="comparison-bar-right">
                    <a href="compare.php" class="btn-compare">
                        Porównaj produkty
                    </a>
                    <button class="btn-clear-comparison" title="Wyczyść wszystko">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(bar);
        this.bar = bar;
        
        // Event listener for clear button
        bar.querySelector('.btn-clear-comparison').addEventListener('click', () => {
            if (confirm('Czy na pewno chcesz wyczyścić porównanie?')) {
                this.clearAll();
            }
        });
    }
    
    /**
     * Attach event listeners to "Add to Compare" buttons
     */
    attachEventListeners() {
        // Delegate event for dynamically added buttons
        document.addEventListener('click', async (e) => {
            // Add to compare
            if (e.target.closest('.btn-add-to-compare')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-add-to-compare');
                const productId = parseInt(btn.dataset.productId);
                await this.addProduct(productId, btn);
            }
            
            // Remove from compare (in bar)
            if (e.target.closest('.remove-from-compare')) {
                e.preventDefault();
                const btn = e.target.closest('.remove-from-compare');
                const productId = parseInt(btn.dataset.productId);
                await this.removeProduct(productId);
            }
        });
    }
    
    /**
     * Add product to comparison
     */
    async addProduct(productId, button = null) {
        if (!productId) return;
        
        // Check if already in comparison
        if (this.items.some(item => item.id === productId)) {
            this.showToast('⚠️ Ten produkt jest już w porównaniu', 'warning');
            return;
        }
        
        // Check max limit
        if (this.items.length >= this.maxItems) {
            this.showToast(`⚠️ Możesz porównać maksymalnie ${this.maxItems} produkty`, 'warning');
            return;
        }
        
        // Show loading
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> Dodawanie...';
        }
        
        try {
            // API call
            const response = await fetch(`${this.apiUrl}?action=add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload items from API
                await this.loadFromAPI();
                this.syncWithLocalStorage();
                this.updateUI();
                
                this.showToast('✓ Dodano do porównania', 'success');
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            console.error('Error adding to comparison:', error);
            this.showToast('❌ ' + (error.message || 'Błąd dodawania'), 'error');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = '⚖️ Dodaj do porównania';
            }
        }
    }
    
    /**
     * Remove product from comparison
     */
    async removeProduct(productId) {
        if (!productId) return;
        
        try {
            // API call
            const response = await fetch(`${this.apiUrl}?action=remove`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload items
                await this.loadFromAPI();
                this.syncWithLocalStorage();
                this.updateUI();
                
                this.showToast('✓ Usunięto z porównania', 'success');
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            console.error('Error removing from comparison:', error);
            this.showToast('❌ Błąd usuwania', 'error');
        }
    }
    
    /**
     * Clear all items
     */
    async clearAll() {
        try {
            const response = await fetch(`${this.apiUrl}?action=clear`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.items = [];
                this.syncWithLocalStorage();
                this.updateUI();
                
                this.showToast('✓ Porównanie wyczyszczone', 'success');
            }
        } catch (error) {
            console.error('Error clearing comparison:', error);
            this.showToast('❌ Błąd czyszczenia', 'error');
        }
    }
    
    /**
     * Update UI
     */
    updateUI() {
        // Update count badge
        const badges = document.querySelectorAll('.comparison-count, .comparison-badge');
        badges.forEach(badge => {
            badge.textContent = this.items.length;
            badge.style.display = this.items.length > 0 ? 'inline-block' : 'none';
        });
        
        // Update comparison bar
        if (this.bar) {
            if (this.items.length > 0) {
                this.bar.style.display = 'block';
                this.updateComparisonBar();
            } else {
                this.bar.style.display = 'none';
            }
        }
        
        // Update "Add to Compare" buttons
        document.querySelectorAll('.btn-add-to-compare').forEach(btn => {
            const productId = parseInt(btn.dataset.productId);
            const inComparison = this.items.some(item => item.id === productId);
            
            if (inComparison) {
                btn.classList.add('in-comparison');
                btn.innerHTML = '✓ W porównaniu';
                btn.disabled = true;
            } else {
                btn.classList.remove('in-comparison');
                btn.innerHTML = '⚖️ Dodaj do porównania';
                btn.disabled = false;
            }
        });
    }
    
    /**
     * Update comparison bar items
     */
    updateComparisonBar() {
        const container = document.getElementById('comparison-items-container');
        if (!container) return;
        
        container.innerHTML = this.items.map(item => `
            <div class="comparison-item">
                <img src="${item.image || '/assets/images/no-image.png'}" alt="${item.name}">
                <button class="remove-from-compare" data-product-id="${item.id}" title="Usuń">×</button>
            </div>
        `).join('');
        
        // Update count
        const countElement = this.bar.querySelector('.comparison-count');
        if (countElement) {
            countElement.textContent = `(${this.items.length}/${this.maxItems})`;
        }
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.comparisonManager = new ComparisonManager();
    });
} else {
    window.comparisonManager = new ComparisonManager();
}