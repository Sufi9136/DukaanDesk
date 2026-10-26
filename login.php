<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!empty($_SESSION['uid'])) {
    go('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $st = $db->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute(array($username));
    $user = $st->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid']  = $user['id'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        go('dashboard.php');
    }
    $error = 'Wrong username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="card login-box p-4">
        <div class="text-center mb-3">
            <i class="bi bi-shop fs-1 text-success"></i>
            <h4 class="mb-0"><?= h(APP_NAME) ?></h4>
            <small class="text-muted">Inventory &amp; Billing System</small><br>
            <small class="text-muted"><?= h(SHOP_NAME) ?></small>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
</body>
</html>
