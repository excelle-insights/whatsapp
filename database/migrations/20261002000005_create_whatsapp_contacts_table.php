<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappContactsTable extends AbstractMigration
{
    public function up(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_contacts';
        $table = $this->table($name);
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('phone', 'string', ['limit' => 50])
            ->addColumn('lead_id', 'integer', ['null' => true])
            ->addColumn('client_id', 'integer', ['null' => true])
            ->addColumn('contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('is_opted_in', 'integer', ['default' => 1])
            ->addColumn('created_at', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['phone'])
            ->addIndex(['lead_id'])
            ->create();
    }

    public function down(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_contacts';
        if ($this->hasTable($name)) {
            $this->table($name)->drop()->save();
        }
    }
}
