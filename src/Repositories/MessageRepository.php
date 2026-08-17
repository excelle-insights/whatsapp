<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class MessageRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_messages';
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                profile_id, direction, wam_id, from_number, to_number,
                type, body, media_id, status, template_id, metadata,
                media_type, media_url, media_mime_type, media_file_size,
                caption, wa_media_id, created_at, updated_at
            ) VALUES (
                :profile_id, :direction, :wam_id, :from_number, :to_number,
                :type, :body, :media_id, :status, :template_id, :metadata,
                :media_type, :media_url, :media_mime_type, :media_file_size,
                :caption, :wa_media_id, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':profile_id'        => $data['profile_id'] ?? null,
            ':direction'         => $data['direction'],
            ':wam_id'            => $data['wam_id'] ?? null,
            ':from_number'       => $data['from_number'],
            ':to_number'         => $data['to_number'],
            ':type'              => $data['type'],
            ':body'              => $data['body'] ?? null,
            ':media_id'          => $data['media_id'] ?? null,
            ':status'            => $data['status'] ?? 'received',
            ':template_id'       => $data['template_id'] ?? null,
            ':metadata'          => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
            ':media_type'        => $data['media_type'] ?? null,
            ':media_url'         => $data['media_url'] ?? null,
            ':media_mime_type'   => $data['media_mime_type'] ?? null,
            ':media_file_size'   => $data['media_file_size'] ?? null,
            ':caption'           => $data['caption'] ?? null,
            ':wa_media_id'       => $data['wa_media_id'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $id]);
    }

    public function updateWamId(int $id, string $wamId, string $status = 'sent'): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET wam_id = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$wamId, $status, $id]);
    }

    public function find(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByWamId(string $wamId): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE wam_id = ?");
        $stmt->execute([$wamId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function getAll(int $profileId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE profile_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$profileId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getByFromNumber(string $fromNumber, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE from_number = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$fromNumber, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
