<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Controller;

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

class WebhookController
{
    private WhatsAppManager $whatsapp;

    public function __construct(WhatsAppManager $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function verify(): string
    {
        $mode       = $_GET['hub_mode'] ?? '';
        $token      = $_GET['hub_verify_token'] ?? '';
        $challenge  = $_GET['hub_challenge'] ?? '';

        return $this->whatsapp->verifyWebhook($mode, $token, $challenge);
    }

    public function process(): string
    {
        $payload = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            return 'Invalid JSON payload';
        }

        try {
            $this->whatsapp->processWebhook($payload);
            http_response_code(200);
            return 'OK';
        } catch (\Throwable $e) {
            error_log('Webhook processing error: ' . $e->getMessage());
            http_response_code(200);
            return 'OK';
        }
    }
}
