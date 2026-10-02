<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappSettingsTable extends AbstractMigration
{
    public function up(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_settings';
        $table = $this->table($name);
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('setting_key', 'string', ['limit' => 100])
            ->addColumn('setting_label', 'string', ['limit' => 255])
            ->addColumn('setting_value', 'integer', ['default' => 0])
            ->addColumn('updated_by', 'integer', ['null' => true, 'default' => 0])
            ->addColumn('updated_at', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['setting_key'], ['unique' => true])
            ->create();

        $this->table($name)->insert([
            ['setting_key' => 'payment_receipt', 'setting_label' => 'Payment Receipt', 'setting_value' => 0, 'updated_by' => 0, 'updated_at' => null],
            ['setting_key' => 'installment_reminder', 'setting_label' => 'Installment Reminder', 'setting_value' => 0, 'updated_by' => 0, 'updated_at' => null],
            ['setting_key' => 'overdue_reminder', 'setting_label' => 'Overdue Reminder', 'setting_value' => 0, 'updated_by' => 0, 'updated_at' => null],
            ['setting_key' => 'general_reminder', 'setting_label' => 'General Reminder', 'setting_value' => 0, 'updated_by' => 0, 'updated_at' => null],
            ['setting_key' => 'auto_reply_first_enquiry', 'setting_label' => 'Auto Reply First Enquiry', 'setting_value' => 0, 'updated_by' => 0, 'updated_at' => null],
            ['setting_key' => 'auto_reply_first_enquiry_template', 'setting_label' => 'Auto Reply First Enquiry Template', 'setting_value' => 0, 'updated_by' => 0, 'updated_at' => null],
        ])->save();
    }

    public function down(): void
    {
        $name = ($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_settings';
        if ($this->hasTable($name)) {
            $this->table($name)->drop()->save();
        }
    }
}
