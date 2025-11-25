# 🚀 SERSOLTEC v2.3a - QUICK REFERENCE

## ⚡ Cheat Sheet dla Biblioteki lib/

---

## 🗄️ DATABASE

```php
// Fetch all
$users = db()->fetchAll('SELECT * FROM users WHERE active = ?', [1]);

// Fetch one
$user = db()->fetchOne('SELECT * FROM users WHERE id = ?', [5]);

// Fetch single value
$email = db()->fetchColumn('SELECT email FROM users WHERE id = ?', [5]);

// Insert
$id = db()->insert('users', ['email' => 'test@test.com', 'name' => 'John']);

// Update
$affected = db()->update('users', ['name' => 'Jane'], 'id = ?', [5]);

// Delete
$deleted = db()->delete('users', 'id = ?', [5]);

// Exists
$exists = db()->exists('users', 'email = ?', ['test@test.com']);

// Count
$total = db()->count('users', 'active = ?', [1]);

// Transaction
db()->beginTransaction();
try {
    db()->insert('orders', $data);
    db()->commit();
} catch (Exception $e) {
    db()->rollback();
}
```

---

## 🔐 AUTH

```php
// Login
if (auth()->login($email, $password)) {
    Helpers::redirect('/dashboard');
}

// Check if logged in
if (is_authenticated()) { }
if (auth()->check()) { }

// Get current user
$user = current_user();
$user = auth()->user();

// User ID
$userId = auth()->id();

// Check role
if (auth()->isAdmin()) { }
if (auth()->hasRole('admin')) { }

// Logout
auth()->logout();

// Register
$userId = auth()->register([
    'email' => 'new@test.com',
    'password' => 'secret123',
    'name' => 'User'
]);

// Verify email
auth()->verifyEmail($token);

// Password reset
$token = auth()->createPasswordResetToken($email);
auth()->resetPassword($token, $newPassword);
```

---

## ✅ VALIDATOR

```php
$validator = new Validator();

// Validate
$rules = [
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8',
    'password_confirm' => 'match:password',
    'name' => 'required|min:3|max:50',
    'age' => 'numeric',
    'phone' => 'phone'
];

if ($validator->validate($_POST, $rules)) {
    $data = $validator->validated();
} else {
    $errors = $validator->errors();
    $firstError = $validator->firstError('email');
}

// Sanitize
$clean = Validator::sanitize($input);
$email = Validator::sanitizeEmail($email);
$number = Validator::sanitizeInt($age);
$float = Validator::sanitizeFloat($price);
```

**Available Rules:**
- `required` - Field required
- `email` - Valid email
- `min:X` - Min length
- `max:X` - Max length
- `numeric` - Must be number
- `alpha` - Only letters
- `alphanumeric` - Letters and numbers
- `url` - Valid URL
- `match:field` - Match another field
- `in:val1,val2` - Must be one of values
- `unique:table,column` - Unique in DB
- `date` - Valid date
- `phone` - Valid phone (PL format)
- `regex:/pattern/` - Custom regex

---

## 📝 LOGGER

```php
// Log levels
logger()->debug('Debug message', ['context' => 'data']);
logger()->info('Info message');
logger()->warning('Warning message');
logger()->error('Error message', ['error' => $e->getMessage()]);
logger()->critical('Critical error'); // Sends email!
logger()->security('Security event', ['ip' => $ip]);

// Admin action
logger()->admin('Product deleted', $adminId, ['product_id' => 123]);

// Email log
logger()->email($to, $subject, $success);

// Read logs
$errors = logger()->read('error', 100); // Last 100 lines

// Clear old logs (>7 days)
logger()->clearOld(7);

// Helper function
log_message('My message', 'info', ['context' => 'data']);
```

**Log Files:**
- `logs/error.log` - Errors and warnings
- `logs/security.log` - Security events
- `logs/admin.log` - Admin actions
- `logs/email.log` - Email activity
- `logs/debug.log` - Debug messages

---

## 🔒 SECURITY

```php
// CSRF Protection
echo csrf_field(); // <input type="hidden" name="_token" value="...">
echo security()->csrfField();
echo security()->csrfMeta(); // <meta> tag

$token = csrf_token();
$token = security()->getCsrfToken();

if (security()->verifyCsrfToken()) { }

// Sanitize
$clean = security()->sanitize($input);
$cleanArray = security()->sanitizeArray($_POST);

// Rate limiting
if (security()->isRateLimited('action_' . $ip, 5, 60)) {
    die('Too many attempts');
}

// Password
$hash = security()->hashPassword($password);
$valid = security()->verifyPassword($password, $hash);

// Token generation
$token = security()->generateToken(32);

// Encryption
$encrypted = security()->encrypt($data, $key);
$decrypted = security()->decrypt($encrypted, $key);

// File upload
$result = security()->validateUpload($_FILES['file'], ['image/jpeg'], 5242880);
if ($result['valid']) { }

// Get info
$ip = security()->getIp();
$userAgent = security()->getUserAgent();
```

