<?php
// ===== FINALIZACJA ZAMÓWIENIA - WERSJA DEBUGOWA =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Używamy __DIR__ dla bezpieczeństwa
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/translations.php';

// KROK 1: Użycie standardowej funkcji do pobierania języka
$current_lang = getCurrentLanguage();

// Sprawdź czy koszyk nie jest pusty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php?lang=' . $current_lang);
    exit;
}

// Oblicz sumę zamówienia
$order_total = 0;
$order_items = [];
$total_items = 0; 

foreach ($_SESSION['cart'] as $product_id => $item) {
    $order_total += $item['price'] * $item['quantity'];
    $order_items[] = [
        'product_id' => $product_id,
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'unit' => $item['unit']
    ];
    $total_items += $item['quantity'];
}

// Pobierz dane użytkownika jeśli zalogowany
// user_id_to_insert to teraz NULL (dla gościa) lub ID użytkownika
$user_id_to_insert = $_SESSION['user_id'] ?? null; 
$user_data = null;

if ($user_id_to_insert !== null && $user_id_to_insert > 0) { // Tylko jeśli zalogowany
    try {
        $stmt = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
        $stmt->execute([$user_id_to_insert]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("User data fetch error: " . $e->getMessage());
    }
}

// Inicjalizacja zmiennych formularza
$full_name = $_POST['full_name'] ?? ($user_data['full_name'] ?? '');
$email = $_POST['email'] ?? ($user_data['email'] ?? '');
$phone = $_POST['phone'] ?? ($user_data['phone'] ?? '');
$address = $_POST['address'] ?? ($user_data['address'] ?? '');
$notes = $_POST['notes'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'transfer'; 

$errors = [];
$success = false;

// Obsługa złożenia zamówienia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    
    // Walidacja
    if (empty($full_name)) { $errors['full_name'] = t('error_name_required'); }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = t('error_email_invalid'); }
    if (empty($phone)) { $errors['phone'] = t('error_phone_required'); }
    if (empty($address)) { $errors['address'] = t('error_address_required'); }
    
    if (empty($errors)) {
        // Generuj unikalny numer zamówienia
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Zapis zamówienia do bazy danych
        try {
            $pdo->beginTransaction();
            
            // 1. Zapis do tabeli orders (z order_number)
            $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, full_name, email, phone, address, notes, total_amount, payment_method, lang) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // user_id_to_insert będzie NULL dla gościa lub ID użytkownika
            $stmt->execute([
                $order_number,  // DODANE!
                $user_id_to_insert, 
                $full_name,
                $email,
                $phone,
                $address,
                $notes,
                $order_total,
                $payment_method,
                $current_lang // Wymaga kolumny 'lang' w tabeli orders
            ]);
            $order_id = $pdo->lastInsertId();
            
            // 2. Zapis do tabeli order_items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price_per_unit) VALUES (?, ?, ?, ?, ?)");
            foreach ($order_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    // Używamy substr dla pewności, że długa nazwa nie spowoduje błędu (max 255 znaków)
                    substr($item['name'], 0, 255), 
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            $pdo->commit();
            
            // Wyczyść koszyk i przekieruj na stronę sukcesu
            unset($_SESSION['cart']);
            header('Location: order_success.php?id=' . $order_id);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Zapis do logów serwera (na wszelki wypadek)
            error_log("Order submission failed (PDOException): " . $e->getMessage()); 
            
            // 🔴 ZMIANA DEBUGOWANIA: Wstawiamy dokładny komunikat błędu MySQL do komunikatu dla użytkownika
            $errors['general'] = t('error_order_failed') . " [Błąd SQL: " . $e->getMessage() . "]";
        }
    }
}

$page_title = t('nav_checkout'); 
// --- Pełny kod HTML poniżej ---
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    </head>
<body>
<?php include 'includes/header.php'; // Załadowanie nagłówka z nawigacją ?>

