<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Facade;

use PDO;
use ExcelleInsights\WhatsApp\Support\EnvLoader;
use ExcelleInsights\WhatsApp\Auth\Authentication;
use ExcelleInsights\WhatsApp\Config\WhatsAppConfig;

use ExcelleInsights\WhatsApp\Client\BusinessProfileClient;
use ExcelleInsights\WhatsApp\Client\TemplateClient;
use ExcelleInsights\WhatsApp\Client\MessageClient;

use ExcelleInsights\WhatsApp\Contracts\HttpClientInterface;
use ExcelleInsights\WhatsApp\Repositories\TokenRepository;
use ExcelleInsights\WhatsApp\Repositories\BusinessProfileRepository;
use ExcelleInsights\WhatsApp\Repositories\TemplateRepository;
use ExcelleInsights\WhatsApp\Repositories\TemplateComponentRepository;
use ExcelleInsights\WhatsApp\Repositories\MessageRepository;

use ExcelleInsights\WhatsApp\Services\BusinessProfileService;
use ExcelleInsights\WhatsApp\Services\TemplateService;
use ExcelleInsights\WhatsApp\Services\MessageService;
use ExcelleInsights\WhatsApp\Services\MediaService;

class WhatsAppManager
{
    private Authentication $auth;
    private PDO $pdo;
    private string $graphApi;
    private string $apiVersion;
    private HttpClientInterface $http;

    private ?BusinessProfileRepository $profileRepo = null;
    private ?TemplateRepository $templateRepo = null;
    private ?TemplateComponentRepository $componentRepo = null;
    private ?MessageRepository $messageRepo = null;

