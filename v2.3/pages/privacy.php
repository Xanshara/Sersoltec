<?php
/**
 * SERSOLTEC - POLITYKA PRYWATNOŒCI / PRIVACY POLICY
 * Strona zawieraj¹ca politykê prywatnoœci
 */

require_once '../config.php';

$page_title = t('nav_privacy'); // Translacja: Polityka prywatnoœci
$current_lang = getCurrentLanguage();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo t('privacy_meta_description'); ?>">
    <title><?php echo SITE_NAME; ?> - <?php echo t('nav_privacy'); ?></title>
    
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <link rel="icon" type="image/svg+xml" href="../assets/images/logo.svg">
</head>
<body>

<?php include '../includes/header.php'; ?>

<section class="page-hero-section">
    <div class="container text-center">
        <h1><?php echo t('nav_privacy'); ?></h1> 
        
        <p><?php echo t('privacy_hero_subtitle'); ?></p>
    </div>
</section>

<section class="content-section privacy-content">
    <div class="container">
        <div class="section-block">
            <h2><?php echo t('privacy_section_intro_title'); ?></h2>
            <p><?php echo t('privacy_section_intro_text_1'); ?></p>
            <p><?php echo t('privacy_section_intro_text_2'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('privacy_section_data_title'); ?></h2>
            <p><?php echo t('privacy_section_data_text'); ?></p>
            <ul>
                <li><strong><?php echo t('privacy_section_data_item_1_title'); ?>:</strong> <?php echo t('privacy_section_data_item_1_desc'); ?></li>
                <li><strong><?php echo t('privacy_section_data_item_2_title'); ?>:</strong> <?php echo t('privacy_section_data_item_2_desc'); ?></li>
                <li><strong><?php echo t('privacy_section_data_item_3_title'); ?>:</strong> <?php echo t('privacy_section_data_item_3_desc'); ?></li>
            </ul>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('privacy_section_use_title'); ?></h2>
            <p><?php echo t('privacy_section_use_text'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('privacy_section_rights_title'); ?></h2>
            <p><?php echo t('privacy_section_rights_text'); ?></p>
        </div>
        
        <div class="section-block mt-xl">
            <h2><?php echo t('privacy_section_changes_title'); ?></h2>
            <p><?php echo t('privacy_section_changes_text'); ?></p>
            <p class="text-muted small"><?php echo t('privacy_last_updated'); ?></p>
        </div>
        
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
</body>
</html>