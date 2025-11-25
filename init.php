<?php
/**
 * SERSOLTEC - Library Initialization
 * Initialize all lib classes and provide backward compatibility
 * 
 * Usage: require_once 'lib/init.php';
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

// Load autoloader
require_once __DIR__ . '/autoload.php';

use Sersoltec\Lib\Database;
use Sersoltec\Lib\Auth;
use Sersoltec\Lib\Validator;
use Sersoltec\Lib\Logger;
use Sersoltec\Lib\Security;
use Sersoltec\Lib\Email;
use Sersoltec\Lib\Helpers;

// Check if database constants are defined
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die("ERROR: Database constants not defined!\n\nMake sure config.php defines:\n- DB_HOST\n- DB_NAME\n- DB_USER\n- DB_PASS\n\nBefore loading lib/init.php\n");
}

// Initialize Database singleton with config from config.php
$db = Database::getInstance([
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

// Keep backward compatibility - existing code uses $pdo
// But now it goes through our Database class
$pdo = $db->getPdo();

// Initialize Logger
$logger = new Logger(__DIR__ . '/../logs');

// Enable query logging in development
if (defined('DEBUG') && DEBUG === true) {
    $db->enableQueryLog(true);
    $logger->setMinLevel(Logger::LEVEL_DEBUG);
}

// Initialize Security
$security = new Security($logger);

// Initialize Auth
$auth = new Auth();

// Initialize Email
$email = new Email(
    SITE_EMAIL ?? 'noreply@sersoltec.eu',
    SITE_NAME ?? 'Sersoltec',
    $logger
);

// Set email templates directory
$email->setTemplatesDir(__DIR__ . '/../email-templates');

// Enable test mode in development
if (defined('DEBUG') && DEBUG === true) {
    $email->setTestMode(true);
}

// Initialize Validator
$validator = new Validator();

/**
 * =================================================================
 * BACKWARD COMPATIBILITY FUNCTIONS
 * These functions maintain compatibility with existing code
 * =================================================================
 */

/**
 * Sanitize input (backward compatible)
 * 
 * @param string $input Input string
 * @return string
 */
if (!function_exists('sanitize')) {
    function sanitize(string $input): string {
        return Validator::sanitize($input);
    }
}

/**
 * Redirect helper (backward compatible)
 * 
 * @param string $url URL to redirect to
 * @return void
 */
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        Helpers::redirect($url);
    }
}

/**
 * Format price (backward compatible)
 * 
 * @param float $price Price value
 * @return string
 */
if (!function_exists('format_price')) {
    function format_price(float $price): string {
        return Helpers::formatPrice($price);
    }
}

/**
 * =================================================================
 * GLOBAL HELPER FUNCTIONS (NEW)
 * =================================================================
 */

/**
 * Get database instance
 * 
 * @return Database
 */
function db(): Database {
    return Database::getInstance();
}

/**
 * Get auth instance
 * 
 * @return Auth
 */
function auth(): Auth {
    global $auth;
    return $auth;
}

/**
 * Get logger instance
 * 
 * @return Logger
 */
function logger(): Logger {
    global $logger;
    return $logger;
}

/**
 * Get security instance
 * 
 * @return Security
 */
function security(): Security {
    global $security;
    return $security;
}

/**
 * Get email instance
 * 
 * @return Email
 */
function email(): Email {
    global $email;
    return $email;
}

/**
 * Validate data
 * 
 * @param array $data Data to validate
 * @param array $rules Validation rules
 * @return Validator
 */
function validate(array $data, array $rules): Validator {
    $validator = new Validator();
    $validator->validate($data, $rules);
    return $validator;
}

/**
 * Get CSRF token
 * 
 * @return string
 */
function csrf_token(): string {
    global $security;
    return $security->getCsrfToken();
}

/**
 * Get CSRF field (hidden input)
 * 
 * @return string
 */
function csrf_field(): string {
    global $security;
    return $security->csrfField();
}

/**
 * Check if user is authenticated
 * 
 * @return bool
 */
function is_authenticated(): bool {
    global $auth;
    return $auth->check();
}

/**
 * Get current user
 * 
 * @return array|null
 */
function current_user(): ?array {
    global $auth;
    return $auth->user();
}

/**
 * Log message
 * 
 * @param string $message Log message
 * @param string $level Log level (debug, info, warning, error, critical)
 * @param array $context Additional context
 * @return void
 */
function log_message(string $message, string $level = 'info', array $context = []): void {
    global $logger;
    
    switch ($level) {
        case 'debug':
            $logger->debug($message, $context);
            break;
        case 'warning':
            $logger->warning($message, $context);
            break;
        case 'error':
            $logger->error($message, $context);
            break;
        case 'critical':
            $logger->critical($message, $context);
            break;
        default:
            $logger->info($message, $context);
    }
}

/**
 * Dump and die (debug helper)
 * 
 * @param mixed ...$vars Variables to dump
 * @return void
 */
function dd(...$vars): void {
    Helpers::dd(...$vars);
}

// Log initialization
if (defined('DEBUG') && DEBUG === true) {
    $logger->debug('Sersoltec library initialized', [
        'version' => '2.0.0',
        'user_id' => $_SESSION['user_id'] ?? 'guest'
    ]);
}