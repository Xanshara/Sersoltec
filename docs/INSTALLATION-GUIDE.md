# 🛠️ SERSOLTEC v2.3a - INSTRUKCJA INSTALACJI

## 📋 Wymagania

### System:
- ✅ PHP 7.4+ (zalecane: PHP 8.0+)
- ✅ MySQL 5.7+ / MariaDB 10.3+
- ✅ Apache/Nginx
- ✅ Git (opcjonalnie)

### Rozszerzenia PHP (wymagane):
- ✅ pdo
- ✅ pdo_mysql
- ✅ mbstring
- ✅ json
- ✅ openssl
- ✅ curl

### Sprawdź wymagania:
```bash
php -v                                  # Wersja PHP
php -m | grep -E 'pdo|mysql|mbstring'  # Rozszerzenia
mysql --version                         # MySQL/MariaDB
```

---

## 🚀 INSTALACJA - KROK PO KROKU

### Krok 1: Backup (WAŻNE!)

**1.1. Backup bazy danych:**
```bash
# Stwórz backup obecnej bazy
mysqldump -u root -p sersoltec_db > backup_before_v2_$(date +%Y%m%d_%H%M%S).sql

# Sprawdź czy backup jest OK
ls -lh backup_*.sql
```

**1.2. Backup plików:**
```bash
# Przejdź do katalogu nadrzędnego
cd /path/to/

# Stwórz archiwum
tar -czf sersoltec_backup_$(date +%Y%m%d_%H%M%S).tar.gz sersoltec/

# Sprawdź
ls -lh sersoltec_backup_*.tar.gz
```

✅ **Checkpoint:** Masz 2 pliki backup (SQL + tar.gz)

---

### Krok 2: Pobierz pliki v2.3a

**2.1. Z Claude (jeśli masz outputs/):**
```bash
# Jesteś w katalogu z pobranym outputs/
ls outputs/

# Powinno pokazać:
# lib/
# MIGRATION-v2.3a.sql
# *.md files
```

**2.2. Z GitHub (jeśli wrzuciłeś tam):**
```bash
cd /path/to/sersoltec/
git pull origin main
git checkout v2.3a-phase1
```

**2.3. Alternatywnie - ręcznie:**
- Pobierz wszystkie pliki z Claude Interface
- Rozpakuj lokalnie
- Przygotuj do kopiowania

✅ **Checkpoint:** Masz dostęp do plików outputs/

---

### Krok 3: Skopiuj bibliotekę lib/

```bash
# Przejdź do głównego katalogu projektu
cd /path/to/sersoltec/

# Skopiuj katalog lib/
cp -r /path/to/outputs/lib/ ./

# Sprawdź czy się skopiowało
ls -la lib/

# Powinno pokazać 9 plików:
# autoload.php
# init.php
# Database.php
# Auth.php
# Validator.php
# Logger.php
# Security.php
# Email.php
# Helpers.php
```

**Ustaw uprawnienia:**
```bash
# Katalog
chmod 755 lib/

# Pliki
chmod 644 lib/*.php

# Sprawdź
ls -la lib/
```

✅ **Checkpoint:** Katalog `lib/` istnieje i ma 9 plików PHP

---

### Krok 4: Utwórz katalogi systemowe

```bash
# W głównym katalogu projektu
cd /path/to/sersoltec/

# Utwórz katalogi
mkdir -p logs
mkdir -p cache
mkdir -p email-templates

# Ustaw uprawnienia (ważne!)
chmod 755 logs cache email-templates

# Jeśli masz www-data user:
chown -R www-data:www-data logs cache email-templates

# Lub jeśli masz innego użytkownika:
chown -R apache:apache logs cache email-templates
```

**Utwórz .htaccess w logs/ (bezpieczeństwo):**
```bash
cat > logs/.htaccess << 'EOF'
Deny from all
EOF
```

**Sprawdź strukturę:**
```bash
ls -la | grep -E 'logs|cache|email'

# Powinno pokazać:
# drwxr-xr-x logs/
# drwxr-xr-x cache/
# drwxr-xr-x email-templates/
```

