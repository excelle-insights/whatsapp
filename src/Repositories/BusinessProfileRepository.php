<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class BusinessProfileRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_business_profiles';
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (
                name, waba_id, business_id, phone_number_id, phone_number,
                currency, timezone_id, status, created_at, updated_at
            ) VALUES (
                :name, :waba_id, :business_id, :phone_number_id, :phone_number,
                :currency, :timezone_id, :status, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':name'             => $data['name'],
            ':waba_id'          => $data['waba_id'],
            ':business_id'      => $data['business_id'] ?? null,
            ':phone_number_id'  => $data['phone_number_id'] ?? null,
            ':phone_number'     => $data['phone_number'] ?? null,
            ':currency'         => $data['currency'] ?? null,
            ':timezone_id'      => $data['timezone_id'] ?? null,
            ':status'           => $data['status'] ?? 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'waba_id', 'business_id', 'phone_number_id', 'phone_number', 'currency', 'timezone_id', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = "updated_at = NOW()";

        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET " . implode(', ', $fields) . "
            WHERE id = :id
        ");
        $stmt->execute($params);
    }

    public function find(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByWabaId(string $wabaId): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE waba_id = ?");
        $stmt->execute([$wabaId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findByPhoneNumberId(string $phoneNumberId): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE phone_number_id = ?");
        $stmt->execute([$phoneNumberId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getFirst(): ?object
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id ASC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }
}
