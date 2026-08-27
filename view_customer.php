<?php
/**
 * TailorMate - Customer Detail View
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Validate ID parameter
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('view_customers.php', 'error', 'Invalid customer ID.');
}

$stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('view_customers.php', 'error', 'Customer not found.');
}

// Fetch measurements grouped by garment type (latest per type)
$measStmt = $db->prepare('
    SELECT * FROM measurements 
    WHERE customer_id = ? 
    ORDER BY garment_type, created_at DESC
');
$measStmt->execute([$customer['customer_id']]);
$allMeasurements = $measStmt->fetchAll();

// Group: keep only the latest per garment type
$latestMeasurements = [];
foreach ($allMeasurements as $m) {
    if (!isset($latestMeasurements[$m['garment_type']])) {
        $latestMeasurements[$m['garment_type']] = $m;
    }
}

// Fetch orders for this customer
$orderStmt = $db->prepare('
    SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC
');
$orderStmt->execute([$customer['customer_id']]);
$orders = $orderStmt->fetchAll();

$pageTitle = 'Customer: ' . $customer['name'];
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Customer Details</h2>
    <a href="view_customers.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
    <!-- Customer Info Card -->
    <div class="col-md-4">
        <div class="card p-3">
            <h6 class="fw-bold mb-3"><i class="fas fa-user me-1"></i>Information</h6>
            <table class="table table-borderless mb-0">
                <tr><td class="text-muted" style="width:100px;">ID</td><td class="fw-semibold"><?= e($customer['customer_id']) ?></td></tr>
                <tr><td class="text-muted">Name</td><td><?= e($customer['name']) ?></td></tr>
                <tr><td class="text-muted">Phone</td><td><?= e($customer['phone']) ?></td></tr>
                <tr><td class="text-muted">Email</td><td><?= e($customer['email'] ?? '-') ?></td></tr>
                <tr><td class="text-muted">Address</td><td><?= e($customer['address'] ?? '-') ?></td></tr>
                <tr><td class="text-muted">Joined</td><td><?= formatDate($customer['created_at']) ?></td></tr>
            </table>
            <div class="d-flex gap-2 mt-3">
                <a href="edit_customer.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-outline-warning w-100"><i class="fas fa-pen me-1"></i>Edit</a>
                <a href="measurements.php?customer_id=<?= urlencode($customer['customer_id']) ?>" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-ruler me-1"></i>Measurements</a>
            </div>
        </div>
    </div>

    <!-- Measurements Card -->
    <div class="col-md-4">
        <div class="card p-3">
            <h6 class="fw-bold mb-3"><i class="fas fa-ruler me-1"></i>Latest Measurements</h6>
            <?php if (empty($latestMeasurements)): ?>
                <p class="text-muted">No measurements recorded.</p>
                <a href="measurements.php?customer_id=<?= urlencode($customer['customer_id']) ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Add Measurements
                </a>
            <?php else: ?>
                <ul class="nav nav-tabs mb-3" id="measTabs">
                    <?php $first = true; foreach ($latestMeasurements as $type => $m): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="tab" href="#meas-<?= strtolower($type) ?>"><?= e($type) ?></a>
                    </li>
                    <?php $first = false; endforeach; ?>
                </ul>
                <div class="tab-content">
                    <?php $first = true; foreach ($latestMeasurements as $type => $m): ?>
                    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="meas-<?= strtolower($type) ?>">
                        <?php
                        $prefix = strtolower($type) . '_';
                        $labels = [];
                        if ($type === 'Pant') {
                            $labels = ['waist'=>'Waist','seat'=>'Seat','length'=>'Length','fly'=>'Fly','loose'=>'Loose','centre'=>'Centre','centre_length'=>'Centre Length','bottom'=>'Bottom','pleat'=>'Pleat','pocket'=>'Pocket','ironing'=>'Ironing','pocket_style'=>'Pocket Style'];
                        } else {
                            $labels = ['length'=>'Length','shoulder'=>'Shoulder','sleeve_length'=>'Sleeve Length','sleeve_loose'=>'Sleeve Loose','collar'=>'Collar','loose'=>'Loose','centre_loose'=>'Centre Loose','bottom'=>'Bottom','front_style'=>'Front Style','shape'=>'Shape','cuff'=>'Cuff','pocket'=>'Pocket'];
                        }
                        foreach ($labels as $key => $label):
                            $val = $m[$prefix . $key] ?? null;
                            if ($val !== null && $val !== ''):
                        ?>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted"><?= $label ?></span>
                                <span class="fw-semibold"><?= e($val) ?><?= is_numeric($val) ? ' in' : '' ?></span>
                            </div>
                        <?php endif; endforeach; ?>
                        <?php if (!empty($m[$prefix . 'remarks'])): ?>
                            <div class="mt-2"><small class="text-muted">Remarks:</small> <?= nl2br(e($m[$prefix . 'remarks'])) ?></div>
                        <?php endif; ?>
                        <small class="text-muted d-block mt-2">Recorded: <?= formatDate($m['created_at']) ?></small>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="col-md-4">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-boxes me-1"></i>Orders</h6>
                <span class="badge bg-secondary"><?= count($orders) ?></span>
            </div>
            <?php if (empty($orders)): ?>
                <p class="text-muted">No orders yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Order</th><th>Type</th><th>Status</th><th>Amount</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($orders, 0, 5) as $o): ?>
                            <tr>
                                <td><a href="edit_order.php?order_id=<?= urlencode($o['order_id']) ?>" class="text-decoration-none"><?= e($o['order_id']) ?></a></td>
                                <td><?= e($o['garment_type']) ?></td>
                                <td><?= statusBadge($o['order_status']) ?></td>
                                <td><?= formatINR($o['price']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php if (count($orders) > 0): ?>
                <a href="add_order.php?customer_id=<?= urlencode($customer['customer_id']) ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                    <i class="fas fa-plus me-1"></i>New Order
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
