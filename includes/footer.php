<?php
// ===== FOOTER =====

// Dodaj tę funkcję pomocniczą, aby określić poprawną ścieżkę bazową dla zasobów
// (obrazków, CSS, JS), które są dołączane przez ten plik.
if (!function_exists('getBasePath')) {
    function getBasePath() {
        // Sprawdza, czy bieżący skrypt PHP jest wykonywany w podkatalogu 'pages/'
        return (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
    }
}
?>

<section class="content-section text-center partners-section">
    <div class="container">
        <h4><?php echo t('about_partners_title'); ?></h4>
        
        <div class="partners-grid">
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/degol.jpg" alt="Grupo Degol Constructora">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/jergo.webp" alt="Constructora Jergo">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/salesianes.jpg" alt="Salesianos del Perú">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/GrupoAlfard.webp" alt="Grupo Alfard">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/procasa-cusco.webp" alt="Procasa Cusco">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/ABC-Prodein.webp" alt="ABC Prodein">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/glam.webp" alt="Glam Atelier">
            </div>
            <div class="partner-logo">
                <img src="<?php echo getBasePath(); ?>assets/images/partners/Monte-salvado.webp" alt="Monte Salvado">
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3><?php echo t('footer_about'); ?></h3>
            <p><?php echo t('footer_company'); ?></p>
            <p>
                <strong><?php echo t('contact_email'); ?>:</strong><br>
                <a href="mailto:<?php echo CONTACT_EMAIL; ?>" style="color: inherit;">
                    <?php echo CONTACT_EMAIL; ?>
                </a>
            </p>
            <p>
                <strong><?php echo t('contact_phone'); ?>:</strong><br>
                <a href="tel:+34666666666" style="color: inherit;">
                    +34 666 666 666
                </a>
            </p>
        </div>
        
        <div class="footer-section">
            <h3><?php echo t('nav_products'); ?></h3>
            <div class="footer-links">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=okna-pvc' : 'pages/products.php?category=okna-pvc'; ?>"><?php echo t('cat_pvc_windows'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=okna-drewniane' : 'pages/products.php?category=okna-drewniane'; ?>"><?php echo t('cat_wooden_windows'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=panele-grzewcze' : 'pages/products.php?category=panele-grzewcze'; ?>"><?php echo t('cat_heating_panels'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=folie-grzewcze' : 'pages/products.products.php?category=folie-grzewcze'; ?>"><?php echo t('cat_heating_films'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=profile-pvc' : 'pages/products.php?category=profile-pvc'; ?>"><?php echo t('cat_pvc_profiles'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=drzwi-wewnetrzne' : 'pages/products.php?category=drzwi-wewnetrzne'; ?>"><?php echo t('cat_interior_doors'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=drzwi-zewnetrzne' : 'pages/products.php?category=drzwi-zewnetrzne'; ?>"><?php echo t('cat_exterior_doors'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php?category=akcesoria' : 'pages/products.php?category=akcesoria'; ?>"><?php echo t('cat_accessories'); ?></a>
            </div>
        </div>
        
        <div class="footer-section">
            <h3><?php echo t('nav_calculator'); ?></h3>
            <div class="footer-links">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'calculator.php' : 'pages/calculator.php'; ?>"><?php echo t('calc_title'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'chatbot.php' : 'pages/chatbot.php'; ?>">💬 <?php echo t('chat_title'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php' : 'pages/products.php'; ?>"><?php echo t('nav_products'); ?></a>
            </div>
        </div>
        
        <div class="footer-section">
            <h3><?php echo t('footer_contact'); ?></h3>
            <div class="footer-links">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'contact.php' : 'pages/contact.php'; ?>"><?php echo t('contact_form'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'privacy.php' : 'pages/privacy.php'; ?>"><?php echo t('footer_privacy'); ?></a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'terms.php' : 'pages/terms.php'; ?>"><?php echo t('footer_terms'); ?></a>
            </div>
            
            <h3 style="margin-top: 2rem;"><?php echo t('language'); ?></h3>
            <div class="footer-links">
                <?php 
                global $AVAILABLE_LANGUAGES;
                $current_url = $_SERVER['REQUEST_URI'];
                $current_url = preg_replace('/(\?|&)lang=[a-z]{2}/', '', $current_url);
                $separator = (strpos($current_url, '?') !== false) ? '&' : '?';
                
                foreach ($AVAILABLE_LANGUAGES as $code => $name): 
                ?>
                    <a href="<?php echo $current_url . $separator . 'lang=' . $code; ?>"><?php echo $name; ?></a>
                <?php endforeach; ?>
            </div>
            
            <h3 style="margin-top: 2rem;"><?php echo t('footer_follow_us'); ?></h3>
            <div class="social-links">
                <a href="https://www.facebook.com/sersoltec" target="_blank" rel="noopener noreferrer" title="Facebook">f</a>
                <a href="https://www.instagram.com/sersoltec" target="_blank" rel="noopener noreferrer" title="Instagram">📷</a>
                <a href="https://www.linkedin.com/company/sersoltec" target="_blank" rel="noopener noreferrer" title="LinkedIn">in</a>
                <a href="https://wa.me/34666666666" target="_blank" rel="noopener noreferrer" title="WhatsApp">💬</a>
            </div>
    </div>
    
    <div style="border-top: 2px solid rgba(255, 255, 255, 0.35); padding: 2rem 0; margin-top: 2rem; text-align: center;">
        <p style="margin: 0 0 8px 0; font-weight: 500; color: rgba(255, 255, 255, 0.9);">&copy; 2025 <?php echo SITE_NAME; ?>. <?php echo t('footer_copyright'); ?></p>
        <p style="margin: 0; font-size: 0.85rem; color: rgba(255, 255, 255, 0.7);">Designed & Code by Bartosz Rychel</p>
    </div>
</footer>

<!-- Wishlist JavaScript -->
<script src="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../assets/js/wishlist.js' : 'assets/js/wishlist.js'; ?>"></script>
</body>
</html>