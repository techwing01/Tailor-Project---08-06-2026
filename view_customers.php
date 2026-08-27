<?php
/**
 * TailorMate - View Customers (with search and pagination)
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Search
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = '';
$params = [];
if ($search) {
    $where = "WHERE name LIKE ? OR phone LIKE ? OR customer_id LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

// Count
$countSql = "SELECT COUNT(*) FROM customers $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();

// Pagination
$pag = paginate($totalItems, $page, $perPage);

// Fetch
$sql = "SELECT * FROM customers $where ORDER BY name LIMIT {$pag['offset']}, {$pag['per_page']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Handle delete with dependency check
if (isset($_POST['confirm_delete'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('view_customers.php', 'error', 'Invalid request.');
    }
    $customerId = $_POST['customer_id'] ?? '';
    try {
        $db->prepare('DELETE FROM measurements WHERE customer_id = ?')->execute([$customerId]);
        $db->prepare('DELETE FROM orders WHERE customer_id = ?')->execute([$customerId]);
        $db->prepare('DELETE FROM customers WHERE customer_id = ?')->execute([$customerId]);
        redirect('view_customers.php', 'success', 'Customer and all associated records deleted.');
    } catch (Exception $e) {
        redirect('view_customers.php', 'error', 'Error deleting customer: ' . $e->getMessage());
    }
}

// Check dependencies for delete modal
$deleteTarget = null;
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $depCount = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM orders WHERE customer_id = ?) as orders,
            (SELECT COUNT(*) FROM measurements WHERE customer_id = ?) as measurements
    ");
    $depCount->execute([$delId, $delId]);
    $deps = $depCount->fetch();
    if ($deps['orders'] > 0 || $deps['measurements'] > 0) {
        $deleteTarget = [
            'customer_id' => $delId,
            'orders' => (int)$deps['orders'],
            'measurements' => (int)$deps['measurements'],
        ];
    } else {
        // No dependencies - delete directly
        $db->prepare('DELETE FROM customers WHERE customer_id = ?')->execute([$delId]);
        redirect('view_customers.php', 'success', 'Customer deleted.');
    }
}

$pageTitle = 'Customers';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>View Customers <span class="badge bg-primary"><?= number_format($totalItems) ?></span></h2>
    <div class="d-flex gap-2">
        <a href="add_customer.php" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i>Add Customer</a>
    </div>
</div>

<!-- Search -->
<form method="GET" action="" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search by name, phone, or ID...">
        <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
        <?php if ($search): ?>
            <a href="view_customers.php" class="btn btn-outline-danger" title="Clear search"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </div>
</form>

<!-- Customers Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No customers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($c['customer_id']) ?></td>
                        <td><?= e($c['name']) ?></td>
                        <td><?= e($c['phone']) ?></td>
                        <td><?= e($c['email'] ?? '-') ?></td>
                        <td><?= e($c['address'] ?? '-') ?></td>
                        <td class="action-btns">
                            <a href="view_customer.php?id=<?= urlencode($c['id']) ?>" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_customer.php?id=<?= urlencode($c['id']) ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="view_customers.php?delete=<?= urlencode($c['customer_id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this customer?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$baseUrl = 'view_customers.php' . ($search ? '?search=' . urlencode($search) : '?search=');
paginationHtml($pag, $baseUrl);
?>

<!-- Delete Confirmation Modal -->
<?php if ($deleteTarget): ?>
<div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Deletion</h5>
                <a href="view_customers.php" class="btn-close"></a>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p>This customer has associated records:</p>
                    <ul>
                        <li><strong><?= $deleteTarget['orders'] ?></strong> order(s)</li>
                        <li><strong><?= $deleteTarget['measurements'] ?></strong> measurement record(s)</li>
                    </ul>
                    <p class="text-danger fw-bold">All associated records will be permanently deleted.</p>
                    <input type="hidden" name="customer_id" value="<?= e($deleteTarget['customer_id']) ?>">
                    <?php csrfField(); ?>
                </div>
                <div class="modal-footer">
                    <a href="view_customers.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="confirm_delete" class="btn btn-danger">Delete All</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
