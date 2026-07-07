<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappTemplatesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_templates');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('profile_id', 'integer')
            ->addColumn('name', 'string', ['limit' => 512])
            ->addColumn('language', 'string', ['limit' => 10])
            ->addColumn('category', 'string', ['limit' => 50, 'default' => 'MARKETING'])
            ->addColumn('header_format', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft'])
            ->addColumn('quality_score', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('rejection_reason', 'text', ['null' => true])
            ->addColumn('whatsapp_template_id', 'string', ['limit' => 100, 'null' => true])
            ->addTimestamps()
            ->addIndex(['profile_id', 'name'])
            ->addIndex(['whatsapp_template_id'])
            ->create();
    }
}
