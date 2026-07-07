<?php
/**
 * Generate Meta OAuth authorization URL.
 *
 * Usage: php examples/auth-url.php
 *
 * 1. Run this script to get the authorization URL
 * 2. Open the URL in a browser and authorize your Meta app
 * 3. Facebook will redirect to your WHATSAPP_REDIRECT_URI
 *
 * Troubleshooting if you get "Login Error" from Facebook:
 *
 *   ☐ 'Facebook Login' product is added to your Meta App
 *      → https://developers.facebook.com/apps/{APP_ID}/add/
 *
 *   ☐ 'Valid OAuth Redirect URIs' is set under:
 *      Facebook Login > Settings > Valid OAuth Redirect URIs
 *      → Must EXACTLY match WHATSAPP_REDIRECT_URI in .env
 *
 *   ☐ Your Facebook account has a role in the Meta App:
 *      App Dashboard > App Roles > Roles > Add Tester/Admin
 *
 *   ☐ Your redirect URI is HTTPS (required by Facebook)
 *      For local dev, use ngrok: https://ngrok.com
 *
 *   ☐ Run the diagnostic script for more help:
 *      php examples/check-config.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$whatsapp = new WhatsAppManager();
$url = $whatsapp->getAuthUrl();

// echo "Open this URL in your browser to authorize:\n\n";
// echo $url . "\n\n";

// echo "After authorizing, Facebook will redirect to:\n";
// echo ($_ENV['WHATSAPP_REDIRECT_URI'] ?? 'WHATSAPP_REDIRECT_URI not set') . "\n";
?>
<a href="<?php echo $url; ?>">Authorize with Meta</a>
