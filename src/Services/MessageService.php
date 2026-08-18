<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Services;

use ExcelleInsights\WhatsApp\Client\MessageClient;
use ExcelleInsights\WhatsApp\Repositories\MessageRepository;
use ExcelleInsights\WhatsApp\Repositories\BusinessProfileRepository;
use ExcelleInsights\WhatsApp\Validation\MessageValidator;

class MessageService
{
    public function __construct(
        private MessageRepository $messages,
        private BusinessProfileRepository $profiles,
        private MessageClient $client,
        private MediaService $media,
    ) {}

    public function sendText(int $conversationId, string $to, string $text): object
    {
        MessageValidator::validateText($to, $text);

        $profile = $this->profiles->getFirst();

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        $localId = $this->messages->create([
            'conversation_id' => $conversationId,
            'direction'       => 'outbound',
            'message_body'    => $text,
            'message_type'    => 'text',
            'delivery_status' => 'pending',
            'author_id'       => 0,
        ]);

        try {
            $response = $this->client->sendText($profile->phone_number_id, $to, $text);

            if (isset($response->messages[0]->id)) {
                $this->messages->updateWaMessageId($localId, $response->messages[0]->id, 'sent');
            }

            return (object)[
                'status'       => 'sent',
                'local_id'     => $localId,
                'wa_message_id' => $response->messages[0]->id ?? null,
                'data'         => $response,
            ];
        } catch (\Throwable $e) {
            $this->messages->updateStatus($localId, 'failed');

            return (object)[
                'status'   => 'failed',
                'local_id' => $localId,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function sendTemplate(int $conversationId, string $to, string $templateName, array $params = []): object
    {
        MessageValidator::validateTemplate($to, $templateName);

        $profile = $this->profiles->getFirst();

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        $localId = $this->messages->create([
            'conversation_id' => $conversationId,
            'direction'       => 'outbound',
            'message_body'    => $templateName,
            'message_type'    => 'template',
            'delivery_status' => 'pending',
            'author_id'       => 0,
        ]);

        try {
            $response = $this->client->sendTemplate(
                $profile->phone_number_id,
                $to,
                $templateName,
                $params
            );

            if (isset($response->messages[0]->id)) {
                $this->messages->updateWaMessageId($localId, $response->messages[0]->id, 'sent');
            }

            return (object)[
                'status'       => 'sent',
                'local_id'     => $localId,
                'wa_message_id' => $response->messages[0]->id ?? null,
                'data'         => $response,
            ];
        } catch (\Throwable $e) {
            $this->messages->updateStatus($localId, 'failed');

            return (object)[
                'status'   => 'failed',
                'local_id' => $localId,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function sendMedia(
        int $conversationId,
        string $to,
        string $type,
        string $mediaId,
        ?string $caption = null,
        ?string $filename = null
    ): object {
        MessageValidator::validateMedia($to, $type, $mediaId);

        $profile = $this->profiles->getFirst();

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        $localId = $this->messages->create([
            'conversation_id' => $conversationId,
            'direction'       => 'outbound',
            'message_body'    => $caption,
            'message_type'    => $type,
            'wa_media_id'     => $mediaId,
            'delivery_status' => 'pending',
            'author_id'       => 0,
        ]);

        try {
            $response = $this->client->sendMedia(
                $profile->phone_number_id,
                $to,
                $type,
                $mediaId,
                $caption,
                $filename,
            );

            if (isset($response->messages[0]->id)) {
                $this->messages->updateWaMessageId($localId, $response->messages[0]->id, 'sent');
            }

            return (object)[
                'status'       => 'sent',
                'local_id'     => $localId,
                'wa_message_id' => $response->messages[0]->id ?? null,
                'data'         => $response,
            ];
        } catch (\Throwable $e) {
            $this->messages->updateStatus($localId, 'failed');

            return (object)[
                'status'   => 'failed',
                'local_id' => $localId,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function sendMediaByLink(
        int $conversationId,
        string $to,
        string $type,
        string $link,
        ?string $caption = null,
        ?string $filename = null
    ): object {
        MessageValidator::validateMedia($to, $type, $link);

        $profile = $this->profiles->getFirst();

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        $localId = $this->messages->create([
            'conversation_id' => $conversationId,
            'direction'       => 'outbound',
            'message_body'    => $caption,
            'message_type'    => $type,
            'wa_media_id'     => $link,
            'delivery_status' => 'pending',
            'author_id'       => 0,
        ]);

        try {
            $response = $this->client->sendMediaByLink(
                $profile->phone_number_id,
                $to,
                $type,
                $link,
                $caption,
                $filename,
            );

            if (isset($response->messages[0]->id)) {
                $this->messages->updateWaMessageId($localId, $response->messages[0]->id, 'sent');
            }

            return (object)[
                'status'       => 'sent',
                'local_id'     => $localId,
                'wa_message_id' => $response->messages[0]->id ?? null,
                'data'         => $response,
            ];
        } catch (\Throwable $e) {
            $this->messages->updateStatus($localId, 'failed');

            return (object)[
                'status'   => 'failed',
                'local_id' => $localId,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function uploadMedia(int $profileId, string $filePath, string $mimeType): object
    {
        $profile = $this->profiles->find($profileId);

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        try {
            $response = $this->client->uploadMedia(
                $profile->phone_number_id,
                $filePath,
                $mimeType,
            );

            return (object)[
                'status'   => 'uploaded',
                'media_id' => $response->id ?? null,
                'data'     => $response,
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function processIncoming(array $payload): array
    {
        $results = [];

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                $messages = $value['messages'] ?? [];
                foreach ($messages as $msg) {
                    $result = $this->handleIncomingMessage($value, $msg);
                    $results[] = $result;
                }

                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $status) {
                    $result = $this->handleStatusUpdate($status);
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    private function handleIncomingMessage(array $value, array $msg): object
    {
        $metadata = $value['metadata'] ?? [];
        $wabaId   = $metadata['phone_number_id'] ?? null;
        $from     = $msg['from'] ?? '';
        $msgId    = $msg['id'] ?? '';
        $type     = $msg['type'] ?? 'unknown';
        $toNumber = $metadata['display_phone_number'] ?? '';

        $profile = null;
        if ($wabaId) {
            $profile = $this->profiles->findByPhoneNumberId($wabaId);
        }

        $body = null;
        $mediaId = null;
        $mediaType = null;
        $mediaMimeType = null;
        $caption = null;

        switch ($type) {
            case 'text':
                $body = $msg['text']['body'] ?? '';
                break;
            case 'image':
            case 'video':
            case 'document':
            case 'audio':
            case 'sticker':
                $mediaId      = $msg[$type]['id'] ?? null;
                $mediaMimeType = $msg[$type]['mime_type'] ?? null;
                $caption      = $msg[$type]['caption'] ?? null;
                $mediaType    = $type;
                break;
            case 'interactive':
                $interactive = $msg['interactive'] ?? [];
                if (isset($interactive['button_reply'])) {
                    $body = $interactive['button_reply']['title'] ?? '';
                } elseif (isset($interactive['list_reply'])) {
                    $body = $interactive['list_reply']['title'] ?? '';
                }
                break;
        }

        $existing = $this->messages->findByWaMessageId($msgId);
        if ($existing) {
            return (object)[
                'status'  => 'duplicate',
                'wa_message_id' => $msgId,
                'message' => 'Message already processed',
            ];
        }

        $mediaDownload = null;
        if ($mediaId && $profile) {
            try {
                $mediaDownload = $this->media->downloadAndStore(
                    (int) $profile->id,
                    $mediaId,
                    $mediaType ?? $type,
                    $caption,
                );
            } catch (\Throwable $e) {
                error_log("WhatsApp media download failed: " . $e->getMessage());
            }
        }

        $this->messages->create([
            'conversation_id'  => 0,
            'direction'        => 'inbound',
            'message_body'     => $body ?? $caption,
            'message_type'     => $type,
            'media_type'       => $mediaDownload['media_type'] ?? $mediaType,
            'media_url'        => $mediaDownload['media_url'] ?? null,
            'media_mime_type'  => $mediaDownload['media_mime_type'] ?? $mediaMimeType,
            'media_file_size'  => $mediaDownload['media_file_size'] ?? null,
            'caption'          => $caption,
            'wa_media_id'      => $mediaDownload['wa_media_id'] ?? $mediaId,
            'wa_message_id'    => $msgId,
            'delivery_status'  => 'received',
            'author_id'        => 0,
        ]);

        return (object)[
            'status'          => 'received',
            'wa_message_id'   => $msgId,
            'from'            => $from,
            'type'            => $type,
            'body'            => $body,
            'media_type'      => $mediaDownload['media_type'] ?? $mediaType,
            'media_url'       => $mediaDownload['media_url'] ?? null,
            'media_mime_type' => $mediaDownload['media_mime_type'] ?? $mediaMimeType,
            'caption'         => $caption,
        ];
    }

    private function handleStatusUpdate(array $status): object
    {
        $waMessageId = $status['id'] ?? '';
        $statusStr   = $status['status'] ?? '';
        $conversation = $status['conversation'] ?? null;

        if (!$waMessageId || !$statusStr) {
            return (object)[
                'status' => 'ignored',
            ];
        }

        $local = $this->messages->findByWaMessageId($waMessageId);
        if ($local) {
            $this->messages->updateStatus($local->message_id, $statusStr);
        }

        return (object)[
            'status'     => 'updated',
            'wa_message_id' => $waMessageId,
            'new_status' => $statusStr,
            'conversation' => $conversation,
        ];
    }
}
