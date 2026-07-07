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
    ) {}

    public function sendText(int $profileId, string $to, string $text): object
    {
        MessageValidator::validateText($to, $text);

        $profile = $this->profiles->find($profileId);

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        $localId = $this->messages->create([
            'profile_id'  => $profileId,
            'direction'   => 'outbound',
            'from_number' => $profile->phone_number,
            'to_number'   => $to,
            'type'        => 'text',
            'body'        => $text,
            'status'      => 'pending',
        ]);

        try {
            $response = $this->client->sendText($profile->phone_number_id, $to, $text);

            if (isset($response->messages[0]->id)) {
                $this->messages->updateWamId($localId, $response->messages[0]->id, 'sent');
            }

            return (object)[
                'status'   => 'sent',
                'local_id' => $localId,
                'wam_id'   => $response->messages[0]->id ?? null,
                'data'     => $response,
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

    public function sendTemplate(int $profileId, string $to, string $templateName, array $params = []): object
    {
        MessageValidator::validateTemplate($to, $templateName);

        $profile = $this->profiles->find($profileId);

        if (!$profile || !$profile->phone_number_id) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found or no phone number configured',
            ];
        }

        $localId = $this->messages->create([
            'profile_id'  => $profileId,
            'direction'   => 'outbound',
            'from_number' => $profile->phone_number,
            'to_number'   => $to,
            'type'        => 'template',
            'body'        => $templateName,
            'status'      => 'pending',
        ]);

        try {
            $response = $this->client->sendTemplate(
                $profile->phone_number_id,
                $to,
                $templateName,
                $params
            );

            if (isset($response->messages[0]->id)) {
                $this->messages->updateWamId($localId, $response->messages[0]->id, 'sent');
            }

            return (object)[
                'status'   => 'sent',
                'local_id' => $localId,
                'wam_id'   => $response->messages[0]->id ?? null,
                'data'     => $response,
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

    public function processIncoming(array $payload): array
    {
        $results = [];

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                // Process incoming messages
                $messages = $value['messages'] ?? [];
                foreach ($messages as $msg) {
                    $result = $this->handleIncomingMessage($value, $msg);
                    $results[] = $result;
                }

                // Process message status updates
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

        switch ($type) {
            case 'text':
                $body = $msg['text']['body'] ?? '';
                break;
            case 'image':
                $body = $msg['image']['caption'] ?? '';
                $mediaId = $msg['image']['id'] ?? null;
                break;
            case 'video':
                $body = $msg['video']['caption'] ?? '';
                $mediaId = $msg['video']['id'] ?? null;
                break;
            case 'document':
                $body = $msg['document']['caption'] ?? '';
                $mediaId = $msg['document']['id'] ?? null;
                break;
            case 'audio':
                $mediaId = $msg['audio']['id'] ?? null;
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

        $existing = $this->messages->findByWamId($msgId);
        if ($existing) {
            return (object)[
                'status'   => 'duplicate',
                'wam_id'   => $msgId,
                'message'  => 'Message already processed',
            ];
        }

        $this->messages->create([
            'profile_id'  => $profile->id ?? null,
            'direction'   => 'inbound',
            'wam_id'      => $msgId,
            'from_number' => $from,
            'to_number'   => $toNumber,
            'type'        => $type,
            'body'        => $body,
            'media_id'    => $mediaId,
            'status'      => 'received',
            'metadata'    => $msg,
        ]);

        return (object)[
            'status'     => 'received',
            'wam_id'     => $msgId,
            'from'       => $from,
            'type'       => $type,
            'body'       => $body,
            'media_id'   => $mediaId,
        ];
    }

    private function handleStatusUpdate(array $status): object
    {
        $wamId    = $status['id'] ?? '';
        $statusStr = $status['status'] ?? '';
        $conversation = $status['conversation'] ?? null;

        if (!$wamId || !$statusStr) {
            return (object)[
                'status' => 'ignored',
            ];
        }

        $local = $this->messages->findByWamId($wamId);
        if ($local) {
            $this->messages->updateStatus($local->id, $statusStr);
        }

        return (object)[
            'status'   => 'updated',
            'wam_id'   => $wamId,
            'new_status' => $statusStr,
            'conversation' => $conversation,
        ];
    }
}
