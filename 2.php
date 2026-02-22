<?php
/**
 * blog.php (poprawiona, kompletna wersja)
 * Dostosowana do: /var/www/lastchance/sersoltec/
 */

// ---- minimalne ustawienia raportowania (nie pokazujemy błędów publicznie) ----
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// ---- require config pierwsze ----
require_once __DIR__ . '/config.php';
chdir(__DIR__);

// ---- working dir i appRoot ----
chdir(__DIR__);
$appRoot = '/' . trim(basename(__DIR__), '/') . '/';
if (!function_exists('asset')) {
    function asset($path) {
        global $appRoot;
        return $appRoot . ltrim($path, '/');
    }
}

// ---- start buforowania ----
if (!ob_get_level()) ob_start();

// ---- load header (bufor i korekta ścieżek) ----
$headerFile = __DIR__ . '/includes/header.php';
if (file_exists($headerFile)) {
    ob_start();
    require_once $headerFile;
    $hdr = ob_get_clean();
    $hdr = preg_replace('#(href|src)=([\'"])(?:\.+/)*assets/#i', '$1=$2' . $appRoot . 'assets/', $hdr);
    $hdr = str_replace(['...../responsive.css','....../responsive.css'], $appRoot . 'assets/css/responsive.css', $hdr);
    echo $hdr;
} else {
    echo '<div style="padding:20px;background:#fee;border:1px solid #c33;">';
    echo '<strong>Krytyczny błąd:</strong> Brak header.php: ' . htmlspecialchars($headerFile);
    echo '</div>';
}

// ---- blog-data dir ----
$blogDataDir = __DIR__ . '/blog-data';
if (!is_dir($blogDataDir)) {
    echo '<main class="site-main"><div class="container">';
    echo '<article class="blog-message"><h1>Błąd konfiguracji</h1>';
    echo '<p>Katalog blog-data nie istnieje: <code>' . htmlspecialchars($blogDataDir) . '</code></p>';
    echo '</article></div></main>';

    // footer
    $footerFile = __DIR__ . '/includes/footer.php';
    if (file_exists($footerFile)) {
        ob_start();
        require_once $footerFile;
        $ftr = ob_get_clean();
        $ftr = preg_replace('#(href|src)=([\'"])(?:\.+/)*assets/#i', '$1=$2' . $appRoot . 'assets/', $ftr);
        echo $ftr;
    }
    if (ob_get_level()) ob_end_flush();
    exit;
}

// ---- pobierz id (tylko cyfry) ----
$postId = null;
if (isset($_GET['id'])) {
    $postId = preg_replace('/\D+/', '', $_GET['id']);
    if ($postId === '') $postId = null;
}

// ---- prosty markdown -> html ----
function md_to_html($md) {
    $lines = preg_split("/\r\n|\n|\r/", $md);
    $out = '';
    $inP = false;
    foreach ($lines as $line) {
        $line = rtrim($line);
        if ($line === '') {
            if ($inP) { $out .= "</p>\n"; $inP = false; }
            continue;
        }
        if (preg_match('/^\s*#\s+(.*)/', $line, $m)) { $out .= "<h1>".htmlspecialchars($m[1])."</h1>\n"; continue; }
        if (preg_match('/^\s*##\s+(.*)/', $line, $m)) { $out .= "<h2>".htmlspecialchars($m[1])."</h2>\n"; continue; }
        if (!$inP) { $out .= '<p>'; $inP = true; }
        $htmlLine = htmlspecialchars($line);
        $htmlLine = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $htmlLine);
        $htmlLine = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $htmlLine);
        $out .= $htmlLine . "\n";
    }
    if ($inP) $out .= "</p>\n";
    return $out;
}

// ---- ładowanie wpisu z blog-data ----
function load_post_from_blogdata($dir, $id) {
    $candidates = [
        $dir . '/' . $id . '.json',
        $dir . '/' . $id . '.html',
        $dir . '/' . $id . '.md',
    ];
    $realDir = realpath($dir);
    if ($realDir === false) return false;
    foreach ($candidates as $path) {
        if (!file_exists($path)) continue;
        $real = realpath($path);
        if ($real === false) continue;
        if (strpos($real, $realDir) !== 0) continue;

        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        if ($ext === 'json') {
            $raw = file_get_contents($real);
            $json = json_decode($raw, true);
            if ($json && is_array($json)) {
                return [
                    'title' => $json['title'] ?? null,
                    'date'  => $json['date'] ?? null,
                    'author'=> $json['author'] ?? null,
                    'content'=> $json['content'] ?? ''
                ];
            } else {
                return false;
            }
        } elseif ($ext === 'html') {
            $raw = file_get_contents($real);
            $title = null;
            if (preg_match('/<title>(.*?)<\/title>/is', $raw, $m)) $title = strip_tags($m[1]);
            elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $raw, $m)) $title = strip_tags($m[1]);
            return ['title'=>$title,'date'=>null,'author'=>null,'content'=>$raw];
        } elseif ($ext === 'md') {
            $raw = file_get_contents($real);
            $html = md_to_html($raw);
            $title = null;
            if (preg_match('/^\s*#\s*(.+)$/m', $raw, $m)) $title = trim($m[1]);
            return ['title'=>$title,'date'=>null,'author'=>null,'content'=>$html];
        }
    }
    return false;
}

// ---- wyświetlenie ----
?>
<main class="site-main">
  <div class="container">
<?php
if (!$postId) {
    echo '<article class="blog-message"><h1>Brak wskazanego wpisu</h1><p>Użyj <code>?id=1</code>.</p></article>';
} else {
    $post = load_post_from_blogdata($blogDataDir, $postId);
    if ($post === false) {
        echo '<article class="blog-message"><h1>Nie znaleziono wpisu lub błąd pliku</h1>';
        echo '<p>Sprawdź katalog <code>blog-data</code>.</p></article>';
    } else {
        echo '<article class="blog-post">';
        echo '<header class="post-header">';
        echo '<h1 class="post-title">'.htmlspecialchars($post['title'] ?? ('Wpis #' . $postId)).'</h1>';
        $meta = [];
        if (!empty($post['date'])) $meta[] = htmlspecialchars($post['date']);
        if (!empty($post['author'])) $meta[] = 'Autor: ' . htmlspecialchars($post['author']);
        if ($meta) echo '<p class="post-meta">'.implode(' • ', $meta).'</p>';
        echo '</header>';
        echo '<section class="post-content">';
        echo $post['content'] ?? '';
        echo '</section>';
        echo '</article>';
    }
}
?>
  </div>
</main>
<?php
// ---- load footer ----
$footerFile = __DIR__ . '/includes/footer.php';
if (file_exists($footerFile)) {
    ob_start();
    require_once $footerFile;
    $ftr = ob_get_clean();
    $ftr = preg_replace('#(href|src)=([\'"])(?:\.+/)*assets/#i', '$1=$2' . $appRoot . 'assets/', $ftr);
    $ftr = str_replace(['...../responsive.css','....../responsive.css'], $appRoot . 'assets/css/responsive.css', $ftr);
    echo $ftr;
} else {
    echo '<div style="padding:20px;background:#fee;border:1px solid #c33;">';
    echo '<strong>Krytyczny błąd:</strong> Brak footer.php: ' . htmlspecialchars($footerFile);
    echo '</div>';
}

if (ob_get_level()) ob_end_flush();
exit;
?>
