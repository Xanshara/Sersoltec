<?php
/**
 * Compare Page - ABSOLUTE FINAL
 * BEZ pomarańczowej sticky bar!
 * Z tłumaczeniami!
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '/var/www/lastchance/sersoltec/config.php';

if (file_exists('/var/www/lastchance/sersoltec/includes/translations.php')) {
    require_once '/var/www/lastchance/sersoltec/includes/translations.php';
}

$current_lang = getCurrentLanguage();
$page_title = t('compare_title') . ' - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link rel="stylesheet" href="/sersoltec/assets/css/styles.css">
    
    <style>
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
        
        .comparison-page {
            max-width: 1500px;
            margin: 0 auto;
            padding: var(--spacing-xl) var(--spacing-lg);
        }
        
        .comparison-empty {
            text-align: center;
            padding: var(--spacing-xxl);
        }
        
        #comparison-content {
            min-height: 400px;
        }
        
        /* HIDE STICKY BAR! */
        #comparison-bar {
            display: none !important;
        }
        
        .comparison-bar {
            display: none !important;
        }
    </style>
</head>
<body>

<?php include '/var/www/lastchance/sersoltec/includes/header.php'; ?>

<div class="comparison-page">
    <div class="comparison-header">
        <h1>⚖️ <?php echo t('compare_title'); ?></h1>
        <p><?php echo t('compare_subtitle'); ?></p>
    </div>
    
    <div id="comparison-content">
        <div style="text-align: center; padding: var(--spacing-xxl);">
            <div style="width: 50px; height: 50px; border: 4px solid var(--color-gray); border-top: 4px solid var(--color-primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto var(--spacing-lg);"></div>
            <p><?php echo t('compare_loading'); ?></p>
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
class ComparisonPage {
    constructor() {
        this.apiUrl = '/sersoltec/api/comparison-api.php';
        this.products = [];
        this.lang = '<?php echo $current_lang; ?>';
        this.translations = {
            feature: '<?php echo addslashes(t("compare_feature")); ?>',
            image: '<?php echo addslashes(t("compare_image")); ?>',
            name: '<?php echo addslashes(t("compare_name")); ?>',
            price: '<?php echo addslashes(t("compare_price")); ?>',
            category: '<?php echo addslashes(t("compare_category")); ?>',
            description: '<?php echo addslashes(t("compare_description")); ?>',
            stock: '<?php echo addslashes(t("compare_stock")); ?>',
            actions: '<?php echo addslashes(t("compare_actions")); ?>',
            available: '<?php echo addslashes(t("compare_available")); ?>',
            unavailable: '<?php echo addslashes(t("compare_unavailable")); ?>',
            no_category: '<?php echo addslashes(t("compare_no_category")); ?>',
            view_details: '<?php echo addslashes(t("compare_view_details")); ?>',
            remove: '<?php echo addslashes(t("compare_remove")); ?>',
            empty_title: '<?php echo addslashes(t("compare_empty_title")); ?>',
            empty_text: '<?php echo addslashes(t("compare_empty_text")); ?>',
            browse: '<?php echo addslashes(t("compare_browse")); ?>',
            clear: '<?php echo addslashes(t("compare_clear")); ?>',
            confirm_clear: '<?php echo addslashes(t("compare_confirm_clear")); ?>'
        };
        this.init();
    }
    
    async init() {
        await this.loadProducts();
        this.renderComparison();
    }
    
    async loadProducts() {
        try {
            const response = await fetch(`${this.apiUrl}?action=list&lang=${this.lang}`);
            const data = await response.json();
            
            if (data.success && data.data.products) {
                this.products = data.data.products;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    renderComparison() {
        const container = document.getElementById('comparison-content');
        
        if (this.products.length === 0) {
            container.innerHTML = `
                <div class="comparison-empty">
                    <div style="font-size: 5rem; margin-bottom: var(--spacing-lg);">📦</div>
                    <h2>${this.translations.empty_title}</h2>
                    <p>${this.translations.empty_text}</p>
                    <a href="/sersoltec/pages/products.php" class="btn btn-primary">
                        ${this.translations.browse}
                    </a>
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div style="overflow-x: auto;">
                <table class="comparison-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--color-light-gray);">
                            <th style="padding: 1rem; text-align: left;">${this.translations.feature}</th>
                            ${this.products.map(p => `<th style="padding: 1rem;">${this.escape(p.name)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${this.translations.image}</td>
                            ${this.products.map(p => `
                                <td style="padding: 1rem; text-align: center;">
                                    <img src="${p.image_url}" alt="${this.escape(p.name)}" 
                                         style="max-width: 200px; height: auto;"
                                         onerror="this.src='/sersoltec/assets/images/no-image.png'">
                                </td>
                            `).join('')}
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${this.translations.price}</td>
                            ${this.products.map(p => `
                                <td style="padding: 1rem; text-align: center; font-size: 1.5rem; font-weight: 700; color: var(--color-primary);">
                                    ${this.formatPrice(p.price)} PLN
                                </td>
                            `).join('')}
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${this.translations.category}</td>
                            ${this.products.map(p => `
                                <td style="padding: 1rem; text-align: center;">
                                    ${this.escape(p.category || this.translations.no_category)}
                                </td>
                            `).join('')}
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${this.translations.description}</td>
                            ${this.products.map(p => `
                                <td style="padding: 1rem;">
                                    ${this.escape(p.description).substring(0, 200)}...
                                </td>
                            `).join('')}
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${this.translations.stock}</td>
                            ${this.products.map(p => `
                                <td style="padding: 1rem; text-align: center;">
                                    ${p.stock_quantity > 0 ? this.translations.available : this.translations.unavailable}
                                </td>
                            `).join('')}
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${this.translations.actions}</td>
                            ${this.products.map(p => `
                                <td style="padding: 1rem; text-align: center;">
                                    <a href="/sersoltec/pages/product-detail.php?id=${p.id}" 
                                       class="btn btn-primary btn-sm" style="margin-bottom: 0.5rem;">
                                        👁️ ${this.translations.view_details}
                                    </a>
                                    <button onclick="comparisonPage.removeProduct(${p.id})" 
                                            class="btn btn-secondary btn-sm">
                                        ✕ ${this.translations.remove}
                                    </button>
                                </td>
                            `).join('')}
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button onclick="comparisonPage.clearAll()" 
                        class="btn btn-secondary btn-lg">
                    🗑️ ${this.translations.clear}
                </button>
            </div>
        `;
    }
    
    escape(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
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
            console.error('Error:', error);
        }
    }
    
    async clearAll() {
        if (!confirm(this.translations.confirm_clear)) {
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
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

const comparisonPage = new ComparisonPage();
</script>

<?php include '/var/www/lastchance/sersoltec/includes/footer.php'; ?>

<!-- DON'T LOAD comparison.js - it creates orange sticky bar! -->

</body>
</html>