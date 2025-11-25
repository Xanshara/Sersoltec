# 🔧 SERSOLTEC v2.3a - TROUBLESHOOTING GUIDE

## 🐛 Najczęstsze Problemy i Rozwiązania

---

## Problem 1: "Class 'Sersoltec\Lib\Database' not found"

### Przyczyna:
Autoloader nie załadował klas lub błędny namespace.

### Rozwiązanie:

**Krok 1: Sprawdź czy autoloader istnieje**
```bash
ls -la lib/autoload.php
```

**Krok 2: Sprawdź czy init.php ładuje autoloader**
```bash
grep "require.*autoload" lib/init.php
```

**Krok 3: Sprawdź namespace w klasach**
```bash
grep "namespace" lib/Database.php
# Powinno być: namespace Sersoltec\Lib;
```

**Krok 4: Test autoloadera**
```php
<?php
require_once 'lib/autoload.php';

var_dump(class_exists('Sersoltec\Lib\Database'));
// Powinno pokazać: bool(true)
?>
```

**Krok 5: Sprawdź config.php**
```bash
grep "lib/init.php" config.php
# Musi zawierać: require_once __DIR__ . '/lib/init.php';
```

---

## Problem 2: "Permission denied" - nie można zapisać do logów

### Przyczyna:
Brak uprawnień do zapisu w katalog logs/

### Rozwiązanie:

**Krok 1: Sprawdź uprawnienia**
```bash
ls -la | grep logs
# Powinno być: drwxr-xr-x lub drwxrwxr-x
```

**Krok 2: Ustaw uprawnienia**
```bash
chmod 755 logs/
```

**Krok 3: Ustaw właściciela (web server user)**
```bash
# Dla Apache:
chown -R www-data:www-data logs/

# Dla Nginx:
chown -R nginx:nginx logs/

# Sprawdź który user:
ps aux | grep -E 'apache|httpd|nginx' | head -1
```

**Krok 4: SELinux (jeśli używasz RHEL/CentOS)**
```bash
# Sprawdź czy SELinux jest aktywny
getenforce

# Jeśli tak, zezwól na zapis:
chcon -R -t httpd_sys_rw_content_t logs/
```

**Krok 5: Test**
```bash
# Jako web user:
sudo -u www-data touch logs/test.log
ls -la logs/test.log
```

---

## Problem 3: "Table 'sersoltec_db.wishlist' doesn't exist"

### Przyczyna:
Migracja SQL nie została wykonana lub się nie powiodła.

### Rozwiązanie:

**Krok 1: Sprawdź istniejące tabele**
```sql
mysql -u root -p sersoltec_db -e "SHOW TABLES;"
```

**Krok 2: Sprawdź czy wishlist istnieje**
```sql
mysql -u root -p sersoltec_db -e "SHOW TABLES LIKE 'wishlist';"
```

**Krok 3: Uruchom migrację ponownie**
```bash
mysql -u root -p sersoltec_db < MIGRATION-v2.3a.sql
```

**Krok 4: Sprawdź błędy podczas migracji**
```bash
mysql -u root -p sersoltec_db < MIGRATION-v2.3a.sql 2>&1 | tee migration.log
cat migration.log
```

**Krok 5: Ręczne utworzenie tabeli (backup plan)**
```sql
mysql -u root -p sersoltec_db

CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

SHOW TABLES LIKE 'wishlist';
```

---

## Problem 4: "CSRF token mismatch"

### Przyczyna:
Brak CSRF tokenu w formularzu lub sesja wygasła.

### Rozwiązanie:

**Krok 1: Dodaj CSRF field w formularzu**
```php
<form method="POST">
    <?php echo csrf_field(); ?>
    <!-- Lub: -->
    <?php echo security()->csrfField(); ?>
    
    <input type="text" name="email">
    <button>Submit</button>
</form>
```

**Krok 2: Sprawdź czy sesja działa**
```php
<?php
session_start();
$_SESSION['test'] = 'working';

echo "Session ID: " . session_id() . "\n";
echo "Session working: " . ($_SESSION['test'] === 'working' ? 'YES' : 'NO');
?>
```

**Krok 3: W AJAX - dodaj token**
```html
<!-- W <head> -->
<meta name="csrf-token" content="<?php echo csrf_token(); ?>">

<script>
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `action=test&_token=${token}`
});
</script>
```

**Krok 4: Zwiększ czas sesji**
```php
// W config.php PRZED session_start()
ini_set('session.gc_maxlifetime', 7200); // 2 godziny
session_start();
```

