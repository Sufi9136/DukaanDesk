<?php
require_once __DIR__ . '/inc/auth.php';
guard('billing');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // the browser sends only product id and qty, prices are read again from the database
    $cart = json_decode($_POST['cart'] ?? '[]', true);

    try {
        if (!is_array($cart) || count($cart) == 0) {
            throw new Exception('Add at least one product to the bill.');
        }

        $db->beginTransaction();

        $subtotal = 0;
        $lines = array();
        $getProduct = $db->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');

        foreach ($cart as $row) {
            $pid = (int)($row['id'] ?? 0);
            $qty = (int)($row['qty'] ?? 0);
            if ($qty < 1) {
                continue;
            }

            $getProduct->execute(array($pid));
            $p = $getProduct->fetch();
            if (!$p) {
                throw new Exception('One of the products no longer exists.');
            }
            if ($p['stock'] < $qty) {
                throw new Exception('Not enough stock for ' . $p['name'] . ' (only ' . $p['stock'] . ' left).');
            }

            $lineTotal = $p['price'] * $qty;
            $subtotal += $lineTotal;
            $lines[] = array(
                'id'    => $p['id'],
                'name'  => $p['name'],
                'qty'   => $qty,
                'price' => $p['price'],
                'cost'  => $p['cost_price'],
            );
        }

        if (count($lines) == 0) {
            throw new Exception('Add at least one product to the bill.');
        }

        $discount   = (float)($_POST['discount'] ?? 0);
        $discount   = max(0, min($discount, $subtotal));
        $gstPercent = (float)($_POST['gst_percent'] ?? 0);
        $gstPercent = max(0, min($gstPercent, 100));
        $gstAmount  = round(($subtotal - $discount) * $gstPercent / 100, 2);
        $total      = round($subtotal - $discount + $gstAmount, 2);

        $payMode = $_POST['pay_mode'] ?? 'Cash';
        if (!in_array($payMode, array('Cash', 'UPI', 'Card'))) {
            $payMode = 'Cash';
        }

        // customer: either picked from the list or typed as a new one
        $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
        $newName    = trim($_POST['new_name'] ?? '');
        $newPhone   = trim($_POST['new_phone'] ?? '');

        if ($newName !== '') {
            // if this phone number is already saved, use that customer instead of making a duplicate
            $existing = false;
            if ($newPhone !== '') {
                $chk = $db->prepare('SELECT id FROM customers WHERE phone = ? LIMIT 1');
                $chk->execute(array($newPhone));
                $existing = $chk->fetchColumn();
            }
            if ($existing) {
                $customerId = $existing;
            } else {
                $db->prepare('INSERT INTO customers (name, phone) VALUES (?, ?)')->execute(array($newName, $newPhone));
                $customerId = $db->lastInsertId();
            }
        }

        // temporary bill number, real one is made from the id below
        $db->prepare('INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode)
                      VALUES (?,?,?,?,?,?,?,?,?)')
           ->execute(array(uniqid('T'), $customerId, $_SESSION['uid'], $subtotal, $discount, $gstPercent, $gstAmount, $total, $payMode));
        $billId = $db->lastInsertId();

        $billNo = 'INV' . str_pad($billId, 5, '0', STR_PAD_LEFT);
        $db->prepare('UPDATE bills SET bill_no = ? WHERE id = ?')->execute(array($billNo, $billId));

        $addItem   = $db->prepare('INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (?,?,?,?,?,?)');
        $reduce    = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        $writeLog  = $db->prepare('INSERT INTO stock_log (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)');

        foreach ($lines as $l) {
            $addItem->execute(array($billId, $l['id'], $l['name'], $l['qty'], $l['price'], $l['cost']));
            $reduce->execute(array($l['qty'], $l['id']));
            $writeLog->execute(array($l['id'], -$l['qty'], 'Sale', $billNo, $_SESSION['uid']));
        }

        $db->commit();
        flash('success', 'Bill ' . $billNo . ' created.');
        go('invoice.php?id=' . $billId);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}

$products   = $db->query("SELECT p.id, p.name, p.price, p.stock, COALESCE(c.name, 'Other') AS category
                          FROM products p LEFT JOIN categories c ON c.id = p.category_id
                          WHERE p.stock > 0 ORDER BY p.name")->fetchAll();
$categories = $db->query('SELECT name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$customers  = $db->query('SELECT id, name, phone FROM customers ORDER BY name')->fetchAll();

$page_title = 'New Bill';
$active = 'billing';
include 'inc/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" id="billForm">
    <?= csrf_field() ?>
    <input type="hidden" name="cart" id="cartField">

    <div class="row g-3">
        <!-- left side: pick products -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Products</div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <input type="text" id="searchBox" class="form-control" placeholder="Search product... (Enter adds the first one)">
                        </div>
                        <div class="col-5">
                            <select id="catFilter" class="form-select">
                                <option value="">All categories</option>
                                <?php foreach ($categories as $cn): ?>
                                    <option><?= h($cn) ?></option>
                                <?php endforeach; ?>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="product-list">
                        <table class="table table-sm align-middle mb-0">
                            <tbody id="productRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- right side: the bill -->
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Items in this bill</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th class="text-end">Amount</th><th></th></tr></thead>
                            <tbody id="cartRows"></tbody>
                        </table>
                    </div>
                    <p id="emptyMsg" class="text-center text-muted py-3 mb-0">Click a product to add it.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Customer &amp; Payment</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" id="customerSelect" class="form-select">
                            <option value="">Walk-in customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= h($c['name']) ?><?= $c['phone'] ? ' (' . h($c['phone']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="#" id="newCustLink" class="small">+ New customer</a>
                    </div>

                    <div id="newCustBox" class="row g-2 mb-2 d-none">
                        <div class="col-7"><input type="text" name="new_name" class="form-control form-control-sm" placeholder="Customer name"></div>
                        <div class="col-5"><input type="text" name="new_phone" class="form-control form-control-sm" placeholder="Phone"></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label">Discount (Rs.)</label>
                            <input type="number" name="discount" id="discount" class="form-control" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label">GST (%)</label>
                            <input type="number" name="gst_percent" id="gstPercent" class="form-control" min="0" max="100" step="0.01" value="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Payment</label>
                            <select name="pay_mode" class="form-select">
                                <option>Cash</option><option>UPI</option><option>Card</option>
                            </select>
                        </div>
                    </div>

                    <table class="table table-sm mb-3">
                        <tr><td>Subtotal</td><td class="text-end" id="subTxt">Rs. 0.00</td></tr>
                        <tr><td>Discount</td><td class="text-end" id="discTxt">Rs. 0.00</td></tr>
                        <tr><td>GST</td><td class="text-end" id="gstTxt">Rs. 0.00</td></tr>
                        <tr class="fw-bold fs-5"><td>Total</td><td class="text-end" id="totalTxt">Rs. 0.00</td></tr>
                    </table>

                    <button class="btn btn-primary w-100 btn-lg">Generate Bill</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // only used to show the list - the server checks price and stock again
    var PRODUCTS = <?= json_encode($products, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="assets/js/billing.js"></script>

<?php include 'inc/footer.php'; ?>
