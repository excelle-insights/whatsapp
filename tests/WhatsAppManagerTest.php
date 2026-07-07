<?php
namespace ExcelleInsights\WhatsApp\Tests;

use PHPUnit\Framework\TestCase;
use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;
use ExcelleInsights\WhatsApp\Controller\OAuthController;
use ExcelleInsights\WhatsApp\Controller\WebhookController;
use ExcelleInsights\WhatsApp\Validation\TemplateValidator;
use ExcelleInsights\WhatsApp\Validation\MessageValidator;

class WhatsAppManagerTest extends TestCase
{
    private $mockManager;

    protected function setUp(): void
    {
        $this->mockManager = $this->createMock(WhatsAppManager::class);

        $this->mockManager->method('getAuthUrl')
            ->willReturn('https://facebook.com/dialog/oauth?client_id=test');

        $this->mockManager->method('authenticate')
            ->willReturnCallback(function ($code) {
                // simulate token exchange
            });

        $this->mockManager->method('verifyWebhook')
            ->willReturnCallback(function ($mode, $token, $challenge) {
                return $challenge;
            });
    }

    public function testGetAuthUrl(): void
    {
        $url = $this->mockManager->getAuthUrl();
        $this->assertStringContainsString('facebook.com', $url);
        $this->assertStringContainsString('client_id=test', $url);
    }

    public function testOAuthCallbackSuccess(): void
    {
        $_GET['code'] = 'test_code';

        $controller = new OAuthController($this->mockManager);
        $result = $controller->handleCallback();

        $this->assertStringContainsString('successful', $result);
    }

    public function testOAuthCallbackMissingCode(): void
    {
        $_GET = [];

        $controller = new OAuthController($this->mockManager);
        $result = $controller->handleCallback();

        $this->assertStringContainsString('Missing', $result);
    }

    public function testOAuthCallbackWithError(): void
    {
        $_GET['error'] = 'access_denied';

        $controller = new OAuthController($this->mockManager);
        $result = $controller->handleCallback();

        $this->assertStringContainsString('OAuth error', $result);
    }

    public function testWebhookVerifyReturnsChallenge(): void
    {
        $this->mockManager->method('verifyWebhook')
            ->with('subscribe', 'valid_token', '12345')
            ->willReturn('12345');

        $controller = new WebhookController($this->mockManager);

        $_GET['hub_mode'] = 'subscribe';
        $_GET['hub_verify_token'] = 'valid_token';
        $_GET['hub_challenge'] = '12345';

        $result = $controller->verify();
        $this->assertEquals('12345', $result);
    }

    public function testTemplateValidatorValid(): void
    {
        $data = [
            'name'       => 'test_template',
            'language'   => 'en_US',
            'profile_id' => 1,
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'Hello {{1}}',
                ],
            ],
        ];

        TemplateValidator::validate($data);
        $this->assertTrue(true);
    }

    public function testTemplateValidatorMissingField(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TemplateValidator::validate([
            'language'   => 'en_US',
            'profile_id' => 1,
            'components' => [],
        ]);
    }

    public function testTemplateValidatorInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TemplateValidator::validate([
            'name'       => 'Invalid-Name!',
            'language'   => 'en_US',
            'profile_id' => 1,
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello'],
            ],
        ]);
    }

    public function testMessageValidatorValidText(): void
    {
        MessageValidator::validateText('254712345678', 'Hello world');
        $this->assertTrue(true);
    }

    public function testMessageValidatorMissingTo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MessageValidator::validateText('', 'Hello');
    }

    public function testMessageValidatorMissingText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MessageValidator::validateText('254712345678', '');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
}
