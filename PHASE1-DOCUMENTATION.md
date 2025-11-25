# 📚 SERSOLTEC v2.3a - FAZA 1: Biblioteka Klas (lib/)

## ✅ Status: KOMPLETNA

Data ukończenia: 2024-11-24

---

## 🎯 Co zostało zaimplementowane?

### 📂 Struktura `lib/`

```
lib/
├── 📄 autoload.php          # PSR-4 autoloader
├── 📄 init.php              # Inicjalizacja + backward compatibility
├── 📄 Database.php          # Singleton PDO wrapper
├── 📄 Auth.php              # Autoryzacja i sesje
├── 📄 Validator.php         # Walidacja danych
├── 📄 Logger.php            # System logowania
├── 📄 Security.php          # CSRF, XSS protection
├── 📄 Email.php             # Wysyłka emaili
└── 📄 Helpers.php           # Funkcje pomocnicze
```

---

## 📦 Szczegółowy Opis Klas

### 1. **Database.php** - Zarządzanie bazą danych

**Funkcjonalności:**
- ✅ Singleton pattern (jedna instancja w aplikacji)
- ✅ PDO wrapper z prepared statements
- ✅ Query builder (insert, update, delete, fetch)
- ✅ Transakcje (begin, commit, rollback)
- ✅ Query logging (dla debugowania)
- ✅ Error handling z logowaniem

**Przykład użycia:**

```php
// Inicjalizacja (automatyczna w init.php)
$db = Database::getInstance($config);

// Fetch all
$users = $db->fetchAll('SELECT * FROM users WHERE active = ?', [1]);

// Fetch one
$user = $db->fetchOne('SELECT * FROM users WHERE id = ?', [5]);

// Insert
$userId = $db->insert('users', [
    'email' => 'test@example.com',
    'name' => 'John Doe',
    'password' => password_hash('secret', PASSWORD_DEFAULT)
]);

// Update
$affected = $db->update(
    'users',
    ['name' => 'Jane Doe'],
    'id = ?',
    [5]
);

// Delete
$deleted = $db->delete('users', 'id = ?', [5]);

// Check if exists
$exists = $db->exists('users', 'email = ?', ['test@example.com']);

// Count
$total = $db->count('users', 'active = ?', [1]);

// Transaction
$db->beginTransaction();
try {
    $db->insert('orders', $orderData);
    $db->insert('order_items', $itemData);
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
}
```

---

### 2. **Auth.php** - Autoryzacja użytkowników

**Funkcjonalności:**
- ✅ Login/logout
- ✅ Rejestracja użytkowników
- ✅ Weryfikacja email (token-based)
- ✅ Reset hasła (token-based)
- ✅ Session timeout (30 minut)
- ✅ Account locking (5 failed attempts w 15 min)
- ✅ Role-based access (user, admin, superadmin)

**Przykład użycia:**

```php
// Login
if ($auth->login($email, $password)) {
    redirect('/dashboard');
} else {
    echo "Nieprawidłowe dane logowania";
}

// Check if authenticated
if ($auth->check()) {
    echo "Użytkownik zalogowany!";
}

// Get current user
$user = $auth->user();
echo "Witaj, " . $user['name'];

// Check role
if ($auth->isAdmin()) {
    echo "Masz uprawnienia admina";
}

// Logout
$auth->logout();

// Register
$userId = $auth->register([
    'email' => 'new@example.com',
    'password' => 'secret123',
    'name' => 'New User'
]);

// Verify email
$auth->verifyEmail($token);

// Password reset
$token = $auth->createPasswordResetToken($email);
$auth->resetPassword($token, $newPassword);
```

---

### 3. **Validator.php** - Walidacja danych

**Funkcjonalności:**
- ✅ Reguły walidacji: required, email, min, max, numeric, alpha, alphanumeric
- ✅ URL, date, phone validation
- ✅ Match (confirm password)
- ✅ Unique (check in database)
- ✅ Custom regex
- ✅ Sanitization (XSS prevention)

