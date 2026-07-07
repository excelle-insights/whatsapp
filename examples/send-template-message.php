<?php
/**
 * Send a template WhatsApp message.
 *
 * Usage: php examples/send-template-message.php <PROFILE_ID> <PHONE_NUMBER> <TEMPLATE_NAME>
 *
 * The template must be APPROVED by Meta before it can be sent.
 * PHONE_NUMBER must include country code without + or 00, e.g. 254712345678
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId    = (int) ($argv[1] ?? 0);
$to           = $argv[2] ?? '';
$templateName = $argv[3] ?? '';

if ($profileId <= 0 || empty($to) || empty($templateName)) {
    echo "Usage: php examples/send-template-message.php <PROFILE_ID> <PHONE_NUMBER> <TEMPLATE_NAME>\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

$params = [
    'language' => 'en_US',
];

echo "Sending template '{$templateName}' to {$to}...\n\n";

$result = $whatsapp->sendTemplate($profileId, $to, $templateName, $params);

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($result->status === 'sent') {
    echo "\nTemplate message sent. WAM ID: {$result->wam_id}\n";
} else {
    echo "\nFailed to send template message.\n";
}
