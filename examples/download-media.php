<?php
/**
 * Download a WhatsApp media ID to local storage.
 *
 * Usage: php examples/download-media.php <PROFILE_ID> <MEDIA_ID> <TYPE> [caption]
 *
 * Supported types: image, video, audio, document, sticker
 *
 * Example:
 *   php examples/download-media.php 10 4567890123456789 image "Check this out"
 *
 * The file is saved to {WHATSAPP_MEDIA_PATH}/{YYYY}/{MM}/{DD}/{sha1}.{ext}
 * and the stored path is printed on success.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId = (int) ($argv[1] ?? 0);
$mediaId   = $argv[2] ?? '';
$type      = $argv[3] ?? '';
$caption   = $argv[4] ?? null;

if ($profileId <= 0 || empty($mediaId) || empty($type)) {
    echo "Usage: php examples/download-media.php <PROFILE_ID> <MEDIA_ID> <TYPE> [caption]\n";
    exit(1);
}

$allowed = ['image', 'video', 'audio', 'document', 'sticker'];
if (!in_array($type, $allowed)) {
    echo "Invalid type '{$type}'. Allowed: " . implode(', ', $allowed) . "\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

echo "Downloading media {$mediaId}...\n";

try {
    $result = $whatsapp->downloadMedia($profileId, $mediaId, $type, $caption);

    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

    if ($result['media_url']) {
        echo "\nMedia saved to: " . $result['media_url'] . "\n";
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Download failed: " . $e->getMessage() . "\n");
    exit(1);
}