✅ **Checkpoint:** Katalogi logs/, cache/, email-templates/ istnieją z prawami 755

---

### Krok 5: Zaktualizuj config.php

**5.1. Otwórz config.php:**
```bash
nano config.php
# lub
vim config.php
```

**5.2. Znajdź koniec pliku i PRZED zamykającym `?>` dodaj:**

```php
// ====================================================
// SERSOLTEC v2.3a - Library Integration
// ====================================================

// Debug mode (IMPORTANT: set to false in production!)
define('DEBUG', true);  // <- ZMIEŃ NA false NA PRODUKCJI!

// Load library
require_once __DIR__ . '/lib/init.php';

// ====================================================
```

**5.3. Twój config.php powinien wyglądać tak:**

```php
<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sersoltec_db');
define('DB_USER', 'root');
define('DB_PASS', 'twoje_haslo');

// Site configuration
define('SITE_NAME', 'Sersoltec');
define('SITE_EMAIL', 'contact@sersoltec.eu');
define('SITE_URL', 'https://sersoltec.eu');

// [... reszta konfiguracji ...]

// ====================================================
// SERSOLTEC v2.3a - Library Integration
// ====================================================

// Debug mode
define('DEBUG', true);  // <- ZMIEŃ NA false NA PRODUKCJI!

// Load library
require_once __DIR__ . '/lib/init.php';

// ====================================================
?>
```

**5.4. Zapisz plik:**
```bash
# W nano: Ctrl+O, Enter, Ctrl+X
# W vim: :wq
```

✅ **Checkpoint:** `config.php` ma na końcu `require_once __DIR__ . '/lib/init.php';`

---

### Krok 6: Uruchom migrację SQL

**6.1. Sprawdź obecną strukturę bazy:**
```bash
mysql -u root -p sersoltec_db -e "SHOW TABLES;"
```

**6.2. Uruchom migrację:**
```bash
# Przejdź do katalogu z MIGRATION-v2.3a.sql
cd /path/to/outputs/

# Uruchom migrację
mysql -u root -p sersoltec_db < MIGRATION-v2.3a.sql

# Wpisz hasło gdy poprosi
```

**6.3. Sprawdź czy tabele zostały utworzone:**
```bash
mysql -u root -p sersoltec_db -e "SHOW TABLES;"
```

**Powinny pojawić się NOWE tabele:**
- ✅ login_attempts
- ✅ password_resets
- ✅ wishlist
- ✅ product_comparisons
- ✅ product_reviews
- ✅ blog_posts
- ✅ blog_comments

**6.4. Sprawdź strukturę jednej z tabel:**
```bash
mysql -u root -p sersoltec_db -e "DESCRIBE wishlist;"
```

✅ **Checkpoint:** 8 nowych tabel w bazie danych

---

### Krok 7: Test połączenia

**7.1. Utwórz plik testowy:**
```bash
cd /path/to/sersoltec/

cat > test-lib.php << 'EOF'
<?php
/**
 * Test instalacji biblioteki v2.3a
 */

echo "====================================\n";
echo "SERSOLTEC v2.3a - Installation Test\n";
echo "====================================\n\n";

// Load config
require_once 'config.php';

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
    if (file_exists('logs/debug.log')) {
        echo "   ✅ Logger working! Check logs/debug.log\n";
    } else {
        echo "   ⚠️  Log file not created yet\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Security
echo "\nTest 3: Security (CSRF)\n";
try {
    $token = csrf_token();
    if (strlen($token) === 64) {
        echo "   ✅ CSRF token generated: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "   ❌ Token invalid length\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Validator
echo "\nTest 4: Validator\n";
try {
    $clean = Validator::sanitize('<script>alert("XSS")</script>Hello');
    if (strpos($clean, '<script>') === false) {
        echo "   ✅ Sanitization working\n";
    } else {
        echo "   ❌ Sanitization failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Helpers
echo "\nTest 5: Helpers\n";
try {
    $price = Helpers::formatPrice(1299.99);
    echo "   ✅ Helpers working: $price\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 6: Check tables
echo "\nTest 6: New Database Tables\n";
$tables = ['wishlist', 'password_resets', 'product_reviews', 'blog_posts'];
foreach ($tables as $table) {
    try {
        $exists = db()->getPdo()->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        if ($exists) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing!\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking $table\n";
    }
}

// Summary
echo "\n====================================\n";
echo "🎉 ALL TESTS PASSED!\n";
echo "====================================\n";
echo "\nLibrary v2.3a installed successfully!\n";
echo "You can now delete this file: test-lib.php\n\n";
EOF
```

