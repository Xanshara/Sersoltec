<?php
// ===== KONTAKT & FORMULARZ - KOMPAKTOWA WERSJA =====

require_once '../config.php';

$current_lang = getCurrentLanguage();
$success = false;
$error = null;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $company = isset($_POST['company']) ? sanitize($_POST['company']) : '';
    $tax_id = isset($_POST['tax_id']) ? sanitize($_POST['tax_id']) : '';
    $subject = isset($_POST['subject']) ? sanitize($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize($_POST['message']) : '';
    
    // Validacja
    if (!$name || !$email || !$message) {
        $error = t('form_error');
    } elseif (!isValidEmail($email)) {
        $error = t('form_invalid_email');
    } else {
        // Zapisz zapytanie w bazie
        $stmt = $pdo->prepare(
            "INSERT INTO inquiries (name, email, phone, company, subject, message, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $name,
            $email,
            $phone,
            $company,
            $subject,
            $message,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Wyślij email
        $to = CONTACT_EMAIL;
        $email_subject = "Nowe zapytanie: " . $subject;
        $email_body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Nowe Zapytanie od Kontaktu</h2>
            
            <strong>Imię i Nazwisko:</strong> " . htmlspecialchars($name) . "<br>
            <strong>Email:</strong> " . htmlspecialchars($email) . "<br>
            <strong>Telefon:</strong> " . htmlspecialchars($phone) . "<br>
            <strong>Firma:</strong> " . htmlspecialchars($company) . "<br>
            <strong>NIP:</strong> " . htmlspecialchars($tax_id) . "<br>
            <strong>Temat:</strong> " . htmlspecialchars($subject) . "<br>
            
            <hr>
            
            <h3>Wiadomość:</h3>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            
            <hr>
            <small>IP: " . $_SERVER['REMOTE_ADDR'] . " | Data: " . date('Y-m-d H:i:s') . "</small>
        </body>
        </html>";
        
        // Headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM . "\r\n";
        
        mail($to, $email_subject, $email_body, $headers);
        
        // Odpowiedź do klienta
        $reply_subject = "Re: " . $subject;
        $reply_body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Dziękujemy za Wiadomość!</h2>
            <p>Odebraliśmy Twoją wiadomość i skontaktujemy się wkrótce.</p>
            
            <p>
                <strong>Sersoltec</strong><br>
                " . CONTACT_EMAIL . "<br>
                www.sersoltec.eu
            </p>
        </body>
        </html>";
        
        mail($email, $reply_subject, $reply_body, $headers);
        
        $success = true;
    }
}

// Pobierz informacje o produkcie jeśli podany
$product = null;
if ($product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('contact_title'); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/chatbot-widget.css">
    <style>
        /* Kompaktowe pola formularza */
        .form-input,
        .form-textarea {
            padding: 0.4rem 0.6rem !important;
            font-size: 0.85rem !important;
            line-height: 1.3 !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            border-color: var(--color-primary) !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1) !important;
        }
        
        .form-textarea {
            min-height: 80px !important;
            max-height: 150px;
            resize: vertical;
        }
        
        .form-label {
            font-size: 0.85rem !important;
            margin-bottom: 0.25rem !important;
            display: block;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 0.8rem !important;
        }
        
        .required {
            color: #dc3545;
            margin-left: 2px;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            max-width: 1200px;
        }
        
        @media (max-width: 968px) {
            .contact-grid {
                grid-template-columns: 1fr !important;
                gap: 2rem;
            }
        }
        
        .btn-submit {
            padding: 0.6rem 1.5rem !important;
            font-size: 0.95rem !important;
        }
        
        .form-success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        .form-error {
            background: #ffebee;
            color: #d32f2f;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .product-info {
            background: var(--color-light-gray);
            padding: 0.75rem 1rem;
            border-radius: 4px;
            border-left: 4px solid var(--color-primary);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<!-- PAGE HEADER -->
<section class="page-header" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); color: white; padding: 2.5rem 1rem;">
    <div class="container" style="text-align: center;">
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;"><?php echo t('contact_title'); ?></h1>
        <p style="font-size: 1rem; opacity: 0.9;"><?php echo t('footer_company'); ?></p>
    </div>
</section>

<!-- CONTENT -->
<section style="padding: 2.5rem 1rem;">
    <div class="container contact-grid">
        
        <!-- Informacje Kontaktowe -->
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;"><?php echo t('contact_info'); ?></h2>
            
            <div style="margin-top: 1.5rem;">
                <h3 style="color: var(--color-primary); margin-bottom: 0.4rem; font-size: 1rem;">Email</h3>
                <a href="mailto:<?php echo CONTACT_EMAIL; ?>" style="color: var(--color-text); font-size: 1rem; text-decoration: none;">
                    <?php echo CONTACT_EMAIL; ?>
                </a>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <h3 style="color: var(--color-primary); margin-bottom: 0.4rem; font-size: 1rem;"><?php echo t('contact_phone'); ?></h3>
                <p style="font-size: 1rem; margin: 0;">
                    <a href="tel:+34666666666" style="color: var(--color-text); text-decoration: none;">+34 666 666 666</a>
                </p>
                <p style="font-size: 0.8rem; color: var(--color-text); opacity: 0.75; margin-top: 0.3rem;">
                    <?php echo t('contact_weekday_hours'); ?>: 09:00 - 18:00 CET<br>
                    <?php echo t('contact_weekend'); ?>: <?php echo t('contact_closed'); ?>
                </p>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <h3 style="color: var(--color-primary); margin-bottom: 0.4rem; font-size: 1rem;"><?php echo t('contact_address'); ?></h3>
                <p style="margin: 0; font-size: 0.9rem;">
                    Sersoltec S.L.<br>
                    Valencia, Spain
                </p>
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--color-light-gray); border-radius: 6px; font-size: 0.85rem;">
                <h4 style="margin-bottom: 0.6rem; font-size: 0.95rem;"><?php echo t('contact_response_header'); ?></h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="padding: 0.2rem 0;">✓ <?php echo t('contact_response_email'); ?></li>
                    <li style="padding: 0.2rem 0;">✓ <?php echo t('contact_response_phone'); ?></li>
                    <li style="padding: 0.2rem 0;">✓ <?php echo t('contact_response_form'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Formularz -->
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem;"><?php echo t('contact_form'); ?></h2>
            
            <?php if ($success): ?>
                <div class="form-success">
                    ✓ <?php echo t('form_success'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="form-error">
                    ✗ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" style="display: grid; gap: 0.8rem;">
                
                <?php if ($product): ?>
                    <div class="product-info">
                        <strong>Produkt:</strong> <?php echo htmlspecialchars($product['name_' . $current_lang]); ?><br>
                        <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label"><?php echo t('form_name'); ?> <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           placeholder="Jan Kowalski">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           placeholder="jan.kowalski@email.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo t('form_phone'); ?></label>
                    <input type="tel" name="phone" class="form-input"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           placeholder="+48 123 456 789">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo t('form_company'); ?></label>
                    <input type="text" name="company" class="form-input"
                           value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>"
                           placeholder="Firma Sp. z o.o.">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo t('form_tax_id'); ?> (NIP/NIF)</label>
                    <input type="text" name="tax_id" class="form-input"
                           value="<?php echo isset($_POST['tax_id']) ? htmlspecialchars($_POST['tax_id']) : ''; ?>"
                           placeholder="1234567890">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo t('form_subject'); ?></label>
                    <input type="text" name="subject" class="form-input"
                           value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                           placeholder="Temat zapytania">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo t('form_message'); ?> <span class="required">*</span></label>
                    <textarea name="message" class="form-textarea" required placeholder="Twoja wiadomość..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-submit" style="width: 100%;">
                    <?php echo t('form_submit'); ?>
                </button>
            </form>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/chatbot-widget.js"></script>
</body>
</html>