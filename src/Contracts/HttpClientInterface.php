<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Contracts;

interface HttpClientInterface
{
    public function send(
        string $method,
        string $url,
        array $headers = [],
        $body = null
    ): array;
}