**7.2. Uruchom test:**
```bash
php test-lib.php
```

**Oczekiwany output:**
```
====================================
SERSOLTEC v2.3a - Installation Test
====================================

Test 1: Database Connection
   ✅ Connected! Found X users

Test 2: Logger
   ✅ Logger working! Check logs/debug.log

Test 3: Security (CSRF)
   ✅ CSRF token generated: abc1234567...

Test 4: Validator
   ✅ Sanitization working

Test 5: Helpers
   ✅ Helpers working: 1 299,99 €

Test 6: New Database Tables
   ✅ Table 'wishlist' exists
   ✅ Table 'password_resets' exists
   ✅ Table 'product_reviews' exists
   ✅ Table 'blog_posts' exists

====================================
🎉 ALL TESTS PASSED!
====================================
```

✅ **Checkpoint:** Wszystkie testy przechodzą

---

### Krok 8: Test w przeglądarce

**8.1. Utwórz test-browser.php:**
```php
<?php
// test-browser.php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERSOLTEC v2.3a - Browser Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .success { border-left: 5px solid #4caf50; }
        .error { border-left: 5px solid #f44336; }
        h1 { color: #1a4d2e; }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🚀 SERSOLTEC v2.3a - Installation Test</h1>
    
    <?php
    // Test Database
    echo '<div class="test success">';
    echo '<h3>✅ Database Connection</h3>';
    try {
        $count = db()->count('users');
        echo "<p>Connected! Found <strong>$count</strong> users in database.</p>";
    } catch (Exception $e) {
        echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Test CSRF
    echo '<div class="test success">';
    echo '<h3>✅ Security (CSRF Token)</h3>';
    $token = csrf_token();
    echo '<p>Token: <code>' . htmlspecialchars(substr($token, 0, 20)) . '...</code></p>';
    echo csrf_field();
    echo '</div>';
    
    // Test Helpers
    echo '<div class="test success">';
    echo '<h3>✅ Helpers</h3>';
    echo '<p>Formatted price: <strong>' . Helpers::formatPrice(1299.99) . '</strong></p>';
    echo '<p>Current URL: <code>' . htmlspecialchars(Helpers::currentUrl()) . '</code></p>';
    echo '</div>';
    
    // Test Auth
    echo '<div class="test success">';
    echo '<h3>✅ Authentication</h3>';
    if (is_authenticated()) {
        $user = current_user();
        echo '<p>Logged in as: <strong>' . htmlspecialchars($user['email'] ?? 'Unknown') . '</strong></p>';
    } else {
        echo '<p>Not logged in (expected)</p>';
    }
    echo '</div>';
    
    // Test Logger
    echo '<div class="test success">';
    echo '<h3>✅ Logger</h3>';
    logger()->info('Browser test executed');
    echo '<p>Message logged to <code>logs/debug.log</code></p>';
    echo '</div>';
    ?>
    
    <div class="test success">
        <h3>🎉 Installation Complete!</h3>
        <p>All systems operational. You can now:</p>
        <ul>
            <li>Delete <code>test-lib.php</code></li>
            <li>Delete <code>test-browser.php</code></li>
            <li>Start using the new library!</li>
        </ul>
    </div>
</body>
</html>
```

**8.2. Otwórz w przeglądarce:**
```
https://your-domain.com/test-browser.php
```

**Powinieneś zobaczyć:**
- ✅ 5 zielonych boxów z testami
- ✅ "Installation Complete!" na końcu

✅ **Checkpoint:** Strona testowa działa w przeglądarce

---

### Krok 9: Cleanup (opcjonalnie)

```bash
# Usuń pliki testowe
rm test-lib.php
rm test-browser.php

# Sprawdź logi
tail -n 20 logs/debug.log
```