---

## 📧 EMAIL

```php
// Simple email
email()->send($to, $subject, $body);

// With template
email()->sendTemplate($to, 'welcome', [
    'name' => 'John',
    'link' => 'https://...'
]);

// Pre-built emails
email()->sendWelcome($to, $name, $verificationLink);
email()->sendPasswordReset($to, $name, $resetLink);
email()->sendOrderConfirmation($to, $orderData);

// HTML wrapper
$html = email()->wrapHtml('<h1>Hello</h1><p>Content here</p>', 'Title');

// Test mode (dev)
email()->setTestMode(true);

// Helper
if (Email::isValid($email)) { }
$clean = Email::sanitize($email);
```

---

## 🛠️ HELPERS

```php
// Redirect
Helpers::redirect('/dashboard');
Helpers::back(); // Previous page

// Current URL
$url = Helpers::currentUrl();
$isProducts = Helpers::isCurrentPage('/products');

// Format
$price = Helpers::formatPrice(1299.99); // "1 299,99 €"
$date = Helpers::formatDate('2024-01-15'); // "15.01.2024"
$datetime = Helpers::formatDatetime('2024-01-15 14:30'); // "15.01.2024 14:30"
$ago = Helpers::timeAgo('2024-11-23 10:00:00'); // "1 dzień temu"

// String
$short = Helpers::truncate($longText, 100);
$slug = Helpers::slugify('Okna PCV'); // "okna-pcv"
$random = Helpers::randomString(32);

// Array
$value = Helpers::arrayGet($array, 'user.name', 'Guest');
$isAssoc = Helpers::isAssoc($array);

// Other
$bytes = Helpers::formatBytes(1024000); // "1.00 MB"
$uuid = Helpers::uuid();

// Debug
dd($var1, $var2); // Dump and die
```

---

## 🌐 COMMON PATTERNS

### Form with CSRF + Validation

```php
<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!security()->verifyCsrfToken()) {
        die('CSRF error');
    }
    
    $validator = new Validator();
    if ($validator->validate($_POST, [
        'email' => 'required|email',
        'password' => 'required|min:8'
    ])) {
        $data = $validator->validated();
        // Process...
    } else {
        $errors = $validator->errors();
    }
}
?>

<form method="POST">
    <?php echo csrf_field(); ?>
    <input type="email" name="email">
    <input type="password" name="password">
    <button>Submit</button>
</form>
```

### Protected Page (Auth Required)

```php
<?php
require_once 'config.php';

if (!is_authenticated()) {
    Helpers::redirect('/auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user = current_user();
?>

<h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
```

### AJAX Endpoint

```php
<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!security()->verifyCsrfToken()) {
    echo json_encode(['success' => false, 'error' => 'CSRF']);
    exit;
}

try {
    $data = db()->fetchAll('SELECT * FROM products');
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    logger()->error('API error', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
```

### Admin Page

```php
<?php
require_once 'config.php';

if (!auth()->isAdmin()) {
    die('Access denied');
}

logger()->admin('Viewed products page', auth()->id());

$products = db()->fetchAll('SELECT * FROM products');
?>
```

---

## 🔥 Pro Tips

```php
// Chain methods
$user = db()->fetchOne('SELECT * FROM users WHERE id = ?', [5]) ?: [];

// Rate limit per IP
$key = 'login_' . security()->getIp();
if (security()->isRateLimited($key, 5, 300)) { }

// Log with context
logger()->info('User action', [
    'user_id' => auth()->id(),
    'ip' => security()->getIp(),
    'action' => 'delete_product'
]);

// Validate and sanitize
$validator = validate($_POST, $rules);
if ($validator->validate($_POST, $rules)) {
    $data = array_map([Validator::class, 'sanitize'], $validator->validated());
}

// Transaction with logging
db()->beginTransaction();
try {
    $orderId = db()->insert('orders', $orderData);
    db()->insert('order_items', $itemData);
    logger()->info('Order created', ['order_id' => $orderId]);
    db()->commit();
} catch (Exception $e) {
    db()->rollback();
    logger()->error('Order failed', ['error' => $e->getMessage()]);
}
```

---

## 📱 JavaScript Integration

```html
<meta name="csrf-token" content="<?php echo csrf_token(); ?>">

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `action=add&id=5&_token=${csrfToken}`
});
</script>
```

---

## 🐛 Debug Mode

```php
// config.php
define('DEBUG', true);

// Enables:
// - Query logging: db()->getQueryLog()
// - Verbose errors
// - Email test mode
// - Debug level logging
```

---

**💡 TIP:** Trzymaj ten plik pod ręką podczas developmentu!

**📚 Pełna dokumentacja:** PHASE1-DOCUMENTATION.md
