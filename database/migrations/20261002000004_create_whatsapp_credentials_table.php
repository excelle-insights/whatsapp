<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappCredentialsTable extends AbstractMigration
{
    public function up(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_credentials';
        $table = $this->table($name);
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('credential_key', 'string', ['limit' => 100])
            ->addColumn('credential_value', 'text', ['null' => true])
            ->addColumn('updated_at', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['credential_key'], ['unique' => true])
            ->create();

        $this->table($name)->insert([
            ['credential_key' => 'phone_number_id', 'credential_value' => '', 'updated_at' => null],
            ['credential_key' => 'business_account_id', 'credential_value' => '', 'updated_at' => null],
            ['credential_key' => 'access_token', 'credential_value' => '', 'updated_at' => null],
        ])->save();
    }

    public function down(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_credentials';
        if ($this->hasTable($name)) {
            $this->table($name)->drop()->save();
        }
    }
}
