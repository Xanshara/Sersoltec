<?php
/**
 * SERSOLTEC - ADMIN INQUIRIES
 * Zarządzanie zapytaniami od klientów
 */

require_once 'admin-auth.php';

$page_title = 'Zapytania';

$success = '';
$error = '';

// Obsługa zmiany statusu
if (isset($_POST['change_status']) && isset($_POST['inquiry_id'])) {
    $inquiry_id = (int)$_POST['inquiry_id'];
    $new_status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $inquiry_id]);
        $success = 'Status zapytania został zaktualizowany';
    } catch (Exception $e) {
        $error = 'Błąd aktualizacji statusu';
    }
}

// Obsługa usuwania
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $inquiry_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->execute([$inquiry_id]);
        $success = 'Zapytanie zostało usunięte';
    } catch (Exception $e) {
        $error = 'Błąd podczas usuwania zapytania';
    }
}

// Filtry
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Query builder
$query = "SELECT i.*, p.name_pl as product_name 
          FROM inquiries i 
          LEFT JOIN products p ON i.product_id = p.id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND i.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (i.name LIKE ? OR i.email LIKE ? OR i.subject LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

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

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0;">Zapytania (<?php echo count($inquiries); ?>)</h2>
    </div>
    
    <!-- Filtry -->
    <form method="get" style="background: var(--color-light-gray); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Szukaj</label>
                <input type="text" name="search" class="form-input" placeholder="Imię, email, temat..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>Nowe</option>
                    <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Przeczytane</option>
                    <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Odpowiedziano</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                Filtruj
            </button>
        </div>
    </form>
    
    <!-- Tabela zapytań -->
    <?php if ($inquiries): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Imię</th>
                    <th>Email</th>
                    <th>Temat</th>
                    <th>Produkt</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                    <tr style="<?php echo $inquiry['status'] === 'new' ? 'background: #fff3cd;' : ''; ?>">
                        <td><?php echo date('Y-m-d H:i', strtotime($inquiry['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>">
                                <?php echo htmlspecialchars($inquiry['email']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars(substr($inquiry['subject'] ?: 'Brak tematu', 0, 40)); ?></td>
                        <td><?php echo $inquiry['product_name'] ? htmlspecialchars($inquiry['product_name']) : '-'; ?></td>
                        <td>
                            <?php
                            $badge_class = 'badge-info';
                            $status_text = 'Nieznany';
                            
                            switch ($inquiry['status']) {
                                case 'new':
                                    $badge_class = 'badge-danger';
                                    $status_text = 'Nowe';
                                    break;
                                case 'read':
                                    $badge_class = 'badge-warning';
                                    $status_text = 'Przeczytane';
                                    break;
                                case 'replied':
                                    $badge_class = 'badge-success';
                                    $status_text = 'Odpowiedziano';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button class="btn btn-outline btn-sm btn-icon" 
                                        onclick="viewInquiry(<?php echo htmlspecialchars(json_encode($inquiry)); ?>)" 
                                        title="Zobacz">
                                    👁️
                                </button>
                                <button class="btn btn-outline btn-sm btn-icon" 
                                        onclick="changeStatus(<?php echo $inquiry['id']; ?>, '<?php echo $inquiry['status']; ?>')" 
                                        title="Zmień status">
                                    🔄
                                </button>
                                <a href="?delete=1&id=<?php echo $inquiry['id']; ?>" 
                                   class="btn btn-delete btn-sm btn-icon delete-btn" 
                                   title="Usuń">
                                    🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">💬</div>
            <p>Brak zapytań spełniających kryteria</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal szczegółów zapytania -->
<div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-top: 0;">Szczegóły Zapytania</h2>
        <div id="inquiryDetails"></div>
        <button type="button" class="btn btn-outline" onclick="closeViewModal()" style="margin-top: 1rem;">
            Zamknij
        </button>
    </div>
</div>

<!-- Modal zmiany statusu -->
<div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%;">
        <h2 style="margin-top: 0;">Zmień Status</h2>
        
        <form method="POST">
            <input type="hidden" name="change_status" value="1">
            <input type="hidden" name="inquiry_id" id="status_inquiry_id">
            
            <div class="form-group">
                <label class="form-label">Nowy status</label>
                <select name="status" id="status_select" class="form-select">
                    <option value="new">Nowe</option>
                    <option value="read">Przeczytane</option>
                    <option value="replied">Odpowiedziano</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    Zapisz
                </button>
                <button type="button" class="btn btn-outline" onclick="closeStatusModal()">
                    Anuluj
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function viewInquiry(inquiry) {
    // Automatycznie oznacz jako przeczytane
    if (inquiry.status === 'new') {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'change_status=1&inquiry_id=' + inquiry.id + '&status=read'
        }).then(() => {
            // Odśwież stronę po zamknięciu modala
            setTimeout(() => location.reload(), 3000);
        });
    }
    
    let html = '<table style="width: 100%; margin-bottom: 1rem;">';
    html += '<tr><td><strong>Data:</strong></td><td>' + inquiry.created_at + '</td></tr>';
    html += '<tr><td><strong>Imię:</strong></td><td>' + inquiry.name + '</td></tr>';
    html += '<tr><td><strong>Email:</strong></td><td><a href="mailto:' + inquiry.email + '">' + inquiry.email + '</a></td></tr>';
    
    if (inquiry.phone) {
        html += '<tr><td><strong>Telefon:</strong></td><td><a href="tel:' + inquiry.phone + '">' + inquiry.phone + '</a></td></tr>';
    }
    
    if (inquiry.company) {
        html += '<tr><td><strong>Firma:</strong></td><td>' + inquiry.company + '</td></tr>';
    }
    
    if (inquiry.subject) {
        html += '<tr><td><strong>Temat:</strong></td><td>' + inquiry.subject + '</td></tr>';
    }
    
    if (inquiry.product_name) {
        html += '<tr><td><strong>Produkt:</strong></td><td>' + inquiry.product_name + '</td></tr>';
    }
    
    html += '<tr><td><strong>Status:</strong></td><td>' + inquiry.status + '</td></tr>';
    html += '</table>';
    
    if (inquiry.message) {
        html += '<div style="background: #f5f5f5; padding: 1rem; border-radius: 8px; margin-top: 1rem;">';
        html += '<strong>Wiadomość:</strong><br>' + inquiry.message.replace(/\n/g, '<br>');
        html += '</div>';
    }
    
    html += '<div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">';
    html += '<a href="mailto:' + inquiry.email + '?subject=RE: ' + (inquiry.subject || 'Zapytanie') + '" class="btn btn-primary">📧 Odpowiedz przez email</a>';
    html += '</div>';
    
    document.getElementById('inquiryDetails').innerHTML = html;
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function changeStatus(inquiryId, currentStatus) {
    document.getElementById('status_inquiry_id').value = inquiryId;
    document.getElementById('status_select').value = currentStatus;
    document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Zamknij modale klikając poza nimi
document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});

document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) closeStatusModal();
});
</script>

<?php include 'admin-footer.php'; ?>
