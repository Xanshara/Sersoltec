<?php
/**
 * SERSOLTEC - ADMIN AUTH
 * Sprawdzanie autoryzacji admina (include w każdym pliku admin)
 */

// Określ ścieżkę do config.php (w zależności od struktury katalogów)
if (file_exists('../config.php')) {
    require_once '../config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
}

// Sprawdź czy zalogowany
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Funkcje pomocnicze dla admina
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
}

function getAdminName() {
    return $_SESSION['admin_username'] ?? 'Admin';
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? 0;
}

// Logout action
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
