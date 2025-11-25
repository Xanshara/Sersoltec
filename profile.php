<?php
require_once 'config.php';

// Funkcja tłumaczenia
function ut($key) {
    global $translations;
    $lang = getCurrentLanguage();
    return $translations[$lang][$key] ?? $key;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?action=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Obsługa aktualizacji profilu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = ut('msg_all_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = ut('msg_invalid_email');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = ut('msg_email_in_use');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $address, $user_id]);
                
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
                $success = ut('msg_profile_updated');
            }
        } catch (PDOException $e) {
            $error = "Błąd: " . $e->getMessage();
        }
    }
}

// Obsługa zmiany hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    if (empty($current_password) || empty($new_password)) {
        $error = ut('msg_all_required');
    } elseif (strlen($new_password) < 6) {
        $error = ut('msg_password_too_short');
    } elseif ($new_password !== $new_password_confirm) {
        $error = ut('msg_passwords_not_match');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if (password_verify($current_password, $user_data['password'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password_hash, $user_id]);
                
                $success = ut('msg_password_changed');
            } else {
                $error = ut('msg_wrong_password');
            }
        } catch (PDOException $e) {
            $error = "Błąd: " . $e->getMessage();
        }
    }
}

// Pobierz dane użytkownika
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Błąd podczas pobierania danych użytkownika");
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ut('profile_title'); ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #f8f8f8;
            color: #2c2c2c;
            line-height: 1.6;
        }
        
        .top-nav {
            background: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-nav h2 {
            color: #1a4d2e;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 24px;
        }
        
        .nav-links a {
            color: #2c2c2c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-links a:hover {
            color: #1a4d2e;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        .profile-header {
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a4d2e 0%, #0f3d25 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .profile-info h1 {
            color: #1a4d2e;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .profile-info p {
            margin: 6px 0;
            color: #666;
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }
        
        .profile-section {
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .profile-section h2 {
            color: #1a4d2e;
            font-size: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid #1a4d2e;
            padding-bottom: 12px;
            margin-bottom: 24px;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a4d2e;
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.1);
        }
        
        .btn {
            padding: 14px 28px;
            background: #1a4d2e;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0f3d25;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
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
            .top-nav-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="top-nav-content">
            <h2>👤 <?php echo ut('profile_title'); ?></h2>
            <div class="nav-links">
                <a href="index.php">🏠 <?php echo ut('nav_home'); ?></a>
                <a href="order-history.php">📦 <?php echo ut('nav_orders'); ?></a>
                <a href="auth.php?action=logout">🚪 <?php echo ut('nav_logout'); ?></a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <p><strong><?php echo ut('auth_email'); ?>:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong><?php echo ut('profile_registration_date'); ?>:</strong> <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-section">
                <h2><?php echo ut('profile_edit'); ?></h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="first_name"><?php echo ut('auth_first_name'); ?></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name"><?php echo ut('auth_last_name'); ?></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo ut('auth_email'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo ut('profile_phone'); ?></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><?php echo ut('profile_address'); ?></label>
                        <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn"><?php echo ut('profile_save'); ?></button>
                </form>
            </div>
            
            <div class="profile-section">
                <h2><?php echo ut('profile_change_password'); ?></h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password"><?php echo ut('profile_current_password'); ?></label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><?php echo ut('profile_new_password'); ?></label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password_confirm"><?php echo ut('profile_new_password_confirm'); ?></label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn"><?php echo ut('profile_change_password_btn'); ?></button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>