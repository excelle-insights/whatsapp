<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class ContactRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_contacts';
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                phone, lead_id, client_id, contact_name, is_opted_in, created_at
            ) VALUES (
                :phone, :lead_id, :client_id, :contact_name, :is_opted_in, :created_at
            )
        ");

        $stmt->execute([
            ':phone'        => $data['phone'],
            ':lead_id'      => $data['lead_id'] ?? null,
            ':client_id'    => $data['client_id'] ?? null,
            ':contact_name' => $data['contact_name'] ?? null,
            ':is_opted_in'  => $data['is_opted_in'] ?? 1,
            ':created_at'   => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByPhone(string $phone): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE phone = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByLeadId(int $leadId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table} WHERE lead_id = ? ORDER BY id ASC
        ");
        $stmt->execute([$leadId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function updateOptIn(int $id, int $isOptedIn): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} SET is_opted_in = ? WHERE id = ?
        ");
        $stmt->execute([$isOptedIn, $id]);
    }
}
