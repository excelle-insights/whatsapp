<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class CredentialsRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_credentials';
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY credential_key ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function get(string $key): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE credential_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function set(string $key, string $value, string $updatedAt): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET credential_value = ?, updated_at = ?
            WHERE credential_key = ?
        ");
        $stmt->execute([$value, $updatedAt, $key]);
    }

    public function clear(array $keys): void
    {
        if (empty($keys)) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET credential_value = NULL
            WHERE credential_key IN ({$placeholders})
        ");
        $stmt->execute(array_values($keys));
    }
}
