<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Client;

class TemplateClient extends BaseClient
{
    public function create(string $wabaId, array $data): object
    {
        $components = [];

        foreach ($data['components'] ?? [] as $component) {
            $c = [
                'type' => $component['type'],
            ];

            if (!empty($component['format'])) {
                $c['format'] = $component['format'];
            }

            if (!empty($component['text'])) {
                $c['text'] = $component['text'];
            }

            if (!empty($component['buttons'])) {
                $c['buttons'] = $component['buttons'];
            }

            if (!empty($component['example'])) {
                $c['example'] = $component['example'];
            }

            $components[] = $c;
        }

        $payload = [
            'name'               => $data['name'],
            'language'           => $data['language'],
            'category'           => $data['category'] ?? 'MARKETING',
            'components'         => $components,
        ];

        return $this->sendRequest(
            'POST',
            "{$wabaId}/message_templates",
            $payload
        );
    }

    public function getAll(string $wabaId, int $limit = 50): object
    {
        return $this->sendRequest(
            'GET',
            "{$wabaId}/message_templates?limit={$limit}"
        );
    }

    public function getById(string $wabaId, string $templateId): object
    {
        return $this->sendRequest(
            'GET',
            "{$wabaId}/message_templates?name={$templateId}"
        );
    }

    public function update(string $wabaId, string $templateId, array $data): object
    {
        $components = [];

        foreach ($data['components'] ?? [] as $component) {
            $c = [
                'type' => $component['type'],
            ];

            if (!empty($component['format'])) {
                $c['format'] = $component['format'];
            }

            if (!empty($component['text'])) {
                $c['text'] = $component['text'];
            }

            if (!empty($component['buttons'])) {
                $c['buttons'] = $component['buttons'];
            }

            $components[] = $c;
        }

        $payload = [
            'name'       => $data['name'],
            'language'   => $data['language'],
            'category'   => $data['category'] ?? 'MARKETING',
            'components' => $components,
        ];

        return $this->sendRequest(
            'POST',
            "{$wabaId}/message_templates",
            $payload
        );
    }

    public function delete(string $wabaId, string $templateName): object
    {
        return $this->sendRequest(
            'DELETE',
            "{$wabaId}/message_templates?name={$templateName}"
        );
    }
}
