<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Controller;

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

class OAuthController
{
    private WhatsAppManager $whatsapp;

    public function __construct(WhatsAppManager $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function redirectToMeta(): void
    {
        $authUrl = $this->whatsapp->getAuthUrl();
        header("Location: $authUrl");
        exit;
    }

    public function handleCallback(): string
    {
        $code  = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            return "Meta OAuth error: " . htmlspecialchars($error);
        }

        if (!$code) {
            return "Missing authorization code in callback.";
        }

        try {
            $this->whatsapp->authenticate($code);

            // Auto-discover WABAs and link the first phone number
            try {
                $pdo = $this->whatsapp->getPdo();

                // Get the latest token (stored with FB user ID)
                $stmt = $pdo->query("
                    SELECT id, access_token
                    FROM whatsapp_access_tokens
                    ORDER BY updated_at DESC LIMIT 1
                ");
                $row = $stmt->fetch(\PDO::FETCH_OBJ);
                if (!$row) {
                    return "WhatsApp integration successful! (no token found for auto-link)";
                }

                $tokenData = json_decode($row->access_token, true);
                $rawToken = $tokenData['access_token'] ?? null;
                if (!$rawToken) {
                    return "WhatsApp integration successful! (invalid token for auto-link)";
                }

                // Move token to 'default' (delete old default first to avoid UNIQUE conflict)
                $pdo->exec("DELETE FROM whatsapp_access_tokens WHERE user_id = 'default'");
                $pdo->prepare("UPDATE whatsapp_access_tokens SET user_id = 'default' WHERE id = ?")
                    ->execute([$row->id]);

                $this->whatsapp->setDirectToken($rawToken);

                // Auto-discover WABAs and link them
                $wabas = $this->whatsapp->discoverWabas();
                if ($wabas->status === 'success' && !empty($wabas->data)) {
                    $linked = [];
                    foreach ($wabas->data as $waba) {
                        $result = $this->whatsapp->linkBusinessProfile($waba->id);
                        if (isset($result->profile->id)) {
                            $this->whatsapp->syncPhoneNumbers($result->profile->id);
                            $linked[] = $waba->name ?? $waba->id;
                        }
                    }
                    return "WhatsApp integration successful!"
                        . " Linked " . count($linked) . " WABA(s): "
                        . implode(', ', $linked) . "."
                        . " You can close this window.";
                }
            } catch (\Throwable $e) {
                error_log("Auto-link failed: " . $e->getMessage());
            }

            return "WhatsApp integration successful! You can close this window.";
        } catch (\Throwable $e) {
            return "Authentication failed: " . $e->getMessage();
        }
    }
}
