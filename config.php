<?php
/**
 * SERSOLTEC - Konfiguracja Główna
 * WAŻNE: Ten plik NIE może mieć żadnego output przed pierwszą linią kodu!
 */

// =============================================================================
// KRYTYCZNE: Żadnych spacji, BOM, ani echo przed tą linią!
// =============================================================================

// Start session TYLKO RAZ, na samym początku
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ===== BAZA DANYCH =====
define('DB_HOST', 'localhost');
define('DB_USER', 'sersoltec');
define('DB_PASS', 'm1vg!M2Zj*3BY.QX');
define('DB_NAME', 'sersoltec_db');

// Backward compatibility dla starego kodu
define('DB_PASSWORD', DB_PASS);

// ===== EMAIL =====
define('CONTACT_EMAIL', 'info@sersoltec.eu');
define('ADMIN_EMAIL', 'admin@sersoltec.eu');
define('SMTP_FROM', 'noreply@sersoltec.eu');
define('SITE_EMAIL', CONTACT_EMAIL);

// ===== STRONA =====
define('SITE_NAME', 'Sersoltec');
define('SITE_URL', 'https://sersoltec.eu');
define('SITE_DESCRIPTION', 'Nowoczesne rozwiązania okien, drzwi i systemów grzewczych');

// ===== WALUTY =====
define('DEFAULT_CURRENCY', 'EUR');

if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '€');
}

/**
 * Bezpieczny symbol waluty
 */
if (!function_exists('safe_currency_symbol')) {
    function safe_currency_symbol(): string {
        if (defined('CURRENCY_SYMBOL') && is_string(constant('CURRENCY_SYMBOL'))) {
            return constant('CURRENCY_SYMBOL');
        }
        return '€';
    }
}

// ===== JĘZYKI =====
$AVAILABLE_LANGUAGES = [
    'pl' => 'Polski',
    'en' => 'English',
    'es' => 'Español'
];

// ===== FUNKCJE POMOCNICZE (Backward Compatibility) =====

function setLanguage() {
    global $AVAILABLE_LANGUAGES;
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        if (!isset($AVAILABLE_LANGUAGES[$lang])) {
            $lang = 'pl';
        }
        $_SESSION['language'] = $lang;
    } else if (isset($_SESSION['language'])) {
        $lang = $_SESSION['language'];
    } else {
        $lang = 'pl';
        $_SESSION['language'] = $lang;
    }
    return $lang;
}

function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'pl';
}

function t($key, $lang = null) {
    global $translations;
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    return $translations[$lang][$key] ?? $key;
}

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 2, ',', ' ') . ' ' . safe_currency_symbol();
    }
}

if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit;
    }
}

// ===== TŁUMACZENIA =====
if (file_exists(__DIR__ . '/includes/translations.php')) {
    require_once __DIR__ . '/includes/translations.php';
}

// Ustaw język
setLanguage();

// ===== DEBUG MODE =====
define('DEBUG', true);

// ===== LOAD LIBRARY v2.0 =====
require_once __DIR__ . '/lib/init.php';

// ===== BACKWARD COMPATIBILITY =====
// Dla starego kodu który używa $pdo bezpośrednio
$pdo = db()->getPdo();