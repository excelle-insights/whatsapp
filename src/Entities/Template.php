<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Entities;

class Template
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $templateName,
        public readonly string $templateBody,
        public readonly ?string $placeholders,
        public readonly ?string $language,
        public readonly string $category,
        public readonly string $status,
        public readonly ?string $metaTemplateId,
        public readonly ?string $rejectionReason,
        public readonly ?string $headerType = null,
        public readonly int $authorId = 0,
        public readonly ?string $postDate = null,
        public readonly array $components = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['template_id'] ?? $data['id'] ?? null,
            templateName: $data['template_name'] ?? $data['name'] ?? '',
            templateBody: $data['template_body'] ?? $data['body'] ?? '',
            placeholders: $data['placeholders'] ?? null,
            language: $data['language'] ?? 'en_US',
            category: $data['category'] ?? 'MARKETING',
            status: $data['status'] ?? 'Pending',
            metaTemplateId: $data['meta_template_id'] ?? $data['whatsapp_template_id'] ?? null,
            rejectionReason: $data['rejection_reason'] ?? null,
            headerType: $data['header_type'] ?? null,
            authorId: (int) ($data['author_id'] ?? 0),
            postDate: $data['post_date'] ?? null,
            components: $data['components'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'template_id'      => $this->id,
            'template_name'    => $this->templateName,
            'template_body'    => $this->templateBody,
            'placeholders'     => $this->placeholders,
            'language'         => $this->language,
            'category'         => $this->category,
            'status'           => $this->status,
            'meta_template_id' => $this->metaTemplateId,
            'rejection_reason' => $this->rejectionReason,
            'header_type'      => $this->headerType,
            'author_id'        => $this->authorId,
            'post_date'        => $this->postDate,
            'components'       => $this->components,
        ];
    }
}