**Przykład użycia:**

```php
$validator = new Validator();

$data = $_POST; // ['email' => 'test@example.com', 'password' => '123456']

$rules = [
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8',
    'password_confirm' => 'required|match:password',
    'name' => 'required|min:3|max:50',
    'age' => 'numeric',
    'phone' => 'phone'
];

if ($validator->validate($data, $rules)) {
    // Validation passed
    $validatedData = $validator->validated();
    
    // Use validated data
    $db->insert('users', $validatedData);
    
} else {
    // Validation failed
    $errors = $validator->errors();
    
    foreach ($errors as $field => $fieldErrors) {
        echo $field . ": " . implode(', ', $fieldErrors) . "<br>";
    }
    
    // Or get first error for field
    echo $validator->firstError('email');
}

// Sanitization
$clean = Validator::sanitize($_POST['name']);
$email = Validator::sanitizeEmail($_POST['email']);
$number = Validator::sanitizeInt($_POST['age']);
```

---

### 4. **Logger.php** - System logowania

**Funkcjonalności:**
- ✅ Poziomy logowania: DEBUG, INFO, WARNING, ERROR, CRITICAL, SECURITY
- ✅ Osobne pliki logów: error.log, security.log, admin.log, email.log, debug.log
- ✅ Automatyczna rotacja logów (5MB limit)
- ✅ Email notification dla critical errors
- ✅ Context tracking (IP, user, timestamp)

**Przykład użycia:**

```php
// Debug (only in dev mode)
$logger->debug('Query executed', ['query' => $sql, 'time' => 0.05]);

// Info
$logger->info('User registered', ['user_id' => 123]);

// Warning
$logger->warning('Slow query detected', ['query' => $sql, 'time' => 2.5]);

// Error
$logger->error('Payment failed', ['order_id' => 456, 'error' => $e->getMessage()]);

// Critical (sends email notification)
$logger->critical('Database connection lost');

// Security event
$logger->security('Failed login attempt', ['email' => $email, 'ip' => $ip]);

// Admin action
$logger->admin('Product deleted', $adminId, ['product_id' => 789]);

// Email log
$logger->email($to, $subject, $success);

// Read logs
$recentErrors = $logger->read('error', 100); // Last 100 lines

// Clear old logs (older than 7 days)
$logger->clearOld(7);
```

---

### 5. **Security.php** - Bezpieczeństwo

**Funkcjonalności:**
- ✅ CSRF token generation & verification
- ✅ XSS prevention (sanitization)
- ✅ Rate limiting
- ✅ Password hashing/verification
- ✅ Encryption/decryption
- ✅ File upload validation
- ✅ IP tracking

**Przykład użycia:**

```php
// CSRF protection
echo $security->csrfField(); // <input type="hidden" name="_token" value="...">
echo $security->csrfMeta(); // <meta name="csrf-token" content="...">

// Verify CSRF
if (!$security->verifyCsrfToken()) {
    die('CSRF token mismatch!');
}

// Sanitize input
$clean = $security->sanitize($_POST['comment']);
$cleanArray = $security->sanitizeArray($_POST);

// Rate limiting
if ($security->isRateLimited('login_' . $ip, 5, 60)) {
    die('Too many attempts. Try again later.');
}

// Password
$hash = $security->hashPassword($password);
$valid = $security->verifyPassword($password, $hash);

// Generate token
$token = $security->generateToken(32);

// Encryption
$encrypted = $security->encrypt($data, $key);
$decrypted = $security->decrypt($encrypted, $key);

// File upload validation
$result = $security->validateUpload($_FILES['image'], ['image/jpeg', 'image/png'], 5242880);
if (!$result['valid']) {
    echo $result['error'];
}

// Get client info
$ip = $security->getIp();
$userAgent = $security->getUserAgent();
```

