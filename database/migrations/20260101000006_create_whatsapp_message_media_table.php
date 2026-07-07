<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappMessageMediaTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_message_media');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('message_id', 'integer')
            ->addColumn('media_id', 'string', ['limit' => 100])
            ->addColumn('mime_type', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('url', 'text', ['null' => true])
            ->addColumn('filename', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('caption', 'text', ['null' => true])
            ->addColumn('file_size', 'integer', ['null' => true])
            ->addColumn('sha256', 'string', ['limit' => 64, 'null' => true])
            ->addTimestamps()
            ->addIndex(['message_id'])
            ->addIndex(['media_id'])
            ->create();
    }
}
