<?php
/**
 * SERSOLTEC v2.4 - Review List Component
 * Sprint 2.3: Reviews System
 * 
 * Include this in product-detail.php to display reviews
 * Usage: 
 *   $productId = 123; // Set product ID
 *   include 'includes/review-list.php';
 */

// Make sure $productId is set
if (!isset($productId) || !$productId) {
    echo '<div class="error">Product ID not set</div>';
    return;
}
?>

<!-- Reviews Section -->
<section id="reviews-section">
    
    <!-- Section Header -->
    <div class="reviews-header">
        <h2>Opinie klientów</h2>
    </div>
    
    <!-- Review Statistics -->
    <div id="review-stats">
        <div class="loading">Ładowanie statystyk...</div>
    </div>
    
    <!-- Review Form -->
    <?php include __DIR__ . '/review-form.php'; ?>
    
    <!-- Sort Controls -->
    <div class="reviews-controls">
        <div>
            <label for="review-sort">Sortuj według:</label>
            <select id="review-sort">
                <option value="newest">Najnowsze</option>
                <option value="oldest">Najstarsze</option>
                <option value="highest">Najwyższe oceny</option>
                <option value="lowest">Najniższe oceny</option>
                <option value="helpful">Najbardziej pomocne</option>
            </select>
        </div>
    </div>
    
    <!-- Reviews List -->
    <div id="reviews-list">
        <div class="loading">Ładowanie opinii...</div>
    </div>
    
</section>

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
