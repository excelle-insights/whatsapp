<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Services;

use ExcelleInsights\WhatsApp\Client\MessageClient;
use ExcelleInsights\WhatsApp\Repositories\BusinessProfileRepository;

class MediaService
{
    private string $storagePath;

    public function __construct(
        private BusinessProfileRepository $profiles,
        private MessageClient $client,
    ) {
        $this->storagePath = rtrim(
            $_ENV['WHATSAPP_MEDIA_PATH'] ?? 'uploads/whatsapp',
            '/'
        );
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Download media from Meta and store it locally.
     *
     * @return array{media_url: ?string, media_mime_type: ?string, media_file_size: ?int, wa_media_id: string}
     */
    public function downloadAndStore(
        int $profileId,
        string $mediaId,
        string $type,
        ?string $caption = null
    ): array {
        $profile = $this->profiles->find($profileId);

        if (!$profile || !$profile->phone_number_id) {
            throw new \RuntimeException('Business profile not found or no phone number configured');
        }

        $info = $this->client->getMediaUrl($mediaId);

        $url      = $info->url ?? null;
        $mimeType = $info->mime_type ?? $this->guessMimeType($type);
        $fileSize = isset($info->file_size) ? (int) $info->file_size : null;

        if (!$url) {
            throw new \RuntimeException('Meta did not return a media download URL');
        }

        $binary = $this->client->downloadMedia($url);

        $path = $this->store($binary, $mimeType);

        return [
            'media_url'       => $path,
            'media_mime_type' => $mimeType,
            'media_file_size' => $fileSize,
            'wa_media_id'     => $mediaId,
        ];
    }

    private function store(string $binary, string $mimeType): string
    {
        $extension = $this->extensionFor($mimeType);
        $filename  = sha1($binary) . ($extension ? ".{$extension}" : '');
        $relative  = date('Y/m/d') . "/{$filename}";

        $absolute = $this->storagePath . '/' . $relative;

        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        file_put_contents($absolute, $binary);

        return $relative;
    }

    private function extensionFor(string $mimeType): string
    {
        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'image/bmp'       => 'bmp',
            'video/mp4'       => 'mp4',
            'video/3gpp'      => '3gp',
            'audio/ogg'       => 'ogg',
            'audio/mpeg'      => 'mp3',
            'audio/mp4'       => 'm4a',
            'audio/amr'       => 'amr',
            'audio/aac'       => 'aac',
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'text/plain'      => 'txt',
            'text/csv'        => 'csv',
            'application/zip' => 'zip',
        ];

        return $map[$mimeType] ?? '';
    }

    private function guessMimeType(string $type): string
    {
        $map = [
            'image'    => 'image/jpeg',
            'video'    => 'video/mp4',
            'audio'    => 'audio/mpeg',
            'document' => 'application/pdf',
            'sticker'  => 'image/webp',
        ];

        return $map[$type] ?? 'application/octet-stream';
    }
}
