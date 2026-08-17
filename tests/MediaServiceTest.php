<?php
namespace ExcelleInsights\WhatsApp\Tests;

use PHPUnit\Framework\TestCase;
use ExcelleInsights\WhatsApp\Client\MessageClient;
use ExcelleInsights\WhatsApp\Repositories\BusinessProfileRepository;
use ExcelleInsights\WhatsApp\Services\MediaService;

class MediaServiceTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/whatsapp_media_test_' . uniqid();
        $_ENV['WHATSAPP_MEDIA_PATH'] = $this->tmpDir;
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        unset($_ENV['WHATSAPP_MEDIA_PATH']);
    }

    public function testDownloadAndStoreWritesFileAndReturnsMetadata(): void
    {
        $binary = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD'); // tiny jpeg-ish blob

        $profiles = $this->createMock(BusinessProfileRepository::class);
        $profiles->method('find')
            ->with(10)
            ->willReturn((object) [
                'id'              => 10,
                'phone_number_id' => '330859536770902',
            ]);

        $client = $this->createMock(MessageClient::class);
        $client->method('getMediaUrl')
            ->with('media_123')
            ->willReturn((object) [
                'url'       => 'https://graph.facebook.com/some-download-url',
                'mime_type' => 'image/jpeg',
                'file_size' => strlen($binary),
            ]);

        $client->method('downloadMedia')
            ->willReturn($binary);

        $service = new MediaService($profiles, $client);

        $result = $service->downloadAndStore(10, 'media_123', 'image', 'Nice photo');

        $this->assertSame('image/jpeg', $result['media_mime_type']);
        $this->assertSame(strlen($binary), $result['media_file_size']);
        $this->assertSame('media_123', $result['wa_media_id']);
        $this->assertNotNull($result['media_url']);
        $this->assertStringEndsWith('.jpg', $result['media_url']);

        $absolute = $this->tmpDir . '/' . $result['media_url'];
        $this->assertFileExists($absolute);
        $this->assertSame($binary, file_get_contents($absolute));
    }

    public function testDownloadAndStoreThrowsWhenProfileMissing(): void
    {
        $profiles = $this->createMock(BusinessProfileRepository::class);
        $profiles->method('find')->willReturn(null);

        $client = $this->createMock(MessageClient::class);

        $service = new MediaService($profiles, $client);

        $this->expectException(\RuntimeException::class);

        $service->downloadAndStore(10, 'media_123', 'image');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
