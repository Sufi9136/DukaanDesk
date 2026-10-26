<?php
require_once __DIR__ . '/inc/auth.php';
guard('reports');

// date range, default = this month so far
$from = clean_date($_GET['from'] ?? '', date('Y-m-01'));
$to   = clean_date($_GET['to'] ?? '', date('Y-m-d'));
if ($from > $to) {
    $tmp = $from; $from = $to; $to = $tmp;
}

// every query below uses the same date condition
$where = 'b.created_at >= ? AND b.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
$args  = array($from, $to);

// ---- summary ----
$st = $db->prepare("SELECT COUNT(*) AS bills, COALESCE(SUM(b.subtotal),0) AS subtotal,
                           COALESCE(SUM(b.discount),0) AS discount, COALESCE(SUM(b.gst_amount),0) AS gst,
                           COALESCE(SUM(b.total),0) AS total
                    FROM bills b WHERE $where");
$st->execute($args);
$sum = $st->fetch();

// profit = (selling price - cost price) x qty, minus the discounts given
$st = $db->prepare("SELECT COALESCE(SUM(i.qty * (i.price - i.cost_price)),0)
                    FROM bill_items i JOIN bills b ON b.id = i.bill_id WHERE $where");
$st->execute($args);
$profit = $st->fetchColumn() - $sum['discount'];

// ---- sales per day for the line chart ----
$st = $db->prepare("SELECT DATE(b.created_at) AS d, SUM(b.total) AS amount FROM bills b WHERE $where GROUP BY d");
$st->execute($args);
$perDay = $st->fetchAll(PDO::FETCH_KEY_PAIR);

$dayLabels = array();
$dayValues = array();
$cursor = strtotime($from);
$end    = strtotime($to);
$count  = 0;
while ($cursor <= $end && $count < 400) {
    $key = date('Y-m-d', $cursor);
    $dayLabels[] = date('d M', $cursor);
    $dayValues[] = isset($perDay[$key]) ? (float)$perDay[$key] : 0;
    $cursor = strtotime('+1 day', $cursor);
    $count++;
}

// ---- payment mode split ----
$st = $db->prepare("SELECT b.pay_mode, COUNT(*) AS c, SUM(b.total) AS amount FROM bills b WHERE $where GROUP BY b.pay_mode");
$st->execute($args);
$payRows = $st->fetchAll();

// ---- top products in this period ----
$st = $db->prepare("SELECT i.item_name, SUM(i.qty) AS sold, SUM(i.qty * i.price) AS amount
                    FROM bill_items i JOIN bills b ON b.id = i.bill_id
                    WHERE $where GROUP BY i.item_name ORDER BY sold DESC LIMIT 10");
$st->execute($args);
$topProducts = $st->fetchAll();

// ---- bills in this period (also used for the pdf) ----
$st = $db->prepare("SELECT b.bill_no, b.created_at, b.pay_mode, b.total, c.name AS customer
                    FROM bills b LEFT JOIN customers c ON c.id = b.customer_id
                    WHERE $where ORDER BY b.id DESC");
$st->execute($args);
$billRows = $st->fetchAll();

// ---- current inventory ----
$inventory = $db->query("SELECT p.name, p.stock, p.min_stock, p.cost_price, p.price,
                                c.name AS category, s.name AS supplier
                         FROM products p
                         LEFT JOIN categories c ON c.id = p.category_id
                         LEFT JOIN suppliers s ON s.id = p.supplier_id
                         ORDER BY p.stock ASC, p.name")->fetchAll();

$stockCost = 0;
$stockSell = 0;
foreach ($inventory as $p) {
    $stockCost += $p['stock'] * $p['cost_price'];
    $stockSell += $p['stock'] * $p['price'];
}

$page_title = 'Reports';
$active = 'reports';
include 'inc/header.php';
?>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-auto">
                <label class="form-label mb-0 small">From</label>
                <input type="date" name="from" class="form-control" value="<?= h($from) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-0 small">To</label>
                <input type="date" name="to" class="form-control" value="<?= h($to) ?>">
            </div>
            <div class="col-auto"><button class="btn btn-dark">Show Report</button></div>
            <div class="col text-end">
                <button type="button" class="btn btn-danger" id="pdfBtn"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <a class="btn btn-success" href="export_csv.php?type=sales&from=<?= h($from) ?>&to=<?= h($to) ?>"><i class="bi bi-filetype-csv"></i> Sales CSV</a>
                <a class="btn btn-outline-success" href="export_csv.php?type=stock"><i class="bi bi-filetype-csv"></i> Stock CSV</a>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
            </div>
        </form>
    </div>
</div>

<h5 class="mb-3">Sales report: <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></h5>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card stat"><div class="card-body">
            <div class="label">Bills</div><div class="value"><?= (int)$sum['bills'] ?></div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat blue"><div class="card-body">
            <div class="label">Total Sales</div><div class="value"><?= rs($sum['total']) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat orange"><div class="card-body">
            <div class="label">GST Collected</div><div class="value"><?= rs($sum['gst']) ?></div>
            <small class="text-muted">Discount given: <?= rs($sum['discount']) ?></small>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat <?= $profit < 0 ? 'red' : '' ?>"><div class="card-body">
            <div class="label">Estimated Profit</div><div class="value"><?= rs($profit) ?></div>
            <small class="text-muted">after discounts</small>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">Sales per day</div>
            <div class="card-body"><canvas id="dayChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Payment methods</div>
            <div class="card-body">
                <?php if ($payRows): ?>
                    <canvas id="payChart" height="200"></canvas>
                <?php else: ?>
                    <p class="text-muted mb-0">No sales in this period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Top selling products</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th>Sold</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($topProducts as $t): ?>
                        <tr><td><?= h($t['item_name']) ?></td><td><?= (int)$t['sold'] ?></td><td class="text-end"><?= rs($t['amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$topProducts): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No sales in this period.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Bills in this period</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm js-table" id="billsTable">
                        <thead><tr><th>Bill No</th><th>Date</th><th>Customer</th><th>Payment</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($billRows as $r): ?>
                            <tr>
                                <td><?= h($r['bill_no']) ?></td>
                                <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                <td><?= h($r['customer'] ? $r['customer'] : 'Walk-in') ?></td>
                                <td><?= h($r['pay_mode']) ?></td>
                                <td class="text-end"><?= number_format($r['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between flex-wrap">
        <span>Current inventory</span>
        <span class="small text-muted fw-normal">
            Stock value: <?= rs($stockCost) ?> at cost, <?= rs($stockSell) ?> at selling price
        </span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle js-table">
                <thead><tr><th>Product</th><th>Category</th><th>Supplier</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($inventory as $p): ?>
                    <tr>
                        <td><?= h($p['name']) ?></td>
                        <td><?= h($p['category'] ? $p['category'] : '-') ?></td>
                        <td><?= h($p['supplier'] ? $p['supplier'] : '-') ?></td>
                        <td><?= rs($p['price']) ?></td>
                        <td><?= (int)$p['stock'] ?></td>
                        <td><?= stock_badge($p['stock'], $p['min_stock']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
    new Chart(document.getElementById('dayChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($dayLabels) ?>,
            datasets: [{ label: 'Sales (Rs.)', data: <?= json_encode($dayValues) ?>, backgroundColor: '#0d9488' }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    <?php if ($payRows): ?>
    new Chart(document.getElementById('payChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($payRows, 'pay_mode')) ?>,
            datasets: [{
                data: <?= json_encode(array_map('floatval', array_column($payRows, 'amount'))) ?>,
                backgroundColor: ['#0d9488', '#3b82f6', '#f59e0b']
            }]
        }
    });
    <?php endif; ?>

    // PDF of the summary + bill list, built in the browser with jsPDF
    document.getElementById('pdfBtn').addEventListener('click', function () {
        var doc = new window.jspdf.jsPDF();
        doc.setFontSize(16);
        doc.text(<?= json_encode(SHOP_NAME) ?>, 14, 16);
        doc.setFontSize(11);
        doc.text('Sales report: <?= h($from) ?> to <?= h($to) ?>', 14, 24);
        doc.text('Bills: <?= (int)$sum['bills'] ?>    Total sales: Rs. <?= number_format($sum['total'], 2) ?>    GST: Rs. <?= number_format($sum['gst'], 2) ?>', 14, 31);

        var rows = [];
        <?php foreach ($billRows as $r): ?>
        rows.push(<?= json_encode(array(
            $r['bill_no'],
            date('d M Y', strtotime($r['created_at'])),
            $r['customer'] ? $r['customer'] : 'Walk-in',
            $r['pay_mode'],
            number_format($r['total'], 2)
        )) ?>);
        <?php endforeach; ?>

        doc.autoTable({
            startY: 37,
            head: [['Bill No', 'Date', 'Customer', 'Payment', 'Total (Rs.)']],
            body: rows,
            headStyles: { fillColor: [13, 148, 136] }
        });
        doc.save('sales-report-<?= h($from) ?>-to-<?= h($to) ?>.pdf');
    });
</script>

<?php include 'inc/footer.php'; ?>
