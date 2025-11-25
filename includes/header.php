<?php
// ===== HEADER Z NAWIGACJĄ =====

global $AVAILABLE_LANGUAGES;
$current_lang = getCurrentLanguage();
?>

<header>
    <div class="header-content">
        <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../index.php' : 'index.php'; ?>" class="logo">
            <img src="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../assets/images/logo.svg' : 'assets/images/logo.svg'; ?>" alt="<?php echo SITE_NAME; ?>">
          
        </a>
        
        <button class="menu-toggle" id="menuToggle" aria-label="Menu">☰</button>
        
        <nav id="mainNav">
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../index.php' : 'index.php'; ?>"><?php echo t('nav_home'); ?></a>
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'products.php' : 'pages/products.php'; ?>"><?php echo t('nav_products'); ?></a>
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'calculator.php' : 'pages/calculator.php'; ?>"><?php echo t('nav_calculator'); ?></a>
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'about.php' : 'pages/about.php'; ?>"><?php echo t('nav_about'); ?></a>
            
            <!-- ROZWIJANE MENU POMOC -->
            <div class="dropdown-menu">
                <button class="dropdown-btn" onclick="toggleDropdown(event, 'helpDropdown')">
                    💬 <?php echo t('nav_help'); ?> <span class="arrow-down">▼</span>
                </button>
                <div class="dropdown-content" id="helpDropdown">
                    <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'chatbot.php' : 'pages/chatbot.php'; ?>">
                        <?php echo t('nav_faq'); ?>
                    </a>
                    <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'contact.php' : 'pages/contact.php'; ?>">
                        <?php echo t('nav_contact'); ?>
                    </a>
                </div>
            </div>
            
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../cart.php' : 'cart.php'; ?>" class="cart-link" title="<?php echo t('cart'); ?>">
                🛒
                <?php 
                $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                if ($cart_count > 0): 
                ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- WISHLIST HEART - TYLKO DLA ZALOGOWANYCH -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../wishlist.php' : 'wishlist.php'; ?>" class="wishlist-link" title="<?php echo t('wishlist_my_wishlist', $current_lang); ?>">
                    <i class="fa fa-heart"></i>
                    <span class="wishlist-badge" style="display: none;">0</span>
                </a>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <button class="user-menu-btn" onclick="toggleUserMenu()">
                        <span class="user-avatar user-avatar-primary"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
                        <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'Użytkownik')[0]); ?></span>
                        <span class="arrow">▼</span>
                    </button>
<div class="user-dropdown" id="userDropdown">
    <?php 
    // Usunięto niepotrzebną, niespójną logikę tłumaczeń (translate(), $translations_pl),
    // ponieważ funkcja t() jest używana w reszcie header.php
    
    // Określenie ścieżki (czy jesteśmy w folderze /pages/)
    $is_in_pages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
    ?>

    <a href="<?php echo $is_in_pages ? '../profile.php' : 'profile.php'; ?>">
        👤 <?php echo t('nav_profile'); ?>
    </a>
    <a href="<?php echo $is_in_pages ? '../order-history.php' : 'order-history.php'; ?>">
        📦 <?php echo t('nav_orders'); ?>
    </a>
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="<?php echo $is_in_pages ? '../admin/dashboard.php' : 'admin/dashboard.php'; ?>">
            ⚙️ <?php echo t('nav_admin'); ?>
        </a>
    <?php endif; ?>
    <hr style="margin: 5px 0; border: none; border-top: 1px solid #e0e0e0;">
    <a href="<?php echo $is_in_pages ? '../auth.php?action=logout' : 'auth.php?action=logout'; ?>">
        🚪 <?php echo t('nav_logout'); ?>
    </a>
</div>
                </div>
            <?php else: ?>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../auth.php?action=login' : 'auth.php?action=login'; ?>" class="auth-link">
                    🔐 Zaloguj
                </a>
            <?php endif; ?>
            
            <div class="language-switcher">
                <?php 
                // Pobierz bieżący URL bez ?lang parametru
                $current_url = $_SERVER['REQUEST_URI'];
                // Usuń ?lang=xx z URL'a jeśli istnieje
                $current_url = preg_replace('/(\?|&)lang=[a-z]{2}/', '', $current_url);
                // Sprawdź czy URL zawiera już ? (inne parametry)
                $separator = (strpos($current_url, '?') !== false) ? '&' : '?';
                ?>
                <?php foreach ($AVAILABLE_LANGUAGES as $lang_code => $lang_name): ?>
                    <?php if ($lang_code === $current_lang): ?>
                        <button class="lang-btn active" disabled title="<?php echo $lang_name; ?>">
                            <?php echo strtoupper($lang_code); ?> 
                        </button>
                    <?php else: ?>
                        <a href="<?php echo $current_url . $separator . 'lang=' . $lang_code; ?>" class="lang-btn" title="<?php echo $lang_name; ?>">
                            <?php echo strtoupper($lang_code); ?> 
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>
</header>

