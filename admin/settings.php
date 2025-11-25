<?php
/**
 * SERSOLTEC - ADMIN SETTINGS
 * Ustawienia globalne systemu
 */

require_once 'admin-auth.php';

$page_title = 'Ustawienia';

$success = '';
$error = '';

// Obsługa zapisu ustawień
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'company_name' => sanitize($_POST['company_name'] ?? ''),
        'company_phone' => sanitize($_POST['company_phone'] ?? ''),
        'company_email' => sanitize($_POST['company_email'] ?? ''),
        'company_address' => sanitize($_POST['company_address'] ?? ''),
        'vat_id' => sanitize($_POST['vat_id'] ?? ''),
        'currency' => sanitize($_POST['currency'] ?? 'EUR'),
        'tax_rate' => sanitize($_POST['tax_rate'] ?? '21'),
    ];
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE `value` = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $success = 'Ustawienia zostały zapisane';
    } catch (Exception $e) {
        $error = 'Błąd zapisu ustawień: ' . $e->getMessage();
    }
}

// Pobierz obecne ustawienia
$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings_raw = $stmt->fetchAll();

$settings = [];
foreach ($settings_raw as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

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
    <h2>Ustawienia Firmowe</h2>
    
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nazwa firmy</label>
                <input type="text" name="company_name" class="form-input" 
                       value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Telefon</label>
                <input type="text" name="company_phone" class="form-input" 
                       value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="company_email" class="form-input" 
                       value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">NIP / VAT ID</label>
                <input type="text" name="vat_id" class="form-input" 
                       value="<?php echo htmlspecialchars($settings['vat_id'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Adres</label>
            <textarea name="company_address" class="form-textarea" rows="3"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
        </div>
        
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--color-primary);">Ustawienia Finansowe</h3>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Waluta</label>
                <select name="currency" class="form-select">
                    <option value="EUR" <?php echo ($settings['currency'] ?? 'EUR') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                    <option value="PLN" <?php echo ($settings['currency'] ?? '') === 'PLN' ? 'selected' : ''; ?>>PLN (zł)</option>
                    <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                    <option value="GBP" <?php echo ($settings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Stawka VAT (%)</label>
                <input type="number" name="tax_rate" class="form-input" step="0.01" 
                       value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '21'); ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                💾 Zapisz Ustawienia
            </button>
        </div>
    </form>
</div>

<!-- Informacje systemowe -->
<div class="admin-card">
    <h2>Informacje Systemowe</h2>
    
    <table style="width: 100%;">
        <tr>
            <td style="padding: 0.5rem; width: 200px;"><strong>Wersja PHP:</strong></td>
            <td style="padding: 0.5rem;"><?php echo phpversion(); ?></td>
        </tr>
        <tr>
            <td style="padding: 0.5rem;"><strong>Serwer:</strong></td>
            <td style="padding: 0.5rem;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Nieznany'; ?></td>
        </tr>
        <tr>
            <td style="padding: 0.5rem;"><strong>Baza danych:</strong></td>
            <td style="padding: 0.5rem;">
                <?php
                $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                echo $version;
                ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 0.5rem;"><strong>Przestrzeń dyskowa:</strong></td>
            <td style="padding: 0.5rem;">
                <?php
                $free = disk_free_space('.');
                $total = disk_total_space('.');
                $used = $total - $free;
                $percentage = round(($used / $total) * 100, 2);
                echo number_format($used / 1024 / 1024 / 1024, 2) . ' GB / ' . number_format($total / 1024 / 1024 / 1024, 2) . ' GB (' . $percentage . '%)';
                ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 0.5rem;"><strong>Zainstalowane rozszerzenia:</strong></td>
            <td style="padding: 0.5rem;">
                <?php
                $extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'gd', 'curl'];
                $loaded = [];
                foreach ($extensions as $ext) {
                    if (extension_loaded($ext)) {
                        $loaded[] = '<span style="color: #4caf50;">✓ ' . $ext . '</span>';
                    } else {
                        $loaded[] = '<span style="color: #f44336;">✗ ' . $ext . '</span>';
                    }
                }
                echo implode(' &nbsp; ', $loaded);
                ?>
            </td>
        </tr>
    </table>
</div>

<!-- Statystyki bazy danych -->
<div class="admin-card">
    <h2>Statystyki Bazy Danych</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <?php
        $tables = ['products', 'categories', 'orders', 'inquiries', 'window_calculations', 'admin_users'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            
            echo '<div style="background: var(--color-light-gray); padding: 1.5rem; border-radius: 8px; text-align: center;">';
            echo '<div style="font-size: 2rem; font-weight: bold; color: var(--color-primary);">' . $count . '</div>';
            echo '<div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">' . ucfirst($table) . '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Narzędzia -->
<div class="admin-card">
    <h2>Narzędzia</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <button onclick="clearCache()" class="btn btn-outline" style="width: 100%;">
            🗑️ Wyczyść Cache
        </button>
        
        <button onclick="if(confirm('Czy na pewno chcesz wyczyścić wszystkie sesje? Wszyscy użytkownicy zostaną wylogowani.')) { clearSessions(); }" class="btn btn-outline" style="width: 100%;">
            🔒 Wyczyść Sesje
        </button>
        
        <a href="?export_db=1" class="btn btn-outline" style="width: 100%; text-align: center; display: inline-block;">
            💾 Backup Bazy
        </a>
        
        <button onclick="window.open('../', '_blank')" class="btn btn-outline" style="width: 100%;">
            🌐 Zobacz Stronę
        </button>
    </div>
</div>

<script>
function clearCache() {
    alert('Funkcja czyszczenia cache zostanie wkrótce zaimplementowana.');
}

function clearSessions() {
    fetch('?clear_sessions=1')
        .then(response => response.text())
        .then(data => {
            alert('Sesje zostały wyczyszczone');
            location.reload();
        });
}
</script>

<?php
// Obsługa czyszczenia sesji
if (isset($_GET['clear_sessions'])) {
    session_destroy();
    echo 'OK';
    exit;
}
?>

<?php include 'admin-footer.php'; ?>
