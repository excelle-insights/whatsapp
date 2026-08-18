<?php
/**
 * WhatsApp Webhook handler.
 *
 * This script handles both verification (GET) and incoming 
 * message notifications (POST) from Meta.
 *
 * Usage:
 *   1. Set your Meta app's webhook URL to point to this script
 *   2. Set WHATSAPP_WEBHOOK_VERIFY_TOKEN in .env to match
 *      the verify token you configure in Meta App Dashboard
 *
 * For testing locally:
 *   php -S localhost:8080 examples/webhook-handler.php
 *
 * Then use a tool like ngrok to expose localhost to the internet:
 *   ngrok http 8080
 *
 * Set the ngrok URL as your webhook callback in Meta App Dashboard.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;
use ExcelleInsights\WhatsApp\Controller\WebhookController;

$whatsapp = new WhatsAppManager();
$webhook  = new WebhookController($whatsapp);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // Webhook verification
    $response = $webhook->verify();
    echo $response;
} elseif ($method === 'POST') {
    // Incoming message or status update.
    // Media messages (image/video/audio/document/sticker) are automatically
    // downloaded to WHATSAPP_MEDIA_PATH and stored on the message record.
    $response = $webhook->process();
    echo $response;
} else {
    http_response_code(405);
    echo 'Method not allowed';
}