<style>
/* Stylowanie linków logowania/rejestracji */
.auth-link {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.auth-link:hover {
    transform: translateY(-2px);
}

/* Koszyk zakupów */
.cart-link {
    position: relative;
    padding: 8px 16px;
    font-size: 24px;
    text-decoration: none;
    transition: all 0.3s;
}

.cart-link:hover {
    transform: translateY(-2px);
}

.cart-badge {
    position: absolute;
    top: 0;
    right: 6px;
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* DROPDOWN MENU - POMOC */
.dropdown-menu {
    position: relative;
    display: inline-block;
}

.dropdown-btn {
    background: none;
    border: none;
    color: inherit;
    font-size: inherit;
    font-family: inherit;
    padding: 8px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
    border-radius: 5px;
}

.dropdown-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.arrow-down {
    font-size: 10px;
    transition: transform 0.3s;
}

.dropdown-menu.active .arrow-down {
    transform: rotate(180deg);
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 5px;
    background: white;
    min-width: 200px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-radius: 8px;
    overflow: hidden;
    z-index: 1000;
    animation: dropdownFadeIn 0.3s ease;
}

.dropdown-content.show {
    display: block;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-content a {
    display: block;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s;
}

.dropdown-content a:hover {
    background: #f8f9fa;
}

/* Menu użytkownika */
.user-menu {
    position: relative;
}

.user-menu-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s;
}

.user-menu-btn:hover {
    background: #f8f9fa;
    border-color: #3498db;
}

.user-avatar {
    width: 30px;
    height: 30px;
    /* Obecny styl gradientowy */
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

/* ZMIANA: Nowa reguła dla stałego koloru primary */
.user-avatar.user-avatar-primary {
    /* Użycie zmiennej CSS dla koloru primary (zmienić, jeśli nie używasz zmiennej CSS) */
    background: var(--color-primary, #3498db); 
    box-shadow: 0 0 0 2px var(--color-primary, #3498db); /* Opcjonalna ramka */
}

.user-name {
    font-weight: 500;
    color: #333;
}

.arrow {
    font-size: 10px;
    color: #666;
}

.user-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 10px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 200px;
    overflow: hidden;
    z-index: 1000;
}

.user-dropdown.show {
    display: block;
}

.user-dropdown a {
    display: block;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s;
}

.user-dropdown a:hover {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .cart-link {
        font-size: 20px;
    }
    
    .cart-badge {
        font-size: 10px;
        padding: 1px 5px;
    }
    
    .user-menu-btn {
        padding: 6px 12px;
    }
    
    .user-name {
        display: none;
    }
    
    .user-dropdown,
    .dropdown-content {
        right: auto;
        left: 0;
    }
    
    /* Mobile - dropdown jako lista */
    #mainNav .dropdown-content {
        position: static;
        box-shadow: none;
        margin-top: 0;
        border-left: 2px solid var(--color-primary, #3498db);
        margin-left: 20px;
    }
}
</style>

<script>
// Toggle dropdown menu (Pomoc)
function toggleDropdown(event, dropdownId) {
    event.stopPropagation();
    const dropdown = document.getElementById(dropdownId);
    const parent = dropdown.parentElement;
    
    // Zamknij user menu jeśli otwarty
    document.getElementById('userDropdown')?.classList.remove('show');
    
    // Zamknij wszystkie inne dropdowny
    document.querySelectorAll('.dropdown-content').forEach(d => {
        if (d.id !== dropdownId) {
            d.classList.remove('show');
            d.parentElement.classList.remove('active');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('show');
    parent.classList.toggle('active');
}

// Toggle user menu
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    
    // Zamknij dropdown Pomoc jeśli otwarty
    document.querySelectorAll('.dropdown-content').forEach(d => {
        d.classList.remove('show');
        d.parentElement.classList.remove('active');
    });
    
    dropdown.classList.toggle('show');
}

// Zamknij wszystkie dropdowny po kliknięciu poza nimi
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    
    // Zamknij user menu jeśli kliknięto poza nim
    if (userMenu && !userMenu.contains(event.target)) {
        document.getElementById('userDropdown')?.classList.remove('show');
    }
    
    // Zamknij dropdown menu jeśli kliknięto poza nim
    dropdownMenus.forEach(menu => {
        if (!menu.contains(event.target)) {
            menu.querySelector('.dropdown-content')?.classList.remove('show');
            menu.classList.remove('active');
        }
    });
});
</script>

<link rel="stylesheet" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../assets/css/chatbot-widget.css' : 'assets/css/chatbot-widget.css'; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<script>
    // Ustaw ścieżkę do API chatbota
    // WAŻNE: musi być dostępna z root katalogu projektu
    window.CHATBOT_API_URL = '<?php 
        // Określ czy jesteśmy w /pages/ czy w root
        if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
            echo '../api/chatbot-widget-api.php';
        } else {
            echo 'api/chatbot-widget-api.php';
        }
    ?>';
    
    // Aktualny język
    window.CHATBOT_LANG = '<?php echo $current_lang; ?>';
</script>

<script src="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../assets/js/chatbot-widget.js' : 'assets/js/chatbot-widget.js'; ?>"></script>

<script>
// ===== MOBILE MENU TOGGLE =====
document.getElementById('menuToggle')?.addEventListener('click', function() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('hidden');
});

// Zamknij menu po kliknięciu na link
document.querySelectorAll('#mainNav a').forEach(link => {
    link.addEventListener('click', function() {
        document.getElementById('mainNav').classList.add('hidden');
    });
});
</script>