<?php
require_once __DIR__ . '/config/db.php';

// send people to the right place
if (!empty($_SESSION['uid'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
