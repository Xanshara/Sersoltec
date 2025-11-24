<?php
// ===== SERSOLTEC - GŁÓWNA STRONA =====

// Upewnij się, że ten plik dołącza config.php i/lub functions.php
require_once 'config.php'; // Jeśli tu masz funkcje bazodanowe i t()
require_once 'includes/get-category-svg.php';

// DODAJ JEDNĄ Z TYCH LINII, ABY ZAPEWNIĆ, ŻE TŁUMACZENIA SIĘ ŁADUJĄ
// Najpierw spróbuj tej:
// require_once 'includes/translations.php'; 
// A jeśli plik config już to robi, to upewnij się, że poprawnie dołączasz funkcje:
// require_once 'includes/functions.php'; 


$current_lang = getCurrentLanguage();

// Pobierz kategorie
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY `order`");
$categories = $stmt->fetchAll();

// Pobierz produkty promocyjne (12 random)
$stmt = $pdo->query("SELECT * FROM products WHERE active = 1 ORDER BY RAND() LIMIT 12");
$featured_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo t('hero_subtitle'); ?>">
    <title><?php echo SITE_NAME; ?> - <?php echo t('hero_title'); ?></title>
    
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <link rel="icon" type="image/svg+xml" href="assets/images/logo.svg">
    
    <meta property="og:title" content="<?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo t('hero_subtitle'); ?>">
    <meta property="og:image" content="assets/images/logo.svg">
</head>
<body>

<?php include 'includes/header.php'; ?>

<section class="hero-home">
    <div class="hero-content">
        <h1><?php echo t('hero_title'); ?></h1>
        <p><?php echo t('hero_subtitle'); ?></p>
        <div class="hero-buttons">
            <a href="pages/products.php" class="btn btn-primary btn-lg">
                <?php echo t('hero_cta'); ?>
            </a>
            <a href="pages/contact.php" class="btn btn-secondary btn-lg">
                <?php echo t('hero_contact'); ?>
            </a>
        </div>
    </div>
</section>

<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2><?php echo t('nav_products'); ?></h2>
            <p><?php echo t('home_categories_subtitle'); ?></p>
        </div>
        
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="pages/products.php?category=<?php echo urlencode($cat['slug']); ?>" class="category-card">
                    <div class="category-icon">
    <?php 
        // 1. Spróbuj pobrać SVG na podstawie SLUGa
        $svg_code = getCategorySvgIcon($cat['slug']);

        if ($svg_code) {
            // 2. Jeśli SVG istnieje w tablicy, wyświetl je
            echo $svg_code;
        } else {
            // 3. Jeśli nie ma SVG (lub slug jest nowy), wróć do emoji z bazy
            echo $cat['icon'] ?? '📦';
        }
    ?>
</div>
                    <h3><?php 
                        $lang_key = 'name_' . $current_lang;
                        echo htmlspecialchars($cat[$lang_key]);
                    ?></h3>
                    <p class="category-desc">
                        <?php 
                            $lang_key = 'description_' . $current_lang;
                            echo htmlspecialchars(substr($cat[$lang_key] ?? '', 0, 80)) . '...';
                        ?>
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2><?php echo t('home_featured_title'); ?></h2>
            <p><?php echo t('home_featured_subtitle'); ?></p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if ($product['image']): ?>
                            <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name_' . $current_lang]); ?>">
                        <?php else: ?>
                            <span>📦</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <div class="product-category">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name_' . $current_lang]); ?></h3>
                        <p class="product-desc"><?php 
                            echo htmlspecialchars(substr($product['description_' . $current_lang], 0, 100));
                        ?>...</p>
                        
                        <?php if ($product['price_base']): ?>
                            <div class="product-price"><?php echo formatPrice($product['price_base']); ?></div>
                        <?php else: ?>
                            <div class="product-price"><?php echo t('product_contact_for_price'); ?></div>
                        <?php endif; ?>
                        
                        <div class="product-actions">
                            <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">
                                <?php echo t('product_inquire'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container">
        <div class="section-header">
            <h2><?php echo t('home_why_title'); ?></h2>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🏆</div>
                <h3><?php echo t('value_quality_title'); ?></h3>
                <p><?php echo t('value_quality_desc'); ?></p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">🌍</div>
                <h3><?php echo t('value_eco_title'); ?></h3>
                <p><?php echo t('value_eco_desc'); ?></p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">⚡</div>
                <h3><?php echo t('value_modern_title'); ?></h3>
                <p><?php echo t('value_modern_desc'); ?></p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">💼</div>
                <h3><?php echo t('value_service_title'); ?></h3>
                <p><?php echo t('value_service_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<?php // To jest nowy komentarz, wymuszający rewalidację PHP
include('includes/footer.php'); ?>

<script src="assets/js/main.js"></script>

</body>
</html>