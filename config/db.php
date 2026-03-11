<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    $host = $config['db']['host'];
    $port = (int)$config['db']['port'];
    $name = $config['db']['name'];
    $charset = $config['db']['charset'];

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    try {
        $pdo = new PDO(
            $dsn,
            $config['db']['user'],
            $config['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $dbName = htmlspecialchars((string)$name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $schemaPath = htmlspecialchars((string)realpath(__DIR__ . '/../database/schema.sql'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo "<h1>Database connection failed</h1>";
        echo "<p><strong>Error:</strong> {$msg}</p>";
        echo "<p>Create the database <code>{$dbName}</code> and import <code>database/schema.sql</code> (phpMyAdmin) then refresh.</p>";
        if ($schemaPath !== '') {
            echo "<p><strong>Schema file:</strong> <code>{$schemaPath}</code></p>";
        }
        exit;
    }

    return $pdo;
}
