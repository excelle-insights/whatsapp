<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHeaderTypeToTemplates extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_templates');
        if (!$table->exists()) {
            return;
        }

        $columns = $table->getColumns();
        $hasHeaderType = false;
        foreach ($columns as $col) {
            if ($col->getName() === 'header_type') {
                $hasHeaderType = true;
                break;
            }
        }

        if (!$hasHeaderType) {
            $table->addColumn('header_type', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'none|text|image|video|document',
                'after' => 'status',
            ])->update();
        }
    }

    public function down(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_templates');
        if ($table->exists()) {
            $table->removeColumn('header_type')->update();
        }
    }
}
