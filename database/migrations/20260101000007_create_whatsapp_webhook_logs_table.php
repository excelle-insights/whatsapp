<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateWhatsappWebhookLogsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_webhook_logs');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('event_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('object_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('entry_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('raw_payload', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true])
            ->addColumn('processed', 'boolean', ['default' => false])
            ->addColumn('processed_at', 'datetime', ['null' => true])
            ->addTimestamps()
            ->addIndex(['event_type'])
            ->create();
    }
}
