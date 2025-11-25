<?php
/**
 * SERSOLTEC v2.3c - Email Verification
 * Verify user email with token
 * Multi-language: PL/EN/ES
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once __DIR__ . '/config.php';

// Load translations
if (file_exists(__DIR__ . '/includes/translations.php')) {
    require_once __DIR__ . '/includes/translations.php';
}

// Get language
$lang = $_SESSION['language'] ?? 'pl';

// Translations
$verifyTranslations = [
    'pl' => [
        'verify_email_title' => 'Weryfikacja Email',
        'verifying' => 'Weryfikujemy Twoje konto...',
        'success_title' => 'Email zweryfikowany!',
        'success_desc' => 'Twoje konto zostało aktywowane. Możesz teraz się zalogować.',
        'go_to_login' => 'Przejdź do logowania',
        'error_title' => 'Błąd weryfikacji',
        'invalid_token' => 'Link weryfikacyjny jest nieprawidłowy lub wygasł',
        'already_verified' => 'To konto zostało już zweryfikowane',
        'try_again' => 'Spróbuj ponownie',
        'contact_support' => 'Skontaktuj się z supportem',
    ],
    'en' => [
        'verify_email_title' => 'Email Verification',
        'verifying' => 'Verifying your account...',
        'success_title' => 'Email Verified!',
        'success_desc' => 'Your account has been activated. You can now log in.',
        'go_to_login' => 'Go to Login',
        'error_title' => 'Verification Error',
        'invalid_token' => 'Verification link is invalid or expired',
        'already_verified' => 'This account has already been verified',
        'try_again' => 'Try Again',
        'contact_support' => 'Contact Support',
    ],
    'es' => [
        'verify_email_title' => 'Verificación de Email',
        'verifying' => 'Verificando tu cuenta...',
        'success_title' => '¡Email Verificado!',
        'success_desc' => 'Tu cuenta ha sido activada. Ahora puedes iniciar sesión.',
        'go_to_login' => 'Ir al Inicio',
        'error_title' => 'Error de Verificación',
        'invalid_token' => 'El enlace de verificación es inválido o ha expirado',
        'already_verified' => 'Esta cuenta ya ha sido verificada',
        'try_again' => 'Intentar de Nuevo',
        'contact_support' => 'Contactar Soporte',
    ]
];

// Translation helper
function vt($key, $lang = 'pl') {
    global $verifyTranslations;
    return $verifyTranslations[$lang][$key] ?? $key;
}

$token = $_GET['token'] ?? '';
$success = false;
$error = '';
$alreadyVerified = false;

// Process verification
if (!empty($token)) {
    try {
        // Get database connection
        if (function_exists('db')) {
            $user = db()->fetchOne(
                'SELECT id, email, active, email_verified_at FROM users WHERE verification_token = ?',
                [$token]
            );
        } else {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->prepare('SELECT id, email, active, email_verified_at FROM users WHERE verification_token = ?');
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($user) {
            // Check if already verified
            if ($user['active'] == 1 && !empty($user['email_verified_at'])) {
                $alreadyVerified = true;
                $success = true; // Still show success page
            } else {
                // Verify the account
                if (function_exists('db')) {
                    db()->update('users', [
                        'active' => 1,
                        'verification_token' => null,
                        'email_verified_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$user['id']]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET active = 1, verification_token = NULL, email_verified_at = ? WHERE id = ?');
                    $stmt->execute([date('Y-m-d H:i:s'), $user['id']]);
                }
                
                // Log the verification
                if (function_exists('logger')) {
                    logger()->info('Email verified', ['user_id' => $user['id'], 'email' => $user['email']]);
                }
                
                // Auto-login the user
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                
                // Get full user data for session
                if (function_exists('db')) {
                    $fullUser = db()->fetchOne('SELECT * FROM users WHERE id = ?', [$user['id']]);
                } else {
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                    $stmt->execute([$user['id']]);
                    $fullUser = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                if ($fullUser) {
                    $_SESSION['user_name'] = $fullUser['first_name'] . ' ' . $fullUser['last_name'];
                    $_SESSION['user_role'] = $fullUser['role'] ?? 'user';
                }
                
                $success = true;
            }
        } else {
            $error = vt('invalid_token', $lang);
        }
        
    } catch (Exception $e) {
        error_log('Email verification error: ' . $e->getMessage());
        $error = vt('invalid_token', $lang);
    }
} else {
    $error = vt('invalid_token', $lang);
}

// Auto-redirect after 3 seconds if successful
if ($success && !$alreadyVerified) {
    header('Refresh: 3; url=profile.php');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo vt('verify_email_title', $lang); ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .verify-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 64px 48px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: slideIn 0.3s ease-out;
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
        
        .icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            font-size: 50px;
        }
        
        .icon.success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        
        .icon.error {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        }
        
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        h1 {
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 16px;
            font-weight: 700;
        }
        
        p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #718096;
            margin-top: 16px;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(113, 128, 150, 0.4);
        }
        
        .countdown {
            margin-top: 24px;
            font-size: 14px;
            color: #718096;
        }
        
        @media (max-width: 480px) {
            .verify-container {
                padding: 48px 32px;
            }
            
            h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <?php if ($success): ?>
            <!-- Success -->
            <div class="icon success">✓</div>
            
            <h1><?php echo vt('success_title', $lang); ?></h1>
            
            <p>
                <?php 
                if ($alreadyVerified) {
                    echo vt('already_verified', $lang);
                } else {
                    echo vt('success_desc', $lang);
                }
                ?>
            </p>
            
            <?php if (!$alreadyVerified): ?>
                <p class="countdown">
                    <?php 
                    $redirectText = [
                        'pl' => 'Przekierowanie za 3 sekundy...',
                        'en' => 'Redirecting in 3 seconds...',
                        'es' => 'Redirigiendo en 3 segundos...'
                    ];
                    echo $redirectText[$lang];
                    ?>
                </p>
            <?php endif; ?>
            
            <a href="profile.php" class="btn">
                <?php echo vt('go_to_login', $lang); ?> →
            </a>
            
        <?php else: ?>
            <!-- Error -->
            <div class="icon error">✕</div>
            
            <h1><?php echo vt('error_title', $lang); ?></h1>
            
            <p><?php echo $error; ?></p>
            
            <a href="auth.php?action=register" class="btn">
                <?php echo vt('try_again', $lang); ?>
            </a>
            
            <br><br>
            
            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-secondary">
                <?php echo vt('contact_support', $lang); ?>
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
