<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class TemplateComponentRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_template_components';
    }

    public function create(int $templateId, array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                template_id, type, format, text, buttons, example_data, created_at
            ) VALUES (
                :template_id, :type, :format, :text, :buttons, :example_data, NOW()
            )
        ");

        $stmt->execute([
            ':template_id'  => $templateId,
            ':type'         => $data['type'],
            ':format'       => $data['format'] ?? null,
            ':text'         => $data['text'] ?? null,
            ':buttons'      => !empty($data['buttons']) ? json_encode($data['buttons']) : null,
            ':example_data' => !empty($data['example_data']) ? json_encode($data['example_data']) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getByTemplateId(int $templateId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE template_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$templateId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function deleteByTemplateId(int $templateId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE template_id = ?");
        $stmt->execute([$templateId]);
    }
}
