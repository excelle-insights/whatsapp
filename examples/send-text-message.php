<?php
/**
 * Send a plain text WhatsApp message.
 *
 * Usage: php examples/send-text-message.php <PROFILE_ID> <PHONE_NUMBER> <MESSAGE>
 *
 * PHONE_NUMBER must include country code without + or 00, e.g. 254712345678
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId = (int) ($argv[1] ?? 0);
$to        = $argv[2] ?? '';
$text      = $argv[3] ?? '';

if ($profileId <= 0 || empty($to) || empty($text)) {
    echo "Usage: php examples/send-text-message.php <PROFILE_ID> <PHONE_NUMBER> <MESSAGE>\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

echo "Sending message to {$to}...\n\n";

$result = $whatsapp->sendText($profileId, $to, $text);

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($result->status === 'sent') {
    echo "\nMessage sent successfully. WAM ID: {$result->wam_id}\n";
} else {
    echo "\nFailed to send message.\n";
}
