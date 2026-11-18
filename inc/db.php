<?php
/**
 * Creates the shared $pdo connection used by every page.
 * Settings come from inc/config.php (copy inc/config.example.php to create it).
 */
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('Setup needed: copy inc/config.example.php to inc/config.php and add your database details.');
}
$config = require $configFile;

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'] ?? 3306,
        $config['db_name']
    );
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Could not connect to the database. Check the settings in inc/config.php.');
}

require_once __DIR__ . '/functions.php';
