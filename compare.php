<?php
/**
 * SERSOLTEC v2.5 - Product Comparison Page (INTEGRATED)
 * 
 * Uses real header.php and footer.php
 * Uses colors from style.css :root variables
 * 
 * @version 2.5.4
 * @date 2025-11-27
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once __DIR__ . '/config.php';

// Load translations
if (file_exists(__DIR__ . '/includes/translations.php')) {
    require_once __DIR__ . '/includes/translations.php';
}

// Page settings
$page_title = 'Porównanie produktów - ' . SITE_NAME;
$current_lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Main styles -->
    <link rel="stylesheet" href="/sersoltec/assets/css/styles.css">
    
    <!-- Comparison System CSS -->
    <!-- <link rel="stylesheet" href="/sersoltec/assets/css/comparison.css"> -->
    
    <style>
        /* Override comparison.css to use :root variables from styles.css */
        .comparison-header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: var(--color-white);
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .comparison-header h1 {
            color: var(--color-white);
            font-size: 2.5rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .comparison-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        .btn-browse-products {
            background: var(--color-primary);
            color: var(--color-white);
            padding: var(--spacing-md) var(--spacing-xl);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all var(--transition-normal);
        }
        
        .btn-browse-products:hover {
            background: var(--color-primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-clear-comparison {
            background: #dc3545;
            color: white;
            padding: var(--spacing-md) var(--spacing-xl);
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
        }
        
        .btn-clear-comparison:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .comparison-page {
            max-width: 1500px;
            margin: 0 auto;
            padding: var(--spacing-xl) var(--spacing-lg);
        }
        
        .comparison-empty {
            text-align: center;
            padding: var(--spacing-xxl);
        }
        
        .comparison-empty-icon {
            font-size: 5rem;
            margin-bottom: var(--spacing-lg);
        }
        
        .comparison-empty h2 {
            color: var(--color-primary-dark);
            margin-bottom: var(--spacing-md);
        }
        
        .comparison-empty p {
            color: var(--color-text);
            margin-bottom: var(--spacing-xl);
        }
        
        /* Button styling using :root variables */
        .btn-add-to-compare {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: var(--color-white);
            border: none;
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        .btn-add-to-compare:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-add-to-compare.in-comparison {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="comparison-page">
    <div class="comparison-header">
        <h1>⚖️ Porównanie produktów</h1>
        <p>Porównaj wybrane produkty i znajdź najlepszy dla siebie</p>
    </div>
    
    <div id="comparison-content">
        <!-- Loading state -->
        <div class="loading-state" style="text-align: center; padding: var(--spacing-xxl);">
            <div style="width: 50px; height: 50px; border: 4px solid var(--color-gray); border-top: 4px solid var(--color-primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto var(--spacing-lg);"></div>
            <p style="color: var(--color-text);">Ładowanie produktów...</p>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
/**
 * Comparison Page Logic
 */
class ComparisonPage {
    constructor() {
        this.apiUrl = '/sersoltec/api/comparison-api.php';
        this.products = [];
        this.init();
    }
    
    async init() {
        await this.loadProducts();
        this.renderComparison();
    }
    
    async loadProducts() {
        try {
            const response = await fetch(`${this.apiUrl}?action=list`);
            const data = await response.json();
            
            console.log('API Response:', data);
            
            if (data.success && data.data.products) {
                this.products = data.data.products;
            } else {
                console.error('API Error:', data.message);
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }
    
    renderComparison() {
        const container = document.getElementById('comparison-content');
        
        if (this.products.length === 0) {
            container.innerHTML = this.renderEmptyState();
            return;
        }
        
        container.innerHTML = this.renderTable();
    }
    
    renderEmptyState() {
        return `
            <div class="comparison-empty">
                <div class="comparison-empty-icon">📦</div>
                <h2>Brak produktów do porównania</h2>
                <p>Dodaj produkty do porównania, aby zobaczyć je tutaj</p>
                <a href="/sersoltec/pages/products.php" class="btn-browse-products">
                    Przeglądaj produkty
                </a>
            </div>
        `;
    }
    
    renderTable() {
        return `
            <div class="comparison-table-wrapper">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Cecha</th>
                            ${this.products.map(p => `<th>${this.escapeHtml(p.name)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${this.renderImageRow()}
                        ${this.renderPriceRow()}
                        ${this.renderCategoryRow()}
                        ${this.renderDescriptionRow()}
                        ${this.renderStockRow()}
                        ${this.renderActionsRow()}
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: var(--spacing-xxl);">
                <button onclick="comparisonPage.clearAll()" class="btn-clear-comparison">
                    🗑️ Wyczyść porównanie
                </button>
            </div>
        `;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    renderImageRow() {
        return `
            <tr>
                <td class="row-label">Zdjęcie</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <img src="${p.image_url || '/sersoltec/assets/images/no-image.png'}" 
                             alt="${this.escapeHtml(p.name)}" 
                             class="product-image"
                             onerror="this.src='/sersoltec/assets/images/no-image.png'">
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderPriceRow() {
        return `
            <tr>
                <td class="row-label">Cena</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <div class="product-price">${this.formatPrice(p.price)} PLN</div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderCategoryRow() {
        return `
            <tr>
                <td class="row-label">Kategoria</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <span class="product-category">${this.escapeHtml(p.category || 'Bez kategorii')}</span>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderDescriptionRow() {
        return `
            <tr>
                <td class="row-label">Opis</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <div class="spec-value">${this.escapeHtml(p.description || 'Brak opisu')}</div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderStockRow() {
        return `
            <tr>
                <td class="row-label">Dostępność</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <div class="spec-value ${p.stock_quantity > 0 ? 'highlight' : 'lowlight'}">
                            ${p.stock_quantity > 0 ? '✓ Dostępny' : '✗ Niedostępny'}
                        </div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderActionsRow() {
        return `
            <tr>
                <td class="row-label">Akcje</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <div class="product-actions">
                            <a href="/sersoltec/pages/product-detail.php?id=${p.id}" class="btn-view-product">
                                👁️ Zobacz szczegóły
                            </a>
                            <button onclick="comparisonPage.removeProduct(${p.id})" class="btn-remove-compare">
                                ✕ Usuń z porównania
                            </button>
                        </div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    formatPrice(price) {
        return parseFloat(price || 0).toFixed(2).replace('.', ',');
    }
    
    async removeProduct(productId) {
        try {
            const response = await fetch(`${this.apiUrl}?action=remove`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.reload();
            } else {
                alert('Błąd: ' + data.message);
            }
        } catch (error) {
            console.error('Error removing product:', error);
            alert('Błąd usuwania produktu');
        }
    }
    
    async clearAll() {
        if (!confirm('Czy na pewno chcesz wyczyścić całe porównanie?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=clear`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.reload();
            } else {
                alert('Błąd: ' + data.message);
            }
        } catch (error) {
            console.error('Error clearing comparison:', error);
            alert('Błąd czyszczenia porównania');
        }
    }
}

// Initialize
const comparisonPage = new ComparisonPage();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Comparison System JS (sticky bar) -->
<script src="/sersoltec/assets/js/comparison.js"></script>

</body>
</html>