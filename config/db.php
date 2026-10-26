<?php
// ---- database settings ----
// default XAMPP user is root with an empty password
$db_host = 'localhost';
$db_name = 'shop_billing_db';
$db_user = 'root';
$db_pass = '';

// name of the software
define('APP_NAME', 'DukaanDesk');

// ---- shop details (these show on the invoice) ----
define('SHOP_NAME', 'Sharma General Store');
define('SHOP_ADDRESS', 'Main Market Road, Your City - 400001');
define('SHOP_PHONE', '98765 43210');
define('SHOP_GSTIN', '');          // leave empty if the shop has no GST number

date_default_timezone_set('Asia/Kolkata');
session_start();

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // keep mysql time same as php time
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:30'",
        )
    );
} catch (PDOException $e) {
    die('<h3>Could not connect to the database.</h3>'
        . '<p>Start MySQL in XAMPP and import sql/shop_billing.sql in phpMyAdmin.</p>'
        . '<small>' . htmlspecialchars($e->getMessage()) . '</small>');
}
