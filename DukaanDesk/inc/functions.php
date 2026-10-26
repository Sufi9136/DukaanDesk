<?php
// small helper functions used everywhere

// escape text before printing it in html
function h($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// format money
function rs($amount) {
    return 'Rs. ' . number_format((float)$amount, 2);
}

function go($url) {
    header('Location: ' . $url);
    exit;
}

// flash messages - shown once on the next page (see header.php)
function flash($type, $message) {
    $_SESSION['flash'][] = array($type, $message);
}

// ---- csrf protection for all post forms ----
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $sent = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!hash_equals(csrf_token(), $sent)) {
        die('Page expired. Please go back, refresh the page and try again.');
    }
}

function role_name($role) {
    $names = array(
        'admin'         => 'Admin',
        'cashier'       => 'Cashier',
        'stock_manager' => 'Stock Manager',
    );
    return isset($names[$role]) ? $names[$role] : $role;
}

// stops excel from running a cell like =1+1 when a csv is opened
function csv_safe($value) {
    $value = (string)$value;
    if ($value !== '' && strpos('=+-@', $value[0]) !== false) {
        return "'" . $value;
    }
    return $value;
}

// accepts only yyyy-mm-dd, otherwise returns the default
function clean_date($value, $default) {
    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    return $default;
}

function stock_badge($stock, $min) {
    if ($stock <= 0) {
        return '<span class="badge bg-danger">Out of stock</span>';
    }
    if ($stock <= $min) {
        return '<span class="badge bg-warning text-dark">Low stock</span>';
    }
    return '<span class="badge bg-success">In stock</span>';
}
