<?php
/**
 * SERSOLTEC - ADMIN BLOG
 * Zarządzanie wpisami bloga
 */

require_once 'admin-auth.php'; // Sprawdza sesję admina i dołącza config.php
require_once '../lib/BlogManager.php';

$page_title = 'Zarządzanie Blogiem';

$blogManager = new BlogManager();
$success = '';
$error = '';

// Obsługa usuwania
if (isset($_GET['delete']) && isset($_GET['slug'])) {
    $slug = sanitize($_GET['slug']); // Zakładając, że masz funkcję sanitize
    
    if ($blogManager->deletePost($slug)) {
        $success = 'Wpis został usunięty';
    } else {
        $error = 'Błąd podczas usuwania wpisu (plik nie istnieje lub błąd uprawnień)';
    }
}

// Pobierz listę wszystkich postów
$posts = $blogManager->getAllPosts();

// Dołącz header
require_once 'admin-header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>Wpisy Bloga (<?php echo count($posts); ?>)</h2>
        <a href="blog-edit.php" class="btn btn-primary">➕ Dodaj Nowy Wpis</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($posts)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tytuł (PL)</th>
                    <th>Data</th>
                    <th>Slug</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post['title_pl']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($post['date'])); ?></td>
                        <td><code><?php echo htmlspecialchars($post['slug']); ?></code></td>
                        <td class="admin-actions">
                            <a href="blog-edit.php?slug=<?php echo $post['slug']; ?>" class="btn btn-outline btn-sm">
                                Edytuj
                            </a>
                            <a href="?delete=1&slug=<?php echo $post['slug']; ?>" class="btn btn-danger btn-sm btn-delete">
                                Usuń
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📄</div>
            <p>Brak wpisów bloga. Dodaj pierwszy wpis, aby zacząć.</p>
        </div>
    <?php endif; ?>

</div>

<?php 
// Dołącz footer
include 'admin-footer.php'; 
?>