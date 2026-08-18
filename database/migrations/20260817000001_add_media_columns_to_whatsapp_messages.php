<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMediaColumnsToWhatsappMessages extends AbstractMigration
{
    public function change(): void
    {
        $tableName = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_messages';

        if (!$this->hasTable($tableName)) {
            return;
        }

        $table = $this->table($tableName);

        if (!$table->hasColumn('media_type')) {
            $table->addColumn('media_type', 'string', ['limit' => 20, 'null' => true]);
        }

        if (!$table->hasColumn('media_url')) {
            $table->addColumn('media_url', 'string', ['limit' => 500, 'null' => true]);
        }

        if (!$table->hasColumn('media_mime_type')) {
            $table->addColumn('media_mime_type', 'string', ['limit' => 100, 'null' => true]);
        }

        if (!$table->hasColumn('media_file_size')) {
            $table->addColumn('media_file_size', 'integer', ['null' => true]);
        }

        if (!$table->hasColumn('caption')) {
            $table->addColumn('caption', 'text', ['null' => true]);
        }

        if (!$table->hasColumn('wa_media_id')) {
            $table->addColumn('wa_media_id', 'string', ['limit' => 100, 'null' => true]);
        }

        $table->save();
    }
}
