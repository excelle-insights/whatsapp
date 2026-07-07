<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Client;

class MessageClient extends BaseClient
{
    public function sendText(string $phoneNumberId, string $to, string $text): object
    {
        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => $text,
                ],
            ]
        );
    }

    public function sendTemplate(
        string $phoneNumberId,
        string $to,
        string $templateName,
        array $params = []
    ): object {
        $template = [
            'name'       => $templateName,
            'language'   => [
                'code' => $params['language'] ?? 'en_US',
            ],
        ];

        if (!empty($params['components'])) {
            $template['components'] = $params['components'];
        }

        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => 'template',
                'template'          => $template,
            ]
        );
    }

    public function sendMedia(
        string $phoneNumberId,
        string $to,
        string $type,
        string $mediaId,
        ?string $caption = null,
        ?string $filename = null
    ): object {
        $media = [
            'id' => $mediaId,
        ];

        if ($caption !== null) {
            $media['caption'] = $caption;
        }

        if ($filename !== null && $type === 'document') {
            $media['filename'] = $filename;
        }

        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => $type,
                $type               => $media,
            ]
        );
    }

    public function sendInteractive(
        string $phoneNumberId,
        string $to,
        array $interactive
    ): object {
        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => 'interactive',
                'interactive'       => $interactive,
            ]
        );
    }

    public function markAsRead(string $phoneNumberId, string $messageId): object
    {
        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'status'            => 'read',
                'message_id'        => $messageId,
            ]
        );
    }

    public function uploadMedia(string $phoneNumberId, string $filePath, string $mimeType): object
    {
        $this->http->send(
            'POST',
            "{$this->graphApi}/{$this->apiVersion}/{$phoneNumberId}/media",
            [
                'Authorization' => 'Bearer ' . $this->auth->getAccessToken(),
            ],
            [
                'messaging_product' => 'whatsapp',
                'file'              => curl_file_create($filePath, $mimeType),
            ]
        );
    }

    public function getMediaUrl(string $mediaId): object
    {
        return $this->sendRequest(
            'GET',
            "{$mediaId}"
        );
    }

    public function downloadMedia(string $url): string
    {
        $accessToken = $this->auth->getAccessToken();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException(curl_error($ch));
        }
        curl_close($ch);

        return $response;
    }
}
