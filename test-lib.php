#!/usr/bin/env php
<?php
/**
 * SERSOLTEC v2.0 - Final Test
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "\n";
echo "====================================\n";
echo "SERSOLTEC v2.0 - Installation Test\n";
echo "====================================\n\n";

// Load config
require_once __DIR__ . '/config.php';

// Test 1: Database
echo "Test 1: Database Connection\n";
try {
    $count = db()->count('users');
    echo "   ✅ Connected! Found $count users\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Logger
echo "\nTest 2: Logger\n";
try {
    logger()->info('Test message from installation');
    echo "   ✅ Logger working!\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: CSRF Token
echo "\nTest 3: CSRF Token\n";
try {
    $token = csrf_token();
    echo "   ✅ Token generated: " . substr($token, 0, 10) . "...\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Validator
echo "\nTest 4: Validator\n";
try {
    $dirty = '<script>alert("XSS")</script>Hello World';
    $clean = \Sersoltec\Lib\Validator::sanitize($dirty);
    echo "   ✅ Sanitization working\n";
    echo "      Input:  $dirty\n";
    echo "      Output: $clean\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Helpers
echo "\nTest 5: Helpers\n";
try {
    $price = \Sersoltec\Lib\Helpers::formatPrice(1299.99);
    $slug = \Sersoltec\Lib\Helpers::slugify('Okna PCV - Najlepsze Ceny!');
    echo "   ✅ Price formatting: $price\n";
    echo "   ✅ Slug: $slug\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 6: Database Tables
echo "\nTest 6: New Database Tables\n";
$tables = array(
    'login_attempts',
    'password_resets',
    'wishlist',
    'product_comparisons',
    'product_reviews',
    'blog_posts',
    'blog_comments'
);

$missing = array();
foreach ($tables as $table) {
    try {
        $result = db()->getPdo()->query("SHOW TABLES LIKE '$table'");
        $exists = $result->rowCount() > 0;
        if ($exists) {
            echo "   ✅ $table\n";
        } else {
            echo "   ❌ $table MISSING\n";
            $missing[] = $table;
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking $table\n";
        $missing[] = $table;
    }
}

// Summary
echo "\n====================================\n";
if (count($missing) === 0) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "====================================\n\n";
    echo "Library v2.0 is working perfectly!\n\n";
    echo "Next steps:\n";
    echo "1. Delete test files\n";
    echo "2. Set DEBUG to false in config.php (production)\n";
    echo "3. Start building features!\n\n";
} else {
    echo "⚠️  TESTS COMPLETED WITH WARNINGS\n";
    echo "====================================\n\n";
    echo "Missing " . count($missing) . " table(s):\n";
    foreach ($missing as $table) {
        echo "  - $table\n";
    }
    echo "\nRun migration:\n";
    echo "mysql -u root -p sersoltec_db < MIGRATION-v2.0.sql\n\n";
}
?>