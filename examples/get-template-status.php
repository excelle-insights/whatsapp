<?php
/**
 * Fetch templates directly from Meta and display their approval status.
 *
 * Usage: php examples/get-template-status.php <PROFILE_ID>
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId = (int) ($argv[1] ?? 0);
if ($profileId <= 0) {
    echo "Usage: php examples/get-template-status.php <PROFILE_ID>\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

echo "Fetching template statuses from Meta...\n\n";

$result = $whatsapp->getTemplatesFromMeta($profileId);

if ($result->status !== 'success') {
    echo "Error: {$result->error}\n";
    exit(1);
}

if (count($result->data) === 0) {
    echo "No templates found.\n";
    exit(0);
}

foreach ($result->data as $template) {
    echo "Name: {$template->name}\n";
    echo "  ID:     {$template->id}\n";
    echo "  Status: {$template->status}\n";
    echo "  Category: {$template->category}\n";
    echo "  Language: {$template->language}\n";
    if (isset($template->quality_score)) {
        echo "  Quality: {$template->quality_score}\n";
    }
    if (isset($template->rejection_reason)) {
        echo "  Rejection: {$template->rejection_reason}\n";
    }
    echo "\n";
}
