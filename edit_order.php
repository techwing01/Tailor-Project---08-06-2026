<?php
/**
 * TailorMate - Edit Order
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$order_id = trim($_GET['order_id'] ?? '');
if (empty($order_id)) {
    redirect('view_orders.php', 'error', 'No order ID specified.');
}

$stmt = $db->prepare('SELECT o.*, c.name AS customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ?');
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('view_orders.php', 'error', 'Order not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('edit_order.php?order_id=' . urlencode($order_id), 'error', 'Invalid request.');
    }

    $garment_type = trim($_POST['garment_type'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $order_status = trim($_POST['order_status'] ?? '');
    $delivery_date = trim($_POST['delivery_date'] ?? '');
    $paid_amount = trim($_POST['paid_amount'] ?? '0');
    $design_preferences = trim($_POST['design_preferences'] ?? '');

    // Validate
    $errors = [];
    if (!in_array($garment_type, getGarmentTypes())) $errors[] = 'Invalid garment type.';
    if (!is_numeric($price) || (float)$price <= 0) $errors[] = 'Enter a valid price.';
    if (!in_array($order_status, getOrderStatuses())) $errors[] = 'Invalid status.';
    if (empty($delivery_date)) $errors[] = 'Delivery date is required.';

    if (empty($errors)) {
        $balance = (float)$price - (float)$paid_amount;
        try {
            $stmt = $db->prepare('UPDATE orders SET garment_type = ?, price = ?, order_status = ?, delivery_date = ?, paid_amount = ?, balance = ?, design_preferences = ? WHERE order_id = ?');
            $stmt->execute([$garment_type, $price, $order_status, $delivery_date, $paid_amount, $balance, $design_preferences ?: null, $order_id]);
            redirect('view_orders.php', 'success', 'Order updated.');
        } catch (Exception $e) {
            setFlash('error', 'Error updating order.');
        }
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Edit Order';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Edit Order: <?= e($order_id) ?></h2>
    <a href="view_orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
    <!-- Order Form -->
    <div class="col-md-7">
        <div class="card p-4">
            <form method="POST" action="" novalidate>
                <?php csrfField(); ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="garment_type" class="form-label">Garment Type <span class="text-danger">*</span></label>
                        <select name="garment_type" class="form-select" required>
                            <?php foreach (getGarmentTypes() as $type): ?>
                                <option value="<?= e($type) ?>" <?= ($order['garment_type'] == $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price (INR) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" class="form-control" id="price" name="price"
                               value="<?= e($order['price']) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="order_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="order_status" class="form-select" required>
                            <?php foreach (getOrderStatuses() as $status): ?>
                                <option value="<?= e($status) ?>" <?= (strtolower($order['order_status']) == $status) ? 'selected' : '' ?>>
                                    <?= e(ucwords($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                               value="<?= e($order['delivery_date']) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="paid_amount" class="form-label">Paid Amount (INR)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="paid_amount" name="paid_amount"
                               value="<?= e($order['paid_amount'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Balance Due</label>
                        <input type="text" class="form-control bg-light" 
                               value="<?= formatINR($order['balance'] ?? ((float)$order['price'] - (float)($order['paid_amount'] ?? 0))) ?>" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="design_preferences" class="form-label">Design Preferences</label>
                    <input type="text" class="form-control" id="design_preferences" name="design_preferences"
                           value="<?= e($order['design_preferences'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Order</button>
            </form>
        </div>
    </div>

    <!-- Order Info Sidebar -->
    <div class="col-md-5">
        <div class="card p-3 mb-3">
            <h6 class="fw-bold"><i class="fas fa-info-circle me-1"></i>Order Info</h6>
            <table class="table table-borderless table-sm mb-0">
                <tr><td class="text-muted" style="width:100px">Order ID</td><td class="fw-semibold"><?= e($order_id) ?></td></tr>
                <tr><td class="text-muted">Customer</td><td><?= e($order['customer_name'] ?? $order['customer_id']) ?></td></tr>
                <tr><td class="text-muted">Order Date</td><td><?= formatDate($order['order_date']) ?></td></tr>
            </table>
        </div>
        <div class="card p-3">
            <h6 class="fw-bold"><i class="fas fa-print me-1"></i>Quick Actions</h6>
            <a href="print_invoice.php?order_id=<?= urlencode($order_id) ?>" class="btn btn-outline-primary w-100 mb-2">
                <i class="fas fa-print me-1"></i>Print Invoice
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
