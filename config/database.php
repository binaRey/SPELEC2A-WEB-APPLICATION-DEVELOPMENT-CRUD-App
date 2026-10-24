<?php
/**
 * Database configuration.
 * Values can be overridden with environment variables (DB_HOST, DB_NAME, ...).
 */
return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => (int) (getenv('DB_PORT') ?: 3306),
    'dbname'  => getenv('DB_NAME') ?: 'php_crud_app',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') ?: 'root',
    'charset' => 'utf8mb4',
];
