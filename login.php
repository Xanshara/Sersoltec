<?php
/**
 * SERSOLTEC - ADMIN LOGIN
 * Panel logowania administratora
 */

// Określ ścieżkę do config.php
if (file_exists('../config.php')) {
    require_once '../config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
} else {
    die('Nie można znaleźć pliku config.php');
}

// Jeśli już zalogowany, redirect do dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Obsługa logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Proszę wypełnić wszystkie pola';
    } else {
        // Sprawdź w bazie
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Logowanie udane
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Nieprawidłowa nazwa użytkownika lub hasło';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --color-primary: #1a4d2e;
            --color-primary-light: #2e7d32;
            --color-gray: #e0e0e0;
            --color-text: #2c2c2c;
        }
        
        body {
            background: linear-gradient(135deg, #1a4d2e 0%, #0f3d25 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--color-primary);
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .login-header p {
            color: var(--color-text);
            opacity: 0.7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--color-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(26, 77, 46, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: var(--color-primary-light);
            box-shadow: 0 4px 12px rgba(26, 77, 46, 0.3);
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Admin Panel</h1>
            <p><?php echo SITE_NAME; ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Nazwa użytkownika</label>
                <input type="text" id="username" name="username" required autofocus 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">
                Zaloguj się
            </button>
        </form>
        
        <div class="info-box">
            <strong>Domyślne dane logowania:</strong><br>
            Login: <code>admin</code><br>
            Hasło: <code>admin123</code><br>
            <small>⚠️ Zmień hasło po pierwszym logowaniu!</small>
        </div>
        
        <div class="back-link">
            <a href="../index.php">← Powrót do strony głównej</a>
        </div>
    </div>
</body>
</html>
