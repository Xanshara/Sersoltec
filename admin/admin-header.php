<?php
/**
 * SERSOLTEC - ADMIN HEADER
 * Header dla panelu administratora
 */
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="admin-styles.css">
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <h2>🛠️ Admin</h2>
            <p><?php echo SITE_NAME; ?></p>
        </div>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="products.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' || basename($_SERVER['PHP_SELF']) === 'product-edit.php' ? 'active' : ''; ?>">
                📦 Produkty
            </a>
            <a href="categories.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                📂 Kategorie
            </a>
            <a href="orders.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
                🛒 Zamówienia
            </a>
            <a href="inquiries.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'inquiries.php' ? 'active' : ''; ?>">
                💬 Zapytania
            </a>
            
            <div class="admin-nav-divider"></div>
            
            <a href="shop-users.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'shop-users.php' ? 'active' : ''; ?>">
                👥 Klienci sklepu
            </a>
            <a href="users.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                🔐 Administratorzy
            </a>
            
            <div class="admin-nav-divider"></div>
            
            <a href="settings.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                ⚙️ Ustawienia
            </a>
        </nav>
        
        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <strong><?php echo getAdminName(); ?></strong>
                <small><?php echo $_SESSION['admin_role'] ?? 'admin'; ?></small>
            </div>
            <a href="?logout=1" class="btn-logout">
                🚪 Wyloguj
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
                <h1><?php echo $page_title ?? 'Admin Panel'; ?></h1>
            </div>
            <div class="admin-header-right">
                <a href="../index.php" class="btn btn-outline btn-sm" target="_blank">
                    🌐 Zobacz stronę
                </a>
            </div>
        </header>
        
        <div class="admin-content">
