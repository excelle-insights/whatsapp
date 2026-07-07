<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Contracts;

interface LoggerInterface
{
    public function info(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
}
