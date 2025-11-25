<?php
/**
 * SERSOLTEC - Forgot Password (Multi-Language)
 * Supports: PL, EN, ES
 */

// Start session FIRST
session_start();

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'sersoltec_db');
define('DB_USER', 'sersoltec');
define('DB_PASS', 'm1vg!M2Zj*3BY.QX');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Language - check GET parameter first, then session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pl', 'en', 'es'])) {
    $_SESSION['language'] = $_GET['lang'];
}
$lang = $_SESSION['language'] ?? 'pl';

// Translations
$text = [
    'pl' => [
        'title' => 'Zapomniałeś hasła?',
        'subtitle' => 'Podaj adres email, a wyślemy link do resetowania hasła',
        'email' => 'Adres email',
        'placeholder' => 'twoj@email.com',
        'send' => 'Wyślij link resetujący',
        'back' => 'Powrót do logowania',
        'success_title' => 'Link został wysłany!',
        'success_msg' => 'Sprawdź swoją skrzynkę email. Jeśli konto istnieje, otrzymasz link do resetowania hasła.',
        'email_required' => 'Adres email jest wymagany',
        'invalid_email' => 'Nieprawidłowy format email',
        'error' => 'Wystąpił błąd. Spróbuj ponownie.'
    ],
    'en' => [
        'title' => 'Forgot Password?',
        'subtitle' => 'Enter your email address and we\'ll send you a password reset link',
        'email' => 'Email Address',
        'placeholder' => 'your@email.com',
        'send' => 'Send Reset Link',
        'back' => 'Back to Login',
        'success_title' => 'Reset Link Sent!',
        'success_msg' => 'Check your email inbox. If the account exists, you will receive a password reset link.',
        'email_required' => 'Email address is required',
        'invalid_email' => 'Invalid email format',
        'error' => 'An error occurred. Please try again.'
    ],
    'es' => [
        'title' => '¿Olvidaste tu contraseña?',
        'subtitle' => 'Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña',
        'email' => 'Correo Electrónico',
        'placeholder' => 'tu@correo.com',
        'send' => 'Enviar Enlace',
        'back' => 'Volver al Inicio',
        'success_title' => '¡Enlace Enviado!',
        'success_msg' => 'Revisa tu bandeja de entrada. Si la cuenta existe, recibirás un enlace para restablecer tu contraseña.',
        'email_required' => 'El correo electrónico es obligatorio',
        'invalid_email' => 'Formato de correo inválido',
        'error' => 'Ocurrió un error. Inténtalo de nuevo.'
    ]
];

$success = false;
$error = '';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF check
    if (!isset($_POST['_token']) || $_POST['_token'] !== $_SESSION['csrf_token']) {
        $error = $text[$lang]['error'];
    } else {
        
        $email = trim($_POST['email'] ?? '');
        
        // Validate
        if (empty($email)) {
            $error = $text[$lang]['email_required'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $text[$lang]['invalid_email'];
        } else {
            
            try {
                // Connect to database
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? AND active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Generate token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600);
                    
                    // Delete old tokens
                    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                    
                    // Insert new token
                    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$email, $token, $expires]);
                    
                    // Build reset link
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                    $link = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/reset-password.php?token=' . $token;
                    
                    // Email subject/message based on language
                    $subjects = [
                        'pl' => 'Resetowanie hasła - SERSOLTEC',
                        'en' => 'Password Reset - SERSOLTEC',
                        'es' => 'Restablecer Contraseña - SERSOLTEC'
                    ];
                    
                    $messages = [
                        'pl' => "Witaj " . $user['first_name'] . ",\n\nKliknij poniższy link aby zresetować hasło:\n\n" . $link . "\n\nLink jest ważny przez 1 godzinę.\n\nJeśli to nie Ty wysłałeś prośbę, zignoruj tę wiadomość.\n\nPozdrawiamy,\nZespół SERSOLTEC",
                        'en' => "Hello " . $user['first_name'] . ",\n\nClick the link below to reset your password:\n\n" . $link . "\n\nThis link is valid for 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nSERSOLTEC Team",
                        'es' => "Hola " . $user['first_name'] . ",\n\nHaz clic en el siguiente enlace para restablecer tu contraseña:\n\n" . $link . "\n\nEste enlace es válido por 1 hora.\n\nSi no solicitaste esto, ignora este mensaje.\n\nSaludos,\nEquipo SERSOLTEC"
                    ];
                    
                    $subject = $subjects[$lang];
                    $message = $messages[$lang];
                    $headers = "From: noreply@sersoltec.eu\r\nContent-Type: text/plain; charset=UTF-8";
                    
                    @mail($email, $subject, $message, $headers);
                }
                
                // Always show success (don't reveal if email exists)
                $success = true;
                
            } catch (PDOException $e) {
                error_log("Forgot password error: " . $e->getMessage());
                $error = $text[$lang]['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $text[$lang]['title']; ?> - SERSOLTEC</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 48px;
            max-width: 480px;
            width: 100%;
            animation: slideIn 0.3s;
            position: relative;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .lang-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
        }
        .lang-btn {
            padding: 6px 12px;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            color: #4a5568;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .lang-btn:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }
        .lang-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
        }
        h1 {
            text-align: center;
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: #718096;
            font-size: 15px;
            margin-bottom: 32px;
            line-height: 1.5;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 24px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .back:hover {
            color: #764ba2;
        }
        @media (max-width: 480px) {
            .box { padding: 32px 24px; }
            h1 { font-size: 24px; }
            .lang-switcher {
                position: static;
                justify-content: center;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="box">
        <!-- Language Switcher -->
        <div class="lang-switcher">
            <a href="?lang=pl" class="lang-btn <?php echo $lang === 'pl' ? 'active' : ''; ?>">PL</a>
            <a href="?lang=en" class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=es" class="lang-btn <?php echo $lang === 'es' ? 'active' : ''; ?>">ES</a>
        </div>
        
        <?php if ($success): ?>
            <div class="icon">✅</div>
            <h1><?php echo $text[$lang]['success_title']; ?></h1>
            <div class="alert alert-success">
                <?php echo $text[$lang]['success_msg']; ?>
            </div>
            <a href="auth.php" class="back">← <?php echo $text[$lang]['back']; ?></a>
        <?php else: ?>
            <div class="icon">🔐</div>
            <h1><?php echo $text[$lang]['title']; ?></h1>
            <p class="subtitle"><?php echo $text[$lang]['subtitle']; ?></p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label><?php echo $text[$lang]['email']; ?></label>
                    <input type="email" name="email" placeholder="<?php echo $text[$lang]['placeholder']; ?>" required autofocus>
                </div>
                <button type="submit"><?php echo $text[$lang]['send']; ?></button>
            </form>
            
            <a href="auth.php" class="back">← <?php echo $text[$lang]['back']; ?></a>
        <?php endif; ?>
    </div>
</body>
</html>