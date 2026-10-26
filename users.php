<?php
require_once __DIR__ . '/inc/auth.php';
guard('users');

$validRoles = array('admin', 'cashier', 'stock_manager');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        if ($id == $_SESSION['uid']) {
            flash('danger', 'You cannot delete the account you are logged in with.');
        } else {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute(array($id));
            flash('success', 'User deleted.');
        }
        go('users.php');
    }

    $name     = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'cashier';

    if ($name === '' || $username === '' || !in_array($role, $validRoles)) {
        flash('danger', 'Name, username and role are required.');
        go('users.php');
    }

    try {
        if ($id) {
            // do not leave the shop without an admin
            if ($role !== 'admin') {
                $st = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id <> ?");
                $st->execute(array($id));
                if ($st->fetchColumn() == 0) {
                    flash('danger', 'There must be at least one admin.');
                    go('users.php');
                }
            }

            if ($password !== '') {
                if (strlen($password) < 6) {
                    flash('danger', 'Password must be at least 6 characters.');
                    go('users.php');
                }
                $db->prepare('UPDATE users SET full_name=?, username=?, role=?, password=? WHERE id=?')
                   ->execute(array($name, $username, $role, password_hash($password, PASSWORD_DEFAULT), $id));
            } else {
                $db->prepare('UPDATE users SET full_name=?, username=?, role=? WHERE id=?')
                   ->execute(array($name, $username, $role, $id));
            }
            flash('success', 'User updated.');
        } else {
            if (strlen($password) < 6) {
                flash('danger', 'Password must be at least 6 characters.');
                go('users.php');
            }
            $db->prepare('INSERT INTO users (full_name, username, password, role) VALUES (?,?,?,?)')
               ->execute(array($name, $username, password_hash($password, PASSWORD_DEFAULT), $role));
            flash('success', 'User created.');
        }
    } catch (PDOException $e) {
        flash('danger', 'That username is already taken.');
    }
    go('users.php');
}

$users = $db->query('SELECT id, full_name, username, role, created_at FROM users ORDER BY id')->fetchAll();

$page_title = 'Users';
$active = 'users';
include 'inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">
        Admin: everything &nbsp;|&nbsp; Cashier: billing and customers &nbsp;|&nbsp; Stock Manager: products, suppliers, stock and reports
    </p>
    <button class="btn btn-primary" data-form-modal data-title="Add User"
            data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="bi bi-person-plus"></i> Add User
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-table">
                <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= h($u['full_name']) ?></td>
                        <td><?= h($u['username']) ?></td>
                        <td><span class="badge bg-secondary"><?= h(role_name($u['role'])) ?></span></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-form-modal data-title="Edit User"
                                    data-bs-toggle="modal" data-bs-target="#userModal"
                                    data-row="<?= h(json_encode(array('id' => $u['id'], 'full_name' => $u['full_name'], 'username' => $u['username'], 'role' => $u['role']))) ?>"><i class="bi bi-pencil"></i></button>
                            <?php if ($u['id'] != $_SESSION['uid']): ?>
                                <form method="post" class="d-inline js-confirm" data-msg="Delete this user?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Full name *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="6">
                    <small class="text-muted">Needed for a new user. When editing, leave empty to keep the old password.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="cashier">Cashier</option>
                        <option value="stock_manager">Stock Manager</option>
                        <option value="admin">Admin</option>
                    </select>
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
