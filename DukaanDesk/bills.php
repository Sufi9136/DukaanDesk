<?php
require_once __DIR__ . '/inc/auth.php';
guard('bills');

// cancel a bill (admin only) - puts the items back into stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if ($_SESSION['role'] !== 'admin') {
        flash('danger', 'Only the admin can cancel a bill.');
        go('bills.php');
    }

    $billId = (int)$_POST['id'];
    try {
        $db->beginTransaction();

        $st = $db->prepare('SELECT bill_no FROM bills WHERE id = ? FOR UPDATE');
        $st->execute(array($billId));
        $billNo = $st->fetchColumn();
        if (!$billNo) {
            throw new Exception('Bill not found.');
        }

        $items = $db->prepare('SELECT product_id, qty FROM bill_items WHERE bill_id = ?');
        $items->execute(array($billId));

        $restore = $db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
        $log     = $db->prepare('INSERT INTO stock_log (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)');
        foreach ($items->fetchAll() as $it) {
            if ($it['product_id']) {
                $restore->execute(array($it['qty'], $it['product_id']));
                $log->execute(array($it['product_id'], $it['qty'], 'Bill cancelled', $billNo, $_SESSION['uid']));
            }
        }

        // bill_items are removed automatically (ON DELETE CASCADE)
        $db->prepare('DELETE FROM bills WHERE id = ?')->execute(array($billId));
        $db->commit();
        flash('success', 'Bill ' . $billNo . ' cancelled and stock restored.');
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        flash('danger', $e->getMessage());
    }
    go('bills.php');
}

// date filter, default is the last 30 days
$from = clean_date($_GET['from'] ?? '', date('Y-m-d', strtotime('-30 days')));
$to   = clean_date($_GET['to'] ?? '', date('Y-m-d'));

$st = $db->prepare("SELECT b.*, c.name AS customer, u.full_name AS cashier,
                           (SELECT COUNT(*) FROM bill_items i WHERE i.bill_id = b.id) AS items
                    FROM bills b
                    LEFT JOIN customers c ON c.id = b.customer_id
                    LEFT JOIN users u ON u.id = b.user_id
                    WHERE b.created_at >= ? AND b.created_at < DATE_ADD(?, INTERVAL 1 DAY)
                    ORDER BY b.id DESC");
$st->execute(array($from, $to));
$bills = $st->fetchAll();

$sum = 0;
foreach ($bills as $b) {
    $sum += $b['total'];
}

$page_title = 'All Bills';
$active = 'bills';
include 'inc/header.php';
?>

<div class="card mb-3">
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
            <div class="col-auto"><button class="btn btn-dark">Show</button></div>
            <div class="col text-end">
                <strong><?= count($bills) ?> bills</strong> &nbsp;|&nbsp; Total: <strong><?= rs($sum) ?></strong>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-table">
                <thead>
                    <tr><th>Bill No</th><th>Date</th><th>Customer</th><th>Items</th><th>Payment</th><th>Cashier</th><th class="text-end">Total</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($bills as $b): ?>
                    <tr>
                        <td><?= h($b['bill_no']) ?></td>
                        <td data-order="<?= strtotime($b['created_at']) ?>"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></td>
                        <td><?= h($b['customer'] ? $b['customer'] : 'Walk-in') ?></td>
                        <td><?= (int)$b['items'] ?></td>
                        <td><?= h($b['pay_mode']) ?></td>
                        <td><?= h($b['cashier']) ?></td>
                        <td class="text-end" data-order="<?= $b['total'] ?>"><?= rs($b['total']) ?></td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-info text-white btn-bill-view" data-id="<?= $b['id'] ?>" title="Details"><i class="bi bi-eye"></i></button>
                            <a href="invoice.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="Print"><i class="bi bi-printer"></i></a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <form method="post" class="d-inline js-confirm" data-msg="Cancel this bill? The items will go back into stock.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Cancel bill"><i class="bi bi-x-circle"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- bill details popup, filled by app.js -->
<div class="modal fade" id="billModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bill Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="billModalBody"></div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