---

## Problem 5: Database connection failed

### Przyczyna:
Błędne dane dostępowe lub MySQL nie działa.

### Rozwiązanie:

**Krok 1: Sprawdź czy MySQL działa**
```bash
# Linux:
sudo systemctl status mysql
# lub
sudo systemctl status mariadb

# macOS:
brew services list | grep mysql
```

**Krok 2: Test połączenia**
```bash
mysql -u root -p -h localhost
# Wpisz hasło
```

**Krok 3: Sprawdź config.php**
```php
// Wypisz konfigurację (BEZ HASŁA!)
<?php
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
// echo "DB_PASS: " . DB_PASS . "\n"; // NIE POKAZUJ!
?>
```

**Krok 4: Test PDO connection**
```php
<?php
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    echo "✅ Connection successful!\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
```

**Krok 5: Sprawdź uprawnienia użytkownika**
```sql
mysql -u root -p

SHOW GRANTS FOR 'DB_USER'@'localhost';
```

---

## Problem 6: Email nie wysyłają się

### Przyczyna:
Funkcja mail() nie działa lub test mode włączony.

### Rozwiązanie:

**Krok 1: Sprawdź test mode**
```php
<?php
require_once 'config.php';

// Wyłącz test mode
email()->setTestMode(false);

// Test
$result = email()->send('test@example.com', 'Test', 'Test message');
echo $result ? "✅ Sent" : "❌ Failed";
?>
```

**Krok 2: Sprawdź czy sendmail działa**
```bash
which sendmail
# Powinno pokazać ścieżkę, np: /usr/sbin/sendmail
```

**Krok 3: Test mail() function**
```php
<?php
$result = mail(
    'test@example.com',
    'Test Subject',
    'Test message',
    'From: test@sersoltec.eu'
);

echo $result ? "✅ mail() works" : "❌ mail() failed";
?>
```

**Krok 4: Sprawdź logi mailowe**
```bash
# Linux:
tail -f /var/log/mail.log

# lub
tail -f /var/log/maillog
```

**Krok 5: Użyj SMTP (alternatywa)**

Zainstaluj PHPMailer:
```bash
composer require phpmailer/phpmailer
```

Zaktualizuj Email.php do używania SMTP zamiast mail().

---

## Problem 7: Query log jest pusty

### Przyczyna:
Query logging nie jest włączony.

### Rozwiązanie:

**Krok 1: Włącz query logging**
```php
// config.php po require lib/init.php:
if (DEBUG) {
    db()->enableQueryLog(true);
}
```

**Krok 2: Wykonaj zapytania**
```php
$users = db()->fetchAll('SELECT * FROM users LIMIT 5');
```

**Krok 3: Wyświetl logi**
```php
echo "<pre>";
print_r(db()->getQueryLog());
echo "</pre>";
```

**Krok 4: Sprawdź czy DEBUG = true**
```php
echo "DEBUG: " . (DEBUG ? 'true' : 'false');
```

---

## Problem 8: "Call to undefined function sanitize()"

### Przyczyna:
Funkcja helper nie załadowana lub lib/init.php nie wykonany.

### Rozwiązanie:

**Krok 1: Sprawdź czy init.php jest załadowany**
```php
<?php
// Na początku pliku
if (!function_exists('sanitize')) {
    die("Library not loaded! Check config.php");
}
?>
```

**Krok 2: Sprawdź kolejność w config.php**
```php
// POPRAWNA kolejność:
session_start();
// ... definicje DB_HOST itp ...
require_once __DIR__ . '/lib/init.php'; // <- NA KOŃCU!
```

**Krok 3: Użyj alternatywnej składni**
```php
// Zamiast:
$clean = sanitize($input);

// Użyj:
$clean = Validator::sanitize($input);
```

---

## Problem 9: "Too many connections" (MySQL)

### Przyczyna:
Za dużo otwartych połączeń z bazą.

### Rozwiązanie:

**Krok 1: Sprawdź aktywne połączenia**
```sql
mysql -u root -p -e "SHOW PROCESSLIST;"
```

**Krok 2: Zwiększ limit połączeń**
```sql
mysql -u root -p

SET GLOBAL max_connections = 200;
```

**Krok 3: Edytuj my.cnf**
```bash
sudo nano /etc/mysql/my.cnf

# Dodaj w sekcji [mysqld]:
max_connections = 200
```

**Krok 4: Restart MySQL**
```bash
sudo systemctl restart mysql
```