    public function __construct(
        ?HttpClientInterface $http = null,
        ?PDO $pdo = null,
        ?string $envRoot = null
    ) {
        EnvLoader::load($envRoot);

        $this->graphApi   = WhatsAppConfig::graphApi();
        $this->apiVersion = WhatsAppConfig::apiVersion();

        if (!$pdo) {
            $dsn  = $_ENV['DB_DSN'] ?? null;
            $user = $_ENV['DB_USER'] ?? null;
            $pass = $_ENV['DB_PASSWORD'] ?? null;

            if (!$dsn) {
                throw new \RuntimeException(
                    'DB_DSN is not set. Ensure your project .env exists.'
                );
            }

            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        $this->pdo = $pdo;

        if ($http === null) {
            $http = new \ExcelleInsights\WhatsApp\Support\DefaultHttpClient($this->pdo);
        }

        $this->http = $http;

        $tokenRepo = new TokenRepository($pdo);
        $this->auth = new Authentication($tokenRepo, 'default');

        // Allow direct token via env var (System User token — no OAuth needed)
        $directToken = $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? null;
        if ($directToken) {
            $this->auth->setDirectToken($directToken);
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function setDirectToken(string $token): void
    {
        $this->auth->setDirectToken($token);
    }

    public function getAuthUrl(string $state = 'state123'): string
    {
        return $this->auth->getAuthUrl($state);
    }

    public function authenticate(string $code): void
    {
        $this->auth->exchangeAuthorizationCode($code);
    }

    public function getAccessToken(): string
    {
        return $this->auth->getAccessToken();
    }

    // ---------------------------------------------------------------
    // Business Profile
    // ---------------------------------------------------------------

    private function getProfileRepo(): BusinessProfileRepository
    {
        if (!$this->profileRepo) {
            $this->profileRepo = new BusinessProfileRepository($this->pdo);
        }
        return $this->profileRepo;
    }

    private function getTemplateRepo(): TemplateRepository
    {
        if (!$this->templateRepo) {
            $this->templateRepo = new TemplateRepository($this->pdo);
        }
        return $this->templateRepo;
    }

    private function getComponentRepo(): TemplateComponentRepository
    {
        if (!$this->componentRepo) {
            $this->componentRepo = new TemplateComponentRepository($this->pdo);
        }
        return $this->componentRepo;
    }

    private function getMessageRepo(): MessageRepository
    {
        if (!$this->messageRepo) {
            $this->messageRepo = new MessageRepository($this->pdo);
        }
        return $this->messageRepo;
    }

    private function getBusinessProfileService(): BusinessProfileService
    {
        $client = new BusinessProfileClient(
            $this->graphApi,
            $this->apiVersion,
            $this->auth,
            $this->http
        );

        return new BusinessProfileService(
            $this->getProfileRepo(),
            $client
        );
    }

    private function getTemplateService(): TemplateService
    {
        $client = new TemplateClient(
            $this->graphApi,
            $this->apiVersion,
            $this->auth,
            $this->http
        );

        return new TemplateService(
            $this->getTemplateRepo(),
            $this->getComponentRepo(),
            $this->getProfileRepo(),
            $client
        );
    }

    private function getMessageService(): MessageService
    {
        $client = new MessageClient(
            $this->graphApi,
            $this->apiVersion,
            $this->auth,
            $this->http
        );

        $media = new MediaService(
            $this->getProfileRepo(),
            $client
        );

        return new MessageService(
            $this->getMessageRepo(),
            $this->getProfileRepo(),
            $client,
            $media
        );
    }

    public function discoverWabas(): object
    {
        return $this->getBusinessProfileService()->discoverWabas();
    }

    public function linkBusinessProfile(string $wabaId, ?string $phoneNumberId = null): object
    {
        return $this->getBusinessProfileService()->link($wabaId, $phoneNumberId);
    }

    public function getBusinessProfile(int $profileId): ?object
    {
        return $this->getBusinessProfileService()->getProfile($profileId);
    }

    public function getBusinessProfileData(int $profileId): object
    {
        return $this->getBusinessProfileService()->getBusinessProfileData($profileId);
    }

    public function updateBusinessProfile(int $profileId, array $data): object
    {
        return $this->getBusinessProfileService()->updateBusinessProfile($profileId, $data);
    }

    public function syncPhoneNumbers(int $profileId): object
    {
        return $this->getBusinessProfileService()->syncPhoneNumbers($profileId);
    }

    public function getFirstProfile(): ?object
    {
        return $this->getProfileRepo()->getFirst();
    }

    public function listProfiles(): array
    {
        return $this->getProfileRepo()->getAll();
    }

    // ---------------------------------------------------------------
    // Templates
    // ---------------------------------------------------------------

    public function createTemplate(array $data): object
    {
        return $this->getTemplateService()->create($data);
    }

    public function getAllTemplates(int $profileId): object
    {
        return $this->getTemplateService()->getAll($profileId);
    }

    public function getTemplatesFromMeta(int $profileId): object
    {
        return $this->getTemplateService()->getFromMeta($profileId);
    }

    public function deleteTemplate(int $templateId): object
    {
        return $this->getTemplateService()->delete($templateId);
    }

    // ---------------------------------------------------------------
    // Messages
    // ---------------------------------------------------------------

    public function sendText(int $profileId, string $to, string $text): object
    {
        return $this->getMessageService()->sendText($profileId, $to, $text);
    }

    public function sendTemplate(int $profileId, string $to, string $templateName, array $params = []): object
    {
        return $this->getMessageService()->sendTemplate($profileId, $to, $templateName, $params);
    }

    public function sendMedia(
        int $profileId,
        string $to,
        string $type,
        string $mediaId,
        ?string $caption = null,
        ?string $filename = null
    ): object {
        return $this->getMessageService()->sendMedia(
            $profileId, $to, $type, $mediaId, $caption, $filename
        );
    }

    public function sendMediaByLink(
        int $profileId,
        string $to,
        string $type,
        string $link,
        ?string $caption = null,
        ?string $filename = null
    ): object {
        return $this->getMessageService()->sendMediaByLink(
            $profileId, $to, $type, $link, $caption, $filename
        );
    }

    public function uploadMedia(int $profileId, string $filePath, string $mimeType): object
    {
        return $this->getMessageService()->uploadMedia($profileId, $filePath, $mimeType);
    }

    public function downloadMedia(int $profileId, string $mediaId, string $type, ?string $caption = null): array
    {
        $client = new MessageClient(
            $this->graphApi,
            $this->apiVersion,
            $this->auth,
            $this->http
        );

        $media = new MediaService(
            $this->getProfileRepo(),
            $client
        );

        return $media->downloadAndStore($profileId, $mediaId, $type, $caption);
    }

    // ---------------------------------------------------------------
    // Webhooks
    // ---------------------------------------------------------------

    public function processWebhook(array $payload): array
    {
        // Handle template status updates
        $templateService = $this->getTemplateService();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                if (isset($value['event']) && $value['event'] === 'MESSAGE_TEMPLATE_STATUS_UPDATE') {
                    $templateService->handleStatusUpdate($payload);
                }
            }
        }

        // Handle incoming messages
        return $this->getMessageService()->processIncoming($payload);
    }

    public function verifyWebhook(string $mode, string $token, string $challenge): string
    {
        $verifyToken = $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '';

        if ($mode === 'subscribe' && $token === $verifyToken) {
            http_response_code(200);
            return $challenge;
        }

        http_response_code(403);
        return 'Verification failed';
    }
}
