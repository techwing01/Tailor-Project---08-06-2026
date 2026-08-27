<?php
/**
 * TailorMate - Measurements
 * Add and view measurements for customers.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$customer_id = trim($_GET['customer_id'] ?? '');
$garment_type = trim($_GET['garment_type'] ?? '');

// Fetch customers for dropdown
$customers = $db->query('SELECT customer_id, name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

// Garment field definitions
$pantFields = [
    'waist' => 'Waist', 'seat' => 'Seat', 'length' => 'Length', 'fly' => 'Fly',
    'loose' => 'Loose', 'centre' => 'Centre', 'centre_length' => 'Centre Length', 'bottom' => 'Bottom',
    'pleat' => 'Pleat', 'pocket' => 'Pocket', 'ironing' => 'Ironing', 'pocket_style' => 'Pocket Style'
];
$shirtFields = [
    'length' => 'Length', 'shoulder' => 'Shoulder', 'sleeve_length' => 'Sleeve Length', 'sleeve_loose' => 'Sleeve Loose',
    'collar' => 'Collar', 'loose' => 'Loose', 'centre_loose' => 'Centre Loose', 'bottom' => 'Bottom',
    'front_style' => 'Front Style', 'shape' => 'Shape', 'cuff' => 'Cuff', 'pocket' => 'Pocket'
];

$selectOptions = [
    'pant_pleat'       => ['No Pleat', 'One Pleat', 'Two Pleats'],
    'pant_pocket'      => ['Single', 'Double'],
    'pant_ironing'     => ['Normal', 'Round'],
    'pant_pocket_style'=> ['Side Pocket', 'Cross Pocket', 'Round Pocket'],
    'shirt_front_style'=> ['Normal', 'Inbow', 'Box'],
    'shirt_shape'      => ['SLAC', 'AC'],
];

// Auto-fill from latest measurement
$values = [];
if ($customer_id && $garment_type && in_array($garment_type, ['Pant', 'Shirt'])) {
    $stmt = $db->prepare('SELECT * FROM measurements WHERE customer_id = ? AND garment_type = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$customer_id, $garment_type]);
    $latest = $stmt->fetch();
    if ($latest) {
        $fields = ($garment_type === 'Pant') ? $pantFields : $shirtFields;
        foreach ($fields as $key => $label) {
            $colName = strtolower($garment_type) . '_' . $key;
            $values[$key] = $latest[$colName] ?? '';
        }
        $colRemarks = strtolower($garment_type) . '_remarks';
        $values['remarks'] = $latest[$colRemarks] ?? '';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('measurements.php', 'error', 'Invalid request.');
    }

    $customer_id = trim($_POST['customer_id'] ?? '');
    $garment_type = trim($_POST['garment_type'] ?? '');

    if (empty($customer_id) || !in_array($garment_type, ['Pant', 'Shirt'])) {
        redirect('measurements.php', 'error', 'Select a valid customer and garment type.');
    }

    $fields = ($garment_type === 'Pant') ? $pantFields : $shirtFields;
    $columns = ['customer_id', 'garment_type'];
    $placeholders = ['?', '?'];
    $params = [$customer_id, $garment_type];
    $types = 'ss';

    foreach ($fields as $key => $label) {
        $colName = strtolower($garment_type) . '_' . $key;
        $val = trim($_POST[$key] ?? '');
        $columns[] = $colName;
        $placeholders[] = '?';
        $params[] = $val ?: null;
    }

    // Remarks
    $remarksCol = strtolower($garment_type) . '_remarks';
    $remarks = trim($_POST['remarks'] ?? '');
    $columns[] = $remarksCol;
    $placeholders[] = '?';
    $params[] = $remarks ?: null;

    $sql = 'INSERT INTO measurements (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        redirect('measurements.php?customer_id=' . urlencode($customer_id) . '&garment_type=' . urlencode($garment_type), 'success', 'Measurements saved!');
    } catch (Exception $e) {
        setFlash('error', 'Error saving measurements.');
    }
}

$pageTitle = 'Measurements';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Measurements</h2>
    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-home me-1"></i>Dashboard</a>
</div>

<!-- Customer & Garment Selection -->
<div class="card p-3 mb-3">
    <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Select Customer</label>
            <select name="customer_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select --</option>
                <?php foreach ($customers as $cid => $cname): ?>
                    <option value="<?= e($cid) ?>" <?= ($cid == $customer_id) ? 'selected' : '' ?>>
                        <?= e($cid) ?> - <?= e($cname) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Garment Type</label>
            <select name="garment_type" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select --</option>
                <option value="Pant" <?= ($garment_type === 'Pant') ? 'selected' : '' ?>>Pant</option>
                <option value="Shirt" <?= ($garment_type === 'Shirt') ? 'selected' : '' ?>>Shirt</option>
            </select>
        </div>
    </form>
</div>

<!-- Measurement Form -->
<?php if ($customer_id && $garment_type && in_array($garment_type, ['Pant', 'Shirt'])): ?>
<div class="card p-4" style="max-width: 820px;">
    <h5 class="fw-bold mb-3"><?= e($garment_type) ?> Measurements
        <small class="text-muted fw-normal">- <?= e($customers[$customer_id] ?? $customer_id) ?></small>
    </h5>
    <form method="POST" action="" novalidate>
        <?php csrfField(); ?>
        <input type="hidden" name="customer_id" value="<?= e($customer_id) ?>">
        <input type="hidden" name="garment_type" value="<?= e($garment_type) ?>">

        <?php
        $fields = ($garment_type === 'Pant') ? $pantFields : $shirtFields;
        $colPrefix = strtolower($garment_type) . '_';
        $i = 0;
        foreach ($fields as $key => $label):
            $selectKey = $colPrefix . $key;
            $val = $values[$key] ?? ($_POST[$key] ?? '');
            $isSelect = isset($selectOptions[$selectKey]);
            if ($i % 4 === 0) echo '<div class="row g-3 mb-3">';
        ?>
            <div class="col-md-3">
                <label class="form-label"><?= e($label) ?></label>
                <?php if ($isSelect): ?>
                    <select name="<?= $key ?>" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach ($selectOptions[$selectKey] as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= ($val == $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?= $key ?>" class="form-control" value="<?= e($val) ?>" placeholder="<?= e($label) ?>">
                <?php endif; ?>
            </div>
        <?php
            if ($i % 4 === 3 || $i === count($fields) - 1) echo '</div>';
            $i++;
        endforeach;
        ?>

        <div class="mb-3">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-control" rows="2" placeholder="Any special notes..."><?= e($values['remarks'] ?? ($_POST['remarks'] ?? '')) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Measurements</button>
    </form>
</div>
<?php elseif ($customer_id): ?>
<div class="card p-4 text-muted">
    <p>Please select a garment type above to enter measurements.</p>
</div>
<?php else: ?>
<div class="card p-4 text-center text-muted">
    <i class="fas fa-ruler fa-3x mb-3" style="opacity: 0.3;"></i>
    <p>Select a customer and garment type to manage measurements.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
