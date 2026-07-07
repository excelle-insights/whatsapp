<?php
/**
 * Handle Meta OAuth callback.
 *
 * After the user authorizes your app via the auth-url URL,
 * Meta redirects here with ?code=AUTHORIZATION_CODE&state=STATE.
 *
 * Usage: Set your Meta App redirect URI in Facebook Login settings
 *        to the full URL of this script (must be HTTPS).
 *
 * For local testing, use a tunnel like ngrok:
 *   ngrok http 8080
 *   php -S localhost:8080 -t examples/
 *   Then use the ngrok URL in your Meta App settings
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;
use ExcelleInsights\WhatsApp\Controller\OAuthController;

$whatsapp = new WhatsAppManager();
$oauth = new OAuthController($whatsapp);

// Show incoming params for debugging
$error = $_GET['error'] ?? null;
$code  = $_GET['code'] ?? null;

echo "<pre>\n";
echo "=== OAuth Callback Debug ===\n\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "error: " . ($error ?? 'none') . "\n";
echo "code: " . (substr($code ?? '', 0, 20) . '...' ?: 'missing') . "\n";
echo "state: " . ($_GET['state'] ?? 'none') . "\n\n";

echo "--- Result ---\n";
echo $oauth->handleCallback() . "\n";
echo "</pre>\n";
