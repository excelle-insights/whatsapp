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
            WHERE contact_phone = ?
            ORDER BY last_message_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Resolve a thread by the WhatsApp user_id (e.g. KE.1385783877946016).
     * This is the only stable identifier Meta sends for username-only
     * (privacy-enabled) senders, whose phone number is withheld.
     */
    public function findByUserId(string $userId): ?object
    {
        if ($userId === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE contact_user_id = ?
            ORDER BY last_message_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Resolve a conversation by phone first, then by WhatsApp user_id.
     * Matching on user_id links a username-only thread to the same person's
     * phone thread once a phone number is shared.
     */
    public function findBySender(?string $phone, ?string $userId): ?object
    {
        if ($userId !== null && $userId !== '') {
            $byUser = $this->findByUserId($userId);
            if ($byUser) {
                return $byUser;
            }
        }

        if ($phone !== null && $phone !== '') {
            return $this->findByPhone($phone);
        }

        return null;
    }

    /**
     * Auto-link a phone number (and username) onto an existing thread, e.g.
     * when a username-only sender later shares their number. Clears the
     * username-only flag when a phone becomes available.
     */
    public function linkSender(int $id, ?string $phone = null, ?string $userId = null, ?string $username = null): void
    {
        $sets = [];
        $params = [];

        if ($phone !== null && $phone !== '') {
            $sets[] = 'contact_phone = :phone';
            $sets[] = 'is_username_only = 0';
            $params[':phone'] = $phone;
        }
        if ($userId !== null && $userId !== '') {
            $sets[] = 'contact_user_id = :uid';
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

    public function listRecent(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            ORDER BY last_message_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): int
    {
        $phone = $data['contact_phone'] ?? null;
        $userId = $data['contact_user_id'] ?? null;

        $values = [
            'contact_phone'      => $phone,
            'contact_user_id'    => $userId,
            'wa_username'        => $data['wa_username'] ?? null,
            'is_username_only'   => array_key_exists('is_username_only', $data)
                ? (int) $data['is_username_only']
                : (empty($phone) && !empty($userId) ? 1 : 0),
            'contact_name'       => $data['contact_name'] ?? null,
            'lead_id'            => $data['lead_id'] ?? null,
            'last_message_at'    => $data['last_message_at'] ?? null,
            'session_expires_at' => $data['session_expires_at'] ?? null,
            'status'             => $data['status'] ?? 'active',
            'unread_count'       => $data['unread_count'] ?? 0,
            'created_at'         => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        $columns = array_keys($values);
        $placeholders = array_map(static fn($c) => ':' . $c, $columns);

        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")
        ");

        $params = [];
        foreach ($values as $column => $value) {
            $params[':' . $column] = $value;
        }

        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function touchLastMessage(int $id, string $ts): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} SET last_message_at = ? WHERE id = ?
        ");
        $stmt->execute([$ts, $id]);
    }

    public function recordInbound(int $id, string $ts, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET last_message_at = ?, session_expires_at = ?,
                unread_count = unread_count + 1, status = 'active'
            WHERE id = ?
        ");
        $stmt->execute([$ts, $expiresAt, $id]);
    }

    public function resetUnread(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} SET unread_count = 0 WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function setSession(int $id, string $expiresAt, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET session_expires_at = ?, status = ?
            WHERE id = ?
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
            SELECT session_expires_at FROM {$this->table} WHERE id = ?
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
            WHERE id = ?
        ");
        $stmt->execute([$hours, $id]);
    }

    public function getByLeadId(int $leadId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE lead_id = ?
            ORDER BY last_message_at DESC, id DESC
        ");
        $stmt->execute([$leadId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function listPaginated(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            ORDER BY last_message_at DESC, id DESC
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
            WHERE contact_name LIKE ? OR contact_phone LIKE ?
               OR contact_user_id LIKE ? OR wa_username LIKE ?
            ORDER BY last_message_at DESC, id DESC
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

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    public function totalUnread(): int
    {
        return (int) $this->pdo->query("SELECT COALESCE(SUM(unread_count), 0) FROM {$this->table}")->fetchColumn();
    }

    public function stats(): array
    {
        return [
            'total'  => $this->count(),
            'active' => $this->countByStatus('active'),
            'unread' => $this->totalUnread(),
        ];
    }
}
