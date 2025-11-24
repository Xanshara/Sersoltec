<?php
// ===== GALERIA PROJEKTÓW =====

// Upewnij się, że dołączamy config, aby załadować funkcje i ustawienia
require_once '../config.php';

// Upewnij się, że plik z tłumaczeniami został załadowany
require_once '../includes/translations.php'; 

// Ustawienie nagłówka i początku HTML
$current_lang = getCurrentLanguage();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo t('gallery_header_desc'); ?>">
    <title><?php echo SITE_NAME; ?> - <?php echo t('nav_gallery'); ?></title>
    
    <!-- Dołącz style - pamiętaj o zmianie ścieżek! -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main>

   	<section class="page-header">
        <div class="container text-center">
            <h1><?php echo t('gallery_header_title'); ?></h1>
            <p class="text-muted" style="max-width: 800px; margin: 0 auto;">
                <?php echo t('gallery_header_desc'); ?>
            </p>
        </div>
    </section>

    <!-- Sekcja siatki galerii -->
    <section class="content-section pt-0">
        <div class="container">
            <div class="gallery-grid">
                <?php 
                // Generowanie 18 elementów galerii
                // Pamiętaj, że w folderze assets/images/gallery/ musisz mieć pliki image_1.jpg i image_1.webp do image_18.
                for ($i = 1; $i <= 18; $i++): 
                ?>
                    <!-- Element galerii z linkiem do samego siebie (bez Lightboxa) -->
                    <a href="#" class="gallery-item">
                        <picture>
                            <!-- Priorytetowy format WebP -->
                            <source srcset="../assets/images/gallery/image_<?php echo $i; ?>.webp" type="image/webp">
                            <!-- Awaryjny format JPG -->
                            <img src="../assets/images/gallery/image_<?php echo $i; ?>.jpg" 
                                 alt="<?php echo t('gallery_image_alt') . ' ' . $i; ?>" 
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='https://placehold.co/400x300/1a4d2e/ffffff?text=<?php echo t('nav_gallery'); ?>';"
                            >
                        </picture>
                        <div class="overlay">
                            <span class="caption"><?php echo t('gallery_caption_title') . ' #' . $i; ?></span>
                        </div>
                    </a>
                <?php endfor; ?>
            </div>
           
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<!-- Dołączamy główny skrypt, który teraz nie zawiera logiki Lightboxa -->
<script src="../assets/js/main.js"></script>

</body>
</html>