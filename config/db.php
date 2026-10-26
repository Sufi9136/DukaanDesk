<?php
// ---- database settings ----
// default XAMPP user is root with an empty password.
// On a web host, use the values shown in the host's MySQL page instead (see HOSTING.md)
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

// set to true only while fixing a problem, it shows the real error on the page
define('DEBUG', false);
ini_set('display_errors', DEBUG ? '1' : '0');

date_default_timezone_set('Asia/Kolkata');

// session cookie: not readable by javascript, and sent only over https when the site uses https
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params(array(
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $is_https,
    'httponly' => true,
    'samesite' => 'Lax',
));
session_start();

// a clean message instead of a blank page if something unexpected breaks
set_exception_handler(function ($e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '<h3>Something went wrong.</h3><p>Please go back and try again.</p>';
    if (DEBUG) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
});

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
    error_log($e->getMessage());
    echo '<h3>Could not connect to the database.</h3>'
        . '<p>Check that MySQL is running and that the values at the top of config/db.php are correct.</p>';
    if (DEBUG) {
        echo '<small>' . htmlspecialchars($e->getMessage()) . '</small>';
    }
    exit;
}