**Krok 5: Sprawdź czy Singleton działa**
```php
// Database używa Singleton, więc tylko 1 połączenie
$db1 = db();
$db2 = db();

var_dump($db1 === $db2); // Powinno być: bool(true)
```

---

## Problem 10: Session timeout zbyt krótki

### Przyczyna:
Domyślny timeout to 30 minut.

### Rozwiązanie:

**Krok 1: Zwiększ timeout w Auth.php**
```php
// Edytuj lib/Auth.php
private const SESSION_TIMEOUT = 7200; // 2 godziny (zamiast 1800)
```

**Krok 2: Lub zmień w PHP**
```php
// config.php PRZED session_start():
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);
session_start();
```

**Krok 3: Sprawdź ustawienie**
```php
echo "Session timeout: " . ini_get('session.gc_maxlifetime') . " sekund";
```

---

## 🔍 Debug Mode - Jak włączyć?

### Krok 1: W config.php
```php
define('DEBUG', true);
```

### Krok 2: Co to włącza?
- ✅ Query logging
- ✅ Verbose errors
- ✅ Email test mode
- ✅ Debug level logging

### Krok 3: Zobacz logi
```bash
tail -f logs/debug.log
tail -f logs/error.log
```

### Krok 4: Wyświetl query log
```php
print_r(db()->getQueryLog());
```

---

## 📊 Performance Issues

### Logi rosną za szybko

**Rozwiązanie:**
```php
// Automatyczna rotacja (domyślnie 5MB)
// Jest już włączona w Logger.php

// Lub ręcznie wyczyść:
logger()->clear('error');
logger()->clearOld(7); // Starsze niż 7 dni
```

### Baza działa wolno

**Diagnoza:**
```php
// Włącz query log
db()->enableQueryLog(true);

// Wykonaj operacje
$users = db()->fetchAll('SELECT * FROM users');

// Zobacz czasy
foreach (db()->getQueryLog() as $query) {
    echo $query['sql'] . " - " . $query['time'] . "s\n";
}
```

**Rozwiązanie:**
- Dodaj indeksy w SQL
- Użyj LIMIT w zapytaniach
- Cache wyniki

---

## 🆘 Ostateczne Rozwiązanie

Jeśli nic nie działa:

### 1. Reinstalacja
```bash
# Backup
cp -r lib/ lib_backup/
cp config.php config.php.backup

# Usuń
rm -rf lib/

# Zainstaluj ponownie
cp -r outputs/lib/ ./

# Przywróć config
cp config.php.backup config.php
```

### 2. Test czystej instalacji
```bash
# Stwórz nowy katalog testowy
mkdir test-install
cd test-install

# Skopiuj tylko lib/ i config.php
cp -r ../lib/ ./
cp ../config.php ./

# Test
php test-lib.php
```

### 3. Sprawdź PHP version
```bash
php -v
# Minimum: PHP 7.4

php -m | grep pdo
# Musi być: pdo, pdo_mysql
```

---

## 📞 Gdzie szukać pomocy?

### 1. Dokumentacja
- **QUICK-REFERENCE.md** - Przykłady użycia
- **PHASE1-DOCUMENTATION.md** - Pełna dokumentacja
- **INSTALLATION-GUIDE.md** - Instrukcja instalacji

### 2. Logi
```bash
tail -100 logs/error.log
tail -100 logs/debug.log
tail -100 logs/security.log
```

### 3. PHP Error Log
```bash
tail -100 /var/log/php_errors.log
# Lub gdzie jest twój PHP error log
```

### 4. Apache/Nginx Error Log
```bash
tail -100 /var/log/apache2/error.log
# lub
tail -100 /var/log/nginx/error.log
```

---

## ✅ Checklist Debugowania

Gdy coś nie działa, przejdź przez tę listę:

- [ ] PHP version >= 7.4
- [ ] Rozszerzenia: pdo, pdo_mysql, mbstring
- [ ] MySQL działa
- [ ] lib/ katalog istnieje (9 plików)
- [ ] config.php ma require lib/init.php
- [ ] logs/, cache/ katalogi istnieją (755)
- [ ] Migracja SQL wykonana (8 tabel)
- [ ] test-lib.php działa
- [ ] DEBUG = true (w dev)
- [ ] Logi są zapisywane

---

**Jeśli przeszedłeś całą listę i nadal nie działa, sprawdź logi jeszcze raz!**

99% problemów jest widocznych w `logs/error.log` 🔍
