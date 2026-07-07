#!/usr/bin/env php
<?php

declare(strict_types=1);

use ExcelleInsights\WhatsApp\Support\EnvLoader;

require_once 'vendor/autoload.php';

$options = getopt('', ['debug']);

$rootPath = realpath(dirname(__DIR__, 3));
if ($rootPath === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

$projectRoot = isset($options['debug'])
    ? __DIR__ . '/'
    : rtrim($rootPath, '/') . '/';
$envFile = $projectRoot . '.env';
$migrationsDir = isset($options['debug'])
    ? $projectRoot . 'database/migrations'
    : $projectRoot . 'vendor/excelle-insights/whatsapp/database/migrations';

function runCommand(string $command, string $cwd): void
{
    $descriptors = [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
    ];

    $process = proc_open($command, $descriptors, $pipes, $cwd);

    if (!is_resource($process)) {
        throw new RuntimeException("Failed to execute: {$command}");
    }

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException("Command exited with code {$exitCode}");
    }
}

if (!file_exists($envFile)) {
    fwrite(STDERR, "Database config not found (.env missing).\n");
    exit(1);
}

EnvLoader::load($envFile);

$host     = $_ENV['DB_HOST']     ?? null;
$dbname   = $_ENV['DB_NAME']     ?? null;
$user     = $_ENV['DB_USER']     ?? null;
$password = $_ENV['DB_PASSWORD'] ?? '';

if (!$host || !$dbname || !$user) {
    fwrite(STDERR, "Database config incomplete.\n");
    exit(1);
}

$phinxPath = file_exists($projectRoot . 'vendor/bin/phinx')
    ? $projectRoot . 'vendor/bin/phinx'
    : $projectRoot . 'vendor/bin/phinx.bat';

if (!file_exists($phinxPath)) {
    echo "Phinx not found. Installing...\n";

    try {
        runCommand(
            'composer require --dev robmorgan/phinx:^0.14',
            $projectRoot
        );
        echo "Phinx installed successfully.\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "Failed to install Phinx:\n{$e->getMessage()}\n");
        exit(1);
    }
}

$prefix = $_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp';
$tempConfig = sys_get_temp_dir() . '/whatsapp_phinx_' . uniqid() . '.php';

file_put_contents($tempConfig, <<<PHP
<?php
\$_ENV['WHATSAPP_TABLE_PREFIX'] = '{$prefix}';
return [
    'paths' => [
        'migrations' => '{$migrationsDir}',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => '{$host}',
            'name' => '{$dbname}',
            'user' => '{$user}',
            'pass' => '{$password}',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
PHP
);

try {
    runCommand(
        "{$phinxPath} migrate -c {$tempConfig}",
        $projectRoot
    );

    echo "WhatsApp migrations ran successfully!\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migrations failed:\n{$e->getMessage()}\n");
    unlink($tempConfig);
    exit(1);
}

unlink($tempConfig);
