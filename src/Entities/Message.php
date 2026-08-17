<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Entities;

class Message
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $profileId,
        public readonly string $direction,
        public readonly ?string $wamId,
        public readonly string $fromNumber,
        public readonly string $toNumber,
        public readonly string $type,
        public readonly ?string $body,
        public readonly ?string $mediaId,
        public readonly string $status,
        public readonly ?int $templateId,
        public readonly ?array $metadata,
        public readonly ?string $mediaType = null,
        public readonly ?string $mediaUrl = null,
        public readonly ?string $mediaMimeType = null,
        public readonly ?int $mediaFileSize = null,
        public readonly ?string $caption = null,
        public readonly ?string $waMediaId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            profileId: $data['profile_id'] ?? null,
            direction: $data['direction'],
            wamId: $data['wam_id'] ?? null,
            fromNumber: $data['from_number'],
            toNumber: $data['to_number'],
            type: $data['type'],
            body: $data['body'] ?? null,
            mediaId: $data['media_id'] ?? null,
            status: $data['status'] ?? 'received',
            templateId: $data['template_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            mediaType: $data['media_type'] ?? null,
            mediaUrl: $data['media_url'] ?? null,
            mediaMimeType: $data['media_mime_type'] ?? null,
            mediaFileSize: $data['media_file_size'] ?? null,
            caption: $data['caption'] ?? null,
            waMediaId: $data['wa_media_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'profile_id'       => $this->profileId,
            'direction'        => $this->direction,
            'wam_id'           => $this->wamId,
            'from_number'      => $this->fromNumber,
            'to_number'        => $this->toNumber,
            'type'             => $this->type,
            'body'             => $this->body,
            'media_id'         => $this->mediaId,
            'status'           => $this->status,
            'template_id'      => $this->templateId,
            'metadata'         => $this->metadata,
            'media_type'       => $this->mediaType,
            'media_url'        => $this->mediaUrl,
            'media_mime_type'  => $this->mediaMimeType,
            'media_file_size'  => $this->mediaFileSize,
            'caption'          => $this->caption,
            'wa_media_id'      => $this->waMediaId,
        ];
    }
}
