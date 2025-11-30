<?php
session_start();
require_once '../config.php';
require_once 'admin-auth.php';

// AJAX endpoint do pobierania danych użytkownika - MUSI BYĆ NA POCZĄTKU!
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_user' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($user);
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        echo json_encode(['error' => 'Błąd pobierania danych']);
    }
    exit; // WAŻNE - zakończ skrypt
}

$success = '';
$error = '';

// Obsługa usuwania użytkownika
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Nie pozwól usunąć samego siebie
    if ($user_id === $_SESSION['user_id']) {
        $error = "Nie możesz usunąć własnego konta";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "Użytkownik został usunięty";
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            $error = "Błąd podczas usuwania użytkownika";
        }
    }
}

// Obsługa edycji użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "Imię, nazwisko i email są wymagane";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $role, $phone, $address, $user_id]);
            $success = "Dane użytkownika zaktualizowane";
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            $error = "Błąd podczas aktualizacji danych";
        }
    }
}

// Obsługa zmiany hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (strlen($new_password) < 6) {
        $error = "Hasło musi mieć co najmniej 6 znaków";
    } else {
        try {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            $success = "Hasło zostało zmienione";
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            $error = "Błąd podczas zmiany hasła";
        }
    }
}

// Wyszukiwanie i filtry
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
        (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id) as total_spent
        FROM users u WHERE 1=1";

$params = [];

if ($search) {
    $search_param = "%$search%";
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($role_filter) {
    $sql .= " AND u.role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY u.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch users error: " . $e->getMessage());
    $users = [];
    $error = "Błąd podczas pobierania użytkowników";
}

require_once 'admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Zarządzanie użytkownikami</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="filters-bar">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Szukaj użytkownika..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="role">
                <option value="">Wszystkie role</option>
                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Użytkownik</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
            </select>
            <button type="submit" class="btn btn-primary">Szukaj</button>
            <?php if ($search || $role_filter): ?>
                <a href="shop-users.php" class="btn btn-secondary">Wyczyść</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imię i nazwisko</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Rola</th>
                        <th>Zamówienia</th>
                        <th>Wydano</th>
                        <th>Data rejestracji</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                Brak użytkowników
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Administrator' : 'Użytkownik'; ?>
                                    </span>
                                </td>
                                <td><?php echo $user['order_count'] ?? 0; ?></td>
                                <td><?php echo number_format($user['total_spent'] ?? 0, 2); ?> PLN</td>
                                <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="editUser(<?php echo $user['id']; ?>)">Edytuj</button>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <a href="shop-users.php?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?')">
                                            Usuń
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal edycji użytkownika -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edytuj użytkownika</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Imię</label>
                <input type="text" name="first_name" id="edit_first_name" required>
            </div>
            
            <div class="form-group">
                <label>Nazwisko</label>
                <input type="text" name="last_name" id="edit_last_name" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="form-group">
                <label>Rola</label>
                <select name="role" id="edit_role">
                    <option value="user">Użytkownik</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Telefon</label>
                <input type="tel" name="phone" id="edit_phone">
            </div>
            
            <div class="form-group">
                <label>Adres</label>
                <textarea name="address" id="edit_address" rows="3"></textarea>
            </div>
            
            <button type="submit" name="edit_user" class="btn btn-primary">Zapisz zmiany</button>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h3>Zmień hasło</h3>
        <form method="POST" id="passwordForm">
            <input type="hidden" name="user_id" id="password_user_id">
            
            <div class="form-group">
                <label>Nowe hasło (min. 6 znaków)</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            
            <button type="submit" name="change_password" class="btn btn-warning">Zmień hasło</button>
        </form>
    </div>
</div>

<style>
    .filters-bar {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .search-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .search-form input[type="text"],
    .search-form select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .search-form input[type="text"] {
        flex: 1;
        min-width: 200px;
    }
    
    .badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-primary {
        background: #007bff;
        color: white;
    }
    
    .badge-secondary {
        background: #6c757d;
        color: white;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background: white;
        margin: 50px auto;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .close {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
    }
    
    .close:hover {
        color: #000;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th,
    .data-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .data-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .data-table tr:hover {
        background: #f8f9fa;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
</style>

<script>
function editUser(userId) {
    // Pobierz dane użytkownika
    fetch('shop-users.php?ajax=get_user&id=' + userId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(user => {
            if (user.error) {
                alert('Błąd: ' + user.error);
                return;
            }
            
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('password_user_id').value = user.id;
            document.getElementById('edit_first_name').value = user.first_name || '';
            document.getElementById('edit_last_name').value = user.last_name || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role || 'user';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_address').value = user.address || '';
            
            document.getElementById('editModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Błąd podczas pobierania danych użytkownika');
        });
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Zamknij modal po kliknięciu poza nim
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once 'admin-footer.php'; ?>
