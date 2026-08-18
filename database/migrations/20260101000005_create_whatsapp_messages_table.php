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
            ->addColumn('conversation_id', 'integer', ['default' => 0])
            ->addColumn('direction', 'string', ['limit' => 20])
            ->addColumn('message_body', 'text', ['null' => true])
            ->addColumn('message_type', 'string', ['limit' => 50, 'default' => 'text'])
            ->addColumn('media_type', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('media_url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('media_mime_type', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('media_file_size', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('caption', 'text', ['null' => true])
            ->addColumn('wa_media_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('template_id', 'integer', ['null' => true])
            ->addColumn('wa_message_id', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('delivery_status', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('sent_at', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('author_id', 'integer', ['default' => 0])
            ->addTimestamps()
            ->addIndex(['conversation_id'])
            ->addIndex(['direction'])
            ->addIndex(['wa_message_id'])
            ->create();
    }
}
