<?php
/**
 * MINIMAL Admin Reviews Panel
 * Simplified version for debugging
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check admin
$isAdmin = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == 1) {
    $isAdmin = true;
} elseif (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])) {
    $isAdmin = true;
}

if (!$isAdmin) {
    die('Not authorized. <a href="/admin/">Back to admin</a>');
}

// Database connection
try {
    require_once '../config.php';
    
    $host = DB_HOST ?? 'localhost';
    $dbname = DB_NAME ?? 'sersoltec_db';
    $user = DB_USER ?? 'sersoltec';
    $pass = DB_PASS ?? '';
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reviewId = (int)($_POST['review_id'] ?? 0);
    
    if ($action === 'approve' && $reviewId) {
        $stmt = $pdo->prepare("UPDATE product_reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$reviewId]);
        $message = 'Review approved!';
    } elseif ($action === 'reject' && $reviewId) {
        $stmt = $pdo->prepare("UPDATE product_reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$reviewId]);
        $message = 'Review rejected!';
    }
}

// Get stats
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM product_reviews
");
$stats = $stmt->fetch();

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Build where clause
if ($filter === 'approved') {
    $whereClause = "WHERE r.status = 'approved'";
} elseif ($filter === 'rejected') {
    $whereClause = "WHERE r.status = 'rejected'";
} elseif ($filter === 'all') {
    $whereClause = "WHERE 1=1";
} else {
    $whereClause = "WHERE r.status = 'pending'";
}

// Get reviews
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.product_id,
        r.rating,
        r.title,
        r.comment as review_text,
        r.author_name,
        r.status,
        r.created_at,
        p.name_pl as product_name
    FROM product_reviews r
    LEFT JOIN products p ON r.product_id = p.id
    {$whereClause}
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->execute();
$reviews = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Reviews Admin - SERSOLTEC</title>
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        /* Additional review-specific styles matching SERSOLTEC design */
        .review-card {
            background: var(--color-white);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--color-gray-light);
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid var(--color-primary);
        }
        
        .review h3 {
            margin: 0 0 0.75rem 0;
            color: var(--color-primary-dark);
            font-family: var(--font-serif);
            font-size: 1.2rem;
        }
        
        .review .meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--color-gray-light);
        }
        
        .review h4 {
            color: var(--color-text);
            font-weight: 600;
            margin: 1rem 0 0.5rem 0;
        }
        
        .review p {
            line-height: 1.6;
            color: #555;
            white-space: pre-wrap;
        }
        
        .review .actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-gray-light);
            display: flex;
            gap: 0.5rem;
        }
        
        .approve {
            background: #2e7d32 !important;
        }
        
        .approve:hover {
            background: #1b5e20 !important;
        }
        
        .reject {
            background: #d32f2f !important;
        }
        
        .reject:hover {
            background: #c62828 !important;
        }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--color-gray-light);
        }
        
        .filter-tabs a {
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            color: var(--color-text);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .filter-tabs a:hover {
            color: var(--color-primary);
        }
        
        .filter-tabs a.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
    </style>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <h2>SERSOLTEC</h2>
            <p>Admin Panel</p>
        </div>
        
        <nav class="admin-nav">
            <a href="/admin/" class="admin-nav-item">
                📊 Dashboard
            </a>
            <a href="/admin/reviews.php" class="admin-nav-item active">
                ⭐ Opinie
            </a>
            <a href="/admin/products.php" class="admin-nav-item">
                📦 Produkty
            </a>
            <a href="/admin/orders.php" class="admin-nav-item">
                🛒 Zamówienia
            </a>
        </nav>
        
        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>
                <small><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Administrator'); ?></small>
            </div>
            <a href="/admin/logout.php" class="btn-logout">Wyloguj</a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        
        <header class="admin-header">
            <div class="admin-header-left">
                <h1>Moderacja Opinii</h1>
            </div>
        </header>
        
        <div class="admin-content">
            
            <!-- Message -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Wszystkie</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Oczekujące</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $stats['approved']; ?></div>
                    <div class="stat-label">Zatwierdzone</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">Odrzucone</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filter-tabs">
                <a href="?filter=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    Oczekujące (<?php echo $stats['pending']; ?>)
                </a>
                <a href="?filter=approved" class="<?php echo $filter === 'approved' ? 'active' : ''; ?>">
                    Zatwierdzone (<?php echo $stats['approved']; ?>)
                </a>
                <a href="?filter=rejected" class="<?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                    Odrzucone (<?php echo $stats['rejected']; ?>)
                </a>
                <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">
                    Wszystkie (<?php echo $stats['total']; ?>)
                </a>
            </div>
            
            <!-- Reviews List -->
            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>Brak opinii w tej kategorii.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review review-card">
                        <h3><?php echo htmlspecialchars($review['product_name'] ?? 'Nieznany produkt'); ?></h3>
                        <div class="meta">
                            <strong><?php echo htmlspecialchars($review['author_name']); ?></strong> |
                            <span class="stars"><?php echo str_repeat('★', $review['rating']); ?></span> |
                            <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?> |
                            Status: <strong><?php echo $review['status']; ?></strong>
                        </div>
                        <h4><?php echo htmlspecialchars($review['title']); ?></h4>
                        <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        
                        <?php if ($review['status'] === 'pending'): ?>
                            <div class="actions">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm approve">✓ Zatwierdź</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm reject">✗ Odrzuć</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <p style="margin-top: 2rem;">
                <a href="/admin/" class="btn btn-outline">← Powrót do panelu</a>
            </p>
            
        </div>
        
    </main>
    
</div>

</body>
</html>