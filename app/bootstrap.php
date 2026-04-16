<?php

declare(strict_types=1);

session_start();

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

require_once APP_PATH . '/Helpers/functions.php';
require_once APP_PATH . '/Helpers/admin_panel.php';
require_once APP_PATH . '/Core/Database.php';
require_once APP_PATH . '/Core/Auth.php';
require_once APP_PATH . '/Core/Notifier.php';

$configFile = STORAGE_PATH . '/config.php';
$installLock = STORAGE_PATH . '/installed.lock';

function app_installed(): bool
{
    global $configFile, $installLock;

    return file_exists($configFile) && file_exists($installLock);
}

function app_config(): array
{
    global $configFile;

    if (!file_exists($configFile)) {
        return [];
    }

    $config = require $configFile;

    return is_array($config) ? $config : [];
}

function db(): Database
{
    static $database = null;
    static $schemaReady = false;

    if ($database instanceof Database) {
        return $database;
    }

    $config = app_config();
    $database = new Database($config['database'] ?? []);

    if (!$schemaReady && !empty($config['database'])) {
        $database->ensureSchema();
        $schemaReady = true;
    }

    return $database;
}

function app_settings(): array
{
    if (!app_installed()) {
        return [];
    }

    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    try {
        $settings = db()->settings();
    } catch (Throwable $exception) {
        $settings = [];
    }

    return $settings;
}
