<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class ConversationRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_conversations';
    }

    public function findById(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE conversation_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByPhone(string $phone): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE contact_phone = ?
            ORDER BY last_message_at DESC, conversation_id DESC
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function listRecent(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            ORDER BY last_message_at DESC, conversation_id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                contact_phone, contact_name, lead_id, last_message_at,
                session_expires_at, status, unread_count, created_at
            ) VALUES (
                :contact_phone, :contact_name, :lead_id, :last_message_at,
                :session_expires_at, :status, :unread_count, :created_at
            )
        ");

        $stmt->execute([
            ':contact_phone'      => $data['contact_phone'],
            ':contact_name'       => $data['contact_name'] ?? null,
            ':lead_id'            => $data['lead_id'] ?? null,
            ':last_message_at'    => $data['last_message_at'] ?? null,
            ':session_expires_at' => $data['session_expires_at'] ?? null,
            ':status'             => $data['status'] ?? 'active',
            ':unread_count'       => $data['unread_count'] ?? 0,
            ':created_at'         => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function touchLastMessage(int $id, string $ts): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} SET last_message_at = ? WHERE conversation_id = ?
        ");
        $stmt->execute([$ts, $id]);
    }

    public function recordInbound(int $id, string $ts, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET last_message_at = ?, session_expires_at = ?,
                unread_count = unread_count + 1, status = 'active'
            WHERE conversation_id = ?
        ");
        $stmt->execute([$ts, $expiresAt, $id]);
    }

    public function resetUnread(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} SET unread_count = 0 WHERE conversation_id = ?
        ");
        $stmt->execute([$id]);
    }

    public function setSession(int $id, string $expiresAt, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET session_expires_at = ?, status = ?
            WHERE conversation_id = ?
        ");
        $stmt->execute([$expiresAt, $status, $id]);
    }

    public function isSessionActive(int $id): bool
    {
        $expiry = $this->getSessionExpiry($id);
        if ($expiry === null || $expiry === '') {
            return false;
        }

        $timestamp = strtotime($expiry);
        return $timestamp !== false && $timestamp > time();
    }

    public function getSessionExpiry(int $id): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT session_expires_at FROM {$this->table} WHERE conversation_id = ?
        ");
        $stmt->execute([$id]);
        $value = $stmt->fetchColumn();
        return ($value === false || $value === null) ? null : (string) $value;
    }

    public function extendSession(int $id, int $hours): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET session_expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR)
            WHERE conversation_id = ?
        ");
        $stmt->execute([$hours, $id]);
    }
}
