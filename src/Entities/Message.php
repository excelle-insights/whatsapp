<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Entities;

class Message
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $conversationId = 0,
        public readonly string $direction = 'outbound',
        public readonly ?string $messageBody = null,
        public readonly string $messageType = 'text',
        public readonly ?string $mediaType = null,
        public readonly ?string $mediaUrl = null,
        public readonly ?string $mediaMimeType = null,
        public readonly ?int $mediaFileSize = null,
        public readonly ?string $caption = null,
        public readonly ?string $waMediaId = null,
        public readonly ?int $templateId = null,
        public readonly ?string $waMessageId = null,
        public readonly ?string $deliveryStatus = null,
        public readonly ?string $sentAt = null,
        public readonly int $authorId = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['message_id'] ?? $data['id'] ?? null,
            conversationId: (int) ($data['conversation_id'] ?? 0),
            direction: $data['direction'] ?? 'outbound',
            messageBody: $data['message_body'] ?? $data['body'] ?? null,
            messageType: $data['message_type'] ?? $data['type'] ?? 'text',
            mediaType: $data['media_type'] ?? null,
            mediaUrl: $data['media_url'] ?? null,
            mediaMimeType: $data['media_mime_type'] ?? null,
            mediaFileSize: isset($data['media_file_size']) ? (int) $data['media_file_size'] : null,
            caption: $data['caption'] ?? null,
            waMediaId: $data['wa_media_id'] ?? null,
            templateId: isset($data['template_id']) ? (int) $data['template_id'] : null,
            waMessageId: $data['wa_message_id'] ?? $data['wam_id'] ?? null,
            deliveryStatus: $data['delivery_status'] ?? $data['status'] ?? null,
            sentAt: $data['sent_at'] ?? null,
            authorId: (int) ($data['author_id'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'message_id'       => $this->id,
            'conversation_id'  => $this->conversationId,
            'direction'        => $this->direction,
            'message_body'     => $this->messageBody,
            'message_type'     => $this->messageType,
            'media_type'       => $this->mediaType,
            'media_url'        => $this->mediaUrl,
            'media_mime_type'  => $this->mediaMimeType,
            'media_file_size'  => $this->mediaFileSize,
            'caption'          => $this->caption,
            'wa_media_id'      => $this->waMediaId,
            'template_id'      => $this->templateId,
            'wa_message_id'    => $this->waMessageId,
            'delivery_status'  => $this->deliveryStatus,
            'sent_at'          => $this->sentAt,
            'author_id'        => $this->authorId,
        ];
    }
}
