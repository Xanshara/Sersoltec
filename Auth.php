<?php
require_once 'config.php';

// Funkcja tłumaczenia dla systemu użytkowników
function ut($key) {
    global $translations;
    $lang = getCurrentLanguage();
    return $translations[$lang][$key] ?? $key;
}

$action = $_GET['action'] ?? 'login';
$error = '';
$success = '';

try {
    $pdo->query("SELECT 1 FROM users LIMIT 1");
} catch (PDOException $e) {
    die('Tabela users nie istnieje. Uruchom najpierw migrację USER-MIGRATION.sql');
}

// Obsługa rejestracji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = ut('msg_all_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = ut('msg_invalid_email');
    } elseif (strlen($password) < 6) {
        $error = ut('msg_password_too_short');
    } elseif ($password !== $password_confirm) {
        $error = ut('msg_passwords_not_match');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = ut('msg_email_exists');
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");
                $stmt->execute([$first_name, $last_name, $email, $password_hash]);
                
                $success = ut('msg_registration_success');
                $action = 'login';
            }
        } catch (PDOException $e) {
            $error = "Błąd: " . $e->getMessage();
        }
    }
}

// Obsługa logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = ut('msg_all_required');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: profile.php');
                    }
                    exit;
                } else {
                    $error = ut('msg_invalid_credentials');
                }
            } else {
                $error = ut('msg_invalid_credentials');
            }
        } catch (PDOException $e) {
            $error = "Błąd: " . $e->getMessage();
        }
    }
}

if ($action === 'logout') {
    session_destroy();
    header('Location: auth.php?action=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'register' ? ut('auth_register') : ut('auth_login'); ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #f8f8f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
            color: #2c2c2c;
        }
        
        .auth-wrapper {
            width: 100%;
            max-width: 500px;
        }
        
        .auth-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.16);
            padding: 48px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .auth-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a4d2e;
            margin-bottom: 12px;
        }
        
        .auth-header .back-link {
            color: #1a4d2e;
            text-decoration: none;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }
        
        .auth-header .back-link:hover {
            opacity: 0.7;
        }
        
        .auth-tabs {
            display: flex;
            margin-bottom: 48px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .auth-tab {
            flex: 1;
            padding: 16px;
            text-align: center;
            color: #2c2c2c;
            text-decoration: none;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            font-weight: 500;
        }
        
        .auth-tab.active {
            color: #1a4d2e;
            border-bottom-color: #1a4d2e;
        }
        
        .auth-tab:hover {
            color: #1a4d2e;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c2c2c;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1a4d2e;
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px 24px;
            background: #1a4d2e;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: #0f3d25;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border-color: #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #2a7d2e;
            border-color: #1a4d2e;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 32px 24px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .auth-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-header">
                <h1><?php echo $action === 'register' ? ut('auth_register') : ut('auth_login'); ?></h1>
                <a href="index.php" class="back-link"><?php echo ut('auth_back_home'); ?></a>
            </div>
            
            <div class="auth-tabs">
                <a href="auth.php?action=login" class="auth-tab <?php echo $action === 'login' ? 'active' : ''; ?>">
                    <?php echo ut('auth_login'); ?>
                </a>
                <a href="auth.php?action=register" class="auth-tab <?php echo $action === 'register' ? 'active' : ''; ?>">
                    <?php echo ut('auth_register'); ?>
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'login'): ?>
                <form method="POST" action="auth.php?action=login">
                    <div class="form-group">
                        <label for="email"><?php echo ut('auth_email'); ?></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo ut('auth_password'); ?></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn-submit"><?php echo ut('auth_login_btn'); ?></button>
                </form>
                
            <?php else: ?>
                <form method="POST" action="auth.php?action=register">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name"><?php echo ut('auth_first_name'); ?></label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name"><?php echo ut('auth_last_name'); ?></label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo ut('auth_email'); ?></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo ut('auth_password_min'); ?></label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm"><?php echo ut('auth_password_confirm'); ?></label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn-submit"><?php echo ut('auth_register_btn'); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>