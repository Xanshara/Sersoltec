<?php
// ULTRA SIMPLE RESET PASSWORD - TIMEZONE FIXED
session_start();

// FIX TIMEZONE - Poland is UTC+1
date_default_timezone_set('Europe/Warsaw');

// DB
try {
    $pdo = new PDO("mysql:host=localhost;dbname=sersoltec_db", "sersoltec", "m1vg!M2Zj*3BY.QX");
    $pdo->exec("SET time_zone = '+01:00'");
} catch (PDOException $e) {
    die("Database error");
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$success = false;
$error = '';
$validToken = false;
$reset = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
        $stmt->execute(array($token));
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            $validToken = true;
        } else {
            // Check why token failed (used, expired, or non-existent) - logic removed as it was only for debug
            $error = 'Link resetujacy jest nieprawidlowy lub wygasl';
        }
    } catch (PDOException $e) {
        $error = 'Blad bazy danych';
    }
} else {
    $error = 'Brak tokena';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    
    if ($_POST['_token'] !== $_SESSION['csrf_token']) {
        $error = 'Blad bezpieczenstwa';
    } else {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        if (strlen($password) < 8) {
            $error = 'Haslo musi miec min 8 znakow';
        } elseif ($password !== $confirm) {
            $error = 'Hasla nie pasuja';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute(array($hash, $reset['email']));
            
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1, used_at = NOW() WHERE token = ?");
            $stmt->execute(array($token));
            
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body { 
            font-family: Arial; 
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
            min-height: 100vh; 
            padding: 20px; 
        }
        .box { 
            background: white; 
            border-radius: 16px; 
            padding: 40px; 
            max-width: 600px; 
            margin: 0 auto; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        .icon { 
            width: 60px; 
            height: 60px; 
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            margin: 0 auto 20px; 
            font-size: 30px; 
            color: white;
        }
        h1 { 
            text-align: center; 
            color: #2d3748; 
            margin-bottom: 20px; 
        }
        .alert { 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
        }
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
        }
        .alert-error { 
            background: #f8d7da; 
            color: #721c24; 
        }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e2e8f0; 
            border-radius: 6px; 
            margin-bottom: 15px; 
            font-size: 14px; 
            box-sizing: border-box; 
        }
        input:focus { 
            border-color: #1a4d2e; /* ZMIENIONO NA ZIELEŃ */
            outline: none; 
        }
        button { 
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #2d7a4a 0%, #1a4d2e 100%); /* ZMIENIONO NA ZIELEŃ */
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: opacity 0.2s;
        }
        button:hover { 
            opacity: 0.9; 
        }
        a { 
            display: block; 
            text-align: center; 
            margin-top: 15px; 
            color: #1a4d2e; /* ZMIENIONO NA ZIELEŃ */
            text-decoration: none; 
            transition: color 0.2s;
        }
        a:hover {
             color: #0f3d25; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 600; 
            color: #2d3748; 
        }
    </style>
</head>
<body>
    <div class="box">
        <?php if ($success): ?>
            <div class="icon">✅</div>
            <h1>Haslo zmienione!</h1>
            <div class="alert alert-success">Mozesz sie teraz zalogowac nowym haslem.</div>
            <a href="auth.php">Przejdz do logowania</a>
        <?php elseif (!$validToken): ?>
            <div class="icon">❌</div>
            <h1>Nieprawidlowy link</h1>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <a href="forgot-password.php">Wygeneruj nowy link</a>
        <?php else: ?>
            <div class="icon">🔑</div>
            <h1>Nowe haslo</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <label>Nowe haslo:</label>
                <input type="password" name="password" placeholder="Min. 8 znakow" required minlength="8">
                <label>Potwierdz haslo:</label>
                <input type="password" name="confirm_password" placeholder="Wpisz ponownie" required minlength="8">
                <button type="submit">Ustaw nowe haslo</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>