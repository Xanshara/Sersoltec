<?php
// ===== KALKULATOR OKIEN =====

require_once '../config.php';

$current_lang = getCurrentLanguage();

// Definicje cen dla kalkulatora
$window_types = [
    'single' => ['pl' => 'Jedno skrzydło', 'en' => 'Single', 'es' => 'Simple'],
    'double' => ['pl' => 'Dwa skrzydła', 'en' => 'Double', 'es' => 'Doble'],
    'triple' => ['pl' => 'Trzy skrzydła', 'en' => 'Triple', 'es' => 'Triple'],
];

$materials = [
    'pvc' => ['price_factor' => 1.0],
    'wood' => ['price_factor' => 1.5],
    'aluminium' => ['price_factor' => 1.3],
];

$glass_types = [
    'double' => ['price_factor' => 1.0],
    'triple' => ['price_factor' => 1.4],
];

$opening_types = [
    'tilt_turn' => ['price_factor' => 1.0],
    'fixed' => ['price_factor' => 0.7],
    'sliding' => ['price_factor' => 1.2],
];

// Base price per m2
$base_price = 120; // EUR/m2

$result = null;
$error = null;

// Oblicz jeśli POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $width = isset($_POST['width']) ? (int)$_POST['width'] : 0;
    $height = isset($_POST['height']) ? (int)$_POST['height'] : 0;
    $type = isset($_POST['type']) ? sanitize($_POST['type']) : '';
    $material = isset($_POST['material']) ? sanitize($_POST['material']) : '';
    $glass = isset($_POST['glass']) ? sanitize($_POST['glass']) : '';
    $opening = isset($_POST['opening']) ? sanitize($_POST['opening']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if (!$width || !$height || !$type || !$material || !$glass || !$opening) {
        $error = t('calc_error');
    } else {
        // Oblicz powierzchnię w m2
        $area_m2 = ($width / 1000) * ($height / 1000);
        
        // Zbierz faktory
        $material_factor = $materials[$material]['price_factor'] ?? 1.0;
        $glass_factor = $glass_types[$glass]['price_factor'] ?? 1.0;
        $opening_factor = $opening_types[$opening]['price_factor'] ?? 1.0;
        
        // Cena za jedno okno
        $price_per_unit = $base_price * $area_m2 * $material_factor * $glass_factor * $opening_factor;
        
        // Całkowita cena
        $total_price = $price_per_unit * $quantity;
        
        $result = [
            'width' => $width,
            'height' => $height,
            'area_m2' => $area_m2,
            'price_per_unit' => $price_per_unit,
            'quantity' => $quantity,
            'total_price' => $total_price,
            'details' => [
                'type' => $window_types[$type][$current_lang] ?? $type,
                'material' => t('calc_material_' . $material),
                'glass' => t('calc_glass_' . $glass),
                'opening' => t('calc_opening_' . $opening),
            ]
        ];
        
        // Zapisz do bazy (opcjonalnie)
        $stmt = $pdo->prepare(
            "INSERT INTO window_calculations (width, height, type, material, glass_type, opening_type, quantity, estimated_price, data) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $width,
            $height,
            $type,
            $material,
            $glass,
            $opening,
            $quantity,
            $total_price,
            json_encode(['type' => $type, 'material' => $material, 'glass' => $glass, 'opening' => $opening])
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('calc_title'); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .calculator { max-width: 700px; }
        .calc-grid { grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    </style>
    <link rel="stylesheet" href="../assets/css/chatbot-widget.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<!-- PAGE HEADER -->
<section class="page-header" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); color: white; padding: 3rem 1rem;">
    <div class="container" style="text-align: center;">
        <h1><?php echo t('calc_title'); ?></h1>
        <p><?php echo t('calc_subtitle'); ?></p>
    </div>
</section>

<!-- CALCULATOR -->
<section style="padding: 3rem 1rem;">
    <div class="container">
        <div class="calculator">
            <?php if ($error): ?>
                <div class="form-error" style="background: #ffebee; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; color: #d32f2f;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($result): ?>
                <div class="calc-result">
                    <div class="calc-result-label">Szacunkowa cena</div>
                    <div class="calc-result-value"><?php echo formatPrice($result['total_price']); ?></div>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--color-text);">
                        <?php echo $result['quantity']; ?> x <?php echo htmlspecialchars($result['details']['type']); ?> 
                        (<?php printf('%.2f', $result['area_m2']); ?> m²)
                    </p>
                </div>
                
<div style="background: var(--color-light-gray); padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
    <h4 style="margin-bottom: 0.5rem;"><?php echo t('calc_paramsa_title'); ?>:</h4>
    <ul style="list-style: none;">
        <li><strong><?php echo t('calc_resulta_material'); ?>:</strong> <?php echo htmlspecialchars($result['details']['material']); ?></li>
        <li><strong><?php echo t('calc_resulta_glass'); ?>:</strong> <?php echo htmlspecialchars($result['details']['glass']); ?></li>
        <li><strong><?php echo t('calc_resulta_opening'); ?>:</strong> <?php echo htmlspecialchars($result['details']['opening']); ?></li>
        <li><strong><?php echo t('calc_resulta_dimensions'); ?>:</strong> <?php echo $result['width']; ?>x<?php echo $result['height']; ?> mm</li>
    </ul>
</div>
            <?php endif; ?>
            
            <form method="POST" style="display: grid; gap: 1.5rem;">
                <!-- Wymiary -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label"><?php echo t('calc_width'); ?> (mm)</label>
                        <input type="number" name="width" class="form-input" placeholder="np. 900" required 
                               value="<?php echo isset($_POST['width']) ? (int)$_POST['width'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo t('calc_height'); ?> (mm)</label>
                        <input type="number" name="height" class="form-input" placeholder="np. 1200" required
                               value="<?php echo isset($_POST['height']) ? (int)$_POST['height'] : ''; ?>">
                    </div>
                </div>
                
                <!-- Typ -->
                <div class="form-group">
                    <label class="form-label"><?php echo t('calc_type'); ?></label>
                    <select name="type" class="form-select" required>
                        <option value="">--- Wybierz ---</option>
                        <?php foreach ($window_types as $key => $type): ?>
                            <option value="<?php echo $key; ?>" <?php if (isset($_POST['type']) && $_POST['type'] === $key) echo 'selected'; ?>>
                                <?php echo $type[$current_lang]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Materiał -->
                <div class="form-group">
                    <label class="form-label"><?php echo t('calc_material'); ?></label>
                    <select name="material" class="form-select" required>
                        <option value="">--- Wybierz ---</option>
                        <?php foreach ($materials as $key => $material): ?>
                            <option value="<?php echo $key; ?>" <?php if (isset($_POST['material']) && $_POST['material'] === $key) echo 'selected'; ?>>
                                <?php echo t('calc_material_' . $key); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Szyba -->
                <div class="form-group">
                    <label class="form-label"><?php echo t('calc_glass'); ?></label>
                    <select name="glass" class="form-select" required>
                        <option value="">--- Wybierz ---</option>
                        <?php foreach ($glass_types as $key => $glass): ?>
                            <option value="<?php echo $key; ?>" <?php if (isset($_POST['glass']) && $_POST['glass'] === $key) echo 'selected'; ?>>
                                <?php echo t('calc_glass_' . $key); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Otwarcie -->
                <div class="form-group">
                    <label class="form-label"><?php echo t('calc_opening'); ?></label>
                    <select name="opening" class="form-select" required>
                        <option value="">--- Wybierz ---</option>
                        <?php foreach ($opening_types as $key => $opening): ?>
                            <option value="<?php echo $key; ?>" <?php if (isset($_POST['opening']) && $_POST['opening'] === $key) echo 'selected'; ?>>
                                <?php echo t('calc_opening_' . $key); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Ilość -->
                <div class="form-group">
                    <label class="form-label"><?php echo t('calc_quantity'); ?></label>
                    <input type="number" name="quantity" class="form-input" placeholder="1" min="1" value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : '1'; ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <?php echo t('calc_estimate'); ?>
                </button>
            </form>
            
            <?php if ($result): ?>
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-gray);">
                    <a href="contact.php?calc_type=<?php echo $result['details']['type']; ?>&calc_price=<?php echo $result['total_price']; ?>" class="btn btn-outline btn-lg" style="width: 100%;">
                        Poproś o ofertę
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section style="background: var(--color-light-gray); padding: 2rem 1rem; text-align: center;">
    <div class="container">
        <h3><?php echo t('calc_notice_title'); ?></h3>
        <p style="margin-top: 1rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            <?php echo t('calc_notice_text'); ?>
        </p>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/chatbot-widget.js"></script>
</body>
</html>