<main class="checkout-page">
    <div class="container">
        <h1 class="page-title">🛒 <?php echo t('nav_checkout'); ?></h1> 
        
        <?php if (!empty($errors) && isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            
            <div class="checkout-form-container">
               <h2 class="mainers-header"><?php echo t('checkout_personal_data'); ?></h2> 
                
                <form method="POST" action="checkout.php">
                    
                    <div class="form-group">
                        <label for="full_name"><?php echo t('form_name'); ?>:</label> 
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name); ?>" 
                               placeholder="<?php echo t('form_name_placeholder'); ?>" required>
                        <?php if (isset($errors['full_name'])): ?><span class="error"><?php echo $errors['full_name']; ?></span><?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo t('form_email'); ?>:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="<?php echo t('form_email_placeholder'); ?>" required>
                        <?php if (isset($errors['email'])): ?><span class="error"><?php echo $errors['email']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone"><?php echo t('form_phone'); ?>:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($phone); ?>" 
                               placeholder="<?php echo t('form_phone_placeholder'); ?>" required>
                        <?php if (isset($errors['phone'])): ?><span class="error"><?php echo $errors['phone']; ?></span><?php endif; ?>
                    </div>
                    
                    <h2 class="mainers-header"><?php echo t('checkout_delivery_data'); ?></h2>
                    
                    <div class="form-group">
                        <label for="address"><?php echo t('form_address'); ?>:</label>
                        <textarea id="address" name="address" rows="3" 
                                  placeholder="<?php echo t('form_address_placeholder'); ?>" required><?php echo htmlspecialchars($address); ?></textarea>
                        <?php if (isset($errors['address'])): ?><span class="error"><?php echo $errors['address']; ?></span><?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes"><?php echo t('form_notes'); ?>:</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="<?php echo t('form_notes_placeholder'); ?>"><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>

                    <h2 class="mainers-header"><?php echo t('checkout_payment'); ?></h2> 
                    <div class="form-group payment-options">
                        <label>
                            <input type="radio" name="payment_method" value="transfer" checked>
                            <?php echo t('payment_transfer'); ?>
                        </label>
                        <label>
                            <input type="radio" name="payment_method" value="cash_on_delivery" 
                                   <?php if ($payment_method === 'cash_on_delivery') echo 'checked'; ?>>
                            <?php echo t('payment_cod'); ?>
                        </label>
                    </div>

                    <div class="info-box">
                        <p><?php echo t('checkout_info'); ?></p> 
                    </div>
                    
                    <button type="submit" name="submit_order" class="btn btn-primary btn-block submit-btn">
                        <?php echo t('place_order'); ?>
                    </button>
                    
                </form>
            </div>

            <div class="order-summary-container">
                <div class="summary-card">
                    <h3><?php echo t('order_summary'); ?></h3>
                    
                    <?php foreach ($order_items as $item): ?>
                        <div class="summary-item">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span><?php echo $item['quantity']; ?> x <?php echo number_format($item['price'], 2); ?> €</span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="summary-row">
                        <span><?php echo t('form_products'); ?>:</span> 
                        <span><?php echo $total_items; ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span><?php echo t('shipping'); ?>:</span> 
                        <span><?php echo t('shipping_info'); ?></span>
                    </div>

                    <hr>
                    
                    <div class="summary-row total">
                        <span><?php echo t('total'); ?>:</span>
                        <strong><?php echo number_format($order_total, 2); ?> €</strong>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* 🟢 KROK 1: DEFINICJA ZMIENNYCH CSS */
:root {
    /* Kolory główne - Twoja definicja */
    --color-primary: #1a4d2e; 
    --color-white: #ffffff;
}

.checkout-page { padding: 40px 20px; min-height: calc(100vh - 200px); }
.page-title { text-align: center; margin-bottom: 40px; color: #2c3e50; font-size: 2.5em; }
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.checkout-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* Formularz */
/* 🟢 KROK 2: UŻYCIE KLASY I ZMIENNEJ W NAGŁÓWKACH */
.checkout-form-container .mainers-header { /* Używamy klasy .mainers-header dla stylizacji */
    color: var(--color-primary); /* Ustawia kolor na #1a4d2e */
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
    margin-top: 30px;
    margin-bottom: 20px;
}
.checkout-form-container .mainers-header:first-child {
    margin-top: 0;
}
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #555;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #3498db;
    outline: none;
}

.form-group textarea {
    resize: vertical;
}

.payment-options label {
    display: block;
    font-weight: 400;
    margin-bottom: 10px;
}

.error {
    color: #e74c3c;
    font-size: 0.9em;
    display: block;
    margin-top: 5px;
}

/* Podsumowanie */
.order-summary-container {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.summary-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 1.3em;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.95em;
    padding: 5px 0;
    color: #666;
}

.summary-card hr {
    border: none;
    border-top: 1px solid #eee;
    margin: 15px 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    color: #666;
}

.summary-row.total {
    font-size: 1.3em;
    color: #2c3e50;
    padding: 15px 0;
    font-weight: 700;
}

.info-box {
    margin-top: 20px;
    padding: 15px;
    background: #e8f4f8;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.info-box p {
    margin: 0;
    color: #2c3e50;
    font-size: 0.9em;
}

/* Przyciski */
.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-size: 16px;
}

.btn-primary {
    /* Używa zdefiniowanej zmiennej z :root */
    background-color: var(--color-primary); 
    color: var(--color-white);
}

.btn-primary:hover {
    transform: translateY(-2px);
    /* Cień będzie używać koloru tła, aby było spójne, jeśli chcesz */
    box-shadow: 0 4px 12px rgba(26, 77, 46, 0.4); /* Cień dopasowany do #1a4d2e */
}

.btn-block {
    display: block;
    width: 100%;
    margin-bottom: 10px;
}

.submit-btn {
    margin-top: 25px;
    padding: 15px 24px;
    font-size: 1.1em;
}

/* Responsywność */
@media (max-width: 1024px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
    .order-summary-container {
        position: static;
        margin-top: 30px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
</body>
</html>