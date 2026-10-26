<?php
require_once __DIR__ . '/inc/auth.php';
guard('stock');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)($_POST['product_id'] ?? 0);
    $type   = $_POST['type'] ?? 'add';
    $qty    = (int)($_POST['qty'] ?? 0);
    $note   = trim($_POST['note'] ?? '');

    try {
        if ($pid < 1 || $qty < 1) {
            throw new Exception('Choose a product and enter a quantity above 0.');
        }

        $db->beginTransaction();

        $st = $db->prepare('SELECT name, stock FROM products WHERE id = ? FOR UPDATE');
        $st->execute(array($pid));
        $p = $st->fetch();
        if (!$p) {
            throw new Exception('Product not found.');
        }

        // add = new stock arrived, remove = damaged / expired / lost
        if ($type === 'add') {
            $change = $qty;
            $reason = 'Stock added';
        } else {
            $change = -$qty;
            $reason = 'Stock removed';
            if ($p['stock'] < $qty) {
                throw new Exception('Cannot remove ' . $qty . ' - only ' . $p['stock'] . ' in stock.');
            }
        }

        $db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute(array($change, $pid));
        $db->prepare('INSERT INTO stock_log (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)')
           ->execute(array($pid, $change, $reason, $note, $_SESSION['uid']));

        $db->commit();
        flash('success', 'Stock of ' . $p['name'] . ' updated (' . ($change > 0 ? '+' : '') . $change . ').');
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        flash('danger', $e->getMessage());
    }
    go('stock.php');
}

$products = $db->query('SELECT id, name, stock FROM products ORDER BY name')->fetchAll();

$log = $db->query("SELECT l.*, p.name AS product, u.full_name AS user
                   FROM stock_log l
                   JOIN products p ON p.id = l.product_id
                   LEFT JOIN users u ON u.id = l.user_id
                   ORDER BY l.id DESC LIMIT 100")->fetchAll();

$page_title = 'Stock Update';
$active = 'stock';
include 'inc/header.php';
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Add or Remove Stock</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Choose...</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= h($p['name']) ?> (now: <?= (int)$p['stock'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">What happened?</label>
                        <select name="type" class="form-select">
                            <option value="add">New stock received (+)</option>
                            <option value="remove">Damaged / expired / lost (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="qty" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note (optional)</label>
                        <input type="text" name="note" class="form-control" maxlength="255" placeholder="e.g. bought from Sharma Wholesale">
                    </div>
                    <button class="btn btn-primary w-100">Update Stock</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Recent Stock Movements</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle js-table">
                        <thead><tr><th>Date</th><th>Product</th><th>Change</th><th>Reason</th><th>Note</th><th>By</th></tr></thead>
                        <tbody>
                        <?php foreach ($log as $l): ?>
                            <tr>
                                <td data-order="<?= strtotime($l['created_at']) ?>"><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
                                <td><?= h($l['product']) ?></td>
                                <td>
                                    <?php if ($l['change_qty'] > 0): ?>
                                        <span class="text-success fw-bold">+<?= (int)$l['change_qty'] ?></span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold"><?= (int)$l['change_qty'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($l['reason']) ?></td>
                                <td><?= h($l['note']) ?></td>
                                <td><?= h($l['user']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
