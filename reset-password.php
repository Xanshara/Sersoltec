<?php
/**
 * SERSOLTEC v2.3c - Reset Password
 * Set new password with token verification
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
$resetTranslations = [
    'pl' => [
        'reset_password_title' => 'Zresetuj hasło',
        'reset_password_subtitle' => 'Wprowadź nowe hasło dla swojego konta',
        'new_password_label' => 'Nowe hasło',
        'new_password_placeholder' => 'Minimum 8 znaków',
        'confirm_password_label' => 'Potwierdź hasło',
        'confirm_password_placeholder' => 'Wpisz hasło ponownie',
        'reset_password_btn' => 'Ustaw nowe hasło',
        'password_changed' => 'Hasło zostało zmienione!',
        'password_changed_desc' => 'Możesz teraz zalogować się używając nowego hasła.',
        'go_to_login' => 'Przejdź do logowania',
        'invalid_token' => 'Link resetujący jest nieprawidłowy lub wygasł',
        'token_expired' => 'Link resetujący wygasł. Wygeneruj nowy.',
        'password_required' => 'Hasło jest wymagane',
        'password_too_short' => 'Hasło musi mieć minimum 8 znaków',
        'passwords_not_match' => 'Hasła nie są identyczne',
        'error_occurred' => 'Wystąpił błąd. Spróbuj ponownie.',
        'request_new_link' => 'Wygeneruj nowy link',
    ],
    'en' => [
        'reset_password_title' => 'Reset Password',
        'reset_password_subtitle' => 'Enter a new password for your account',
        'new_password_label' => 'New Password',
        'new_password_placeholder' => 'Minimum 8 characters',
        'confirm_password_label' => 'Confirm Password',
        'confirm_password_placeholder' => 'Re-enter password',
        'reset_password_btn' => 'Set New Password',
        'password_changed' => 'Password Changed!',
        'password_changed_desc' => 'You can now log in using your new password.',
        'go_to_login' => 'Go to Login',
        'invalid_token' => 'Reset link is invalid or expired',
        'token_expired' => 'Reset link has expired. Request a new one.',
        'password_required' => 'Password is required',
        'password_too_short' => 'Password must be at least 8 characters',
        'passwords_not_match' => 'Passwords do not match',
        'error_occurred' => 'An error occurred. Please try again.',
        'request_new_link' => 'Request New Link',
    ],
    'es' => [
        'reset_password_title' => 'Restablecer Contraseña',
        'reset_password_subtitle' => 'Ingresa una nueva contraseña para tu cuenta',
        'new_password_label' => 'Nueva Contraseña',
        'new_password_placeholder' => 'Mínimo 8 caracteres',
        'confirm_password_label' => 'Confirmar Contraseña',
        'confirm_password_placeholder' => 'Vuelve a ingresar',
        'reset_password_btn' => 'Establecer Nueva Contraseña',
        'password_changed' => '¡Contraseña Cambiada!',
        'password_changed_desc' => 'Ahora puedes iniciar sesión con tu nueva contraseña.',
        'go_to_login' => 'Ir al Inicio',
        'invalid_token' => 'El enlace es inválido o ha expirado',
        'token_expired' => 'El enlace ha expirado. Solicita uno nuevo.',
        'password_required' => 'La contraseña es obligatoria',
        'password_too_short' => 'La contraseña debe tener al menos 8 caracteres',
        'passwords_not_match' => 'Las contraseñas no coinciden',
        'error_occurred' => 'Ocurrió un error. Inténtalo de nuevo.',
        'request_new_link' => 'Solicitar Nuevo Enlace',
    ]
];

// Translation helper
function rpt($key, $lang = 'pl') {
    global $resetTranslations;
    return $resetTranslations[$lang][$key] ?? $key;
}

$token = $_GET['token'] ?? '';
$success = false;
$error = '';
$validToken = false;

// Verify token
if (!empty($token)) {
    try {
        if (function_exists('db')) {
            $reset = db()->fetchOne(
                'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()',
                [$token]
            );
        } else {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()');
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($reset) {
            $validToken = true;
        } else {
            $error = rpt('invalid_token', $lang);
        }
    } catch (Exception $e) {
        error_log('Token verification error: ' . $e->getMessage());
        $error = rpt('error_occurred', $lang);
    }
} else {
    $error = rpt('invalid_token', $lang);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    
    // CSRF verification
    if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = rpt('error_occurred', $lang);
    } else {
        
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($password)) {
            $error = rpt('password_required', $lang);
        } elseif (strlen($password) < 8) {
            $error = rpt('password_too_short', $lang);
        } elseif ($password !== $confirmPassword) {
            $error = rpt('passwords_not_match', $lang);
        } else {
            
            try {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                if (function_exists('db')) {
                    // Update password
                    db()->update('users', ['password' => $hashedPassword], 'email = ?', [$reset['email']]);
                    // Mark token as used
                    db()->update('password_resets', ['used' => 1, 'used_at' => date('Y-m-d H:i:s')], 'token = ?', [$token]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
                    $stmt->execute([$hashedPassword, $reset['email']]);
                    $stmt = $pdo->prepare('UPDATE password_resets SET used = 1, used_at = ? WHERE token = ?');
                    $stmt->execute([date('Y-m-d H:i:s'), $token]);
                }
                
                // Log the password change
                if (function_exists('logger')) {
                    logger()->info('Password reset completed', ['email' => $reset['email']]);
                }
                
                $success = true;
                
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $error = rpt('error_occurred', $lang);
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
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo rpt('reset_password_title', $lang); ?> - <?php echo SITE_NAME; ?></title>
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
        
        .reset-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            max-width: 480px;
            width: 100%;
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
        
        .reset-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .reset-icon {
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
        
        .reset-header h1 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .reset-header p {
            color: #718096;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            color: #718096;
        }
        
        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            background: #e53e3e;
            transition: all 0.3s;
        }
        
        .strength-weak { background: #e53e3e; width: 33%; }
        .strength-medium { background: #ed8936; width: 66%; }
        .strength-strong { background: #48bb78; width: 100%; }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
            margin-top: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #718096;
            margin-top: 16px;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(113, 128, 150, 0.4);
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
        
        .alert h3 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        @media (max-width: 480px) {
            .reset-container {
                padding: 32px 24px;
            }
            
            .reset-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <?php if ($success): ?>
            <!-- Success Message -->
            <div class="reset-header">
                <div class="reset-icon">✅</div>
                <h1><?php echo rpt('password_changed', $lang); ?></h1>
            </div>
            
            <div class="alert alert-success">
                <p><?php echo rpt('password_changed_desc', $lang); ?></p>
            </div>
            
            <a href="auth.php?action=login" class="btn" style="text-decoration: none; display: block; text-align: center;">
                <?php echo rpt('go_to_login', $lang); ?> →
            </a>
            
        <?php elseif (!$validToken): ?>
            <!-- Invalid Token -->
            <div class="reset-header">
                <div class="reset-icon">❌</div>
                <h1><?php echo rpt('invalid_token', $lang); ?></h1>
            </div>
            
            <div class="alert alert-error">
                <p><?php echo $error; ?></p>
            </div>
            
            <a href="forgot-password.php" class="btn" style="text-decoration: none; display: block; text-align: center;">
                <?php echo rpt('request_new_link', $lang); ?>
            </a>
            
        <?php else: ?>
            <!-- Reset Form -->
            <div class="reset-header">
                <div class="reset-icon">🔑</div>
                <h1><?php echo rpt('reset_password_title', $lang); ?></h1>
                <p><?php echo rpt('reset_password_subtitle', $lang); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="password">
                        <?php echo rpt('new_password_label', $lang); ?>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        placeholder="<?php echo rpt('new_password_placeholder', $lang); ?>"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthBar"></div>
                        </div>
                        <span id="strengthText"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        <?php echo rpt('confirm_password_label', $lang); ?>
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input"
                        placeholder="<?php echo rpt('confirm_password_placeholder', $lang); ?>"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>
                
                <button type="submit" class="btn">
                    <?php echo rpt('reset_password_btn', $lang); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                
                strengthBar.className = 'strength-bar-fill';
                
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                    strengthText.textContent = '<?php echo $lang === "pl" ? "Słabe" : ($lang === "es" ? "Débil" : "Weak"); ?>';
                } else if (strength <= 4) {
                    strengthBar.classList.add('strength-medium');
                    strengthText.textContent = '<?php echo $lang === "pl" ? "Średnie" : ($lang === "es" ? "Medio" : "Medium"); ?>';
                } else {
                    strengthBar.classList.add('strength-strong');
                    strengthText.textContent = '<?php echo $lang === "pl" ? "Silne" : ($lang === "es" ? "Fuerte" : "Strong"); ?>';
                }
            });
        }
        
        // Form validation
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('<?php echo rpt('passwords_not_match', $lang); ?>');
                    return false;
                }
            });
        }
    </script>
</body>
</html>
