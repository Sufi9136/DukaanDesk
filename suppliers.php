<?php
require_once __DIR__ . '/inc/auth.php';
guard('suppliers');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $db->prepare('DELETE FROM suppliers WHERE id = ?')->execute(array((int)$_POST['id']));
        flash('success', 'Supplier deleted.');
        go('suppliers.php');
    }

    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        flash('danger', 'Supplier name is required.');
        go('suppliers.php');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'That email address does not look right.');
        go('suppliers.php');
    }

    if ($id) {
        $db->prepare('UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=?')
           ->execute(array($name, $phone, $email, $address, $id));
        flash('success', 'Supplier updated.');
    } else {
        $db->prepare('INSERT INTO suppliers (name, phone, email, address) VALUES (?,?,?,?)')
           ->execute(array($name, $phone, $email, $address));
        flash('success', 'Supplier added.');
    }
    go('suppliers.php');
}

$suppliers = $db->query("SELECT s.*, COUNT(p.id) AS items
                         FROM suppliers s LEFT JOIN products p ON p.supplier_id = s.id
                         GROUP BY s.id, s.name, s.phone, s.email, s.address
                         ORDER BY s.name")->fetchAll();

$page_title = 'Suppliers';
$active = 'suppliers';
include 'inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">The people you buy your stock from.</p>
    <button class="btn btn-primary" data-form-modal data-title="Add Supplier"
            data-bs-toggle="modal" data-bs-target="#supModal">
        <i class="bi bi-plus-lg"></i> Add Supplier
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-table">
                <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Products</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><?= h($s['name']) ?></td>
                        <td><?= h($s['phone']) ?></td>
                        <td><?= h($s['email']) ?></td>
                        <td><?= h($s['address']) ?></td>
                        <td><?= (int)$s['items'] ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-form-modal data-title="Edit Supplier"
                                    data-bs-toggle="modal" data-bs-target="#supModal"
                                    data-row="<?= h(json_encode($s)) ?>"><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline js-confirm" data-msg="Delete this supplier?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

<div class="modal fade" id="supModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control">
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
