<?php
// returns the items of one bill as html, used by the "details" popup on bills.php
require_once __DIR__ . '/../inc/auth.php';

if (empty($_SESSION['uid']) || !can('bills')) {
    http_response_code(403);
    exit('Not allowed');
}

$id = (int)($_GET['id'] ?? 0);

$st = $db->prepare('SELECT b.*, c.name AS customer, c.phone
                    FROM bills b LEFT JOIN customers c ON c.id = b.customer_id
                    WHERE b.id = ?');
$st->execute(array($id));
$bill = $st->fetch();

if (!$bill) {
    exit('<p class="text-danger">Bill not found.</p>');
}

$st = $db->prepare('SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id');
$st->execute(array($id));
$items = $st->fetchAll();
?>
<div class="row mb-3">
    <div class="col-6">
        <div class="text-muted small">Bill No</div>
        <strong><?= h($bill['bill_no']) ?></strong>
    </div>
    <div class="col-6 text-end">
        <div class="text-muted small">Date</div>
        <?= date('d M Y, h:i A', strtotime($bill['created_at'])) ?>
    </div>
</div>
<p class="mb-2">
    Customer: <strong><?= h($bill['customer'] ? $bill['customer'] : 'Walk-in') ?></strong>
    <?= $bill['phone'] ? '(' . h($bill['phone']) . ')' : '' ?>
    &nbsp;|&nbsp; Paid by: <strong><?= h($bill['pay_mode']) ?></strong>
</p>

<table class="table table-sm table-bordered">
    <thead><tr><th>Item</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
        <tr>
            <td><?= h($it['item_name']) ?></td>
            <td class="text-end"><?= rs($it['price']) ?></td>
            <td class="text-end"><?= (int)$it['qty'] ?></td>
            <td class="text-end"><?= rs($it['price'] * $it['qty']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= rs($bill['subtotal']) ?></td></tr>
        <tr><td colspan="3" class="text-end">Discount</td><td class="text-end">- <?= rs($bill['discount']) ?></td></tr>
        <tr><td colspan="3" class="text-end">GST (<?= (float)$bill['gst_percent'] ?>%)</td><td class="text-end"><?= rs($bill['gst_amount']) ?></td></tr>
        <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= rs($bill['total']) ?></td></tr>
    </tfoot>
</table>
<div class="text-end">
    <a href="invoice.php?id=<?= $bill['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Open invoice</a>
</div>
