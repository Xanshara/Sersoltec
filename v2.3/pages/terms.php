<?php
/**
 * SERSOLTEC - WARUNKI U¯YWANIA / TERMS OF USE
 * Strona zawieraj¹ca warunki u¿ywania
 */

require_once '../config.php';

$page_title = t('nav_terms'); // Translacja: Warunki u¿ywania
$current_lang = getCurrentLanguage();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo t('terms_meta_description'); ?>">
    <title><?php echo SITE_NAME; ?> - <?php echo t('nav_terms'); ?></title>
    
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <link rel="icon" type="image/svg+xml" href="../assets/images/logo.svg">
</head>
<body>

<?php include '../includes/header.php'; ?>

<section class="page-hero-section">
    <div class="container text-center">
        <h1><?php echo t('nav_terms'); ?></h1> 
        
        <p><?php echo t('terms_hero_subtitle'); ?></p>
    </div>
</section>

<section class="content-section terms-content">
    <div class="container">
        <div class="section-block">
            <h2><?php echo t('terms_section_acceptance_title'); ?></h2>
            <p><?php echo t('terms_section_acceptance_text'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('terms_section_liability_title'); ?></h2>
            <p><?php echo t('terms_section_liability_text_1'); ?></p>
            <p><?php echo t('terms_section_liability_text_2'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('terms_section_ip_title'); ?></h2>
            <p><?php echo t('terms_section_ip_text'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('terms_section_law_title'); ?></h2>
            <p><?php echo t('terms_section_law_text'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('terms_section_miscellaneous_title'); ?></h2>
            <p><?php echo t('terms_section_miscellaneous_text'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('terms_section_changes_title'); ?></h2>
            <p><?php echo t('terms_section_changes_text'); ?></p>
            <p class="text-muted small"><?php echo t('terms_last_updated'); ?></p>
        </div>
        
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
</body>
</html>