<?php
/**
 * Send (or upload and send) media messages — image, document, video, audio, sticker.
 *
 * Usage:
 *   By URL:   php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> <URL> [caption]
 *   By file:  php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> file <FILE_PATH> <MIME> [caption]
 *   By ID:    php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> id <MEDIA_ID> [caption] [filename]
 *
 * Examples:
 *   php examples/send-media-message.php 10 254720068917 image https://example.com/photo.jpg "Nice view"
 *   php examples/send-media-message.php 10 254720068917 document file /tmp/report.pdf application/pdf "Report"
 *   php examples/send-media-message.php 10 254720068917 image id 123456789 "My photo"
 *
 * Supported types: image, document, video, audio, sticker
 * PHONE must include country code without + or 00, e.g. 254712345678
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$profileId = (int) ($argv[1] ?? 0);
$to        = $argv[2] ?? '';
$type      = $argv[3] ?? '';
$mode      = $argv[4] ?? '';

if ($profileId <= 0 || empty($to) || empty($type) || empty($mode)) {
    echo "Usage:\n";
    echo "  php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> <URL>            [caption]\n";
    echo "  php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> file <FILE> <MIME> [caption]\n";
    echo "  php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> id    <MEDIA_ID>   [caption] [filename]\n";
    exit(1);
}

$allowed = ['image', 'document', 'video', 'audio', 'sticker'];
if (!in_array($type, $allowed)) {
    echo "Invalid type '{$type}'. Allowed: " . implode(', ', $allowed) . "\n";
    exit(1);
}

$whatsapp = new WhatsAppManager();

switch ($mode) {
    case 'file':
        $filePath = $argv[5] ?? '';
        $mimeType = $argv[6] ?? '';
        $caption  = $argv[7] ?? null;

        if (empty($filePath) || empty($mimeType)) {
            echo "Usage: php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> file <FILE_PATH> <MIME> [caption]\n";
            exit(1);
        }

        if (!file_exists($filePath)) {
            echo "File not found: {$filePath}\n";
            exit(1);
        }

        echo "Uploading {$filePath} ({$mimeType})...\n";
        $upload = $whatsapp->uploadMedia($profileId, $filePath, $mimeType);

        if ($upload->status !== 'uploaded') {
            echo "Upload failed: {$upload->error}\n";
            exit(1);
        }

        $mediaId = $upload->media_id;
        echo "Uploaded. Media ID: {$mediaId}\n\n";

        echo "Sending as {$type} message...\n";
        $result = $whatsapp->sendMedia($profileId, $to, $type, $mediaId, $caption);
        break;

    case 'id':
        $mediaId  = $argv[5] ?? '';
        $caption  = $argv[6] ?? null;
        $filename = $argv[7] ?? null;

        if (empty($mediaId)) {
            echo "Usage: php examples/send-media-message.php <PROFILE_ID> <PHONE> <TYPE> id <MEDIA_ID> [caption] [filename]\n";
            exit(1);
        }

        echo "Sending {$type} by media ID {$mediaId}...\n";
        $result = $whatsapp->sendMedia($profileId, $to, $type, $mediaId, $caption, $filename);
        break;

    default:
        // Sending by URL
        $link    = $mode;
        $caption = $argv[5] ?? null;

        echo "Sending {$type} by URL {$link}...\n";
        $result = $whatsapp->sendMediaByLink($profileId, $to, $type, $link, $caption);
        break;
}

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($result->status === 'sent') {
    echo "\nMedia message sent. WAM ID: {$result->wam_id}\n";
} elseif ($result->status === 'uploaded') {
    echo "\nMedia uploaded. Media ID: {$result->media_id}\n";
} else {
    echo "\nFailed: {$result->error}\n";
}
