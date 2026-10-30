<?php

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

$composerAutoload = PROJECT_ROOT . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

if (!isset($GLOBALS['project_config'])) {
    $GLOBALS['project_config'] = [
        'app' => require PROJECT_ROOT . '/config/app.php',
        'database' => require PROJECT_ROOT . '/config/database.php',
        'logging' => require PROJECT_ROOT . '/config/logging.php',
        'storage' => require PROJECT_ROOT . '/config/storage.php',
    ];
}

$appConfiguration = $GLOBALS['project_config']['app'];
$loggingConfiguration = $GLOBALS['project_config']['logging'];

date_default_timezone_set($appConfiguration['timezone']);

ini_set('log_errors', '1');
ini_set('error_log', $loggingConfiguration['path']);

if ($appConfiguration['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
    $sessionCookiePath = $appConfiguration['base_path'] !== ''
        ? $appConfiguration['base_path'] . '/'
        : '/';
    $applicationScheme = parse_url($appConfiguration['url'], PHP_URL_SCHEME);
    $secureCookie = $appConfiguration['environment'] === 'production'
        && strtolower((string) $applicationScheme) === 'https';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $sessionCookiePath,
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

