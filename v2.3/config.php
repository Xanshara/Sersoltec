<?php
/**
 * SERSOLTEC - Konfiguracja Główna
 * Zmień te wartości na swoje dane
 */

// ===== BAZA DANYCH =====
define('DB_HOST', 'localhost');
define('DB_USER', 'sersoltec');
define('DB_PASSWORD', 'm1vg!M2Zj*3BY.QX');
define('DB_NAME', 'sersoltec_db');

// ===== EMAIL =====
define('CONTACT_EMAIL', 'info@sersoltec.eu');
define('ADMIN_EMAIL', 'admin@sersoltec.eu');
define('SMTP_FROM', 'noreply@sersoltec.eu');

// ===== STRONA =====
define('SITE_NAME', 'Sersoltec');
define('SITE_URL', 'https://sersoltec.eu');
define('SITE_DESCRIPTION', 'Nowoczesne rozwiązania okien, drzwi i systemów grzewczych');

// ===== WALUTY =====
define('DEFAULT_CURRENCY', 'EUR');

// Nie nadpiszemy CURRENCY_SYMBOL, jeśli istnieje w PHP (u Ciebie istnieje i ma wartość 262145),
// dlatego używamy funkcji fallback zamiast tej stałej, aby wymusić poprawny symbol.
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '€');
}

/**
 * Bezpieczny symbol waluty — używany W ZAMIAN za CURRENCY_SYMBOL,
 * bo na serwerze istnieje konstanta o tej nazwie z wartością int(262145),
 * której nie da się nadpisać.
 */
if (!function_exists('safe_currency_symbol')) {
    function safe_currency_symbol(): string {
        if (defined('CURRENCY_SYMBOL') && is_string(constant('CURRENCY_SYMBOL'))) {
            return constant('CURRENCY_SYMBOL');
        }
        return '€'; // fallback wymuszający euro
    }
}

// ===== JĘZYKI =====
$AVAILABLE_LANGUAGES = [
    'pl' => 'Polski',
    'en' => 'English',
    'es' => 'Español'
];

// ===== POŁĄCZENIE Z BAZĄ =====
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Błąd połączenia z bazą danych: ' . $e->getMessage());
}

// ===== FUNKCJE POMOCNICZE =====

/**
 * Ustaw wybrany język
 */
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

/**
 * Pobierz aktualny język
 */
function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'pl';
}

/**
 * Tłumaczenie stringów
 */
function t($key, $lang = null) {
    global $translations;

    if ($lang === null) {
        $lang = getCurrentLanguage();
    }

    return $translations[$lang][$key] ?? $key;
}

/**
 * Formatuj cenę — teraz bezpiecznie z wymuszonym symbolem euro
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' ' . safe_currency_symbol();
}

/**
 * Sanityzuj wejście
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sprawdź czy email jest poprawny
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Redirect na URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Załaduj tłumaczenia
require_once 'includes/translations.php';

// Startuj sesję
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setLanguage();
?>
