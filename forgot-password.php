<?php
/**
 * SERSOLTEC - Forgot Password v2.3c
 * Multi-language (PL/EN/ES) | SMTP Email | Timezone Fixed
 */

// FIX TIMEZONE - Poland is UTC+1
date_default_timezone_set('Europe/Warsaw');

// Start session
session_start();

// Load PHPMailer
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'sersoltec_db');
define('DB_USER', 'sersoltec');
define('DB_PASS', 'm1vg!M2Zj*3BY.QX');

// SMTP Configuration
define('SMTP_HOST', 'ssl0.ovh.net');
define('SMTP_PORT', 465);
define('SMTP_USER', 'noreply@sersoltec.eu');
define('SMTP_PASS', 'Grunwaldzka50?');

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Language
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

/**
 * Send email via PHPMailer SMTP
 */
function sendEmailSMTP($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom(SMTP_USER, 'SERSOLTEC');
        $mail->addAddress($to);
        
        $mail->Subject = $subject;
        $mail->Body     = $body;
        $mail->isHTML(false);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['_token']) || $_POST['_token'] !== $_SESSION['csrf_token']) {
        $error = $text[$lang]['error'];
    } else {
        
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = $text[$lang]['email_required'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $text[$lang]['invalid_email'];
        } else {
            
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Set MySQL timezone
                $pdo->exec("SET time_zone = '+01:00'");
                
                $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? AND active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Generate token
                    $token = bin2hex(random_bytes(32));
                    // ZMIENIONO: 3600 sekund (1h) na 86400 sekund (24h)
                    $expires = date('Y-m-d H:i:s', time() + 86400); 
                    
                    // Delete old tokens
                    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                    
                    // Insert new token
                    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$email, $token, $expires]);
                    
                    // Build reset link
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $link = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/reset-password.php?token=' . $token;
                    
                    // Email subject/message
                    $subjects = [
                        'pl' => 'Resetowanie hasla - SERSOLTEC',
                        'en' => 'Password Reset - SERSOLTEC',
                        'es' => 'Restablecer Contrasena - SERSOLTEC'
                    ];
                    
                    $messages = [
                        // ZMIENIONO: z '1 godzine' na '24 godziny'
                        'pl' => "Witaj " . $user['first_name'] . ",\n\nKliknij ponizszy link aby zresetowac haslo:\n\n" . $link . "\n\nLink jest wazny przez 24 godziny.\n\nJesli to nie Ty wyslales prosbe, zignoruj te wiadomosc.\n\nPozdrawiamy,\nZespol SERSOLTEC",
                        // ZMIENIONO: z '1 hour' na '24 hours'
                        'en' => "Hello " . $user['first_name'] . ",\n\nClick the link below to reset your password:\n\n" . $link . "\n\nThis link is valid for 24 hours.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nSERSOLTEC Team",
                        // ZMIENIONO: z '1 hora' na '24 horas'
                        'es' => "Hola " . $user['first_name'] . ",\n\nHaz clic en el siguiente enlace para restablecer tu contrasena:\n\n" . $link . "\n\nEste enlace es valido por 24 horas.\n\nSi no solicitaste esto, ignora este mensaje.\n\nSaludos,\nEquipo SERSOLTEC"
                    ];
                    
                    $subject = $subjects[$lang];
                    $message = $messages[$lang];
                    
                    sendEmailSMTP($email, $subject, $message);
                }
                
                // Always show success
                $success = true;
                
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error = $text[$lang]['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($text[$lang]['title']); ?> - SERSOLTEC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 48px;
            max-width: 480px;
            width: 100%;
            animation: slideIn 0.3s ease-out;
            position: relative;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            background: #f8f8f8;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            color: #2c2c2c;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .lang-btn:hover {
            background: #eeeeee;
            border-color: #1a4d2e; /* ZMIENIONO NA ZIELEŃ */
        }
        
        .lang-btn.active {
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
            color: white;
            border-color: #1a4d2e; /* ZMIENIONO NA ZIELEŃ */
        }
        
        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
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
            color: #4a5568;
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
            transition: all 0.2s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #1a4d2e; /* ZMIENIONO NA ZIELEŃ */
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.1); /* ZMIENIONO NA ZIELEŃ */
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(26, 77, 46, 0.4); /* ZMIENIONO NA ZIELEŃ */
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .back {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #1a4d2e; /* ZMIENIONO NA ZIELEŃ */
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .back:hover {
            color: #0f3d25; /* CIEMNA ZIELEŃ */
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 32px 24px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .lang-switcher {
                position: static;
                justify-content: center;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="lang-switcher">
            <a href="?lang=pl" class="lang-btn <?php echo $lang === 'pl' ? 'active' : ''; ?>">PL</a>
            <a href="?lang=en" class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=es" class="lang-btn <?php echo $lang === 'es' ? 'active' : ''; ?>">ES</a>
        </div>
        
        <?php if ($success): ?>
            <div class="icon">✅</div>
            <h1><?php echo htmlspecialchars($text[$lang]['success_title']); ?></h1>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($text[$lang]['success_msg']); ?>
            </div>
            <a href="auth.php" class="back">← <?php echo htmlspecialchars($text[$lang]['back']); ?></a>
        <?php else: ?>
            <div class="icon">🔐</div>
            <h1><?php echo htmlspecialchars($text[$lang]['title']); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($text[$lang]['subtitle']); ?></p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars($text[$lang]['email']); ?></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="<?php echo htmlspecialchars($text[$lang]['placeholder']); ?>" 
                        required 
                        autofocus
                        autocomplete="email"
                    >
                </div>
                <button type="submit"><?php echo htmlspecialchars($text[$lang]['send']); ?></button>
            </form>
            
            <a href="auth.php" class="back">← <?php echo htmlspecialchars($text[$lang]['back']); ?></a>
        <?php endif; ?>
    </div>
</body>
</html>