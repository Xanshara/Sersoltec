<?php
/**
 * SERSOLTEC - ADMIN BLOG EDIT
 * Formularz do tworzenia i edycji wpisów bloga
 */

require_once 'admin-auth.php'; // Sprawdza sesję admina i dołącza config.php
require_once '../lib/BlogManager.php';
// Zakładając, że funkcja `sanitize` jest dostępna przez config/lib/Helpers

$blogManager = new BlogManager();
$success = '';
$error = '';
$post_data = [
    'slug' => '',
    'title_pl' => '', 'title_en' => '', 'title_de' => '',
    'content_pl' => '', 'content_en' => '', 'content_de' => '',
];
$is_edit = false;

// 1. Obsługa formularza (Zapis)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_to_save = [
        'slug'       => sanitize($_POST['slug']),
        'author_id'  => getAdminId(), // Używamy funkcji z admin-auth.php
        'title_pl'   => sanitize($_POST['title_pl'], false), // false = nie usuwaj HTML
        'title_en'   => sanitize($_POST['title_en'], false),
        'title_de'   => sanitize($_POST['title_de'], false),
        'content_pl' => sanitize($_POST['content_pl'], false),
        'content_en' => sanitize($_POST['content_en'], false),
        'content_de' => sanitize($_POST['content_de'], false),
    ];

    $result = $blogManager->savePost($data_to_save);

    if ($result === 'success') {
        $success = 'Wpis został pomyślnie zapisany!';
        // Przekierowanie na listę lub stronę edycji, aby uniknąć ponownego wysłania formularza
        header('Location: blog-edit.php?slug=' . $data_to_save['slug'] . '&status=success');
        exit;
    } else {
        $error = $result; // Wyświetl błąd z BlogManagera
        // Utrzymaj wprowadzone dane w formularzu
        $post_data = array_merge($post_data, $data_to_save);
    }
}

// 2. Obsługa edycji (Wczytanie danych)
$edit_slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if ($edit_slug && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $post = $blogManager->getPost($edit_slug);
    if ($post) {
        $is_edit = true;
        $post_data['slug'] = $post['slug'];
        $post_data['title_pl'] = $post['title']['pl'] ?? '';
        $post_data['title_en'] = $post['title']['en'] ?? '';
        $post_data['title_de'] = $post['title']['de'] ?? '';
        $post_data['content_pl'] = $post['content']['pl'] ?? '';
        $post_data['content_en'] = $post['content']['en'] ?? '';
        $post_data['content_de'] = $post['content']['de'] ?? '';
    } else {
        $error = 'Wpis o podanym slug nie istnieje.';
    }
}

// Ustawienia tytułu strony
$page_title = $is_edit ? 'Edycja Wpisu: ' . ($post_data['title_pl'] ?? $post_data['slug']) : 'Dodaj Nowy Wpis';

// Sprawdź status z URL po przekierowaniu
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $success = 'Wpis został pomyślnie zapisany!';
}

// Dołącz header
require_once 'admin-header.php';
?>

<div class="admin-card">
    <h2><?php echo $page_title; ?></h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="slug">Slug (URL)</label>
            <input type="text" id="slug" name="slug" required 
                   value="<?php echo htmlspecialchars($post_data['slug']); ?>" 
                   <?php echo $is_edit ? 'readonly' : ''; ?>
                   placeholder="np. jak-zrobic-blog">
            <?php if ($is_edit): ?>
                <small>Sluga nie można zmienić po utworzeniu. Pełny link: twojastrona.pl/blog/<?php echo htmlspecialchars($post_data['slug']); ?></small>
            <?php endif; ?>
        </div>
        
        <div class="tab-system">
            <input type="radio" name="tabs" id="tab-pl" checked>
            <label for="tab-pl">🇵🇱 Polski</label>
            <input type="radio" name="tabs" id="tab-en">
            <label for="tab-en">🇬🇧 Angielski</label>
            <input type="radio" name="tabs" id="tab-de">
            <label for="tab-de">🇩🇪 Niemiecki</label>
            
            <div class="tab-content" id="content-pl">
                <div class="form-group">
                    <label for="title_pl">Tytuł (PL)</label>
                    <input type="text" id="title_pl" name="title_pl" value="<?php echo htmlspecialchars($post_data['title_pl']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="content_pl">Treść (PL)</label>
                    <textarea id="content_pl" name="content_pl" rows="15" required><?php echo htmlspecialchars($post_data['content_pl']); ?></textarea>
                </div>
            </div>
            
            <div class="tab-content" id="content-en">
                <div class="form-group">
                    <label for="title_en">Tytuł (EN)</label>
                    <input type="text" id="title_en" name="title_en" value="<?php echo htmlspecialchars($post_data['title_en']); ?>">
                </div>
                <div class="form-group">
                    <label for="content_en">Treść (EN)</label>
                    <textarea id="content_en" name="content_en" rows="15"><?php echo htmlspecialchars($post_data['content_en']); ?></textarea>
                </div>
            </div>
            
            <div class="tab-content" id="content-de">
                <div class="form-group">
                    <label for="title_de">Tytuł (DE)</label>
                    <input type="text" id="title_de" name="title_de" value="<?php echo htmlspecialchars($post_data['title_de']); ?>">
                </div>
                <div class="form-group">
                    <label for="content_de">Treść (DE)</label>
                    <textarea id="content_de" name="content_de" rows="15"><?php echo htmlspecialchars($post_data['content_de']); ?></textarea>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                💾 Zapisz Wpis
            </button>
            <a href="blog.php" class="btn btn-outline">← Anuluj i Wróć do Listy</a>
        </div>
    </form>
</div>

<style>
.tab-system { 
    display: flex; 
    flex-wrap: wrap; 
    margin-top: 1rem;
}
.tab-system input[type="radio"] { 
    display: none; 
}
.tab-system label {
    padding: 10px 15px;
    cursor: pointer;
    border: 1px solid #ccc;
    border-bottom: none;
    background: #f0f0f0;
    margin-right: -1px;
    z-index: 1;
}
.tab-system input[type="radio"]:checked + label {
    background: #fff;
    border-top: 3px solid var(--color-primary);
    border-left: 1px solid #ccc;
    border-right: 1px solid #ccc;
    z-index: 2;
}
.tab-content {
    order: 1; /* Resetuje porządek, musi być poniżej zakładek */
    width: 100%;
    border: 1px solid #ccc;
    padding: 20px;
    display: none;
    margin-top: -1px;
}
.tab-system input[type="radio"]:nth-of-type(1):checked ~ .tab-content:nth-of-type(1),
.tab-system input[type="radio"]:nth-of-type(2):checked ~ .tab-content:nth-of-type(2),
.tab-system input[type="radio"]:nth-of-type(3):checked ~ .tab-content:nth-of-type(3) {
    display: block;
}
</style>

<?php include 'admin-footer.php'; ?>