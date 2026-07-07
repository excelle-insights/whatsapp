<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Client;

use ExcelleInsights\WhatsApp\Auth\Authentication;
use ExcelleInsights\WhatsApp\Contracts\HttpClientInterface;
use RuntimeException;

abstract class BaseClient
{
    protected string $graphApi;
    protected string $apiVersion;
    protected Authentication $auth;
    protected HttpClientInterface $http;

    public function __construct(
        string $graphApi,
        string $apiVersion,
        Authentication $auth,
        HttpClientInterface $http
    ) {
        $this->graphApi   = rtrim($graphApi, '/');
        $this->apiVersion = $apiVersion;
        $this->auth       = $auth;
        $this->http       = $http;
    }

    protected function sendRequest(string $method, string $path, array $data = []): object
    {
        $url = "{$this->graphApi}/{$this->apiVersion}/{$path}";

        $accessToken = $this->auth->getAccessToken();

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $response = $this->http->send(
            $method,
            $url,
            $headers,
            empty($data) ? null : $data
        );

        $status = $response['status'] ?? 0;
        $body   = $response['body'] ?? null;

        if (is_string($body)) {
            $decoded = json_decode($body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(
                    "Invalid JSON response from Meta API ({$status}): " . json_last_error_msg()
                );
            }
        } else {
            $decoded = $body;
        }

        if ($status >= 400) {
            $message = is_object($decoded) && property_exists($decoded, 'error')
                ? json_encode($decoded->error)
                : json_encode($decoded);

            throw new RuntimeException("Meta API Error ({$status}): {$message}");
        }

        return is_object($decoded) ? $decoded : (object) $decoded;
    }
}
