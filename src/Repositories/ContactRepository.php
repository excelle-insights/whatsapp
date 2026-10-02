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

    public function listAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            ORDER BY contact_name ASC, id ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function search(string $term, int $limit = 50): array
    {
        $like = '%' . $term . '%';
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE contact_name LIKE ? OR phone LIKE ?
            ORDER BY contact_name ASC, id ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $like);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function count(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    public function countOptedIn(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$this->table} WHERE is_opted_in = 1")->fetchColumn();
    }

    public function countOptedOut(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$this->table} WHERE is_opted_in = 0")->fetchColumn();
    }

    public function stats(): array
    {
        return [
            'total'     => $this->count(),
            'opted_in'  => $this->countOptedIn(),
            'opted_out' => $this->countOptedOut(),
        ];
    }
}
