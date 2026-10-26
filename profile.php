<?php
require_once __DIR__ . '/inc/auth.php';
guard('profile');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'name') {
        $name = trim($_POST['full_name'] ?? '');
        if ($name === '') {
            flash('danger', 'Name cannot be empty.');
        } else {
            $db->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute(array($name, $_SESSION['uid']));
            flash('success', 'Name updated.');
        }
    }

    if ($action === 'password') {
        $old  = $_POST['old_password'] ?? '';
        $new  = $_POST['new_password'] ?? '';
        $new2 = $_POST['new_password2'] ?? '';

        $st = $db->prepare('SELECT password FROM users WHERE id = ?');
        $st->execute(array($_SESSION['uid']));
        $hash = $st->fetchColumn();

        if (!password_verify($old, $hash)) {
            flash('danger', 'Current password is wrong.');
        } elseif (strlen($new) < 6) {
            flash('danger', 'New password must be at least 6 characters.');
        } elseif ($new !== $new2) {
            flash('danger', 'The two new passwords do not match.');
        } else {
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')
               ->execute(array(password_hash($new, PASSWORD_DEFAULT), $_SESSION['uid']));
            flash('success', 'Password changed.');
        }
    }
    go('profile.php');
}

$st = $db->prepare('SELECT full_name, username, role, created_at FROM users WHERE id = ?');
$st->execute(array($_SESSION['uid']));
$me = $st->fetch();

$page_title = 'My Profile';
$active = '';
include 'inc/header.php';
?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Account</div>
            <div class="card-body">
                <p class="mb-1">Username: <strong><?= h($me['username']) ?></strong></p>
                <p class="mb-1">Role: <strong><?= h(role_name($me['role'])) ?></strong></p>
                <p class="mb-3">Member since: <?= date('d M Y', strtotime($me['created_at'])) ?></p>

                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="name">
                    <label class="form-label">Full name</label>
                    <div class="input-group">
                        <input type="text" name="full_name" class="form-control" value="<?= h($me['full_name']) ?>" required>
                        <button class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Change Password</div>
            <div class="card-body">
                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repeat new password</label>
                        <input type="password" name="new_password2" class="form-control" minlength="6" required>
                    </div>
                    <button class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
