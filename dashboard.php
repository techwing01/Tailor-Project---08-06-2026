<?php
/**
 * TailorMate - Dashboard
 * Enhanced business overview.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$username = e($_SESSION['username']);

$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$totalCustomers = (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

$totalRevenue = (float)$db->query("
    SELECT COALESCE(SUM(price), 0)
    FROM orders
    WHERE LOWER(order_status) != 'cancelled'
")->fetchColumn();

$totalCollected = (float)$db->query("
    SELECT COALESCE(SUM(paid_amount), 0)
    FROM orders
    WHERE LOWER(order_status) != 'cancelled'
")->fetchColumn();

$totalOutstanding = (float)$db->query("
    SELECT COALESCE(SUM(
        CASE
            WHEN balance IS NOT NULL THEN balance
            ELSE GREATEST(price - COALESCE(paid_amount, 0), 0)
        END
    ), 0)
    FROM orders
    WHERE LOWER(order_status) != 'cancelled'
")->fetchColumn();

$pendingOrders = (int)$db->query("
    SELECT COUNT(*) FROM orders
    WHERE LOWER(order_status) = 'pending'
")->fetchColumn();

$inProgressOrders = (int)$db->query("
    SELECT COUNT(*) FROM orders
    WHERE LOWER(order_status) = 'in progress'
")->fetchColumn();

$readyOrders = (int)$db->query("
    SELECT COUNT(*) FROM orders
    WHERE LOWER(order_status) = 'ready'
")->fetchColumn();

$deliveredOrders = (int)$db->query("
    SELECT COUNT(*) FROM orders
    WHERE LOWER(order_status) = 'delivered'
")->fetchColumn();

$cancelledOrders = (int)$db->query("
    SELECT COUNT(*) FROM orders
    WHERE LOWER(order_status) = 'cancelled'
")->fetchColumn();

$overdueStmt = $db->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE delivery_date < CURDATE()
      AND LOWER(order_status) NOT IN ('delivered', 'cancelled')
");
$overdueStmt->execute();
$overdueCount = (int)$overdueStmt->fetchColumn();

$dueTodayStmt = $db->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE delivery_date = CURDATE()
      AND LOWER(order_status) NOT IN ('delivered', 'cancelled')
");
$dueTodayStmt->execute();
$dueTodayCount = (int)$dueTodayStmt->fetchColumn();

$statusData = [
    'pending'     => $pendingOrders,
    'in progress' => $inProgressOrders,
    'ready'       => $readyOrders,
    'delivered'   => $deliveredOrders,
    'cancelled'   => $cancelledOrders
];

$recentStmt = $db->prepare("
    SELECT
        o.order_id,
        o.customer_id,
        c.name AS customer_name,
        o.garment_type,
        o.order_date,
        o.order_status,
        o.price,
        o.paid_amount,
        o.balance,
        o.delivery_date
    FROM orders o
    LEFT JOIN customers c
        ON o.customer_id = c.customer_id
    ORDER BY o.order_date DESC, o.id DESC
    LIMIT 8
");
$recentStmt->execute();
$recentOrders = $recentStmt->fetchAll();

$upcomingStmt = $db->prepare("
    SELECT
        o.order_id,
        o.customer_id,
        c.name AS customer_name,
        o.garment_type,
        o.order_status,
        o.delivery_date
    FROM orders o
    LEFT JOIN customers c
        ON o.customer_id = c.customer_id
    WHERE o.delivery_date IS NOT NULL
      AND o.delivery_date >= CURDATE()
      AND LOWER(o.order_status) NOT IN ('delivered', 'cancelled')
    ORDER BY o.delivery_date ASC, o.id ASC
    LIMIT 6
");
$upcomingStmt->execute();
$upcomingOrders = $upcomingStmt->fetchAll();

$pageTitle = 'Dashboard';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <div>
        <h2 class="mb-1">Dashboard</h2>
        <p class="text-muted mb-0">
            Welcome back, <?= $username ?>. Here's your tailoring business overview.
        </p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="add_customer.php" class="btn btn-outline-primary">
            <i class="fas fa-user-plus me-1"></i>
            Customer
        </a>

        <a href="add_order.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>
            New Order
        </a>
    </div>
</div>

<div class="row g-3 mb-3">

    <div class="col-6 col-xl-3">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small">Total Orders</div>
                    <div class="fs-3 fw-bold mt-1">
                        <?= number_format($totalOrders) ?>
                    </div>
                </div>

                <div class="stat-icon blue">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>

            <a href="view_orders.php" class="small text-decoration-none mt-3 d-inline-block">
                View all orders →
            </a>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small">Customers</div>
                    <div class="fs-3 fw-bold mt-1">
                        <?= number_format($totalCustomers) ?>
                    </div>
                </div>

                <div class="stat-icon green">
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <a href="view_customers.php" class="small text-decoration-none mt-3 d-inline-block">
                Manage customers →
            </a>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fs-4 fw-bold mt-1">
                        <?= formatINR($totalRevenue) ?>
                    </div>
                </div>

                <div class="stat-icon orange">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
            </div>

            <div class="small text-muted mt-3">
                <?= formatINR($totalCollected) ?> collected
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small">Outstanding</div>
                    <div class="fs-4 fw-bold mt-1 text-danger">
                        <?= formatINR($totalOutstanding) ?>
                    </div>
                </div>

                <div class="stat-icon purple">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <div class="small text-muted mt-3">
                Amount still to collect
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-3">

    <div class="col-6 col-md-3">
        <a href="view_orders.php?filter=pending"
           class="card p-3 h-100 text-decoration-none text-reset">
            <div class="d-flex justify-content-between">
                <span class="text-muted">Pending</span>
                <i class="fas fa-hourglass-half text-warning"></i>
            </div>
            <div class="fs-3 fw-bold mt-2">
                <?= number_format($pendingOrders) ?>
            </div>
            <small class="text-muted">Awaiting work</small>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="view_orders.php?filter=in%20progress"
           class="card p-3 h-100 text-decoration-none text-reset">
            <div class="d-flex justify-content-between">
                <span class="text-muted">In Progress</span>
                <i class="fas fa-person-running text-primary"></i>
            </div>
            <div class="fs-3 fw-bold mt-2">
                <?= number_format($inProgressOrders) ?>
            </div>
            <small class="text-muted">Currently being stitched</small>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="view_orders.php?filter=ready"
           class="card p-3 h-100 text-decoration-none text-reset">
            <div class="d-flex justify-content-between">
                <span class="text-muted">Ready</span>
                <i class="fas fa-check-circle text-info"></i>
            </div>
            <div class="fs-3 fw-bold mt-2">
                <?= number_format($readyOrders) ?>
            </div>
            <small class="text-muted">Ready for delivery</small>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="view_orders.php?filter=delivered"
           class="card p-3 h-100 text-decoration-none text-reset">
            <div class="d-flex justify-content-between">
                <span class="text-muted">Delivered</span>
                <i class="fas fa-circle-check text-success"></i>
            </div>
            <div class="fs-3 fw-bold mt-2">
                <?= number_format($deliveredOrders) ?>
            </div>
            <small class="text-muted">Completed orders</small>
        </a>
    </div>

</div>

<?php if ($overdueCount > 0 || $dueTodayCount > 0): ?>

<div class="row g-3 mb-3">

    <?php if ($overdueCount > 0): ?>
    <div class="col-md-6">
        <div class="alert alert-danger d-flex align-items-center mb-0 h-100">
            <i class="fas fa-triangle-exclamation fs-4 me-3"></i>

            <div>
                <strong><?= number_format($overdueCount) ?> overdue order(s)</strong>
                <div class="small">
                    These orders have passed their delivery date.
                </div>
            </div>

            <a href="view_orders.php?filter=overdue"
               class="btn btn-sm btn-danger ms-auto">
                View
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($dueTodayCount > 0): ?>
    <div class="col-md-6">
        <div class="alert alert-warning d-flex align-items-center mb-0 h-100">
            <i class="fas fa-calendar-day fs-4 me-3"></i>

            <div>
                <strong><?= number_format($dueTodayCount) ?> order(s) due today</strong>
                <div class="small">
                    Check these before the end of the day.
                </div>
            </div>

            <a href="view_orders.php"
               class="btn btn-sm btn-warning ms-auto">
                Orders
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php endif; ?>

<div class="row g-3 mb-3">

    <div class="col-lg-5">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1">Order Status</h6>
                    <small class="text-muted">Current workflow</small>
                </div>
            </div>

            <div class="chart-container" style="height:280px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1">Upcoming Deliveries</h6>
                    <small class="text-muted">Next orders to complete</small>
                </div>

                <a href="view_orders.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Garment</th>
                            <th>Delivery</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($upcomingOrders)): ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No upcoming deliveries.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($upcomingOrders as $row): ?>

                        <tr>
                            <td>
                                <a
                                    href="edit_order.php?order_id=<?= urlencode($row['order_id']) ?>"
                                    class="fw-semibold text-decoration-none"
                                >
                                    <?= e($row['order_id']) ?>
                                </a>
                            </td>

                            <td>
                                <?= e($row['customer_name'] ?? $row['customer_id']) ?>
                            </td>

                            <td>
                                <?= e($row['garment_type']) ?>
                            </td>

                            <td class="fw-semibold">
                                <?= formatDate($row['delivery_date']) ?>
                            </td>

                            <td>
                                <?= statusBadge($row['order_status']) ?>
                            </td>
                        </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="card p-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-1">Recent Orders</h6>
            <small class="text-muted">Latest activity</small>
        </div>

        <a href="view_orders.php" class="btn btn-sm btn-outline-primary">
            View All Orders
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">

            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Garment</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($recentOrders)): ?>

                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-2x mb-2"></i>
                        <div>No orders yet.</div>
                        <a href="add_order.php" class="btn btn-sm btn-primary mt-2">
                            Create First Order
                        </a>
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($recentOrders as $row): ?>

                <?php
                    $balance = (float)($row['balance'] ?? 0);
                    $isOverdue =
                        !empty($row['delivery_date']) &&
                        $row['delivery_date'] < date('Y-m-d') &&
                        !in_array(
                            strtolower($row['order_status']),
                            ['delivered', 'cancelled'],
                            true
                        );
                ?>

                <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">

                    <td>
                        <a
                            href="edit_order.php?order_id=<?= urlencode($row['order_id']) ?>"
                            class="fw-semibold text-decoration-none"
                        >
                            <?= e($row['order_id']) ?>
                        </a>
                    </td>

                    <td>
                        <a
                            href="view_customer.php?id=<?= urlencode($row['customer_id']) ?>"
                            class="text-decoration-none"
                        >
                            <?= e($row['customer_name'] ?? $row['customer_id']) ?>
                        </a>
                    </td>

                    <td>
                        <?= e($row['garment_type']) ?>
                    </td>

                    <td>
                        <?= formatDate($row['order_date']) ?>
                    </td>

                    <td>
                        <?= statusBadge($row['order_status']) ?>

                        <?php if ($isOverdue): ?>
                            <span class="badge bg-danger ms-1">Overdue</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-end">
                        <?= formatINR($row['price']) ?>
                    </td>

                    <td class="text-end text-success">
                        <?= formatINR($row['paid_amount'] ?? 0) ?>
                    </td>

                    <td class="text-end fw-semibold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= formatINR($balance) ?>
                    </td>

                    <td class="text-end">
                        <a
                            href="print_invoice.php?order_id=<?= urlencode($row['order_id']) ?>"
                            class="btn btn-sm btn-outline-primary"
                            title="Print Invoice"
                        >
                            <i class="fas fa-print"></i>
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
$statusJson = json_encode(
    $statusData,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$extraScripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function () {

    const statusMap = $statusJson;

    const labels = [
        'Pending',
        'In Progress',
        'Ready',
        'Delivered',
        'Cancelled'
    ];

    const data = labels.map(function (label) {
        return Number(statusMap[label.toLowerCase()] || 0);
    });

    const canvas = document.getElementById('statusChart');

    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const existing = Chart.getChart(canvas);

    if (existing) {
        existing.destroy();
    }

    const total = data.reduce(function (sum, value) {
        return sum + value;
    }, 0);

    new Chart(canvas, {
        type: 'doughnut',

        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#f59e0b',
                    '#3b82f6',
                    '#06b6d4',
                    '#22c55e',
                    '#ef4444'
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',

            animation: {
                duration: 400
            },

            plugins: {
                legend: {
                    position: 'bottom',

                    labels: {
                        padding: 16,
                        usePointStyle: true
                    }
                },

                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = context.raw || 0;
                            return ' ' + context.label + ': ' + value;
                        }
                    }
                }
            }
        },

        plugins: [{
            id: 'centerText',

            afterDraw: function (chart) {

                const area = chart.chartArea;

                if (!area) {
                    return;
                }

                const x = (area.left + area.right) / 2;
                const y = (area.top + area.bottom) / 2;

                const ctx = chart.ctx;

                ctx.save();

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.font = '700 28px Arial';
                ctx.fillStyle = '#212529';

                ctx.fillText(String(total), x, y - 8);

                ctx.font = '500 12px Arial';
                ctx.fillStyle = '#6c757d';

                ctx.fillText('Total Orders', x, y + 18);

                ctx.restore();
            }
        }]
    });
});
</script>
JS;

require_once __DIR__ . '/includes/footer.php';
?>
