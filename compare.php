<?php
/**
 * SERSOLTEC v2.5+ - Product Comparison Page (IMPROVED DESIGN)
 * 
 * IMPROVEMENTS:
 * 1. Beautiful table with gradient header
 * 2. Card-based mobile design
 * 3. Color-coded availability
 * 4. Better typography & spacing
 * 5. Smooth animations
 * 6. Product images
 * 7. Better buttons & icons
 * 8. Responsive grid layout
 * 
 * @version 2.7.0 (DESIGN IMPROVED)
 * @date 2025-11-30
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/includes/translations.php')) {
    require_once __DIR__ . '/includes/translations.php';
}

$current_lang = getCurrentLanguage();
$page_title = t('compare_page_title') . ' - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link rel="stylesheet" href="/sersoltec/assets/css/styles.css">
    
    <style>
        /* ============================================
           COMPARISON PAGE - IMPROVED DESIGN
           ============================================ */
        
        .comparison-page {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 60px 20px;
        }
        
        .comparison-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* HEADER */
        .comparison-header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInDown 0.6s ease-out;
        }
        
        .comparison-header h1 {
            font-size: 3rem;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0 0 15px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .comparison-header p {
            font-size: 1.2rem;
            color: var(--color-text-light);
            margin: 0;
        }
        
        /* EMPTY STATE */
        .comparison-empty {
            background: white;
            border-radius: 16px;
            padding: 80px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .comparison-empty-icon {
            font-size: 100px;
            margin-bottom: 20px;
        }
        
        .comparison-empty h2 {
            font-size: 2rem;
            color: var(--color-text);
            margin: 0 0 15px 0;
        }
        
        .comparison-empty p {
            font-size: 1.1rem;
            color: var(--color-text-light);
            margin: 0 0 30px 0;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-browse-comparison {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .btn-browse-comparison:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        /* COMPARISON TABLE */
        .comparison-wrapper {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* TABLE HEAD */
        .comparison-table thead {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: white;
        }
        
        .comparison-table th {
            padding: 25px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: none;
        }
        
        .comparison-table th:first-child {
            background: rgba(0,0,0,0.1);
        }
        
        /* TABLE BODY */
        .comparison-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .comparison-table tbody tr:hover {
            background-color: #fafafa;
        }
        
        .comparison-table td {
            padding: 20px 15px;
            font-size: 0.95rem;
        }
        
        .row-label {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--color-primary);
            min-width: 150px;
            text-align: left;
            border-right: 3px solid var(--color-primary);
        }
        
        .product-cell {
            text-align: center;
            vertical-align: middle;
        }
        
        /* PRODUCT IMAGE */
        .product-image {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            margin: 0 auto;
            display: block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .product-image:hover {
            transform: scale(1.05);
        }
        
        .product-name {
            font-weight: 700;
            color: var(--color-text);
            font-size: 1.05rem;
            margin-top: 10px;
        }
        
        .product-sku {
            font-size: 0.85rem;
            color: var(--color-text-light);
            margin-top: 5px;
            font-family: 'Courier New', monospace;
        }
        
        /* PRICE */
        .product-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--color-primary);
            margin: 10px 0;
        }
        
        .price-currency {
            font-size: 0.8em;
            opacity: 0.8;
        }
        
        /* AVAILABILITY */
        .availability-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin: 10px 0;
        }
        
        .availability-available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .availability-unavailable {
            background: #ffebee;
            color: #c62828;
        }
        
        /* DESCRIPTION */
        .product-description {
            color: var(--color-text-light);
            font-size: 0.9rem;
            line-height: 1.6;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* ACTIONS */
        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-action {
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            display: inline-block;
        }
        
        .btn-view {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-remove {
            background: #ffebee;
            color: #c62828;
            border: 2px solid #ef5350;
        }
        
        .btn-remove:hover {
            background: #ef5350;
            color: white;
        }
        
        /* CLEAR BUTTON */
        .clear-button-container {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .btn-clear {
            padding: 16px 40px;
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(244,67,54,0.3);
        }
        
        .btn-clear:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(244,67,54,0.4);
        }
        
        /* ANIMATIONS */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .comparison-page {
                padding: 20px 10px;
            }
            
            .comparison-header h1 {
                font-size: 2rem;
            }
            
            .comparison-table thead {
                font-size: 0.75rem;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 12px 8px;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
            }
            
            .product-price {
                font-size: 1.3rem;
            }
            
            .product-name {
                font-size: 0.9rem;
            }
            
            .btn-action {
                padding: 10px 12px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="comparison-page">
    <div class="comparison-container">
        <div class="comparison-header">
            <h1>⚖️ <?php echo t('compare_title'); ?></h1>
            <p><?php echo t('compare_subtitle'); ?></p>
        </div>
        
        <div id="comparison-content">
            <!-- Loading state -->
            <div style="text-align: center; padding: 80px 20px;">
                <div style="width: 60px; height: 60px; border: 4px solid var(--color-primary); border-top: 4px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <p style="font-size: 1.1rem; color: var(--color-text-light);">⏳ <?php echo t('compare_loading'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/**
 * Comparison Page - Improved Design Version
 */
class ComparisonPage {
    constructor() {
        this.apiUrl = this.detectApiUrl();
        this.products = [];
        this.init();
    }
    
    detectApiUrl() {
        const path = window.location.pathname;
        const pathParts = path.split('/').filter(p => p && !p.includes('.php'));
        
        if (pathParts.length > 0) {
            const firstPart = pathParts[0];
            if (!firstPart.includes('.')) {
                return '/' + firstPart + '/api/comparison-api.php';
            }
        }
        return '/api/comparison-api.php';
    }
    
    async init() {
        try {
            await this.loadProducts();
            this.render();
        } catch (error) {
            this.renderError(error.message);
        }
    }
    
    async loadProducts() {
        const response = await fetch(this.apiUrl + '?action=list');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();
        if (!data.success) throw new Error(data.error || 'API Error');
        
        this.products = data.data?.products || data.products || [];
    }
    
    render() {
        const container = document.getElementById('comparison-content');
        
        if (!this.products || this.products.length === 0) {
            container.innerHTML = this.renderEmpty();
            return;
        }
        
        container.innerHTML = this.renderTable() + this.renderClearButton();
    }
    
    renderEmpty() {
        return `
            <div class="comparison-empty">
                <div class="comparison-empty-icon">📦</div>
                <h2>${this.t('compare_empty_title')}</h2>
                <p>${this.t('compare_empty_text')}</p>
                <a href="/sersoltec/pages/products.php" class="btn-browse-comparison">
                    ${this.t('compare_empty_browse')}
                </a>
            </div>
        `;
    }
    
    renderTable() {
        return `
            <div class="comparison-wrapper">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>${this.t('compare_row_name')}</th>
                            ${this.products.map(p => `<th>${this.escape(p.name)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${this.renderImageRow()}
                        ${this.renderSkuRow()}
                        ${this.renderPriceRow()}
                        ${this.renderDescriptionRow()}
                        ${this.renderAvailabilityRow()}
                        ${this.renderActionsRow()}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    renderImageRow() {
        return `
            <tr>
                <td class="row-label">${this.t('compare_row_name')}</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        ${p.image_url 
                            ? `<img src="${this.escape(p.image_url)}" alt="${this.escape(p.name)}" class="product-image">` 
                            : '<div style="width:140px; height:140px; background:#eee; border-radius:12px; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:50px;">📦</div>'}
                        <div class="product-name">${this.escape(p.name)}</div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderSkuRow() {
        return `
            <tr>
                <td class="row-label">${this.t('compare_row_sku')}</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <div class="product-sku">${this.escape(p.sku || 'N/A')}</div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderPriceRow() {
        return `
            <tr>
                <td class="row-label">${this.t('compare_row_price')}</td>
                ${this.products.map(p => `
                    <td class="product-cell">
                        <div class="product-price">
                            <span class="price-currency">zł</span>
                            ${parseFloat(p.price || 0).toFixed(2).replace('.', ',')}
                        </div>
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
                        <div class="product-description">${this.escape(p.description ? p.description.substring(0, 100) + '...' : 'N/A')}</div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderAvailabilityRow() {
        return `
            <tr>
                <td class="row-label">${this.t('compare_row_stock')}</td>
                ${this.products.map(p => {
                    const inStock = p.stock_quantity > 0;
                    const className = inStock ? 'availability-available' : 'availability-unavailable';
                    const text = inStock ? '✓ ' + this.t('compare_row_available') : '✗ ' + this.t('compare_row_unavailable');
                    return `<td class="product-cell"><span class="availability-badge ${className}">${text}</span></td>`;
                }).join('')}
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
                            <a href="/sersoltec/pages/product-detail.php?id=${p.id}" class="btn-action btn-view">
                                👁️ ${this.t('compare_btn_view_details')}
                            </a>
                            <button onclick="comparisonPage.removeProduct(${p.id})" class="btn-action btn-remove">
                                ✕ ${this.t('compare_btn_remove')}
                            </button>
                        </div>
                    </td>
                `).join('')}
            </tr>
        `;
    }
    
    renderClearButton() {
        return `
            <div class="clear-button-container">
                <button onclick="comparisonPage.clearAll()" class="btn-clear">
                    🗑️ ${this.t('compare_btn_clear')}
                </button>
            </div>
        `;
    }
    
    async removeProduct(productId) {
        try {
            const response = await fetch(this.apiUrl + '?action=remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
        }
    }
    
    async clearAll() {
        if (!confirm('Czy na pewno chcesz wyczyścić porównanie?')) return;
        
        try {
            const response = await fetch(this.apiUrl + '?action=clear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (error) {
            alert('Błąd: ' + error.message);
        }
    }
    
    t(key) {
        const translations = {
            'compare_title': 'Porównanie produktów',
            'compare_subtitle': 'Porównaj wybrane produkty i znajdź najlepszy dla siebie',
            'compare_empty_title': 'Brak produktów do porównania',
            'compare_empty_text': 'Dodaj produkty aby zobaczyć je tutaj',
            'compare_empty_browse': 'Przeglądaj produkty',
            'compare_loading': 'Ładowanie produktów...',
            'compare_row_name': 'Nazwa',
            'compare_row_sku': 'SKU',
            'compare_row_price': 'Cena',
            'compare_row_stock': 'Dostępność',
            'compare_row_available': 'Dostępny',
            'compare_row_unavailable': 'Niedostępny',
            'compare_btn_view_details': 'Szczegóły',
            'compare_btn_remove': 'Usuń',
            'compare_btn_clear': 'Wyczyść porównanie',
        };
        return translations[key] || key;
    }
    
    escape(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    renderError(message) {
        const container = document.getElementById('comparison-content');
        container.innerHTML = `
            <div class="comparison-empty" style="background: #ffebee;">
                <div class="comparison-empty-icon">❌</div>
                <h2>Błąd ładowania</h2>
                <p>${this.escape(message)}</p>
                <a href="/sersoltec/pages/products.php" class="btn-browse-comparison">Powrót do produktów</a>
            </div>
        `;
    }
}

const comparisonPage = new ComparisonPage();
</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

</body>
</html>