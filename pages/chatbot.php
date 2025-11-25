<?php
// ===== SERSOLTEC - CHATBOT =====

require_once '../config.php';

$current_lang = getCurrentLanguage();
$message_sent = false;
$error_message = '';

// Obsługa wysyłania wiadomości
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $user_message = sanitize($_POST['message']);
    $user_email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    
    if (empty($user_message)) {
        $error_message = 'Wiadomość nie może być pusta!';
    } else {
        // Wysłanie emaila
        $to = CONTACT_EMAIL;
        $subject = "Nowa wiadomość z Chatbota - " . date('Y-m-d H:i:s');
        $message_body = "
Nowa wiadomość z chatbota\n
Język: " . strtoupper($current_lang) . "\n
Email: " . ($user_email ? $user_email : 'Nie podany') . "\n
IP: " . $_SERVER['REMOTE_ADDR'] . "\n
Data: " . date('Y-m-d H:i:s') . "\n
\n
Wiadomość:\n
" . $user_message;
        
        $headers = "From: " . SMTP_FROM . "\r\n";
        $headers .= "Reply-To: " . ($user_email ? $user_email : CONTACT_EMAIL) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        if (mail($to, $subject, $message_body, $headers)) {
            $message_sent = true;
        } else {
            $error_message = 'Błąd podczas wysyłania wiadomości. Spróbuj ponownie.';
        }
    }
}

// FAQ questions dla danego języka
$faq_items = [
    'faq_1',
    'faq_2',
    'faq_3',
    'faq_4',
    'faq_5'
];
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo t('chat_title'); ?> - Sersoltec FAQ">
    <title><?php echo SITE_NAME; ?> - <?php echo t('chat_title'); ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/logo.svg">
    
    <style>
        .chatbot-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .chat-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .faq-card {
            background: #f9f9f9;
            border-left: 4px solid var(--color-primary);
            padding: 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            max-height: 200px;
            overflow: hidden;
        }
        
        .faq-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left-color: #ffcc00;
        }
        
        .faq-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--color-primary);
            font-size: 1rem;
        }
        
        .faq-card p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .message-form {
            background: #f5f5f5;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-text);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.1);
        }
        
        .btn-submit {
            background-color: #ffcc00;
            color: var(--color-primary);
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background-color: #ffdd33;
            box-shadow: 0 4px 12px rgba(255, 204, 0, 0.3);
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid #4caf50;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid #f44336;
        }
        
        .chat-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .chat-header h1 {
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }
        
        .chat-header p {
            color: #666;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<!-- ===== CHATBOT SECTION ===== -->
<section class="chatbot-container">
    <div class="chat-section">
        <div class="chat-header">
            <h1>💬 <?php echo t('chat_title'); ?></h1>
            <p><?php echo t('chat_faq'); ?></p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($message_sent): ?>
            <div class="success-message">
                ✅ Dziękujemy! Twoja wiadomość została wysłana. Wkrótce się z Tobą skontaktujemy.
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Greeting -->
        <p style="font-size: 1.1rem; margin-bottom: 2rem; text-align: center;">
            <?php echo t('chat_hello'); ?>
        </p>
        
        <!-- FAQ Cards Grid -->
        <div class="faq-grid">
            <?php foreach ($faq_items as $faq_key): ?>
                <div class="faq-card" onclick="this.scrollIntoView({behavior: 'smooth'})">
                    <h3><?php echo t($faq_key . '_question'); ?></h3>
                    <p><?php echo t($faq_key . '_answer'); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Message Form -->
        <div class="message-form">
            <h3><?php echo t('chat_send_message'); ?></h3>
            <form method="POST">
                <div class="form-group">
                    <label for="email"><?php echo t('chat_email'); ?></label>
                    <input type="email" id="email" name="email" placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label for="message"><?php echo t('form_message'); ?> *</label>
                    <textarea id="message" name="message" placeholder="<?php echo t('chat_message_placeholder'); ?>" required></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    ✉️ <?php echo t('chat_send'); ?>
                </button>
            </form>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<!-- Scripts -->
<script src="../assets/js/main.js"></script>

</body>
</html>
