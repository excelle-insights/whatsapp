<?php
/**
 * Diagnose common Meta App configuration issues.
 *
 * Usage: php examples/check-config.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use ExcelleInsights\WhatsApp\Support\EnvLoader;
use ExcelleInsights\WhatsApp\Config\WhatsAppConfig;

EnvLoader::load(dirname(__DIR__));

echo "=== WhatsApp Config Diagnostic ===\n\n";

$checks = [
    'WHATSAPP_APP_ID' => [
        'value' => $_ENV['WHATSAPP_APP_ID'] ?? 'NOT SET',
        'required' => true,
        'hint' => 'Found in Meta App Dashboard > App Settings > Basic',
    ],
    'WHATSAPP_APP_SECRET' => [
        'value' => $_ENV['WHATSAPP_APP_SECRET'] ?? 'NOT SET',
        'required' => true,
        'hint' => 'Found in Meta App Dashboard > App Settings > Basic',
    ],
    'WHATSAPP_ACCESS_TOKEN' => [
        'value' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? 'NOT SET',
        'required' => false,
        'hint' => 'If set, skips OAuth entirely. Generate in Business Manager > System Users',
    ],
    'WHATSAPP_REDIRECT_URI' => [
        'value' => $_ENV['WHATSAPP_REDIRECT_URI'] ?? 'NOT SET',
        'required' => false,
        'hint' => "Only needed for OAuth. Must match 'Valid OAuth Redirect URIs' in Meta App",
    ],
    'WHATSAPP_TABLE_PREFIX' => [
        'value' => $_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp (default)',
        'required' => false,
    ],
    'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => [
        'value' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? 'NOT SET',
        'required' => false,
        'hint' => 'Used for webhook verification. Set in Meta App > WhatsApp > Configuration',
    ],
    'DB_DSN' => [
        'value' => $_ENV['DB_DSN'] ?? 'NOT SET',
        'required' => true,
        'hint' => 'Database connection string (e.g. mysql:host=127.0.0.1;dbname=myapp)',
    ],
];

$allOk = true;

foreach ($checks as $name => $check) {
    $value = $check['value'];
    $isSet = $value !== 'NOT SET';
    $status = $isSet ? 'OK' : (in_array($name, ['WHATSAPP_ACCESS_TOKEN', 'WHATSAPP_REDIRECT_URI', 'WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'WHATSAPP_TABLE_PREFIX']) ? 'SKIP (optional)' : 'MISSING');
    $hint = $check['hint'] ?? '';

    if ($check['required'] && !$isSet) {
        $status = 'ERROR';
        $allOk = false;
    }

    $display = $value;
    if (in_array($name, ['WHATSAPP_APP_SECRET', 'WHATSAPP_APP_ID', 'WHATSAPP_ACCESS_TOKEN']) && $isSet) {
        $display = substr($value, 0, 6) . '...' . substr($value, -4);
    }

    echo "[{$status}] {$name} = {$display}\n";
    if (!$isSet && $hint) {
        echo "       Hint: {$hint}\n";
    }
}

echo "\n--- Auth Method ---\n\n";

$hasToken = $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? null;
if ($hasToken) {
    echo "Using System User Token (WHATSAPP_ACCESS_TOKEN) — no OAuth needed.\n";
    echo "The package will use this token directly. OAuth flow is skipped.\n";
} else {
    echo "Using OAuth flow (requires WHATSAPP_REDIRECT_URI and Meta App config).\n";
    echo "Your Meta App needs:\n\n";
    $redirectUri = $_ENV['WHATSAPP_REDIRECT_URI'] ?? '';
    $appId = $_ENV['WHATSAPP_APP_ID'] ?? '';

    echo "☐ 'Facebook Login' product added to your Meta App\n";
    echo "☐ 'WhatsApp' product added to your Meta App\n";
    echo "☐ App Domains (Settings > Basic): The domain of your redirect URI\n";
    echo "☐ Valid OAuth Redirect URIs (Facebook Login > Settings):\n";
    echo "    → {$redirectUri}\n";
    echo "    (must match EXACTLY — no trailing slash unless configured with one)\n";
    echo "☐ App is in 'Development' mode; your FB account is Admin/Tester\n";
    echo "☐ Redirect URL uses HTTPS (required unless using localhost)\n";

    echo "\n--- Alternative: Skip OAuth entirely ---\n\n";
    echo "Instead of OAuth, set WHATSAPP_ACCESS_TOKEN in .env:\n";
    echo "1. Go to https://business.facebook.com/settings/system-users\n";
    echo "2. Create a System User or use existing one\n";
    echo "3. Assign 'WhatsApp business messaging' and 'WhatsApp business management' permissions\n";
    echo "4. Generate a token and copy it to WHATSAPP_ACCESS_TOKEN\n";
    echo "5. That's it — no OAuth redirect required.\n";
}

echo "\n" . ($allOk ? "✅ All required env vars are set.\n" : "❌ Some required env vars are missing.\n");
echo "\n";
