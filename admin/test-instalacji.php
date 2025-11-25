<?php
/**
 * TEST INSTALACJI PANELU ADMINA
 * Sprawdza czy wszystko jest poprawnie skonfigurowane
 */

echo "<h1>🔧 Test Instalacji Panelu Admina</h1>";
echo "<hr>";

// Test 1: Połączenie config.php
echo "<h2>Test 1: Plik config.php</h2>";
if (file_exists('../config.php')) {
    require_once '../config.php';
    echo "✅ Plik config.php znaleziony<br>";
} elseif (file_exists('config.php')) {
    require_once 'config.php';
    echo "✅ Plik config.php znaleziony<br>";
} else {
    echo "❌ BŁĄD: Nie można znaleźć pliku config.php<br>";
    die();
}

// Test 2: Połączenie z bazą
echo "<h2>Test 2: Połączenie z bazą danych</h2>";
try {
    $result = $pdo->query("SELECT DATABASE() as db_name");
    $db = $result->fetch();
    echo "✅ Połączono z bazą: <strong>" . $db['db_name'] . "</strong><br>";
} catch (Exception $e) {
    echo "❌ BŁĄD połączenia: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Sprawdź czy tabela admin_users istnieje
echo "<h2>Test 3: Tabela admin_users</h2>";
try {
    $result = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->rowCount() > 0) {
        echo "✅ Tabela admin_users istnieje<br>";
    } else {
        echo "❌ BŁĄD: Tabela admin_users nie istnieje<br>";
        echo "📝 Uruchom: mysql -u root -p " . DB_NAME . " < ADMIN-MIGRATION.sql<br>";
        die();
    }
} catch (Exception $e) {
    echo "❌ BŁĄD: " . $e->getMessage() . "<br>";
    die();
}

// Test 4: Sprawdź użytkownika admin
echo "<h2>Test 4: Użytkownik admin</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM admin_users WHERE username = 'admin'");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Użytkownik admin istnieje<br>";
        echo "📧 Email: <strong>" . htmlspecialchars($admin['email']) . "</strong><br>";
        echo "👤 Rola: <strong>" . htmlspecialchars($admin['role']) . "</strong><br>";
        echo "🟢 Status: <strong>" . ($admin['active'] ? 'Aktywny' : 'Nieaktywny') . "</strong><br>";
        
        if (!$admin['active']) {
            echo "⚠️ UWAGA: Użytkownik jest nieaktywny!<br>";
        }
    } else {
        echo "❌ BŁĄD: Użytkownik admin nie istnieje<br>";
        echo "📝 Dodaj użytkownika ręcznie:<br>";
        echo "<pre>";
        echo "INSERT INTO admin_users (username, email, password, role, active) \n";
        echo "VALUES ('admin', 'admin@sersoltec.eu', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1);";
        echo "</pre>";
        die();
    }
} catch (Exception $e) {
    echo "❌ BŁĄD: " . $e->getMessage() . "<br>";
    die();
}

// Test 5: Test hasła
echo "<h2>Test 5: Weryfikacja hasła</h2>";
$default_password = 'admin123';
$stored_hash = $admin['password'];

if (password_verify($default_password, $stored_hash)) {
    echo "✅ Hasło '<strong>admin123</strong>' jest poprawne<br>";
    echo "⚠️ <strong>ZMIEŃ TO HASŁO natychmiast po zalogowaniu!</strong><br>";
} else {
    echo "❌ Domyślne hasło nie działa<br>";
    echo "📝 Hash w bazie: <code>" . substr($stored_hash, 0, 30) . "...</code><br>";
}

// Test 6: Sprawdź inne tabele
echo "<h2>Test 6: Inne tabele</h2>";
$required_tables = ['products', 'categories', 'orders', 'inquiries', 'window_calculations', 'settings'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($result->rowCount() > 0) {
        echo "✅ $table<br>";
    } else {
        echo "❌ $table - BRAK<br>";
        $missing_tables[] = $table;
    }
}

if (count($missing_tables) > 0) {
    echo "<br>⚠️ Brakujące tabele: " . implode(', ', $missing_tables) . "<br>";
    echo "📝 Uruchom: mysql -u root -p " . DB_NAME . " < SETUP.sql<br>";
}

// Test 7: Sprawdź PHP extensions
echo "<h2>Test 7: PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext<br>";
    } else {
        echo "❌ $ext - BRAK<br>";
        $missing_extensions[] = $ext;
    }
}

if (count($missing_extensions) > 0) {
    echo "<br>⚠️ Brakujące rozszerzenia: " . implode(', ', $missing_extensions) . "<br>";
}

// Test 8: Sprawdź wersję PHP
echo "<h2>Test 8: Wersja PHP</h2>";
$php_version = phpversion();
echo "📌 Wersja PHP: <strong>$php_version</strong><br>";

if (version_compare($php_version, '7.4.0', '>=')) {
    echo "✅ Wersja PHP jest wystarczająca (7.4+)<br>";
} else {
    echo "❌ PHP jest za stare! Wymagane: 7.4+<br>";
}

// Test 9: Sprawdź uprawnienia do zapisu
echo "<h2>Test 9: Uprawnienia katalogów</h2>";
$upload_dir = '../assets/images/products';
if (is_writable($upload_dir)) {
    echo "✅ Katalog uploads jest zapisywalny<br>";
} else {
    echo "⚠️ Katalog uploads może nie być zapisywalny<br>";
    echo "📝 Uruchom: chmod -R 755 ../assets<br>";
}

// Podsumowanie
echo "<hr>";
echo "<h2>📊 Podsumowanie</h2>";

if (
    file_exists('../config.php') || file_exists('config.php') &&
    $pdo &&
    $admin &&
    $admin['active'] &&
    password_verify($default_password, $stored_hash) &&
    count($missing_tables) == 0
) {
    echo "<div style='background: #e8f5e9; padding: 20px; border-left: 5px solid #4caf50; border-radius: 5px;'>";
    echo "<h3 style='color: #2e7d32; margin-top: 0;'>✅ WSZYSTKO GOTOWE!</h3>";
    echo "<p>Panel admina jest poprawnie skonfigurowany.</p>";
    echo "<p><strong>Następne kroki:</strong></p>";
    echo "<ol>";
    echo "<li>Przejdź do <a href='login.php' style='color: #1a4d2e;'><strong>login.php</strong></a></li>";
    echo "<li>Zaloguj się: <strong>admin</strong> / <strong>admin123</strong></li>";
    echo "<li><strong style='color: red;'>ZMIEŃ HASŁO natychmiast!</strong></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #ffebee; padding: 20px; border-left: 5px solid #f44336; border-radius: 5px;'>";
    echo "<h3 style='color: #c62828; margin-top: 0;'>❌ WYKRYTO PROBLEMY</h3>";
    echo "<p>Przeczytaj komunikaty powyżej i napraw błędy.</p>";
    echo "<p>Zobacz plik <strong>README-NAPRAWA.md</strong> dla szczegółów.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #999;'>Test zakończony - " . date('Y-m-d H:i:s') . "</p>";
echo "<p style='text-align: center;'><strong>⚠️ USUŃ TEN PLIK PO TESTACH!</strong></p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1, h2 {
    color: #1a4d2e;
}
hr {
    border: none;
    border-top: 2px solid #e0e0e0;
    margin: 30px 0;
}
code, pre {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
pre {
    padding: 15px;
    overflow-x: auto;
}
</style>
