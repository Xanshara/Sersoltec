/**
 * SERSOLTEC v2.5 - Product Comparison System (FIXED v2.6)
 * 
 * FIXES:
 * 1. Auto-detect API path (works in root and subdirectories)
 * 2. Better error handling with user-friendly messages
 * 3. Visual feedback for button states
 * 4. Proper localStorage sync
 * 5. Toast notifications for user feedback
 * 
 * @version 2.6.0
 * @date 2025-11-30
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
     * FIX: Removed hardcoded /sersoltec/ path
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
        
        console.log('🔍 Path detection debug:', {
            pathname: path,
            parts: pathParts
        });
        
        // If we're in /pages/ subdirectory, go up one level
        if (pathParts.includes('pages')) {
            const index = pathParts.indexOf('pages');
            const basePath = '/' + pathParts.slice(0, index).join('/');
            return basePath + '/api/comparison-api.php';
        }
        
        // If we're in a subdirectory like /sersoltec/
        if (pathParts.length > 0 && !pathParts.includes('index.php')) {
            // Check if first part looks like subdirectory (not a filename)
            const firstPart = pathParts[0];
            if (!firstPart.includes('.php')) {
                return '/' + firstPart + '/api/comparison-api.php';
            }
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
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.products) {
                this.items = data.data.products.map(p => ({
                    id: p.id,
                    name: p.name || 'Unknown',
                    price: p.price || 0,
                    image: p.image_url || p.image,
                    category: p.category || 'Uncategorized'
                }));
                console.log('✅ Loaded from API:', this.items.length, 'items');
            } else if (!data.success) {
                console.warn('⚠️ API error:', data.message);
            }
        } catch (error) {
            console.warn('⚠️ Could not load from API:', error);
            // Fallback to localStorage
            this.loadFromLocalStorage();
        }
    }
    
    /**
     * Load from localStorage fallback
     */
    loadFromLocalStorage() {
        try {
            const stored = localStorage.getItem('comparison_items');
            if (stored) {
                this.items = JSON.parse(stored);
                console.log('✅ Loaded from localStorage:', this.items.length, 'items');
            }
        } catch (error) {
            console.warn('⚠️ Could not load from localStorage:', error);
            this.items = [];
        }
    }
    
    /**
     * Sync with localStorage
     */
    syncWithLocalStorage() {
        try {
            // Save current items
            localStorage.setItem('comparison_items', JSON.stringify(this.items));
            
            // Listen for changes from other tabs
            window.addEventListener('storage', (e) => {
                if (e.key === 'comparison_items') {
                    try {
                        this.items = JSON.parse(e.newValue || '[]');
                        this.updateUI();
                    } catch (err) {
                        console.error('Error parsing localStorage:', err);
                    }
                }
            });
        } catch (error) {
            console.warn('⚠️ localStorage not available:', error);
        }
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
        if (!productId) {
            this.showToast('❌ Błąd: Brak ID produktu', 'error');
            return;
        }
        
        // Check if already in comparison
        if (this.items.some(item => item.id === productId)) {
            this.showToast('⚠️ Ten produkt jest już w porównaniu', 'warning');
            return;
        }
        
        // Check max limit
        if (this.items.length >= this.maxItems) {
            this.showToast(`⚠️ Maksymalnie ${this.maxItems} produkty`, 'warning');
            return;
        }
        
        // Show loading
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> Dodawanie...';
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Reload items from API
                await this.loadFromAPI();
                this.syncWithLocalStorage();
                this.updateUI();
                
                this.showToast('✓ Dodano do porównania', 'success');
                
                // Update button
                if (button) {
                    button.classList.add('in-comparison');
                    button.innerHTML = '✓ W porównaniu';
                }
            } else {
                throw new Error(data.message || 'Błąd API');
            }
        } catch (error) {
            console.error('Error adding to comparison:', error);
            this.showToast('❌ Błąd dodawania: ' + error.message, 'error');
        } finally {
            if (button) {
                button.disabled = false;
                if (!button.classList.contains('in-comparison')) {
                    button.innerHTML = '⚖️ Dodaj do porównania';
                }
            }
        }
    }
    
    /**
     * Remove product from comparison
     */
    async removeProduct(productId) {
        if (!productId) return;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=remove`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Reload items
                await this.loadFromAPI();
                this.syncWithLocalStorage();
                this.updateUI();
                
                this.showToast('✓ Usunięto z porównania', 'success');
            } else {
                throw new Error(data.message || 'Błąd API');
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
        if (!confirm('Czy na pewno chcesz wyczyścić porównanie?')) return;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=clear`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
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
        
        // Update bar visibility
        if (this.bar) {
            this.bar.style.display = this.items.length > 0 ? 'block' : 'none';
        }
        
        // Update items in bar
        this.updateComparisonBar();
    }
    
    /**
     * Update comparison bar items
     */
    updateComparisonBar() {
        const container = document.getElementById('comparison-items-container');
        if (!container) return;
        
        if (this.items.length === 0) {
            container.innerHTML = '';
            return;
        }
        
        container.innerHTML = this.items.map(item => `
            <div class="comparison-item" title="${item.name}">
                ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<span>📦</span>'}
                <button class="remove-from-compare" data-product-id="${item.id}" title="Usuń">✕</button>
            </div>
        `).join('');
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
        bar.style.cssText = `
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #ff9800 0%, #ff6f00 100%);
            color: white;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            padding: 15px 20px;
        `;
        
        bar.innerHTML = `
            <div class="comparison-bar-content" style="display: flex; gap: 20px; align-items: center; max-width: 1400px; margin: 0 auto;">
                <div class="comparison-bar-left" style="display: flex; align-items: center; gap: 10px; font-size: 16px;">
                    <strong>Porównanie</strong>
                    <span class="comparison-count" style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 20px; font-size: 14px;">0</span>
                </div>
                <div class="comparison-bar-items" id="comparison-items-container" style="flex: 1; display: flex; gap: 10px; overflow-x: auto;"></div>
                <div class="comparison-bar-right" style="display: flex; gap: 10px;">
                    <a href="${this.getComparePageUrl()}" class="btn-compare" style="background: white; color: #ff9800; padding: 10px 24px; border-radius: 25px; font-weight: 600; text-decoration: none; white-space: nowrap;">
                        👁️ Porównaj
                    </a>
                    <button class="btn-clear-comparison" onclick="window.comparisonManager?.clearAll()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer;">
                        🗑️
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(bar);
        this.bar = bar;
    }
    
    /**
     * Get comparison page URL
     */
    getComparePageUrl() {
        // Try to detect the path
        const path = window.location.pathname;
        const pathParts = path.split('/').filter(p => p && !p.includes('.php'));
        
        if (pathParts.length > 0 && !pathParts.includes('pages')) {
            return '/' + pathParts[0] + '/compare.php';
        }
        
        if (pathParts.includes('pages')) {
            const index = pathParts.indexOf('pages');
            const basePath = pathParts.slice(0, index).join('/');
            return basePath ? '/' + basePath + '/compare.php' : '/compare.php';
        }
        
        return '/compare.php';
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Use existing toast system if available
        if (window.showToast) {
            window.showToast(message, type);
            return;
        }
        
        // Fallback: create simple toast
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: ${
                type === 'success' ? '#4CAF50' :
                type === 'error' ? '#f44336' :
                type === 'warning' ? '#ff9800' : '#2196F3'
            };
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 9998;
            animation: slideUp 0.3s ease-out;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize globally
window.comparisonManager = new ComparisonManager();

// CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes slideDown {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);