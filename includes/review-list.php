<?php
/**
 * SERSOLTEC v2.4 - Review List Component with i18n
 * Sprint 2.3: Reviews System
 */

// Make sure $productId is set
if (!isset($productId) || !$productId) {
    echo '<div class="error">Product ID not set</div>';
    return;
}

// Get current language
$current_lang = getCurrentLanguage();

// Prepare translations for JavaScript
$jsTranslations = [
    'sort_newest' => t('reviews_sort_newest'),
    'sort_oldest' => t('reviews_sort_oldest'),
    'sort_highest' => t('reviews_sort_highest'),
    'sort_lowest' => t('reviews_sort_lowest'),
    'sort_helpful' => t('reviews_sort_helpful'),
    'loading' => t('reviews_loading'),
    'loading_stats' => t('reviews_loading_stats'),
    'error_loading' => t('reviews_error_loading'),
    'no_reviews' => t('reviews_no_reviews'),
    'no_reviews_message' => t('reviews_no_reviews_message'),
    'verified_purchase' => t('reviews_verified_purchase'),
    'helpful' => t('reviews_helpful'),
    'report' => t('reviews_report'),
    'submitting' => t('reviews_submitting'),
    'success_submitted' => t('reviews_success_submitted'),
    'error_login_required' => t('reviews_error_login_required'),
    'error_already_reviewed' => t('reviews_error_already_reviewed'),
    'error_rating_required' => t('reviews_error_rating_required'),
    'error_title_short' => t('reviews_error_title_short'),
    'error_title_long' => t('reviews_error_title_long'),
    'error_text_short' => t('reviews_error_text_short'),
    'error_text_long' => t('reviews_error_text_long'),
    'error_generic' => t('reviews_error_generic'),
    'total' => t('reviews_total'),
    'total_singular' => t('reviews_total_singular'),
    'average' => t('reviews_average'),
];
?>

<!-- Reviews Section -->
<section id="reviews-section">
    <div class="reviews-container">
        
        <!-- Section Title -->
        <h2 class="reviews-title"><?php echo t('reviews_title'); ?></h2>
        
        <!-- Statistics -->
        <div id="review-stats" class="review-stats">
            <div class="loading"><?php echo t('reviews_loading_stats'); ?></div>
        </div>
        
        <!-- Add Review Form -->
        <?php include __DIR__ . '/review-form.php'; ?>
        
        <!-- Sort & Filter -->
        <div class="reviews-controls">
            <label for="review-sort"><?php echo t('reviews_sort_by'); ?></label>
            <select id="review-sort" class="sort-select">
                <option value="newest"><?php echo t('reviews_sort_newest'); ?></option>
                <option value="oldest"><?php echo t('reviews_sort_oldest'); ?></option>
                <option value="highest"><?php echo t('reviews_sort_highest'); ?></option>
                <option value="lowest"><?php echo t('reviews_sort_lowest'); ?></option>
                <option value="helpful"><?php echo t('reviews_sort_helpful'); ?></option>
            </select>
        </div>
        
        <!-- Reviews List -->
        <div id="reviews-list" class="reviews-list">
            <div class="loading"><?php echo t('reviews_loading'); ?></div>
        </div>
        
        <!-- Pagination -->
        <div id="reviews-pagination" class="pagination"></div>
        
    </div>
</section>

<!-- Pass translations to JavaScript -->
<script>
    window.REVIEWS_I18N = <?php echo json_encode($jsTranslations, JSON_UNESCAPED_UNICODE); ?>;
    window.REVIEWS_LANG = '<?php echo $current_lang; ?>';
</script>

<!-- Initialize Reviews JavaScript -->
<script>
    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize reviews system with product ID
        if (typeof Reviews !== 'undefined') {
            Reviews.init(<?php echo (int)$productId; ?>);
        } else {
            console.error('Reviews module not loaded. Make sure reviews.js is included.');
        }
    });
</script>

<style>
/* Inline critical CSS - main styles should be in reviews.css */
#reviews-section {
    padding: 40px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #999;
}

.error {
    text-align: center;
    padding: 40px;
    color: #f44336;
    background: #ffebee;
    border-radius: 8px;
}
</style>