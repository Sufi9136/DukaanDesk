<?php
require_once __DIR__ . '/inc/auth.php';
guard('products');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $db->prepare('DELETE FROM products WHERE id = ?')->execute(array((int)$_POST['id']));
        flash('success', 'Product deleted.');
        go('products.php');
    }

    // add / edit
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = (int)($_POST['category_id'] ?? 0) ?: null;
    $supplier = (int)($_POST['supplier_id'] ?? 0) ?: null;
    $cost     = (float)($_POST['cost_price'] ?? 0);
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock'] ?? 0);
    $min      = (int)($_POST['min_stock'] ?? 5);

    if ($name === '' || $price <= 0 || $cost < 0 || $stock < 0 || $min < 0) {
        flash('danger', 'Please fill the name and a selling price greater than 0. Stock cannot be negative.');
        go('products.php');
    }

    if ($id) {
        // stock is changed only from the Stock Update page so the log stays correct
        $sql = 'UPDATE products SET name=?, description=?, category_id=?, supplier_id=?, cost_price=?, price=?, min_stock=? WHERE id=?';
        $db->prepare($sql)->execute(array($name, $desc, $category, $supplier, $cost, $price, $min, $id));
        flash('success', 'Product updated.');
    } else {
        $sql = 'INSERT INTO products (name, description, category_id, supplier_id, cost_price, price, stock, min_stock)
                VALUES (?,?,?,?,?,?,?,?)';
        $db->prepare($sql)->execute(array($name, $desc, $category, $supplier, $cost, $price, $stock, $min));
        $newId = $db->lastInsertId();

        if ($stock > 0) {
            $db->prepare('INSERT INTO stock_log (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)')
               ->execute(array($newId, $stock, 'Opening stock', 'Added with the product', $_SESSION['uid']));
        }
        flash('success', 'Product added.');
    }
    go('products.php');
}

$products = $db->query("SELECT p.*, c.name AS category, s.name AS supplier
                        FROM products p
                        LEFT JOIN categories c ON c.id = p.category_id
                        LEFT JOIN suppliers s ON s.id = p.supplier_id
                        ORDER BY p.name")->fetchAll();
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers  = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

$page_title = 'Products';
$active = 'products';
include 'inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Manage your items, prices and stock levels.</p>
    <button class="btn btn-primary" data-form-modal data-title="Add Product"
            data-bs-toggle="modal" data-bs-target="#productModal">
        <i class="bi bi-plus-lg"></i> Add Product
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-table">
                <thead>
                    <tr>
                        <th>#</th><th>Product</th><th>Category</th><th>Supplier</th>
                        <th>Cost</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td>
                            <?= h($p['name']) ?>
                            <?php if ($p['description']): ?><br><small class="text-muted"><?= h($p['description']) ?></small><?php endif; ?>
                        </td>
                        <td><?= h($p['category'] ?: '-') ?></td>
                        <td><?= h($p['supplier'] ?: '-') ?></td>
                        <td><?= rs($p['cost_price']) ?></td>
                        <td><?= rs($p['price']) ?></td>
                        <td><?= (int)$p['stock'] ?></td>
                        <td><?= stock_badge($p['stock'], $p['min_stock']) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-form-modal data-title="Edit Product"
                                    data-bs-toggle="modal" data-bs-target="#productModal"
                                    data-row="<?= h(json_encode($p)) ?>"><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline js-confirm" data-msg="Delete this product?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- add / edit popup -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Product name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- none --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost price (Rs.)</label>
                        <input type="number" name="cost_price" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Selling price (Rs.) *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">-- none --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock quantity</label>
                        <input type="number" name="stock" class="form-control" min="0" value="0">
                        <small class="text-muted">When editing, use the Stock Update page to change stock.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Low stock alert at</label>
                        <input type="number" name="min_stock" class="form-control" min="0" value="5">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
