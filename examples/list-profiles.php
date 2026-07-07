<?php
/**
 * List all linked WhatsApp Business profiles.
 *
 * Usage: php examples/list-profiles.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$whatsapp = new WhatsAppManager();
$profiles = $whatsapp->listProfiles();

if (empty($profiles)) {
    echo "No profiles linked yet.\n";
    echo "Run: php examples/link-business-profile.php <WABA_ID>\n";
    exit;
}

echo "=== WhatsApp Business Profiles ===\n\n";
echo str_pad('ID', 4) . str_pad('Name', 30) . str_pad('Phone', 20) . "WABA ID\n";
echo str_repeat('-', 80) . "\n";

foreach ($profiles as $p) {
    echo str_pad($p->id, 4)
        . str_pad(mb_substr($p->name ?? 'N/A', 0, 28), 30)
        . str_pad($p->phone_number ?? 'N/A', 20)
        . $p->waba_id . "\n";
}

echo "\nTo send a message: php examples/send-template-message.php <ID> <PHONE> <TEMPLATE>\n";
