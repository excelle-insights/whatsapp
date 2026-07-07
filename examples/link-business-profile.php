<?php
/**
 * Link a Meta WhatsApp Business Account (WABA).
 *
 * Usage: php examples/link-business-profile.php <WABA_ID> [<PHONE_NUMBER_ID>]
 *
 * WABA_ID: Found in Meta Business Manager > WhatsApp Accounts > Account ID
 * PHONE_NUMBER_ID (optional): Found in WhatsApp Manager > Phone Numbers > ID
 *
 * If PHONE_NUMBER_ID is omitted, the system will try to discover it automatically.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$wabaId = $argv[1] ?? '';
$phoneNumberId = $argv[2] ?? null;

if (empty($wabaId)) {
    echo "Usage: php examples/link-business-profile.php <WABA_ID> [<PHONE_NUMBER_ID>]\n\n";
    echo "Find your WABA ID:\n";
    echo "  1. Go to https://business.facebook.com/wa/manage/home/\n";
    echo "  2. Your WABA ID is visible in the page URL or account info\n\n";
    echo "Find your Phone Number ID (needed for sending messages):\n";
    echo "  1. In WhatsApp Manager, go to Phone Numbers\n";
    echo "  2. Click your phone number\n";
    echo "  3. The ID is in the URL or under account details\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

echo "Linking WABA: {$wabaId}...\n";
if ($phoneNumberId) {
    echo "Using Phone Number ID: {$phoneNumberId}\n";
}
echo "\n";

$result = $whatsapp->linkBusinessProfile($wabaId, $phoneNumberId);

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($result->status === 'linked') {
    echo "\n✓ Business profile linked successfully.\n";
    echo "  Profile ID: {$result->profile->id}\n";
    echo "  Name: {$result->profile->name}\n";
    echo "  Phone: {$result->profile->phone_number}\n";

    if (!$result->profile->phone_number_id) {
        echo "\n⚠ Phone Number ID is missing. To send messages, set it:\n";
        echo "  php examples/link-business-profile.php {$wabaId} <PHONE_NUMBER_ID>\n";
    }
} else {
    echo "\n✗ Failed to link business profile.\n";
}
