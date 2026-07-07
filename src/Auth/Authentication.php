<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Auth;

use ExcelleInsights\WhatsApp\Repositories\TokenRepository;
use ExcelleInsights\WhatsApp\Config\WhatsAppConfig;

class Authentication
{
    private string $graphApi;
    private string $apiVersion;
    private ?string $directToken = null;

    public function __construct(
        private TokenRepository $tokens,
        private string $userId
    ) {
        $this->graphApi   = WhatsAppConfig::graphApi();
        $this->apiVersion = WhatsAppConfig::apiVersion();
    }

    public function setDirectToken(string $token): void
    {
        $this->directToken = $token;
    }

    public function getAuthUrl(string $state = ''): string
    {
        $clientId    = WhatsAppConfig::appId();
        $redirectUri = WhatsAppConfig::redirectUri();
        $scope = 'business_management,whatsapp_business_messaging,whatsapp_business_management';

        if (empty($state)) {
            $state = bin2hex(random_bytes(16));
        }

        return "https://www.facebook.com/{$this->apiVersion}/dialog/oauth"
            . "?client_id={$clientId}"
            . "&redirect_uri={$redirectUri}"
            . "&response_type=code"
            . "&scope=" . urlencode($scope)
            . "&state={$state}";
    }

    public function getAccessToken(): string
    {
        if ($this->directToken !== null) {
            return $this->directToken;
        }

        $record = $this->tokens->getLatest($this->userId);

        if (!$record) {
            throw new \RuntimeException('No access token found');
        }

        $token = json_decode($record->access_token, true);

        $expiresAt = strtotime($record->expires_at);
        $currentTime = time();

        // Refresh if within 7 days of expiry
        $refreshWindow = 7 * 24 * 3600;
        if ($currentTime < ($expiresAt - $refreshWindow)) {
            return $token['access_token'];
        }

        // Attempt to extend the long-lived token
        try {
            $newToken = $this->exchangeLongLivedToken($token['access_token']);

            $payload = [
                'access_token' => $newToken['access_token'],
                'token_type'   => $newToken['token_type'] ?? 'bearer',
            ];

            $expiresIn = $newToken['expires_in'] ?? (60 * 24 * 3600);
            $this->tokens->save(
                $this->userId,
                $payload,
                date('Y-m-d H:i:s', $currentTime + $expiresIn)
            );

            return $payload['access_token'];
        } catch (\Throwable $e) {
            error_log("Failed to refresh WhatsApp token: " . $e->getMessage());
            return $token['access_token'];
        }
    }

    public function exchangeAuthorizationCode(string $code): void
    {
        $clientId    = WhatsAppConfig::appId();
        $clientSecret = WhatsAppConfig::appSecret();
        $redirectUri = WhatsAppConfig::redirectUri();

        $url = "{$this->graphApi}/{$this->apiVersion}/oauth/access_token";

        $postData = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'client_secret' => $clientSecret,
            'code'          => $code,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException(curl_error($ch));
        }
        curl_close($ch);

        $shortLived = json_decode($response, true);

        if (empty($shortLived['access_token'])) {
            throw new \RuntimeException(
                'Failed to exchange authorization code: '
                . ($shortLived['error']['message'] ?? 'Unknown error')
            );
        }

        // Exchange short-lived for long-lived
        $longLived = $this->exchangeLongLivedToken($shortLived['access_token']);

        $payload = [
            'access_token' => $longLived['access_token'],
            'token_type'   => $longLived['token_type'] ?? 'bearer',
        ];

        // Extract FB user ID from token debug
        $fbUserId = $this->getUserIdFromToken($longLived['access_token']);

        $expiresIn = $longLived['expires_in'] ?? (60 * 24 * 3600);

        $this->tokens->save(
            $fbUserId,
            $payload,
            date('Y-m-d H:i:s', time() + $expiresIn)
        );
    }

    private function exchangeLongLivedToken(string $shortLivedToken): array
    {
        $clientId    = WhatsAppConfig::appId();
        $clientSecret = WhatsAppConfig::appSecret();

        $url = "{$this->graphApi}/{$this->apiVersion}/oauth/access_token"
            . "?grant_type=fb_exchange_token"
            . "&client_id={$clientId}"
            . "&client_secret={$clientSecret}"
            . "&fb_exchange_token={$shortLivedToken}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException(curl_error($ch));
        }
        curl_close($ch);

        $token = json_decode($response, true);

        if (empty($token['access_token'])) {
            throw new \RuntimeException(
                'Failed to exchange for long-lived token: '
                . ($token['error']['message'] ?? 'Unknown error')
            );
        }

        return $token;
    }

    private function getUserIdFromToken(string $accessToken): string
    {
        $url = "{$this->graphApi}/{$this->apiVersion}/me?access_token={$accessToken}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException(curl_error($ch));
        }
        curl_close($ch);

        $data = json_decode($response, true);

        return $data['id'] ?? 'unknown';
    }
}
