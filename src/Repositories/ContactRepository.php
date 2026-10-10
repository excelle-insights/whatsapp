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
        $values = [
            'phone'         => $data['phone'] ?? null,
            'user_id'       => $data['user_id'] ?? null,
            'wa_username'   => $data['wa_username'] ?? null,
            'lead_id'       => $data['lead_id'] ?? null,
            'client_id'     => $data['client_id'] ?? null,
            'contact_name'  => $data['contact_name'] ?? null,
            'is_opted_in'   => $data['is_opted_in'] ?? 1,
            'created_at'    => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        $columns = array_keys($values);
        $placeholders = array_map(static fn($c) => ':' . $c, $columns);
        $params = [];
        foreach ($values as $column => $value) {
            $params[':' . $column] = $value;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")
        ");
        $stmt->execute($params);

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
        if ($phone === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE phone = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Resolve a contact by the WhatsApp user_id (stable across phone /
     * username-only senders).
     */
    public function findByUserId(string $userId): ?object
    {
        if ($userId === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Resolve a contact by phone, then by WhatsApp user_id.
     */
    public function findBySender(?string $phone, ?string $userId): ?object
    {
        if ($phone !== null && $phone !== '') {
            $byPhone = $this->findByPhone($phone);
            if ($byPhone) {
                return $byPhone;
            }
        }

        if ($userId !== null && $userId !== '') {
            return $this->findByUserId($userId);
        }

        return null;
    }

    /**
     * Backfill phone / user_id / username onto an existing contact row.
     */
    public function linkSender(int $id, ?string $phone = null, ?string $userId = null, ?string $username = null): void
    {
        $sets = [];
        $params = [];

        if ($phone !== null && $phone !== '') {
            $sets[] = 'phone = :phone';
            $params[':phone'] = $phone;
        }
        if ($userId !== null && $userId !== '') {
            $sets[] = 'user_id = :uid';
            $params[':uid'] = $userId;
        }
        if ($username !== null && $username !== '') {
            $sets[] = 'wa_username = :uname';
            $params[':uname'] = $username;
        }

        if (!$sets) {
            return;
        }

        $params[':id'] = $id;
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id");
        $stmt->execute($params);
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
               OR user_id LIKE ? OR wa_username LIKE ?
            ORDER BY contact_name ASC, id ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $like);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
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
