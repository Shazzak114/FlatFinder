<?php

declare(strict_types=1);

/**
 * Loads application configuration.
 *
 * InfinityFree does not reliably support environment variables, so the primary
 * configuration method is a PHP file outside the web root:
 *   ../config/config.php
 */
function app_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    // Load constants from config/config.php (intended to be OUTSIDE public_html).
    $root = dirname(__DIR__, 2); // public_html/includes -> (public_html) -> project root (or above htdocs)
    $configFile = $root . '/config/config.php';
    if (is_file($configFile)) {
        require_once $configFile;
    }

    $host = defined('DB_HOST') ? (string)DB_HOST : (string)(getenv('DB_HOST') ?: '127.0.0.1');
    $name = defined('DB_NAME') ? (string)DB_NAME : (string)(getenv('DB_NAME') ?: 'flatfinder');
    $user = defined('DB_USER') ? (string)DB_USER : (string)(getenv('DB_USER') ?: 'root');
    $pass = defined('DB_PASS') ? (string)DB_PASS : (string)(getenv('DB_PASS') ?: '');

    $dbDsn = (string)(getenv('DB_DSN') ?: '');
    if ($dbDsn === '') {
        $dbDsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    }

    $storageBase = realpath(__DIR__ . '/../storage');
    if ($storageBase === false) {
        $storageBase = __DIR__ . '/../storage';
    }

    $cfg = [
        'app_name' => defined('APP_NAME') ? (string)APP_NAME : (string)(getenv('APP_NAME') ?: 'FlatFinder'),
        'app_secret' => defined('APP_SECRET') ? (string)APP_SECRET : (string)(getenv('APP_SECRET') ?: 'change-me'),
        'app_env' => (string)(getenv('APP_ENV') ?: 'production'),
        'db' => [
            'dsn' => $dbDsn,
            'user' => $user,
            'pass' => $pass,
        ],
        'storage_base' => $storageBase,
        'admin' => [
            'username' => defined('ADMIN_USERNAME') ? (string)ADMIN_USERNAME : 'admin',
            'password_hash' => defined('ADMIN_PASSWORD_HASH') ? (string)ADMIN_PASSWORD_HASH : '',
        ],
    ];

    return $cfg;
}
