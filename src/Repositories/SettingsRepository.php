<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Repositories;

use PDO;

class SettingsRepository
{
    private string $table;

    public function __construct(private PDO $pdo)
    {
        $this->table = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_settings';
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY setting_key ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function get(string $key): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function isEnabled(string $key): bool
    {
        $setting = $this->get($key);
        return $setting !== null && (int) $setting->setting_value !== 0;
    }

    public function set(string $key, int $value, int $updatedBy, string $updatedAt): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET setting_value = ?, updated_by = ?, updated_at = ?
            WHERE setting_key = ?
        ");
        $stmt->execute([$value, $updatedBy, $updatedAt, $key]);
    }
}
