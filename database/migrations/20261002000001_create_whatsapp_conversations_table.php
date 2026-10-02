<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappConversationsTable extends AbstractMigration
{
    public function up(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_conversations';
        $table = $this->table($name);
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('contact_phone', 'string', ['limit' => 50])
            ->addColumn('contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('lead_id', 'integer', ['null' => true])
            ->addColumn('last_message_at', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('session_expires_at', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'active'])
            ->addColumn('unread_count', 'integer', ['default' => 0])
            ->addColumn('created_at', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['contact_phone'])
            ->addIndex(['lead_id'])
            ->addIndex(['status'])
            ->create();
    }

    public function down(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_conversations';
        if ($this->hasTable($name)) {
            $this->table($name)->drop()->save();
        }
    }
}
