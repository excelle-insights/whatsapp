<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWhatsappAccessTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(($_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp') . '_access_tokens');
        if ($table->exists()) {
            return;
        }

        $table
            ->addColumn('user_id', 'string', ['limit' => 100])
            ->addColumn('profile_id', 'integer', ['null' => true])
            ->addColumn('access_token', 'text')
            ->addColumn('expires_at', 'datetime', ['null' => true])
            ->addTimestamps()
            ->addIndex(['user_id'], ['unique' => true])
            ->create();
    }
}
