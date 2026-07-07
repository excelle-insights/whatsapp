<?php
/**
 * Create a new WhatsApp message template.
 *
 * Usage: php examples/create-template.php <PROFILE_ID>
 *
 * The template will be submitted to Meta for review.
 * Check approval status with get-template-status.php.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId = (int) ($argv[1] ?? 0);
if ($profileId <= 0) {
    echo "Usage: php examples/create-template.php <PROFILE_ID>\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

$result = $whatsapp->createTemplate([
    'profile_id'    => $profileId,
    'name'          => 'welcome_message_v1',
    'language'      => 'en_US',
    'category'      => 'MARKETING',
    'components'    => [
        [
            'type' => 'BODY',
            'text' => 'Welcome {{1}}! Thank you for reaching out. We will get back to you shortly.',
            'example' => [
                'body_text' => [
                    ['John'],
                ],
            ],
        ],
        [
            'type' => 'BUTTONS',
            'buttons' => [
                [
                    'type' => 'QUICK_REPLY',
                    'text' => 'Learn More',
                ],
            ],
        ],
    ],
]);

echo "Template creation result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($result->status === 'submitted') {
    echo "\nTemplate submitted for review. WhatsApp Template ID: {$result->whatsapp_template_id}\n";
}
