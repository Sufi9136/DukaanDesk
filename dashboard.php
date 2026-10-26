<?php
require_once __DIR__ . '/inc/auth.php';
guard('dashboard');

// ---- numbers for the top cards ----
$todaySales  = $db->query("SELECT COALESCE(SUM(total),0) FROM bills WHERE created_at >= CURDATE()")->fetchColumn();
$todayBills  = $db->query("SELECT COUNT(*) FROM bills WHERE created_at >= CURDATE()")->fetchColumn();
$monthSales  = $db->query("SELECT COALESCE(SUM(total),0) FROM bills WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$totalProd   = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowCount    = $db->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= min_stock")->fetchColumn();
$outCount    = $db->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();
$custCount   = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$suppCount   = $db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();

// ---- monthly sales for the last 12 months ----
$rows = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, SUM(total) AS amount
                    FROM bills
                    WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
                    GROUP BY ym")->fetchAll(PDO::FETCH_KEY_PAIR);

$chartLabels = array();
$chartValues = array();
for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime(date('Y-m-01') . " -$i month");
    $chartLabels[] = date('M Y', $ts);
    $chartValues[] = isset($rows[date('Y-m', $ts)]) ? (float)$rows[date('Y-m', $ts)] : 0;
}

// ---- top 5 selling products ----
$topProducts = $db->query("SELECT item_name, SUM(qty) AS sold FROM bill_items
                           GROUP BY item_name ORDER BY sold DESC LIMIT 5")->fetchAll();

// ---- recent bills and low stock list ----
$recent = $db->query("SELECT b.id, b.bill_no, b.total, b.created_at, c.name AS customer
                      FROM bills b LEFT JOIN customers c ON c.id = b.customer_id
                      ORDER BY b.id DESC LIMIT 6")->fetchAll();

$lowList = $db->query("SELECT name, stock, min_stock FROM products
                       WHERE stock <= min_stock ORDER BY stock ASC LIMIT 7")->fetchAll();

$page_title = 'Dashboard';
$active = 'dashboard';
include 'inc/header.php';
?>

<?php if (can('billing')): ?>
    <div class="mb-3">
        <a href="billing.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Bill</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat"><div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Today's Sales</div>
                <div class="value"><?= rs($todaySales) ?></div>
                <small class="text-muted"><?= (int)$todayBills ?> bills today</small>
            </div>
            <i class="bi bi-cash-coin icon"></i>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat blue"><div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="label">This Month</div>
                <div class="value"><?= rs($monthSales) ?></div>
                <small class="text-muted"><?= date('F Y') ?></small>
            </div>
            <i class="bi bi-calendar-check icon"></i>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat orange"><div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Products</div>
                <div class="value"><?= (int)$totalProd ?></div>
                <small class="text-muted"><?= (int)$lowCount ?> low on stock</small>
            </div>
            <i class="bi bi-box-seam icon"></i>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat red"><div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Out of Stock</div>
                <div class="value"><?= (int)$outCount ?></div>
                <small class="text-muted"><?= (int)$custCount ?> customers, <?= (int)$suppCount ?> suppliers</small>
            </div>
            <i class="bi bi-exclamation-triangle icon"></i>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">Monthly Sales (last 12 months)</div>
            <div class="card-body"><canvas id="monthChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Top Selling Products</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($topProducts as $i => $tp): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="rank"><?= $i + 1 ?></span><?= h($tp['item_name']) ?></span>
                        <span class="badge bg-light text-dark border"><?= (int)$tp['sold'] ?> sold</span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$topProducts): ?>
                    <li class="list-group-item text-muted">No sales yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>Recent Bills</span>
                <?php if (can('bills')): ?><a href="bills.php" class="small">View all</a><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Bill</th><th>Customer</th><th>Date</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td>
                                <?php if (can('bills')): ?>
                                    <a href="invoice.php?id=<?= $r['id'] ?>"><?= h($r['bill_no']) ?></a>
                                <?php else: ?>
                                    <?= h($r['bill_no']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= h($r['customer'] ? $r['customer'] : 'Walk-in') ?></td>
                            <td><?= date('d M, h:i A', strtotime($r['created_at'])) ?></td>
                            <td class="text-end"><?= rs($r['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No bills yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header text-danger">Stock Alerts</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Product</th><th>Stock</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowList as $l): ?>
                        <tr>
                            <td><?= h($l['name']) ?></td>
                            <td><?= (int)$l['stock'] ?></td>
                            <td><?= stock_badge($l['stock'], $l['min_stock']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowList): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Everything is well stocked.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('monthChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Sales (Rs.)',
                data: <?= json_encode($chartValues) ?>,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13,148,136,.12)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include 'inc/footer.php'; ?>
