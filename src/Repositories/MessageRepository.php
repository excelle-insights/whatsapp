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
                conversation_id, direction, message_body, message_type,
                media_type, media_url, media_mime_type, media_file_size,
                caption, wa_media_id, template_id, wa_message_id,
                delivery_status, sent_at, author_id, created_at, updated_at
            ) VALUES (
                :conversation_id, :direction, :message_body, :message_type,
                :media_type, :media_url, :media_mime_type, :media_file_size,
                :caption, :wa_media_id, :template_id, :wa_message_id,
                :delivery_status, :sent_at, :author_id, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':conversation_id' => $data['conversation_id'] ?? 0,
            ':direction'       => $data['direction'],
            ':message_body'    => $data['message_body'] ?? null,
            ':message_type'    => $data['message_type'] ?? 'text',
            ':media_type'      => $data['media_type'] ?? null,
            ':media_url'       => $data['media_url'] ?? null,
            ':media_mime_type' => $data['media_mime_type'] ?? null,
            ':media_file_size' => $data['media_file_size'] ?? null,
            ':caption'         => $data['caption'] ?? null,
            ':wa_media_id'     => $data['wa_media_id'] ?? null,
            ':template_id'     => $data['template_id'] ?? null,
            ':wa_message_id'   => $data['wa_message_id'] ?? null,
            ':delivery_status' => $data['delivery_status'] ?? null,
            ':sent_at'         => $data['sent_at'] ?? null,
            ':author_id'       => $data['author_id'] ?? 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $messageId, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET delivery_status = ?, updated_at = NOW()
            WHERE message_id = ?
        ");
        $stmt->execute([$status, $messageId]);
    }

    public function updateWaMessageId(int $messageId, string $waMessageId, string $status = 'sent'): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET wa_message_id = ?, delivery_status = ?, updated_at = NOW()
            WHERE message_id = ?
        ");
        $stmt->execute([$waMessageId, $status, $messageId]);
    }

    public function find(int $messageId): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE message_id = ?");
        $stmt->execute([$messageId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByWaMessageId(string $waMessageId): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE wa_message_id = ?");
        $stmt->execute([$waMessageId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function getByConversation(int $conversationId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE conversation_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$conversationId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getOutboundByConversation(int $conversationId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE conversation_id = ? AND direction = 'outbound'
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$conversationId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
