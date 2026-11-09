<?php
// Core bootstrap: config, session, helpers. Compatible with PHP 7.4+.
define('ROOT', dirname(__DIR__));

if (!is_file(ROOT . '/config.php')) {
    $inAdmin = strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false;
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
        header('Location: ' . ($inAdmin ? '../' : '') . 'install.php');
        exit;
    }
} else {
    require ROOT . '/config.php';
}

if (defined('DEBUG') && DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

date_default_timezone_set('Africa/Nairobi');

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('tripexsid');
    session_start();
}

require __DIR__ . '/functions.php';
