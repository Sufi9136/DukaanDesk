<?php

// ==================================================
// DATABASE SETTINGS
// Local: XAMPP
// Hosted: Render + Aiven MySQL
// ==================================================

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'shop_billing_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';


// ==================================================
// APPLICATION SETTINGS
// ==================================================

define('APP_NAME', 'DukaanDesk');

define('SHOP_NAME', 'Sharma General Store');
define('SHOP_ADDRESS', 'Main Market Road, Your City - 400001');
define('SHOP_PHONE', '98765 43210');
define('SHOP_GSTIN', '');

define('DEBUG', false);

ini_set('display_errors', DEBUG ? '1' : '0');

date_default_timezone_set('Asia/Kolkata');


// ==================================================
// SESSION SETTINGS
// ==================================================

$is_https = (
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
) || (
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==================================================
// DATABASE CONNECTION
// ==================================================

try {

    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:30'",
        PDO::ATTR_TIMEOUT => 10,
    ];

    // Use the Aiven CA certificate for hosted connections.
    // Local XAMPP does not require this certificate.
    if (getenv('DB_HOST')) {

        $caPath = __DIR__ . '/../ca.pem';

        if (!is_readable($caPath)) {
            throw new RuntimeException(
                'Aiven CA certificate is missing or unreadable.'
            );
        }

        $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    }

    $db = new PDO(
        $dsn,
        $db_user,
        $db_pass,
        $options
    );

} catch (Throwable $e) {

    error_log(
        'DukaanDesk database connection failed: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo '<h3>Could not connect to the database.</h3>';
    echo '<p>Please check the database configuration and connection.</p>';

    if (DEBUG) {
        echo '<pre>' .
            htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</pre>';
    }

    exit;
}


// ==================================================
// GLOBAL EXCEPTION HANDLER
// ==================================================

set_exception_handler(function ($e) {

    error_log(
        'DukaanDesk application error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo '<h3>Something went wrong.</h3>';
    echo '<p>Please go back and try again.</p>';

    if (DEBUG) {
        echo '<pre>' .
            htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</pre>';
    }
});
