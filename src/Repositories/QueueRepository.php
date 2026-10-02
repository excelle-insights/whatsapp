<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class QueueRepository
{
    private string $table;
    private string $templatesTable;

    public function __construct(private PDO $pdo)
    {
        $prefix = $_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp';
        $this->table = $prefix . '_queue';
        $this->templatesTable = $prefix . '_templates';
    }

    public function enqueue(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                lead_id, contact_phone, contact_name, template_id, placeholders_data,
                message_body, scheduled_at, status, sent_at, error_log, author_id, created_at
            ) VALUES (
                :lead_id, :contact_phone, :contact_name, :template_id, :placeholders_data,
                :message_body, :scheduled_at, :status, :sent_at, :error_log, :author_id, :created_at
            )
        ");

        $stmt->execute([
            ':lead_id'           => $data['lead_id'] ?? 0,
            ':contact_phone'     => $data['contact_phone'],
            ':contact_name'      => $data['contact_name'] ?? null,
            ':template_id'       => $data['template_id'] ?? null,
            ':placeholders_data' => isset($data['placeholders_data']) && is_array($data['placeholders_data'])
                ? json_encode($data['placeholders_data'])
                : ($data['placeholders_data'] ?? null),
            ':message_body'      => $data['message_body'] ?? null,
            ':scheduled_at'      => $data['scheduled_at'] ?? null,
            ':status'            => $data['status'] ?? 'pending',
            ':sent_at'           => $data['sent_at'] ?? null,
            ':error_log'         => $data['error_log'] ?? null,
            ':author_id'         => $data['author_id'] ?? 0,
            ':created_at'        => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findPending(int $limit = 50, ?string $dueBefore = null): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status = 'pending'
        ";
        $params = [];

        if ($dueBefore !== null) {
            $sql .= " AND (scheduled_at IS NULL OR scheduled_at = '' OR scheduled_at <= :due_before)";
            $params[':due_before'] = $dueBefore;
        }

        $sql .= " ORDER BY scheduled_at ASC, id ASC LIMIT {$limit}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findPendingById(int $id): ?object
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table} WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function listWithTemplate(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT q.*, t.template_name, t.template_body, t.language, t.status AS template_status
            FROM {$this->table} q
            LEFT JOIN {$this->templatesTable} t ON t.id = q.template_id
            ORDER BY q.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function countPending(): int
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'
        ");
        return (int) $stmt->fetchColumn();
    }

    public function markSent(int $id, string $ts): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET status = 'sent', sent_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$ts, $id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET status = 'failed', error_log = ?
            WHERE id = ?
        ");
        $stmt->execute([$error, $id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    public function recentFailed(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE status = 'failed'
            ORDER BY id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function stats(): array
    {
        return [
            'total'   => (int) $this->pdo->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn(),
            'pending' => $this->countByStatus('pending'),
            'sent'    => $this->countByStatus('sent'),
            'failed'  => $this->countByStatus('failed'),
        ];
    }
}
