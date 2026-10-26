<?php
// ---- Database settings: Render + Aiven MySQL ----
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'shop_billing_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
define('APP_NAME', 'DukaanDesk');

// ---- Shop details ----
define('SHOP_NAME', 'Sharma General Store');
define('SHOP_ADDRESS', 'Main Market Road, Your City - 400001');
define('SHOP_PHONE', '98765 43210');
define('SHOP_GSTIN', '');

date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:30'",
    ];

    // Use TLS for the hosted database.
    if (getenv('DB_HOST')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] =
            getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt';
    }

    $db = new PDO($dsn, $db_user, $db_pass, $options);

} catch (PDOException $e) {
    error_log('DukaanDesk database connection failed: ' . $e->getMessage());

    http_response_code(500);

    die(
        '<h3>Could not connect to the database.</h3>' .
        '<p>Please check the database configuration and connection.</p>'
    );
}
