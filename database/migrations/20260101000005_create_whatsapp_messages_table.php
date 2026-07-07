<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappMessagesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_messages');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('profile_id', 'integer', ['null' => true])
            ->addColumn('direction', 'string', ['limit' => 10])
            ->addColumn('wam_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('from_number', 'string', ['limit' => 20])
            ->addColumn('to_number', 'string', ['limit' => 20])
            ->addColumn('type', 'string', ['limit' => 30])
            ->addColumn('body', 'text', ['null' => true])
            ->addColumn('media_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'received'])
            ->addColumn('template_id', 'integer', ['null' => true])
            ->addColumn('metadata', 'json', ['null' => true])
            ->addTimestamps()
            ->addIndex(['wam_id'])
            ->addIndex(['from_number'])
            ->addIndex(['profile_id'])
            ->create();
    }
}
