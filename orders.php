<?php
/**
 * SERSOLTEC - ADMIN ORDERS
 * Zarządzanie zamówieniami
 */

require_once 'admin-auth.php';

$page_title = 'Zamówienia';

$success = '';
$error = '';

// Obsługa zmiany statusu
if (isset($_POST['change_status']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success = 'Status zamówienia został zaktualizowany';
    } catch (Exception $e) {
        $error = 'Błąd aktualizacji statusu';
    }
}

// Obsługa usuwania
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $success = 'Zamówienie zostało usunięte';
    } catch (Exception $e) {
        $error = 'Błąd podczas usuwania zamówienia';
    }
}

// Filtry
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Query builder
$query = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (order_number LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

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
        <h2 style="margin: 0;">Zamówienia (<?php echo count($orders); ?>)</h2>
    </div>
    
    <!-- Filtry -->
    <form method="get" style="background: var(--color-light-gray); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Szukaj</label>
                <input type="text" name="search" class="form-input" placeholder="Numer zamówienia, imię, email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Potwierdzone</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>W realizacji</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Zrealizowane</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                Filtruj
            </button>
        </div>
    </form>
    
    <!-- Tabela zamówień -->
    <?php if ($orders): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Numer</th>
                    <th>Data</th>
                    <th>Klient</th>
                    <th>Email</th>
                    <th>Produkty</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo $order['total_items'] ?? 0; ?> szt.</td>
                        <td>
                            <?php
                            $badge_class = 'badge-info';
                            $status_text = 'Nieznany';
                            
                            switch ($order['status']) {
                                case 'pending':
                                    $badge_class = 'badge-warning';
                                    $status_text = 'Oczekujące';
                                    break;
                                case 'confirmed':
                                    $badge_class = 'badge-info';
                                    $status_text = 'Potwierdzone';
                                    break;
                                case 'processing':
                                    $badge_class = 'badge-info';
                                    $status_text = 'W realizacji';
                                    break;
                                case 'completed':
                                    $badge_class = 'badge-success';
                                    $status_text = 'Zrealizowane';
                                    break;
                                case 'cancelled':
                                    $badge_class = 'badge-danger';
                                    $status_text = 'Anulowane';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button class="btn btn-outline btn-sm btn-icon" 
                                        onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                        title="Zobacz">
                                    👁️
                                </button>
                                <button class="btn btn-outline btn-sm btn-icon" 
                                        onclick="changeStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
                                        title="Zmień status">
                                    🔄
                                </button>
                                <a href="?delete=1&id=<?php echo $order['id']; ?>" 
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
            <div class="empty-state-icon">🛒</div>
            <p>Brak zamówień spełniających kryteria</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal szczegółów zamówienia -->
<div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-top: 0;">Szczegóły Zamówienia</h2>
        <div id="orderDetails"></div>
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
            <input type="hidden" name="order_id" id="status_order_id">
            
            <div class="form-group">
                <label class="form-label">Nowy status</label>
                <select name="status" id="status_select" class="form-select">
                    <option value="pending">Oczekujące</option>
                    <option value="confirmed">Potwierdzone</option>
                    <option value="processing">W realizacji</option>
                    <option value="completed">Zrealizowane</option>
                    <option value="cancelled">Anulowane</option>
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
function viewOrder(order) {
    let html = '<table style="width: 100%; margin-bottom: 1rem;">';
    html += '<tr><td><strong>Numer:</strong></td><td>' + order.order_number + '</td></tr>';
    html += '<tr><td><strong>Data:</strong></td><td>' + order.created_at + '</td></tr>';
    html += '<tr><td><strong>Klient:</strong></td><td>' + order.full_name + '</td></tr>';
    html += '<tr><td><strong>Email:</strong></td><td>' + order.email + '</td></tr>';
    
    if (order.phone) {
        html += '<tr><td><strong>Telefon:</strong></td><td>' + order.phone + '</td></tr>';
    }
    
    if (order.company) {
        html += '<tr><td><strong>Firma:</strong></td><td>' + order.company + '</td></tr>';
    }
    
    if (order.tax_id) {
        html += '<tr><td><strong>NIP:</strong></td><td>' + order.tax_id + '</td></tr>';
    }
    
    html += '<tr><td><strong>Status:</strong></td><td>' + order.status + '</td></tr>';
    html += '</table>';
    
    if (order.notes) {
        html += '<div style="background: #f5f5f5; padding: 1rem; border-radius: 8px; margin-top: 1rem;">';
        html += '<strong>Wiadomość:</strong><br>' + order.notes.replace(/\n/g, '<br>');
        html += '</div>';
    }
    
    if (order.products) {
        html += '<div style="margin-top: 1rem;"><strong>Produkty:</strong><br>';
        html += '<pre style="background: #f5f5f5; padding: 1rem; border-radius: 8px; overflow-x: auto;">';
        html += order.products;
        html += '</pre></div>';
    }
    
    document.getElementById('orderDetails').innerHTML = html;
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function changeStatus(orderId, currentStatus) {
    document.getElementById('status_order_id').value = orderId;
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