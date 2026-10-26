<?php
require_once __DIR__ . '/inc/auth.php';
guard('bills');

$id = (int)($_GET['id'] ?? 0);

$st = $db->prepare('SELECT b.*, c.name AS customer, c.phone AS customer_phone, u.full_name AS cashier
                    FROM bills b
                    LEFT JOIN customers c ON c.id = b.customer_id
                    LEFT JOIN users u ON u.id = b.user_id
                    WHERE b.id = ?');
$st->execute(array($id));
$bill = $st->fetch();

if (!$bill) {
    flash('danger', 'Bill not found.');
    go('bills.php');
}

$st = $db->prepare('SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id');
$st->execute(array($id));
$items = $st->fetchAll();

$page_title = 'Invoice ' . $bill['bill_no'];
$active = 'bills';
include 'inc/header.php';
?>

<div class="text-center mb-3 no-print">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    <a href="billing.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> New Bill</a>
    <a href="bills.php" class="btn btn-outline-secondary">All Bills</a>
</div>

<div class="invoice-box">
    <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
        <div>
            <h3 class="mb-1"><?= h(SHOP_NAME) ?></h3>
            <div class="text-muted"><?= h(SHOP_ADDRESS) ?></div>
            <div class="text-muted">Phone: <?= h(SHOP_PHONE) ?></div>
            <?php if (SHOP_GSTIN !== ''): ?><div class="text-muted">GSTIN: <?= h(SHOP_GSTIN) ?></div><?php endif; ?>
        </div>
        <div class="text-end">
            <h4 class="mb-1">INVOICE</h4>
            <div><strong><?= h($bill['bill_no']) ?></strong></div>
            <div><?= date('d M Y, h:i A', strtotime($bill['created_at'])) ?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-6">
            <div class="text-muted small">Billed to</div>
            <strong><?= h($bill['customer'] ? $bill['customer'] : 'Walk-in customer') ?></strong>
            <?php if ($bill['customer_phone']): ?><div><?= h($bill['customer_phone']) ?></div><?php endif; ?>
        </div>
        <div class="col-6 text-end">
            <div class="text-muted small">Payment / Cashier</div>
            <?= h($bill['pay_mode']) ?> / <?= h($bill['cashier'] ? $bill['cashier'] : '-') ?>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>#</th><th>Item</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $n => $it): ?>
            <tr>
                <td><?= $n + 1 ?></td>
                <td><?= h($it['item_name']) ?></td>
                <td class="text-end"><?= rs($it['price']) ?></td>
                <td class="text-end"><?= (int)$it['qty'] ?></td>
                <td class="text-end"><?= rs($it['price'] * $it['qty']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row justify-content-end">
        <div class="col-md-5">
            <table class="table table-sm">
                <tr><td>Subtotal</td><td class="text-end"><?= rs($bill['subtotal']) ?></td></tr>
                <tr><td>Discount</td><td class="text-end">- <?= rs($bill['discount']) ?></td></tr>
                <tr><td>GST (<?= (float)$bill['gst_percent'] ?>%)</td><td class="text-end"><?= rs($bill['gst_amount']) ?></td></tr>
                <tr class="fw-bold fs-5"><td>Total</td><td class="text-end"><?= rs($bill['total']) ?></td></tr>
            </table>
        </div>
    </div>

    <p class="text-center text-muted mt-4 mb-0">Thank you, visit again!</p>
    <p class="text-center text-muted small mb-0">Billed with <?= h(APP_NAME) ?></p>
</div>

<?php include 'inc/footer.php'; ?>
