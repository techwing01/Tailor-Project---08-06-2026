<?php
/**
 * TailorMate - Add Order
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$order_id = generateUniqueID('ORD', $db, 'orders', 'order_id');
$prefillCustomerId = $_GET['customer_id'] ?? '';

// Fetch customers for dropdown
$customers = $db->query('SELECT customer_id, name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('add_order.php', 'error', 'Invalid request.');
    }

    $customer_id = trim($_POST['customer_id'] ?? '');
    $garment_type = trim($_POST['garment_type'] ?? '');
    $design_preferences = trim($_POST['design_preferences'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $order_status = trim($_POST['order_status'] ?? 'pending');
    $delivery_date = trim($_POST['delivery_date'] ?? '');

    // Validate
    $errors = [];
    if (empty($customer_id) || !isset($customers[$customer_id])) $errors[] = 'Please select a valid customer.';
    if (!in_array($garment_type, getGarmentTypes())) $errors[] = 'Please select a valid garment type.';
    if (!is_numeric($price) || (float)$price <= 0) $errors[] = 'Enter a valid price greater than 0.';
    if (empty($delivery_date)) $errors[] = 'Delivery date is required.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare('INSERT INTO orders (order_id, customer_id, garment_type, design_preferences, price, order_status, order_date, delivery_date, paid_amount, balance) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, 0, ?)');
            $balance = (float)$price;
            $stmt->execute([$order_id, $customer_id, $garment_type, $design_preferences ?: null, $price, $order_status, $delivery_date, $balance]);
            redirect('view_orders.php', 'success', 'Order ' . e($order_id) . ' created successfully!');
        } catch (Exception $e) {
            redirect('add_order.php', 'error', 'Error creating order. Please try again.');
        }
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Add Order';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Add New Order</h2>
    <a href="view_orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card p-4" style="max-width: 560px;">
    <form method="POST" action="" novalidate>
        <?php csrfField(); ?>
        <div class="mb-3">
            <label class="form-label">Order ID</label>
            <input type="text" class="form-control bg-light" value="<?= e($order_id) ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-select" id="customer_id" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $cid => $cname): ?>
                    <option value="<?= e($cid) ?>" <?= ($cid == $prefillCustomerId) ? 'selected' : '' ?>>
                        <?= e($cid) ?> - <?= e($cname) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="garment_type" class="form-label">Garment Type <span class="text-danger">*</span></label>
                <select name="garment_type" class="form-select" required>
                    <?php foreach (getGarmentTypes() as $type): ?>
                        <option value="<?= e($type) ?>" <?= (($_POST['garment_type'] ?? '') == $type) ? 'selected' : '' ?>>
                            <?= e($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="price" class="form-label">Price (INR) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="1" class="form-control" id="price" name="price"
                       value="<?= e($_POST['price'] ?? '') ?>" placeholder="e.g. 1500" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="design_preferences" class="form-label">Design Preferences</label>
            <input type="text" class="form-control" id="design_preferences" name="design_preferences"
                   value="<?= e($_POST['design_preferences'] ?? '') ?>" placeholder="e.g. Slim fit, Button-down collar">
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                       value="<?= e($_POST['delivery_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label for="order_status" class="form-label">Status</label>
                <select name="order_status" class="form-select">
                    <?php foreach (getOrderStatuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= (($_POST['order_status'] ?? 'pending') == $status) ? 'selected' : '' ?>>
                            <?= e(ucwords($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-cart-plus me-1"></i>Create Order</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
