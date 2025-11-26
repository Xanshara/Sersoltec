<?php
/**
 * SERSOLTEC - Admin Reviews Panel
 * Moderacja opinii produktów
 */

require_once 'admin-auth.php';

$page_title = 'Moderacja Opinii';

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reviewId = (int)($_POST['review_id'] ?? 0);
    
    if ($action === 'approve' && $reviewId) {
        try {
            $stmt = $pdo->prepare("UPDATE product_reviews SET status = 'approved' WHERE id = ?");
            $stmt->execute([$reviewId]);
            $success = 'Opinia została zatwierdzona!';
        } catch (Exception $e) {
            $error = 'Błąd podczas zatwierdzania opinii';
        }
    } elseif ($action === 'reject' && $reviewId) {
        try {
            $stmt = $pdo->prepare("UPDATE product_reviews SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$reviewId]);
            $success = 'Opinia została odrzucona!';
        } catch (Exception $e) {
            $error = 'Błąd podczas odrzucania opinii';
        }
    } elseif ($action === 'delete' && $reviewId) {
        try {
            $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $success = 'Opinia została usunięta!';
        } catch (Exception $e) {
            $error = 'Błąd podczas usuwania opinii';
        }
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
switch($filter) {
    case 'approved':
        $whereClause = "WHERE r.status = 'approved'";
        break;
    case 'rejected':
        $whereClause = "WHERE r.status = 'rejected'";
        break;
    case 'all':
        $whereClause = "WHERE 1=1";
        break;
    default:
        $whereClause = "WHERE r.status = 'pending'";
}

// Get reviews
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.product_id,
        r.user_id,
        r.rating,
        r.title,
        r.comment as review_text,
        r.author_name,
        r.verified_purchase,
        r.status,
        r.created_at,
        p.name_pl as product_name,
        CONCAT(u.first_name, ' ', u.last_name) as user_name,
        u.email as user_email
    FROM product_reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    {$whereClause}
    ORDER BY r.created_at DESC
    LIMIT 50
");
$stmt->execute();
$reviews = $stmt->fetchAll();

include 'admin-header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success">
        ✓ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        ✗ <?php echo htmlspecialchars($error); ?>
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

<!-- Reviews List -->
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0;">Opinie</h2>
        
        <!-- Filter tabs -->
        <div style="display: flex; gap: 0.5rem;">
            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Oczekujące (<?php echo $stats['pending']; ?>)
            </a>
            <a href="?filter=approved" class="btn <?php echo $filter === 'approved' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Zatwierdzone (<?php echo $stats['approved']; ?>)
            </a>
            <a href="?filter=rejected" class="btn <?php echo $filter === 'rejected' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Odrzucone (<?php echo $stats['rejected']; ?>)
            </a>
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Wszystkie (<?php echo $stats['total']; ?>)
            </a>
        </div>
    </div>
    
    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>Brak opinii w tej kategorii.</p>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produkt</th>
                    <th>Autor</th>
                    <th>Ocena</th>
                    <th>Tytuł</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></td>
                        <td>
                            <a href="../pages/product-detail.php?id=<?php echo $review['product_id']; ?>" target="_blank">
                                <?php echo htmlspecialchars(substr($review['product_name'] ?? 'Nieznany produkt', 0, 30)); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($review['author_name']); ?>
                            <?php if ($review['verified_purchase']): ?>
                                <span class="badge badge-success" title="Zweryfikowany zakup">✓</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color: #f39c12; font-weight: bold;">
                                <?php echo str_repeat('★', $review['rating']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(substr($review['title'], 0, 40)); ?></td>
                        <td>
                            <?php
                            $badge_class = 'badge-info';
                            $status_text = 'Nieznany';
                            
                            switch ($review['status']) {
                                case 'pending':
                                    $badge_class = 'badge-warning';
                                    $status_text = 'Oczekujące';
                                    break;
                                case 'approved':
                                    $badge_class = 'badge-success';
                                    $status_text = 'Zatwierdzone';
                                    break;
                                case 'rejected':
                                    $badge_class = 'badge-danger';
                                    $status_text = 'Odrzucone';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button class="btn btn-outline btn-sm btn-icon" 
                                        onclick="viewReview(<?php echo htmlspecialchars(json_encode($review)); ?>)" 
                                        title="Zobacz">
                                    👁️
                                </button>
                                <?php if ($review['status'] === 'pending'): ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Czy zatwierdzić tę opinię?')">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-icon" style="background: #2e7d32; color: white;" title="Zatwierdź">
                                            ✓
                                        </button>
                                    </form>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Czy odrzucić tę opinię?')">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-icon" style="background: #d32f2f; color: white;" title="Odrzuć">
                                            ✗
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Czy na pewno usunąć tę opinię?')">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-delete btn-sm btn-icon" title="Usuń">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal szczegółów opinii -->
<div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-top: 0;">Szczegóły Opinii</h2>
        <div id="reviewDetails"></div>
        <button type="button" class="btn btn-outline" onclick="closeViewModal()" style="margin-top: 1rem;">
            Zamknij
        </button>
    </div>
</div>

<script>
function viewReview(review) {
    const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
    
    let html = '<table style="width: 100%; margin-bottom: 1rem;">';
    html += '<tr><td style="width: 150px;"><strong>Data:</strong></td><td>' + review.created_at + '</td></tr>';
    html += '<tr><td><strong>Produkt:</strong></td><td><a href="../pages/product-detail.php?id=' + review.product_id + '" target="_blank">' + review.product_name + '</a></td></tr>';
    html += '<tr><td><strong>Autor:</strong></td><td>' + review.author_name;
    if (review.verified_purchase) {
        html += ' <span class="badge badge-success">✓ Zweryfikowany zakup</span>';
    }
    html += '</td></tr>';
    
    if (review.user_email) {
        html += '<tr><td><strong>Email:</strong></td><td><a href="mailto:' + review.user_email + '">' + review.user_email + '</a></td></tr>';
    }
    
    html += '<tr><td><strong>Ocena:</strong></td><td style="color: #f39c12; font-size: 1.2rem;">' + stars + '</td></tr>';
    html += '<tr><td><strong>Status:</strong></td><td>' + review.status + '</td></tr>';
    html += '</table>';
    
    html += '<div style="background: #f5f5f5; padding: 1rem; border-radius: 8px; margin-top: 1rem;">';
    html += '<strong>Tytuł:</strong><br>' + review.title;
    html += '</div>';
    
    html += '<div style="background: #f5f5f5; padding: 1rem; border-radius: 8px; margin-top: 1rem;">';
    html += '<strong>Treść:</strong><br>' + review.review_text.replace(/\n/g, '<br>');
    html += '</div>';
    
    document.getElementById('reviewDetails').innerHTML = html;
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

// Zamknij modal klikając poza nim
document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});
</script>

<style>
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left-color: #4caf50;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border-left-color: #f44336;
}
</style>

<?php include 'admin-footer.php'; ?>