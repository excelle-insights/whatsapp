<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class TemplateRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_templates';
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                template_name, template_body, placeholders, language, category,
                status, header_type, meta_template_id, author_id, post_date, created_at, updated_at
            ) VALUES (
                :template_name, :template_body, :placeholders, :language, :category,
                :status, :header_type, :meta_template_id, :author_id, :post_date, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':template_name'    => $data['template_name'],
            ':template_body'    => $data['template_body'],
            ':placeholders'     => $data['placeholders'] ?? null,
            ':language'         => $data['language'] ?? 'en_US',
            ':category'         => $data['category'] ?? 'MARKETING',
            ':status'           => $data['status'] ?? 'Pending',
            ':header_type'      => $data['header_type'] ?? null,
            ':meta_template_id' => $data['meta_template_id'] ?? null,
            ':author_id'        => $data['author_id'] ?? 0,
            ':post_date'        => $data['post_date'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markSynced(int $id, string $metaTemplateId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET meta_template_id = ?, status = 'APPROVED', updated_at = NOW()
            WHERE template_id = ?
        ");
        $stmt->execute([$metaTemplateId, $id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET status = 'FAILED', rejection_reason = :error, updated_at = NOW()
            WHERE template_id = :id
        ");
        $stmt->execute([':error' => $error, ':id' => $id]);
    }

    public function updateStatus(int $id, string $status, ?string $rejectionReason = null): void
    {
        $fields = ["status = :status", "updated_at = NOW()"];
        $params = [':status' => $status, ':id' => $id];

        if ($rejectionReason !== null) {
            $fields[] = "rejection_reason = :rejection_reason";
            $params[':rejection_reason'] = $rejectionReason;
        }

        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET " . implode(', ', $fields) . "
            WHERE template_id = :id
        ");
        $stmt->execute($params);
    }

    public function find(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE template_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByName(string $name, int $authorId = 0): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE template_name = ? AND author_id = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$name, $authorId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByMetaId(string $metaTemplateId): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table} WHERE meta_template_id = ?
        ");
        $stmt->execute([$metaTemplateId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function getAll(int $authorId = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE author_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$authorId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getByStatus(string $status, int $authorId = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE author_id = ? AND status = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$authorId, $status]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE template_id = ?");
        $stmt->execute([$id]);
    }
}
