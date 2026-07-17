<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLanguageDefaultToTemplates extends AbstractMigration
{
    public function change(): void
    {
        $tableName = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_templates';

        if (!$this->hasTable($tableName)) {
            return;
        }

        $table = $this->table($tableName);

        if ($table->hasColumn('language')) {
            return;
        }

        $table
            ->addColumn('language', 'string', ['limit' => 10, 'default' => 'en_US'])
            ->save();
    }
}
