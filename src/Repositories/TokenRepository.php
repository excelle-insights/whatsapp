<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class TokenRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_access_tokens';
    }

    public function getLatest(string $userId): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE user_id = ?
            ORDER BY updated_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $record = $stmt->fetch(PDO::FETCH_OBJ);
        return $record ?: null;
    }

    public function save(string $userId, array $accessToken, string $expiresAt): void
    {
        $existing = $this->getLatest($userId);

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table}
                SET access_token = ?, expires_at = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([json_encode($accessToken), $expiresAt, $userId]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (user_id, access_token, expires_at, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$userId, json_encode($accessToken), $expiresAt]);
        }
    }

    public function delete(string $userId): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table} WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
    }
}
