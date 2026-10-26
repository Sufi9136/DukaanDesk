<?php
// every page sets $page_title and $active before including this file
$menu = array(
    'Main' => array(
        array('dashboard', 'Dashboard', 'bi-speedometer2', 'dashboard.php'),
    ),
    'Sales' => array(
        array('billing',   'New Bill',  'bi-receipt',    'billing.php'),
        array('bills',     'All Bills', 'bi-journal-text', 'bills.php'),
        array('customers', 'Customers', 'bi-people',     'customers.php'),
    ),
    'Inventory' => array(
        array('products',   'Products',     'bi-box-seam',    'products.php'),
        array('categories', 'Categories',   'bi-tags',        'categories.php'),
        array('suppliers',  'Suppliers',    'bi-truck',       'suppliers.php'),
        array('stock',      'Stock Update', 'bi-clipboard-check', 'stock.php'),
    ),
    'Analysis' => array(
        array('reports', 'Reports', 'bi-bar-chart-line', 'reports.php'),
    ),
    'Admin' => array(
        array('users', 'Users', 'bi-person-gear', 'users.php'),
    ),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($page_title) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-shop"></i>
            <span><?= h(APP_NAME) ?></span>
        </div>

        <?php foreach ($menu as $menu_group => $menu_links): ?>
            <?php
            // skip the whole group if this role can't see any link in it
            $menu_visible = array();
            foreach ($menu_links as $link) {
                if (can($link[0])) {
                    $menu_visible[] = $link;
                }
            }
            if (!$menu_visible) continue;
            ?>
            <div class="menu-title"><?= h($menu_group) ?></div>
            <ul class="menu">
                <?php foreach ($menu_visible as $link): ?>
                    <li>
                        <a href="<?= $link[3] ?>" class="<?= $active === $link[0] ? 'on' : '' ?>">
                            <i class="bi <?= $link[2] ?>"></i> <?= h($link[1]) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </aside>

    <div class="main">
        <header class="topbar no-print">
            <button class="btn btn-light btn-sm" id="menuBtn" type="button"><i class="bi bi-list fs-5"></i></button>
            <h5 class="mb-0 ms-3"><?= h($page_title) ?></h5>

            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="d-none d-md-inline text-muted small">
                    <?= h($_SESSION['name']) ?>
                    <span class="badge bg-secondary"><?= h(role_name($_SESSION['role'])) ?></span>
                </span>
                <a href="profile.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-person"></i> Profile</a>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </div>
        </header>

        <div class="content">
            <?php
            if (!empty($_SESSION['flash'])) {
                foreach ($_SESSION['flash'] as $msg) {
                    echo '<div class="alert alert-' . h($msg[0]) . ' alert-dismissible fade show no-print">'
                        . h($msg[1])
                        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                unset($_SESSION['flash']);
            }
            ?>
