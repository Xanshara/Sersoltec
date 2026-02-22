<?php
/**
 * SERSOLTEC - Blog Single Post
 * @version 2.5
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/includes/translations.php')) {
    require_once __DIR__ . '/includes/translations.php';
}

$current_lang = getCurrentLanguage();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!$slug) {
    header('Location: /sersoltec/blog.php');
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    // AUTO-DETECT columns
    $cols = $pdo->query("SHOW COLUMNS FROM blog_posts")->fetchAll(PDO::FETCH_COLUMN);
    
    $title_col = in_array('title_' . $current_lang, $cols) ? 'title_' . $current_lang : 
                (in_array('title', $cols) ? 'title' : 'title_pl');
    $excerpt_col = in_array('excerpt_' . $current_lang, $cols) ? 'excerpt_' . $current_lang :
                  (in_array('excerpt', $cols) ? 'excerpt' : 'excerpt_pl');
    $content_col = in_array('content_' . $current_lang, $cols) ? 'content_' . $current_lang :
                  (in_array('content', $cols) ? 'content' : 'content_pl');
    $status_col = in_array('published', $cols) ? 'published' :
                 (in_array('status', $cols) ? 'status' : null);
    $date_col = in_array('published_at', $cols) ? 'published_at' :
               (in_array('created_at', $cols) ? 'created_at' : 'published_at');
    
    $status_check = $status_col ? "($status_col = 1 OR $status_col = 'published')" : "1";
    
    // Get post
    $query = "SELECT id, $title_col as title, slug, $excerpt_col as excerpt, $content_col as content, 
                     image_url, author, $date_col as published_at, views
              FROM blog_posts
              WHERE slug = ? AND $status_check AND ($date_col IS NULL OR $date_col <= NOW())";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: /sersoltec/blog.php');
        exit;
    }
    
    // Update views
    $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);
    
    // Get related posts
    $stmt = $pdo->prepare("
        SELECT id, $title_col as title, slug, $excerpt_col as excerpt, image_url, $date_col as published_at
        FROM blog_posts
        WHERE $status_check AND $date_col <= NOW() AND id != ?
        ORDER BY $date_col DESC LIMIT 3
    ");
    $stmt->execute([$post['id']]);
    $related = $stmt->fetchAll();
    
} catch (Exception $e) {
    header('Location: /sersoltec/blog.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Minimal blog post styles */
    .blog-post-hero { width: 100%; height: 350px; overflow: hidden; margin-bottom: 40px; position: relative; }
    .blog-post-hero img { width: 100%; height: 100%; object-fit: cover; }
    .blog-post-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, transparent, rgba(0,0,0,0.6)); padding: 30px 20px 15px; color: white; }
    .blog-post-container { max-width: 900px; margin: 0 auto; padding: 0 20px 60px 20px; }
    .blog-back { color: var(--color-primary); text-decoration: none; font-weight: 600; margin-bottom: 30px; display: inline-block; }
    .blog-back:hover { text-decoration: underline; }
    .blog-post-title { font-size: 2.2rem; margin: 0 0 20px 0; color: var(--color-text); font-weight: 700; }
    .blog-post-meta { display: flex; gap: 30px; color: var(--color-gray); font-size: 0.9rem; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #eee; flex-wrap: wrap; }
    .blog-post-content { font-size: 1.05rem; line-height: 1.7; color: var(--color-text); margin-bottom: 50px; }
    .blog-post-content p { margin-bottom: 20px; }
    .blog-post-content h2 { font-size: 1.6rem; margin: 30px 0 15px 0; color: var(--color-primary); }
    .blog-post-share { display: flex; gap: 15px; padding-top: 30px; border-top: 1px solid #eee; }
    .blog-share-btn { padding: 10px 20px; background: var(--color-primary); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
    .blog-share-btn:hover { background: var(--color-primary-dark); }
    .blog-related { background: #f8f8f8; padding: 60px 20px; margin-top: 60px; }
    .blog-related h2 { text-align: center; margin-bottom: 40px; color: var(--color-text); }
    .blog-related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
    @media (max-width: 768px) { .blog-post-title { font-size: 1.6rem; } .blog-post-meta { flex-direction: column; gap: 10px; } .blog-share-btn { width: 100%; } }
</style>

<?php if ($post['image_url']): ?>
    <div class="blog-post-hero">
        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="">
        <div class="blog-post-overlay">
            <h1 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        </div>
    </div>
<?php endif; ?>

<div class="blog-post-container">
    <a href="/sersoltec/blog.php" class="blog-back">← Powrót do bloga</a>
    
    <?php if (!$post['image_url']): ?>
        <h1 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
    <?php endif; ?>
    
    <div class="blog-post-meta">
        <span>📅 <?php echo date('d.m.Y', strtotime($post['published_at'] ?? 'now')); ?></span>
        <span>✍️ <?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?></span>
        <span>👁️ <?php echo (int)$post['views']; ?> wyświetleń</span>
    </div>
    
    <div class="blog-post-content">
        <?php echo nl2br(htmlspecialchars($post['content'] ?? 'Brak zawartości')); ?>
    </div>
    
    <div class="blog-post-share">
        <button class="blog-share-btn" onclick="shareFacebook()">👍 Facebook</button>
        <button class="blog-share-btn" onclick="shareTwitter()">🐦 Twitter</button>
        <button class="blog-share-btn" onclick="shareCopy()">🔗 Skopiuj link</button>
    </div>
</div>

<?php if ($related): ?>
    <div class="blog-related">
        <h2>📚 Polecane artykuły</h2>
        <div class="blog-related-grid">
            <?php foreach ($related as $rel): ?>
                <div class="blog-card" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <?php if ($rel['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" alt="" style="width: 100%; height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 180px; background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); display: flex; align-items: center; justify-content: center; font-size: 50px; color: white;">📝</div>
                    <?php endif; ?>
                    <div style="padding: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: var(--color-text); font-weight: 700;"><?php echo htmlspecialchars($rel['title']); ?></h3>
                        <p style="color: var(--color-gray); margin: 0 0 15px 0; font-size: 0.9rem;"><?php echo htmlspecialchars(substr($rel['excerpt'] ?? '', 0, 80)); ?>...</p>
                        <a href="/sersoltec/blog-post.php?slug=<?php echo urlencode($rel['slug']); ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Czytaj więcej →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    function shareFacebook() { window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400'); }
    function shareTwitter() { window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400'); }
    function shareCopy() { navigator.clipboard.writeText(window.location.href).then(() => alert('Link skopiowany!')).catch(() => alert('Błąd')); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>