<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappBusinessProfilesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_business_profiles');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('waba_id', 'string', ['limit' => 100])
            ->addColumn('business_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone_number_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone_number', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('currency', 'string', ['limit' => 3, 'null' => true])
            ->addColumn('timezone_id', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active'])
            ->addTimestamps()
            ->addIndex(['waba_id'], ['unique' => true])
            ->create();
    }
}
