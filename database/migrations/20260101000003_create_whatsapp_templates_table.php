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
            ->addColumn('template_name', 'string', ['limit' => 255])
            ->addColumn('template_body', 'text')
            ->addColumn('placeholders', 'text', ['null' => true])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => true, 'default' => 'en_US'])
            ->addColumn('category', 'string', ['limit' => 50, 'default' => 'MARKETING'])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Pending'])
            ->addColumn('meta_template_id', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('rejection_reason', 'text', ['null' => true])
            ->addColumn('header_type', 'string', ['limit' => 20, 'null' => true, 'comment' => 'none|text|image|video|document'])
            ->addColumn('author_id', 'integer', ['default' => 0])
            ->addColumn('post_date', 'string', ['limit' => 255, 'null' => true])
            ->addTimestamps()
            ->addIndex(['category'])
            ->addIndex(['status'])
            ->addIndex(['meta_template_id'])
            ->create();
    }
}
