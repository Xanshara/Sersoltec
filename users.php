<?php
/**
 * SERSOLTEC - ADMIN USERS MANAGEMENT
 * Zarządzanie użytkownikami admina
 */

require_once 'admin-auth.php';

$page_title = 'Zarządzanie Użytkownikami';

$success = '';
$error = '';

// Tylko superadmin może zarządzać użytkownikami
if (!isSuperAdmin()) {
    $error = 'Brak uprawnień do zarządzania użytkownikami';
}

// Obsługa usuwania
if (isSuperAdmin() && isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Nie można usunąć siebie
    if ($user_id === getAdminId()) {
        $error = 'Nie możesz usunąć własnego konta';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = 'Użytkownik został usunięty';
        } catch (Exception $e) {
            $error = 'Błąd podczas usuwania użytkownika';
        }
    }
}

// Obsługa dodawania/edycji
if (isSuperAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $password = $_POST['password'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Walidacja
    if (empty($username) || empty($email)) {
        $error = 'Proszę wypełnić wszystkie wymagane pola';
    } elseif (!isValidEmail($email)) {
        $error = 'Nieprawidłowy adres email';
    } elseif ($user_id === 0 && empty($password)) {
        $error = 'Hasło jest wymagane dla nowego użytkownika';
    } else {
        try {
            if ($user_id > 0) {
                // UPDATE
                if (!empty($password)) {
                    // Zmiana hasła
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE admin_users SET 
                            username = ?, 
                            email = ?, 
                            password = ?, 
                            role = ?, 
                            active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $hashed, $role, $active, $user_id]);
                } else {
                    // Bez zmiany hasła
                    $stmt = $pdo->prepare("
                        UPDATE admin_users SET 
                            username = ?, 
                            email = ?, 
                            role = ?, 
                            active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $role, $active, $user_id]);
                }
                $success = 'Użytkownik został zaktualizowany';
            } else {
                // INSERT
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO admin_users (username, email, password, role, active) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, $hashed, $role, $active]);
                $success = 'Użytkownik został dodany';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Nazwa użytkownika lub email już istnieje';
            } else {
                $error = 'Błąd zapisu: ' . $e->getMessage();
            }
        }
    }
}

// Pobierz użytkowników
$stmt = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

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

<?php if (isSuperAdmin()): ?>
    <!-- Formularz dodawania użytkownika -->
    <div class="admin-card">
        <h2>Dodaj Nowego Użytkownika</h2>
        
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nazwa użytkownika <span class="required">*</span></label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" required>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Hasło <span class="required">*</span></label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rola</label>
                    <select name="role" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" checked>
                    <span>Aktywny</span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                ➕ Dodaj Użytkownika
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Lista użytkowników -->
<div class="admin-card">
    <h2>Użytkownicy (<?php echo count($users); ?>)</h2>
    
    <?php if ($users): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th>Email</th>
                    <th>Rola</th>
                    <th>Ostatnie logowanie</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'superadmin'): ?>
                                <span class="badge badge-danger">Super Admin</span>
                            <?php else: ?>
                                <span class="badge badge-info">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['last_login']): ?>
                                <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?>
                            <?php else: ?>
                                <span style="color: #999;">Nigdy</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['active']): ?>
                                <span class="badge badge-success">Aktywny</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Nieaktywny</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <?php if (isSuperAdmin()): ?>
                                    <button class="btn btn-outline btn-sm btn-icon" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                            title="Edytuj">
                                        ✏️
                                    </button>
                                    <?php if ($user['id'] !== getAdminId()): ?>
                                        <a href="?delete=1&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-delete btn-sm btn-icon delete-btn" 
                                           title="Usuń">
                                            🗑️
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">Brak uprawnień</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <p>Brak użytkowników</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal edycji (ukryty) -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
        <h2 style="margin-top: 0;">Edytuj Użytkownika</h2>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label class="form-label">Nazwa użytkownika</label>
                <input type="text" name="username" id="edit_username" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="edit_email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Hasło (zostaw puste aby nie zmieniać)</label>
                <input type="password" name="password" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">Rola</label>
                <select name="role" id="edit_role" class="form-select">
                    <option value="admin">Admin</option>
                    <option value="superadmin">Super Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="active" id="edit_active" value="1">
                    <span>Aktywny</span>
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    Zapisz
                </button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">
                    Anuluj
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_active').checked = user.active == 1;
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Zamknij modal klikając poza nim
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include 'admin-footer.php'; ?>
