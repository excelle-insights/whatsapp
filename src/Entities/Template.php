<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Entities;

class Template
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $profileId,
        public readonly string $name,
        public readonly string $language,
        public readonly string $category,
        public readonly ?string $headerFormat,
        public readonly string $status,
        public readonly ?string $qualityScore,
        public readonly ?string $rejectionReason,
        public readonly ?string $whatsappTemplateId,
        public readonly array $components = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            profileId: $data['profile_id'] ?? null,
            name: $data['name'],
            language: $data['language'],
            category: $data['category'] ?? 'MARKETING',
            headerFormat: $data['header_format'] ?? null,
            status: $data['status'] ?? 'draft',
            qualityScore: $data['quality_score'] ?? null,
            rejectionReason: $data['rejection_reason'] ?? null,
            whatsappTemplateId: $data['whatsapp_template_id'] ?? null,
            components: $data['components'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'profile_id'            => $this->profileId,
            'name'                  => $this->name,
            'language'              => $this->language,
            'category'              => $this->category,
            'header_format'         => $this->headerFormat,
            'status'                => $this->status,
            'quality_score'         => $this->qualityScore,
            'rejection_reason'      => $this->rejectionReason,
            'whatsapp_template_id'  => $this->whatsappTemplateId,
            'components'            => $this->components,
        ];
    }
}
