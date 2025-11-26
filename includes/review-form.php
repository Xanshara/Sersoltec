<?php
/**
 * SERSOLTEC v2.4 - Review Form Component
 * Sprint 2.3: Reviews System
 * 
 * Include this in product-detail.php to display review submission form
 * Usage: include 'includes/review-form.php';
 */

// Check if user is logged in (check multiple possible session variables)
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['id']) || (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);
$userName = '';
if ($isLoggedIn) {
    $userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'User';
}
?>

<div class="review-form-container">
    <h3><?php echo $isLoggedIn ? 'Dodaj swoją opinię' : 'Zaloguj się, aby dodać opinię'; ?></h3>
    
    <?php if (!$isLoggedIn): ?>
        <!-- Not logged in - show prompt -->
        <div class="auth-required">
            <p>Aby dodać opinię, musisz być zalogowany.</p>
            <a href="/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-login">
                Zaloguj się
            </a>
            lub
            <a href="/register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-register">
                Zarejestruj się
            </a>
        </div>
        
    <?php else: ?>
        <!-- Logged in - show form -->
        <form id="review-form" method="post">
            
            <!-- Rating -->
            <div class="form-group">
                <label for="rating-input">Ocena *</label>
                <input type="hidden" id="rating-input" name="rating" required>
                <div id="rating-stars"></div>
                <small>Kliknij na gwiazdki, aby wybrać ocenę (1-5)</small>
            </div>
            
            <!-- Title -->
            <div class="form-group">
                <label for="review-title">Tytuł opinii *</label>
                <input 
                    type="text" 
                    id="review-title" 
                    name="title" 
                    placeholder="Krótko podsumuj swoją opinię"
                    maxlength="255"
                    required
                >
                <small>Minimum 3 znaki, maksimum 255 znaków</small>
            </div>
            
            <!-- Review text -->
            <div class="form-group">
                <label for="review-text">Twoja opinia *</label>
                <textarea 
                    id="review-text" 
                    name="review_text" 
                    placeholder="Podziel się swoim doświadczeniem z produktem. Co Ci się podobało? Co mogłoby być lepsze?"
                    maxlength="5000"
                    required
                ></textarea>
                <small>Minimum 10 znaków, maksimum 5000 znaków</small>
            </div>
            
            <!-- Submit button -->
            <div class="form-actions">
                <button type="submit" class="btn-submit-review">
                    Wyślij opinię
                </button>
            </div>
            
            <!-- Message container -->
            <div id="review-message"></div>
            
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 4px; font-size: 13px;">
            <strong>📝 Zasady dodawania opinii:</strong>
            <ul style="margin: 10px 0 0 20px; line-height: 1.6;">
                <li>Opinie są moderowane przed publikacją</li>
                <li>Możesz dodać tylko jedną opinię do produktu</li>
                <li>Używaj kulturalnego języka</li>
                <li>Opisz swoje rzeczywiste doświadczenie z produktem</li>
                <li>Nie umieszczaj danych osobowych ani kontaktowych</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
/* Additional inline styles for auth buttons */
.auth-required {
    background: #fff3cd;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ffc107;
}

.auth-required p {
    margin: 0 0 15px 0;
    color: #856404;
}

.auth-required a {
    display: inline-block;
    padding: 10px 20px;
    background: #ff9800;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    margin-right: 10px;
    transition: background 0.3s ease;
}

.auth-required a:hover {
    background: #f57c00;
}

.auth-required a.btn-register {
    background: #4caf50;
}

.auth-required a.btn-register:hover {
    background: #45a049;
}
</style>
