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

        $profile = $this->profiles->find((int) $data['profile_id']);

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        $localId = $this->templates->create([
            'profile_id'    => $profile->id,
            'name'          => $data['name'],
            'language'      => $data['language'],
            'category'      => $data['category'] ?? 'MARKETING',
            'header_format' => $data['header_format'] ?? null,
            'status'        => 'draft',
        ]);

        foreach ($data['components'] ?? [] as $component) {
            $this->components->create($localId, $component);
        }

        try {
            $response = $this->client->create($profile->waba_id, [
                'name'       => $data['name'],
                'language'   => $data['language'],
                'category'   => $data['category'] ?? 'MARKETING',
                'components' => $data['components'],
            ]);

            if (isset($response->id)) {
                $this->templates->markSynced($localId, $response->id);
            }

            return (object)[
                'status'              => 'submitted',
                'local_id'            => $localId,
                'whatsapp_template_id' => $response->id ?? null,
                'data'                => $response,
            ];
        } catch (\Throwable $e) {
            error_log("WhatsApp Template sync failed: " . $e->getMessage());
            $this->templates->markFailed($localId, $e->getMessage());

            return (object)[
                'status'   => 'draft',
                'local_id' => $localId,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function getAll(int $profileId): object
    {
        $local = $this->templates->getAll($profileId);

        $profile = $this->profiles->find($profileId);

        $remote = [];
        if ($profile) {
            try {
                $remoteResult = $this->client->getAll($profile->waba_id);
                $remote = $remoteResult->data ?? [];
            } catch (\Throwable $e) {
                error_log("Failed to fetch templates from Meta: " . $e->getMessage());
            }
        }

        return (object)[
            'local'  => $local,
            'remote' => $remote,
        ];
    }

    public function getFromMeta(int $profileId): object
    {
        $profile = $this->profiles->find($profileId);

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

        if (!$template->whatsapp_template_id) {
            $this->components->deleteByTemplateId($templateId);
            $this->templates->delete($templateId);

            return (object)[
                'status' => 'deleted',
                'message' => 'Local draft deleted',
            ];
        }

        $profile = $this->profiles->find($template->profile_id);

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        try {
            $this->client->delete($profile->waba_id, $template->name);

            $this->components->deleteByTemplateId($templateId);

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

        $templateId = $event['message_template_id'] ?? null;
        $newStatus  = $event['status'] ?? null;
        $quality    = $event['quality_score'] ?? null;
        $reason     = $event['rejection_reason'] ?? null;

        if (!$templateId || !$newStatus) {
            return (object)[
                'status' => 'ignored',
                'reason' => 'Missing template_id or status',
            ];
        }

        $local = $this->templates->findByWhatsappId($templateId);
        if (!$local) {
            return (object)[
                'status' => 'ignored',
                'reason' => 'Template not found locally',
            ];
        }

        $this->templates->updateStatus($local->id, $newStatus, $quality, $reason);

        return (object)[
            'status'         => 'updated',
            'local_id'       => $local->id,
            'new_status'     => $newStatus,
            'quality_score'  => $quality,
            'rejection_reason' => $reason,
        ];
    }

}
