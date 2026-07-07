<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Validation;

class MessageValidator
{
    public static function validateText(string $to, string $text): void
    {
        if (empty($to)) {
            throw new \InvalidArgumentException('Recipient phone number is required.');
        }

        if (empty($text)) {
            throw new \InvalidArgumentException('Message text is required.');
        }

        if (strlen($text) > 4096) {
            throw new \InvalidArgumentException('Message text must not exceed 4096 characters.');
        }

        if (strlen($text) === 0) {
            throw new \InvalidArgumentException('Message text cannot be empty.');
        }
    }

    public static function validateTemplate(string $to, string $templateName): void
    {
        if (empty($to)) {
            throw new \InvalidArgumentException('Recipient phone number is required.');
        }

        if (empty($templateName)) {
            throw new \InvalidArgumentException('Template name is required.');
        }
    }

    public static function validateMedia(string $to, string $type, string $mediaId): void
    {
        if (empty($to)) {
            throw new \InvalidArgumentException('Recipient phone number is required.');
        }

        $allowed = ['image', 'video', 'document', 'audio', 'sticker'];

        if (!in_array($type, $allowed)) {
            throw new \InvalidArgumentException(
                "Invalid media type '{$type}'. Allowed: " . implode(', ', $allowed)
            );
        }

        if (empty($mediaId)) {
            throw new \InvalidArgumentException('Media ID is required.');
        }
    }
}
