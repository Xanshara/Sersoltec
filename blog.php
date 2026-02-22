<?php
/**
 * SERSOLTEC - Blog
 * Fixed paths version
 */

// Okreœl œcie¿kê bazow¹ (czy jesteœmy w /pages/ czy g³ównym katalogu)
$isInPages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
$basePath = $isInPages ? '../' : '';

session_start();
require_once $basePath . 'config.php';
require_once $basePath . 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <h2>?? Blog</h2>
    <p>Zawartoœæ bloga bêdzie tutaj.</p>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>