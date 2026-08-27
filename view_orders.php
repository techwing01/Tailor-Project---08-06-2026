<?php
/**
 * TailorMate - View Orders (with search, filter, and pagination)
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Handle delete
if (isset($_POST['delete_order'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('view_orders.php', 'error', 'Invalid request.');
    }
    $orderId = $_POST['order_id'] ?? '';
    $db->prepare('DELETE FROM orders WHERE order_id = ?')->execute([$orderId]);
    redirect('view_orders.php', 'success', 'Order deleted.');
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Build WHERE clause
$where = '';
$params = [];
$conditions = [];

if ($search) {
    $conditions[] = "(o.order_id LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

if ($filter === 'overdue') {
    $conditions[] = "o.delivery_date < CURDATE() AND o.order_status NOT IN ('delivered', 'cancelled')";
} elseif ($filter && in_array($filter, getOrderStatuses())) {
    $conditions[] = "o.order_status = ?";
    $params[] = $filter;
}

if (!empty($conditions)) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

// Count
$countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();

$pag = paginate($totalItems, $page, $perPage);

// Fetch orders
$sql = "SELECT o.*, c.name AS customer_name, c.phone
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        $where
        ORDER BY o.order_date DESC, o.id DESC
        LIMIT {$pag['offset']}, {$pag['per_page']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Orders <span class="badge bg-primary"><?= number_format($totalItems) ?></span></h2>
    <a href="add_order.php" class="btn btn-primary"><i class="fas fa-cart-plus me-1"></i>Add Order</a>
</div>

<!-- Search & Filter -->
<form method="GET" action="" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search orders, customers...">
        </div>
        <div class="col-md-3">
            <select name="filter" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach (getOrderStatuses() as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($filter == $s) ? 'selected' : '' ?>><?= e(ucwords($s)) ?></option>
                <?php endforeach; ?>
                <option value="overdue" <?= ($filter == 'overdue') ? 'selected' : '' ?>>Overdue</option>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i> Filter</button>
        </div>
        <?php if ($search || $filter): ?>
            <div class="col-md-auto">
                <a href="view_orders.php" class="btn btn-outline-danger"><i class="fas fa-times"></i> Clear</a>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- Orders Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Garment</th>
                    <th>Price</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Delivery</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $isOverdue = $o['delivery_date'] < date('Y-m-d') && !in_array(strtolower($o['order_status']), ['delivered', 'cancelled']);
                    ?>
                    <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                        <td class="fw-semibold"><?= e($o['order_id']) ?></td>
                        <td>
                            <?php if ($o['customer_name']): ?>
                                <a href="view_customer.php?id=<?= urlencode($o['customer_id']) ?>" class="text-decoration-none"><?= e($o['customer_name']) ?></a>
                            <?php else: ?>
                                <?= e($o['customer_id']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e($o['phone'] ?? '-') ?></td>
                        <td><?= e($o['garment_type']) ?></td>
                        <td><?= formatINR($o['price']) ?></td>
                        <td><?= formatINR($o['paid_amount'] ?? 0) ?></td>
                        <td class="fw-semibold <?= ((float)($o['balance'] ?? 0) > 0) ? 'text-danger' : 'text-success' ?>"><?= formatINR($o['balance'] ?? 0) ?></td>
                        <td><?= statusBadge($o['order_status']) ?></td>
                        <td class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>"><?= formatDate($o['delivery_date']) ?></td>
                        <td class="action-btns">
                            <a href="print_invoice.php?order_id=<?= urlencode($o['order_id']) ?>" class="btn btn-sm btn-outline-primary" title="Invoice">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="edit_order.php?order_id=<?= urlencode($o['order_id']) ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this order?')">
                                <input type="hidden" name="order_id" value="<?= e($o['order_id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCSRF()) ?>">
                                <button type="submit" name="delete_order" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$baseUrl = 'view_orders.php?' . http_build_query(array_filter(['search' => $search, 'filter' => $filter]));
paginationHtml($pag, $baseUrl);
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