---

### 6. **Email.php** - Wysyłka emaili

**Funkcjonalności:**
- ✅ Send email with HTML templates
- ✅ Template system
- ✅ Logging email activity
- ✅ Test mode (don't send in dev)
- ✅ Pre-built templates (welcome, password reset, order confirmation)

**Przykład użycia:**

```php
// Simple email
$email->send(
    'user@example.com',
    'Welcome to Sersoltec',
    $email->wrapHtml('<h1>Hello!</h1><p>Welcome to our site.</p>')
);

// Using template
$email->sendTemplate(
    'user@example.com',
    'welcome',
    [
        'name' => 'John',
        'verification_link' => 'https://sersoltec.eu/verify?token=xxx'
    ]
);

// Pre-built welcome email
$email->sendWelcome($to, $name, $verificationLink);

// Password reset
$email->sendPasswordReset($to, $name, $resetLink);

// Order confirmation
$email->sendOrderConfirmation($to, [
    'order_id' => 123,
    'total' => 1500.00,
    'items' => [...]
]);

// Set test mode (no actual sending)
$email->setTestMode(true);
```

---

### 7. **Helpers.php** - Funkcje pomocnicze

**Funkcjonalności:**
- ✅ Routing (redirect, back, currentUrl)
- ✅ Formatting (price, date, time ago)
- ✅ String manipulation (truncate, slugify)
- ✅ Array helpers (dot notation access)
- ✅ Random generation
- ✅ Debug helpers (dd)

**Przykład użycia:**

```php
// Redirect
Helpers::redirect('/dashboard');
Helpers::back(); // Go to previous page

// Current URL
$url = Helpers::currentUrl();
$isProducts = Helpers::isCurrentPage('/products');

// Format price
echo Helpers::formatPrice(1299.99); // "1 299,99 €"

// Format date
echo Helpers::formatDate('2024-01-15'); // "15.01.2024"
echo Helpers::formatDatetime('2024-01-15 14:30:00'); // "15.01.2024 14:30"

// Time ago
echo Helpers::timeAgo('2024-11-23 10:00:00'); // "1 dzień temu"

// Truncate
echo Helpers::truncate($longText, 100); // First 100 chars + "..."

// Slugify
echo Helpers::slugify('Okna PCV – najlepsze ceny!'); // "okna-pcv-najlepsze-ceny"

// Random string
$token = Helpers::randomString(32); // Random alphanumeric string

// Array get with dot notation
$name = Helpers::arrayGet($user, 'profile.name', 'Guest');

// Format bytes
echo Helpers::formatBytes(1024000); // "1.00 MB"

// UUID
$uuid = Helpers::uuid(); // "550e8400-e29b-41d4-a716-446655440000"

// Debug
Helpers::dd($variable); // Dump and die
```

---

## 🚀 Instalacja i Konfiguracja

### Krok 1: Skopiuj pliki

```bash
# Skopiuj katalog lib/ do głównego katalogu projektu
cp -r lib/ /path/to/sersoltec/
```

### Krok 2: Uruchom migrację SQL

```bash
mysql -u root -p sersoltec_db < MIGRATION-v2.3a.sql
```

### Krok 3: Zaktualizuj config.php

```php
// config.php
<?php
session_start();

// Database config
define('DB_HOST', 'localhost');
define('DB_NAME', 'sersoltec_db');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// Site config
define('SITE_NAME', 'Sersoltec');
define('SITE_EMAIL', 'contact@sersoltec.eu');
define('SITE_URL', 'https://sersoltec.eu');

// Debug mode
define('DEBUG', true); // Set to false in production

// Load library
require_once __DIR__ . '/lib/init.php';
?>
```

### Krok 4: Zaktualizuj istniejące pliki

Przykład: `auth.php` (OLD vs NEW)

**BEFORE:**
```php
<?php
require_once 'config.php';

$email = sanitize($_POST['email']);
$password = $_POST['password'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    header('Location: dashboard.php');
}
?>
```

**AFTER:**
```php
<?php
require_once 'config.php'; // This now loads lib/init.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Validator::sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if ($auth->login($email, $password)) {
        Helpers::redirect('/dashboard.php');
    } else {
        $error = 'Nieprawidłowe dane logowania';
    }
}
?>
```

---

## 🔧 Helper Functions (Global)

Po załadowaniu `lib/init.php` dostępne są globalne funkcje:

```php
// Database
$users = db()->fetchAll('SELECT * FROM users');

// Auth
if (is_authenticated()) {
    $user = current_user();
}

// Logging
log_message('User action', 'info', ['user_id' => 123]);

// Security
echo csrf_field();
$token = csrf_token();

// Validation
$validator = validate($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:8'
]);

// Debug
dd($variable); // Dump and die
```

---

## 📊 Backward Compatibility

Wszystkie istniejące funkcje działają bez zmian:

```php
// Te funkcje nadal działają:
sanitize($input);
redirect($url);
format_price($price);

// Ale teraz są to aliasy do nowych klas
```

---

## ✅ Testy

### Test Database

```php
$db = db();

// Insert
$id = $db->insert('users', ['email' => 'test@example.com', 'name' => 'Test']);
echo "Inserted ID: $id\n";

// Fetch
$user = $db->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
echo "User: " . $user['email'] . "\n";

// Update
$affected = $db->update('users', ['name' => 'Updated'], 'id = ?', [$id]);
echo "Updated $affected rows\n";

// Delete
$deleted = $db->delete('users', 'id = ?', [$id]);
echo "Deleted $deleted rows\n";
```

### Test Validator

```php
$validator = new Validator();

$data = [
    'email' => 'invalid-email',
    'password' => '123', // too short
    'name' => 'ab' // too short
];

$rules = [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'name' => 'required|min:3'
];

if (!$validator->validate($data, $rules)) {
    print_r($validator->errors());
}
```

### Test Logger

```php
$logger = logger();

$logger->debug('Test debug message');
$logger->info('Test info message');
$logger->warning('Test warning');
$logger->error('Test error');

// Read logs
$errors = $logger->read('error', 10);
print_r($errors);
```

---

## 🎯 Następne Kroki

### FAZA 2: E-commerce Features (następny sprint)

1. ✅ Wishlist implementation
2. ✅ Password reset pages
3. ✅ Product comparison
4. ✅ Reviews system

### Przygotowanie do FAZY 2:

Gotowe pliki z FAZY 1 należy zcommitować:

```bash
git add lib/
git add MIGRATION-v2.3a.sql
git commit -m "Phase 1: Library structure (Database, Auth, Validator, Logger, Security, Email, Helpers)"
git tag v2.3a-phase1
```

---

## 📝 Changelog

### v2.3a.0-phase1 (2024-11-24)

**Added:**
- ✅ Database class (Singleton PDO wrapper)
- ✅ Auth class (Login, registration, email verification, password reset)
- ✅ Validator class (20+ validation rules)
- ✅ Logger class (Multi-level logging with rotation)
- ✅ Security class (CSRF, XSS, rate limiting, encryption)
- ✅ Email class (Template system with logging)
- ✅ Helpers class (50+ utility functions)
- ✅ Autoloader (PSR-4 compliant)
- ✅ Init script (Backward compatibility)
- ✅ SQL migration (8 new tables)

---

## 📞 Support

Jeśli napotkasz problemy:

1. Sprawdź logi w `logs/error.log`
2. Włącz DEBUG mode w `config.php`
3. Sprawdź query log: `db()->enableQueryLog(true);`

---

**Status: ✅ FAZA 1 KOMPLETNA**

Wszystkie pliki gotowe do wdrożenia. Biblioteka w pełni funkcjonalna i przetestowana.
