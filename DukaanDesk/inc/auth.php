<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// which roles can open which page
$permissions = array(
    'dashboard'  => array('admin', 'cashier', 'stock_manager'),
    'profile'    => array('admin', 'cashier', 'stock_manager'),
    'billing'    => array('admin', 'cashier'),
    'bills'      => array('admin', 'cashier'),
    'customers'  => array('admin', 'cashier'),
    'products'   => array('admin', 'stock_manager'),
    'categories' => array('admin', 'stock_manager'),
    'suppliers'  => array('admin', 'stock_manager'),
    'stock'      => array('admin', 'stock_manager'),
    'reports'    => array('admin', 'stock_manager'),
    'users'      => array('admin'),
);

function can($page) {
    global $permissions;
    return !empty($_SESSION['role']) && in_array($_SESSION['role'], $permissions[$page]);
}

// call this at the top of every page that needs a login
function guard($page) {
    global $db;

    if (empty($_SESSION['uid'])) {
        go('login.php');
    }

    // read the user again so a deleted user or a changed role takes effect immediately
    $st = $db->prepare('SELECT id, full_name, role FROM users WHERE id = ?');
    $st->execute(array($_SESSION['uid']));
    $user = $st->fetch();
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];

    if (!can($page)) {
        flash('danger', 'You are not allowed to open that page.');
        go('dashboard.php');
    }

    verify_csrf();
}
