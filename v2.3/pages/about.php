<?php
/**
 * SERSOLTEC - O NAS / ABOUT US
 * Strona informacyjna o firmie
 */

require_once '../config.php';

$page_title = t('nav_about');
$current_lang = getCurrentLanguage();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo t('about_innovation_text'); ?>">
    <title><?php echo SITE_NAME; ?> - <?php echo t('nav_about'); ?></title>
    
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <link rel="icon" type="image/svg+xml" href="../assets/images/logo.svg">
</head>
<body>

<?php include '../includes/header.php'; ?>

<section class="page-hero-section">
    <div class="container text-center">
        <h1><?php echo t('nav_about'); ?></h1> 
        
        <p><?php echo t('hero_title'); ?></p>
    </div>
</section>

<section class="content-section about-intro">
    <div class="container">
        <div class="section-header">
            <h2><?php echo t('about_innovation_title'); ?></h2>
        </div>
        
        <p><?php echo t('about_innovation_text'); ?></p>
        <p class="mt-lg"><strong><?php echo t('about_mission_text'); ?></strong></p>
    </div>
</section>

<section class="content-section ceo-section" style="background-color: var(--color-light-gray);">
    <div class="container">
        
        <div class="ceo-card">
            <div class="ceo-image">
                <img src="../assets/images/photo.jpg" 
                     alt="<?php echo t('about_ceo_name'); ?>" 
                     id="ceo-photo"> 
            </div>
            
            <div class="ceo-details">
                <h3><?php echo t('about_ceo_name'); ?></h3>
                <p class="text-muted" style="font-weight: 600;"><?php echo t('about_ceo_title'); ?></p>
                
                <p><?php echo t('about_ceo_text'); ?></p>
            </div>
        </div>
        
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
</body>
</html>