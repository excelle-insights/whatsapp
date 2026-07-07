<?php
/**
 * List all WhatsApp message templates (local and remote).
 *
 * Usage: php examples/list-templates.php <PROFILE_ID>
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId = (int) ($argv[1] ?? 0);
if ($profileId <= 0) {
    echo "Usage: php examples/list-templates.php <PROFILE_ID>\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

echo "Fetching templates...\n\n";

$result = $whatsapp->getAllTemplates($profileId);

echo "=== Local Templates ===\n";
if (count($result->local) === 0) {
    echo "(none)\n";
} else {
    foreach ($result->local as $template) {
        echo "  [{$template->id}] {$template->name} ({$template->language}) - {$template->status}\n";
    }
}

echo "\n=== Remote (Meta) Templates ===\n";
if (count($result->remote) === 0) {
    echo "(none)\n";
} else {
    foreach ($result->remote as $template) {
        echo "  [{$template->id}] {$template->name} - {$template->status}\n";
    }
}
