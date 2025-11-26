<?php
/**
 * SERSOLTEC v2.4 - Review Form with i18n
 */

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['id']) || (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);
?>

<div class="review-form-container">
    <?php if ($isLoggedIn): ?>
        <h3><?php echo t('reviews_add_yours'); ?></h3>
        <form id="review-form" class="review-form">
            <div class="form-group">
                <label for="rating"><?php echo t('reviews_rating_required'); ?></label>
                <div id="rating-stars"></div>
                <input type="hidden" id="rating-input" name="rating" required>
                <small class="form-help"><?php echo t('reviews_rating_click'); ?></small>
            </div>
            
            <div class="form-group">
                <label for="review-title"><?php echo t('reviews_title_label'); ?></label>
                <input 
                    type="text" 
                    id="review-title" 
                    name="title" 
                    required 
                    minlength="3" 
                    maxlength="255"
                    placeholder="<?php echo htmlspecialchars(t('reviews_title_placeholder')); ?>"
                >
                <small class="form-help"><?php echo t('reviews_title_help'); ?></small>
            </div>
            
            <div class="form-group">
                <label for="review-text"><?php echo t('reviews_text_label'); ?></label>
                <textarea 
                    id="review-text" 
                    name="review_text" 
                    required 
                    minlength="10" 
                    maxlength="5000"
                    rows="5"
                    placeholder="<?php echo htmlspecialchars(t('reviews_text_placeholder')); ?>"
                ></textarea>
                <small class="form-help"><?php echo t('reviews_text_help'); ?></small>
            </div>
            
            <button type="submit" class="btn btn-primary" id="submit-review">
                <?php echo t('reviews_submit'); ?>
            </button>
        </form>
        
        <div class="review-guidelines">
            <strong><?php echo t('reviews_rules_title'); ?></strong>
            <ul>
                <li><?php echo t('reviews_rules_moderation'); ?></li>
                <li><?php echo t('reviews_rules_one_per_product'); ?></li>
                <li><?php echo t('reviews_rules_language'); ?></li>
                <li><?php echo t('reviews_rules_experience'); ?></li>
                <li><?php echo t('reviews_rules_no_personal'); ?></li>
            </ul>
        </div>
    <?php else: ?>
        <p class="login-required">
            <?php echo t('reviews_error_login_required'); ?>
            <a href="/login.php"><?php echo t('nav_login'); ?></a>
        </p>
    <?php endif; ?>
</div>