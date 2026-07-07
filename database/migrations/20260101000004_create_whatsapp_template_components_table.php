<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappTemplateComponentsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_template_components');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('template_id', 'integer')
            ->addColumn('type', 'string', ['limit' => 20])
            ->addColumn('format', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('text', 'text', ['null' => true])
            ->addColumn('buttons', 'json', ['null' => true])
            ->addColumn('example_data', 'json', ['null' => true])
            ->addTimestamps()
            ->addIndex(['template_id'])
            ->create();
    }
}
