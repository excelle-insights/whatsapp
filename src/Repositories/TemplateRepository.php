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
                profile_id, name, language, category, header_format,
                status, created_at, updated_at
            ) VALUES (
                :profile_id, :name, :language, :category, :header_format,
                :status, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':profile_id'    => $data['profile_id'],
            ':name'          => $data['name'],
            ':language'      => $data['language'] ?? 'en_US',
            ':category'      => $data['category'] ?? 'MARKETING',
            ':header_format' => $data['header_format'] ?? null,
            ':status'        => $data['status'] ?? 'draft',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markSynced(int $id, string $whatsappTemplateId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET whatsapp_template_id = ?, status = 'pending', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$whatsappTemplateId, $id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET status = 'failed', rejection_reason = :error, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':error' => $error, ':id' => $id]);
    }

    public function updateStatus(int $id, string $status, ?string $qualityScore = null, ?string $rejectionReason = null): void
    {
        $fields = ["status = :status", "updated_at = NOW()"];
        $params = [':status' => $status, ':id' => $id];

        if ($qualityScore !== null) {
            $fields[] = "quality_score = :quality_score";
            $params[':quality_score'] = $qualityScore;
        }

        if ($rejectionReason !== null) {
            $fields[] = "rejection_reason = :rejection_reason";
            $params[':rejection_reason'] = $rejectionReason;
        }

        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET " . implode(', ', $fields) . "
            WHERE id = :id
        ");
        $stmt->execute($params);
    }

    public function find(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByName(string $name, int $profileId): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE name = ? AND profile_id = ?
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmt->execute([$name, $profileId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByWhatsappId(string $whatsappTemplateId): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table} WHERE whatsapp_template_id = ?
        ");
        $stmt->execute([$whatsappTemplateId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function getAll(int $profileId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE profile_id = ?
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$profileId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getByStatus(int $profileId, string $status): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE profile_id = ? AND status = ?
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$profileId, $status]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
    }
}
