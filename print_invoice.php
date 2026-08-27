<?php
/**
 * TailorMate - Print Invoice
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$order_id = trim($_GET['order_id'] ?? '');
if (empty($order_id)) {
    redirect('view_orders.php', 'error', 'No order ID specified.');
}

$stmt = $db->prepare('
    SELECT o.*, c.name AS customer_name, c.phone, c.address
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = ?
');
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('view_orders.php', 'error', 'Order not found.');
}

$subtotal = (float)$order['price'];
$paid = (float)($order['paid_amount'] ?? 0);
$balance = (float)($order['balance'] ?? ($subtotal - $paid));

$pageTitle = 'Invoice ' . $order_id;
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header no-print">
    <h2>Invoice</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
        <a href="view_orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="invoice-container">
    <div class="invoice-header">
        <h1><i class="fas fa-cut me-2"></i>TailorMate</h1>
        <p>Tailoring Invoice</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="fw-bold">Customer Details</h6>
            <table class="table table-borderless table-sm">
                <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= e($order['customer_name'] ?? '-') ?></td></tr>
                <tr><td class="text-muted">Phone</td><td><?= e($order['phone'] ?? '-') ?></td></tr>
                <tr><td class="text-muted">Address</td><td><?= nl2br(e($order['address'] ?? '-')) ?></td></tr>
            </table>
        </div>
        <div class="col-md-6 text-md-end">
            <h6 class="fw-bold">Invoice Info</h6>
            <table class="table table-borderless table-sm">
                <tr><td class="text-muted">Invoice #</td><td class="fw-semibold"><?= e($order_id) ?></td></tr>
                <tr><td class="text-muted">Order Date</td><td><?= formatDate($order['order_date']) ?></td></tr>
                <tr><td class="text-muted">Delivery Date</td><td><?= formatDate($order['delivery_date']) ?></td></tr>
                <tr><td class="text-muted">Status</td><td><?= statusBadge($order['order_status']) ?></td></tr>
            </table>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Garment Type</th>
                <th>Design Preferences</th>
                <th class="text-end">Price (INR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= e($order['garment_type']) ?></td>
                <td><?= e($order['design_preferences'] ?? 'Standard') ?></td>
                <td class="text-end fw-semibold"><?= formatINR($subtotal) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="text-end mt-4" style="max-width: 300px; margin-left: auto;">
        <table class="table table-borderless">
            <tr><td class="text-muted">Subtotal</td><td class="fw-semibold"><?= formatINR($subtotal) ?></td></tr>
            <tr><td class="text-muted">Amount Paid</td><td class="text-success fw-semibold"><?= formatINR($paid) ?></td></tr>
            <tr class="border-top border-2"><td class="text-muted fw-bold">Balance Due</td><td class="fw-bold text-danger fs-5"><?= formatINR($balance) ?></td></tr>
        </table>
    </div>

    <div class="text-center mt-5 pt-3 border-top">
        <small class="text-muted">Thank you for your business!</small>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