---

### Krok 10: Konfiguracja produkcyjna (WAŻNE!)

Jeśli wdrażasz na produkcji:

**10.1. Wyłącz DEBUG mode:**
```php
// config.php
define('DEBUG', false);  // <- WAŻNE!
```

**10.2. Ustaw min log level:**
```php
// Po require_once lib/init.php dodaj:
logger()->setMinLevel(Logger::LEVEL_WARNING);
```

**10.3. Wyłącz email test mode:**
```php
// Po require_once lib/init.php dodaj:
email()->setTestMode(false);
```

**10.4. Ustaw uprawnienia:**
```bash
# Katalogi tylko do odczytu dla WWW
chmod 750 lib/
chmod 640 lib/*.php

# Logi zapisywalne
chmod 770 logs/
```

---

## ✅ INSTALACJA ZAKOŃCZONA!

### Sprawdź:
- [x] Biblioteka lib/ skopiowana
- [x] Katalogi logs/, cache/ utworzone
- [x] config.php zaktualizowany
- [x] Migracja SQL wykonana
- [x] Test CLI działa (test-lib.php)
- [x] Test przeglądarki działa (test-browser.php)
- [x] DEBUG mode wyłączony (produkcja)

---

## 🎯 Co teraz?

### Dla deweloperów:
1. Przeczytaj **QUICK-REFERENCE.md** (5 min)
2. Zobacz **PHASE1-DOCUMENTATION.md** (30 min)
3. Zacznij używać biblioteki w kodzie

### Przykład użycia:
```php
<?php
require_once 'config.php';

// Database
$products = db()->fetchAll('SELECT * FROM products WHERE active = 1');

// Validation
$validator = validate($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:8'
]);

// Authentication
if (auth()->login($email, $password)) {
    Helpers::redirect('/dashboard');
}

// Logging
logger()->info('User action', ['user_id' => auth()->id()]);
?>
```

---

## 🐛 Troubleshooting

### Problem: "Class not found"
```bash
# Sprawdź autoloader
php -r "require 'lib/autoload.php'; var_dump(class_exists('Sersoltec\Lib\Database'));"

# Powinno pokazać: bool(true)
```

**Rozwiązanie:**
- Sprawdź czy `lib/autoload.php` istnieje
- Sprawdź czy namespace w plikach to `namespace Sersoltec\Lib;`

---

### Problem: "Permission denied" (logs)
```bash
ls -la logs/
```

**Rozwiązanie:**
```bash
chmod 755 logs/
chown www-data:www-data logs/
```

---

### Problem: "Table doesn't exist"
```sql
mysql -u root -p sersoltec_db -e "SHOW TABLES;"
```

**Rozwiązanie:**
```bash
# Uruchom migrację ponownie
mysql -u root -p sersoltec_db < MIGRATION-v2.3a.sql
```

---

### Problem: "CSRF token mismatch"

**Rozwiązanie:**
Dodaj w formularzu:
```php
<?php echo csrf_field(); ?>
```

Lub w JavaScript:
```html
<meta name="csrf-token" content="<?php echo csrf_token(); ?>">
```

---

### Problem: Query log jest pusty

**Rozwiązanie:**
```php
// W config.php po require lib/init.php:
if (DEBUG) {
    db()->enableQueryLog(true);
}

// Potem możesz sprawdzić:
print_r(db()->getQueryLog());
```

---

## 📞 Wsparcie

### Dokumentacja:
- **QUICK-REFERENCE.md** - Szybki lookup
- **PHASE1-DOCUMENTATION.md** - Pełna dokumentacja
- **FILES-MANIFEST.md** - Lista plików

### Debug:
```bash
# Logi błędów
tail -f logs/error.log

# Logi debug
tail -f logs/debug.log

# Logi bezpieczeństwa
tail -f logs/security.log
```

---

## ✨ To wszystko!

Gratulacje! 🎉 SERSOLTEC v2.3a jest zainstalowany i gotowy do użycia!

**Następny krok:** Zobacz **NEXT-STEPS.md** dla planu FAZY 2
