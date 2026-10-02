<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappQueueTable extends AbstractMigration
{
    public function up(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_queue';
        $table = $this->table($name);
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('lead_id', 'integer', ['default' => 0])
            ->addColumn('contact_phone', 'string', ['limit' => 50])
            ->addColumn('contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('template_id', 'integer', ['null' => true])
            ->addColumn('placeholders_data', 'text', ['null' => true])
            ->addColumn('message_body', 'text', ['null' => true])
            ->addColumn('scheduled_at', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'pending'])
            ->addColumn('sent_at', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('error_log', 'text', ['null' => true])
            ->addColumn('author_id', 'integer', ['default' => 0])
            ->addColumn('created_at', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['lead_id'])
            ->addIndex(['scheduled_at'])
            ->addIndex(['status'])
            ->create();
    }

    public function down(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_queue';
        if ($this->hasTable($name)) {
            $this->table($name)->drop()->save();
        }
    }
}
