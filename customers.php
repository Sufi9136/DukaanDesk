<?php
require_once __DIR__ . '/inc/auth.php';
guard('customers');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        // old bills stay, they just show as walk-in
        $db->prepare('DELETE FROM customers WHERE id = ?')->execute(array((int)$_POST['id']));
        flash('success', 'Customer deleted.');
        go('customers.php');
    }

    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        flash('danger', 'Customer name is required.');
        go('customers.php');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'That email address does not look right.');
        go('customers.php');
    }

    if ($id) {
        $db->prepare('UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?')
           ->execute(array($name, $phone, $email, $address, $id));
        flash('success', 'Customer updated.');
    } else {
        $db->prepare('INSERT INTO customers (name, phone, email, address) VALUES (?,?,?,?)')
           ->execute(array($name, $phone, $email, $address));
        flash('success', 'Customer added.');
    }
    go('customers.php');
}

$customers = $db->query("SELECT c.*, COUNT(b.id) AS bill_count, COALESCE(SUM(b.total),0) AS spent
                         FROM customers c LEFT JOIN bills b ON b.customer_id = c.id
                         GROUP BY c.id, c.name, c.phone, c.email, c.address, c.created_at
                         ORDER BY c.name")->fetchAll();

$page_title = 'Customers';
$active = 'customers';
include 'inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Regular customers and how much they have bought.</p>
    <button class="btn btn-primary" data-form-modal data-title="Add Customer"
            data-bs-toggle="modal" data-bs-target="#custModal">
        <i class="bi bi-plus-lg"></i> Add Customer
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-table">
                <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Bills</th><th>Total Purchases</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= h($c['name']) ?></td>
                        <td><?= h($c['phone']) ?></td>
                        <td><?= h($c['email']) ?></td>
                        <td><?= (int)$c['bill_count'] ?></td>
                        <td><?= rs($c['spent']) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-form-modal data-title="Edit Customer"
                                    data-bs-toggle="modal" data-bs-target="#custModal"
                                    data-row="<?= h(json_encode($c)) ?>"><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline js-confirm" data-msg="Delete this customer?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

<div class="modal fade" id="custModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title">Add Customer</h5>
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
