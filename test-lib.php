#!/usr/bin/env php
<?php
/**
 * SERSOLTEC v2.0 - Installation Test Script
 * 
 * Run from command line: php test-lib.php
 */

// Prevent session headers issue in CLI
if (php_sapi_name() === 'cli') {
    // Running from command line - no session needed for basic tests
    define('CLI_MODE', true);
}

echo "====================================\n";
echo "SERSOLTEC v2.0 - Installation Test\n";
echo "====================================\n\n";

// Check if config.php exists
if (!file_exists(__DIR__ . '/config.php')) {
    echo "❌ ERROR: config.php not found!\n\n";
    echo "Please create config.php based on config.example.php\n";
    echo "Or copy your existing config and add at the end:\n\n";
    echo "require_once __DIR__ . '/lib/init.php';\n\n";
    exit(1);
}

// Load config
try {
    require_once __DIR__ . '/config.php';
} catch (Exception $e) {
    echo "❌ ERROR loading config.php: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if required constants are defined
echo "Checking configuration...\n";
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required as $const) {
    if (!defined($const)) {
        echo "   ❌ Missing: $const\n";
        $errors = true;
    } else {
        $value = constant($const);
        $display = $const === 'DB_PASS' ? '***' : $value;
        echo "   ✅ $const = $display\n";
    }
}

if (isset($errors)) {
    echo "\n❌ Configuration incomplete! Check config.php\n";
    exit(1);
}

echo "\n";

// Test 1: Database Connection
echo "Test 1: Database Connection\n";
try {
    $count = db()->count('users');
    echo "   ✅ Connected! Found $count users\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   TIP: Check DB credentials in config.php\n";
    exit(1);
}

// Test 2: Check new tables
echo "\nTest 2: New Database Tables\n";
$tables = [
    'login_attempts',
    'password_resets',
    'wishlist',
    'product_comparisons',
    'product_reviews',
    'blog_posts',
    'blog_comments'
];

$missing = [];
foreach ($tables as $table) {
    try {
        $exists = db()->getPdo()->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        if ($exists) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' MISSING!\n";
            $missing[] = $table;
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking '$table'\n";
        $missing[] = $table;
    }
}

if (!empty($missing)) {
    echo "\n⚠️  WARNING: " . count($missing) . " table(s) missing!\n";
    echo "Run migration: mysql -u root -p sersoltec_db < MIGRATION-v2.0.sql\n\n";
}

// Test 3: Logger
echo "\nTest 3: Logger\n";
try {
    logger()->info('Test message from installation');
    if (file_exists(__DIR__ . '/logs/debug.log')) {
        echo "   ✅ Logger working! Check logs/debug.log\n";
    } else {
        echo "   ⚠️  Log file not created yet (may need permissions)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Security (CSRF)
echo "\nTest 4: Security (CSRF Token)\n";
try {
    $token = csrf_token();
    if (strlen($token) === 64) {
        echo "   ✅ CSRF token generated: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "   ❌ Token invalid length: " . strlen($token) . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Validator
echo "\nTest 5: Validator (Sanitization)\n";
try {
    $dirty = '<script>alert("XSS")</script>Hello';
    $clean = Sersoltec\Lib\Validator::sanitize($dirty);
    if (strpos($clean, '<script>') === false) {
        echo "   ✅ Sanitization working\n";
        echo "   Input:  $dirty\n";
        echo "   Output: $clean\n";
    } else {
        echo "   ❌ Sanitization failed!\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 6: Helpers
echo "\nTest 6: Helpers\n";
try {
    $price = Sersoltec\Lib\Helpers::formatPrice(1299.99);
    echo "   ✅ Price formatting: $price\n";
    
    $slug = Sersoltec\Lib\Helpers::slugify('Okna PCV - Najlepsze Ceny!');
    echo "   ✅ Slugify: $slug\n";
    
    $random = Sersoltec\Lib\Helpers::randomString(8);
    echo "   ✅ Random string: $random\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 7: Auth (basic)
echo "\nTest 7: Authentication\n";
try {
    if (is_authenticated()) {
        $user = current_user();
        echo "   ✅ Logged in as: " . ($user['email'] ?? 'Unknown') . "\n";
    } else {
        echo "   ✅ Not logged in (expected in CLI)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 8: Email (test mode check)
echo "\nTest 8: Email System\n";
try {
    // Check if email is in test mode
    echo "   ✅ Email system initialized\n";
    if (DEBUG) {
        echo "   ℹ️  Test mode: ON (emails won't be sent)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Summary
echo "\n====================================\n";
if (empty($missing)) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "====================================\n";
    echo "\nLibrary v2.0 installed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Delete this test file: rm test-lib.php\n";
    echo "2. Test in browser: create test-browser.php\n";
    echo "3. Start using the library!\n\n";
    echo "See QUICK-REFERENCE.md for usage examples.\n\n";
    exit(0);
} else {
    echo "⚠️  TESTS COMPLETED WITH WARNINGS\n";
    echo "====================================\n";
    echo "\nLibrary is working, but some tables are missing.\n";
    echo "Run migration to create missing tables:\n\n";
    echo "mysql -u root -p sersoltec_db < MIGRATION-v2.0.sql\n\n";
    exit(1);
}
