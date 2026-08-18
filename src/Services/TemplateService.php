<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Services;

use ExcelleInsights\WhatsApp\Client\TemplateClient;
use ExcelleInsights\WhatsApp\Repositories\TemplateRepository;
use ExcelleInsights\WhatsApp\Repositories\TemplateComponentRepository;
use ExcelleInsights\WhatsApp\Repositories\BusinessProfileRepository;
use ExcelleInsights\WhatsApp\Validation\TemplateValidator;

class TemplateService
{
    public function __construct(
        private TemplateRepository $templates,
        private TemplateComponentRepository $components,
        private BusinessProfileRepository $profiles,
        private TemplateClient $client,
    ) {}

    public function create(array $data): object
    {
        TemplateValidator::validate($data);

        $localId = $this->templates->create([
            'template_name' => $data['template_name'] ?? $data['name'] ?? '',
            'template_body' => $data['template_body'] ?? $data['body'] ?? '',
            'placeholders'  => $data['placeholders'] ?? null,
            'language'      => $data['language'] ?? 'en_US',
            'category'      => $data['category'] ?? 'MARKETING',
            'status'        => 'Pending',
            'author_id'     => $data['author_id'] ?? 0,
            'post_date'     => $data['post_date'] ?? null,
        ]);

        foreach ($data['components'] ?? [] as $component) {
            $this->components->create($localId, $component);
        }

        $profile = $this->profiles->getFirst();
        if (!$profile) {
            return (object)[
                'status'  => 'draft',
                'local_id' => $localId,
                'error'   => 'No business profile configured',
            ];
        }

        try {
            $response = $this->client->create($profile->waba_id, [
                'name'       => $data['template_name'] ?? $data['name'] ?? '',
                'language'   => $data['language'] ?? 'en_US',
                'category'   => $data['category'] ?? 'MARKETING',
                'components' => $data['components'],
            ]);

            if (isset($response->id)) {
                $this->templates->markSynced($localId, $response->id);
            }

            return (object)[
                'status'         => 'submitted',
                'local_id'       => $localId,
                'meta_template_id' => $response->id ?? null,
                'data'           => $response,
            ];
        } catch (\Throwable $e) {
            error_log("WhatsApp Template sync failed: " . $e->getMessage());
            $this->templates->markFailed($localId, $e->getMessage());

            return (object)[
                'status'   => 'Pending',
                'local_id' => $localId,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function getAll(int $authorId = 0): object
    {
        $local = $this->templates->getAll($authorId);

        return (object)[
            'local'  => $local,
            'remote' => [],
        ];
    }

    public function getFromMeta(): object
    {
        $profile = $this->profiles->getFirst();

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        try {
            $response = $this->client->getAll($profile->waba_id);

            return (object)[
                'status' => 'success',
                'data'   => $response->data ?? [],
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function delete(int $templateId): object
    {
        $template = $this->templates->find($templateId);

        if (!$template) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Template not found',
            ];
        }

        if (!$template->meta_template_id) {
            $this->components->deleteByTemplateId($templateId);
            $this->templates->delete($templateId);

            return (object)[
                'status'  => 'deleted',
                'message' => 'Local draft deleted',
            ];
        }

        $profile = $this->profiles->getFirst();

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        try {
            $this->client->delete($profile->waba_id, $template->template_name);

            $this->components->deleteByTemplateId($templateId);
            $this->templates->delete($templateId);

            return (object)[
                'status'  => 'deleted',
                'message' => 'Template deleted from Meta and locally',
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function handleStatusUpdate(array $payload): object
    {
        $event = $payload['entry'][0]['changes'][0]['value'] ?? [];
        $eventType = $event['event'] ?? '';

        if ($eventType !== 'MESSAGE_TEMPLATE_STATUS_UPDATE') {
            return (object)[
                'status' => 'ignored',
                'event'  => $eventType,
            ];
        }

        $metaTemplateId = $event['message_template_id'] ?? null;
        $newStatus      = $event['status'] ?? null;
        $reason         = $event['rejection_reason'] ?? null;

        if (!$metaTemplateId || !$newStatus) {
            return (object)[
                'status' => 'ignored',
                'reason' => 'Missing template_id or status',
            ];
        }

        $local = $this->templates->findByMetaId($metaTemplateId);
        if (!$local) {
            return (object)[
                'status' => 'ignored',
                'reason' => 'Template not found locally',
            ];
        }

        $this->templates->updateStatus($local->template_id, $newStatus, $reason);

        return (object)[
            'status'           => 'updated',
            'local_id'         => $local->template_id,
            'new_status'       => $newStatus,
            'rejection_reason' => $reason,
        ];
    }
}
