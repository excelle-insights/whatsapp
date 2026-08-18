<?php
/**
 * Authenticated media proxy — reference implementation.
 *
 * Serves downloaded WhatsApp media through a PHP endpoint that verifies the
 * user is logged in, preventing direct hot-linking of the files.
 *
 * URL pattern: media.php?file=uploads/whatsapp/2026/08/17/a1b2c3d4.jpg
 *
 * SECURITY NOTES (adapt to your app):
 *   - Replace `is_logged_in()` with your own session/auth check.
 *   - Block direct web access to the media folder (see uploads/whatsapp/.htaccess).
 *   - Never trust the `file` parameter blindly: only serve paths under the
 *     configured WHATSAPP_MEDIA_PATH.
 *
 * Usage (dev server):
 *   php -S localhost:8080 examples/media-proxy.php
 */

$file = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(400);
    exit('Missing file parameter');
}

// --- Replace with your application's real authentication check ---
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

if (PHP_SAPI === 'cli-server') {
    session_start();
}

if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}
// ---------------------------------------------------------------

$storage = rtrim($_ENV['WHATSAPP_MEDIA_PATH'] ?? 'uploads/whatsapp', '/');
$base    = realpath($storage);

if ($base === false) {
    http_response_code(500);
    exit('Media storage not configured');
}

// Resolve the requested path and ensure it stays inside the storage dir.
$requested = realpath($storage . '/' . $file);

if ($requested === false
    || strpos($requested, $base) !== 0
    || !is_file($requested)
) {
    http_response_code(404);
    exit('File not found');
}

$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
    'mp4'  => 'video/mp4',
    'mp3'  => 'audio/mpeg',
    'ogg'  => 'audio/ogg',
    'm4a'  => 'audio/mp4',
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt'  => 'text/plain',
    'csv'  => 'text/csv',
    'zip'  => 'application/zip',
];

$ext = strtolower(pathinfo($requested, PATHINFO_EXTENSION));

header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($requested));
header('Cache-Control: public, max-age=86400');

readfile($requested);