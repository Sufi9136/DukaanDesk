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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d9488">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="DukaanDesk">
</head>
<body>

<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-shop"></i>
            <span class="app-name-side"><b>Dukaan</b>Desk</span>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
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
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar no-print">
            <button class="btn btn-light btn-sm" id="menuBtn" type="button"><i class="bi bi-list fs-5"></i></button>
            <h5 class="mb-0 ms-3 text-truncate"><?= h($page_title) ?></h5>

            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="d-none d-md-inline text-muted small">
                    <?= h($_SESSION['name']) ?>
                    <span class="badge bg-secondary"><?= h(role_name($_SESSION['role'])) ?></span>
                </span>
                <a href="profile.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-person"></i><span class="d-none d-sm-inline"> Profile</span></a>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline"> Logout</span>
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
