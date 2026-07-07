<?php

require_once __DIR__ . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Support\EnvLoader;
EnvLoader::load(__DIR__);

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'production_db',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'development_db',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'testing_db',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
