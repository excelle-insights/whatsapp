<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * WhatsApp username-only senders (privacy-enabled accounts).
 *
 * Meta webhooks for contacts who have not shared a phone number omit
 * `messages[].from` and `contacts[].wa_id`, but still include a stable
 * `user_id` (e.g. KE.1385783877946016) and `profile.username`. Threads used
 * to be keyed on `contact_phone` only, so those messages were dropped.
 *
 * These columns let a conversation be keyed on the WhatsApp user_id, storing
 * the username for display and flagging the thread as inbound-only until a
 * phone number is shared (at which point it is auto-linked by user_id).
 */
final class AddWhatsappUsernameSupport extends AbstractMigration
{
    public function up(): void
    {
        $prefix = $_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp';

        $conversations = $prefix . '_conversations';
        if ($this->hasTable($conversations)) {
            $table = $this->table($conversations);
            if (!$table->hasColumn('contact_user_id')) {
                $table->addColumn('contact_user_id', 'string', ['limit' => 50, 'null' => true, 'after' => 'contact_phone']);
            }
            if (!$table->hasColumn('wa_username')) {
                $table->addColumn('wa_username', 'string', ['limit' => 255, 'null' => true, 'after' => 'contact_user_id']);
            }
            if (!$table->hasColumn('is_username_only')) {
                $table->addColumn('is_username_only', 'integer', ['limit' => 1, 'default' => 0, 'after' => 'wa_username']);
            }
            // Username-only threads have no phone number.
            $table->changeColumn('contact_phone', 'string', ['limit' => 50, 'null' => true]);
            if (!$table->hasIndex(['contact_user_id'])) {
                $table->addIndex(['contact_user_id'], ['name' => 'idx_wa_conversation_user_id']);
            }
            $table->update();
        }

        $contacts = $prefix . '_contacts';
        if ($this->hasTable($contacts)) {
            $table = $this->table($contacts);
            if (!$table->hasColumn('user_id')) {
                $table->addColumn('user_id', 'string', ['limit' => 50, 'null' => true, 'after' => 'phone']);
            }
            if (!$table->hasColumn('wa_username')) {
                $table->addColumn('wa_username', 'string', ['limit' => 255, 'null' => true, 'after' => 'user_id']);
            }
            $table->changeColumn('phone', 'string', ['limit' => 50, 'null' => true]);
            if (!$table->hasIndex(['user_id'])) {
                $table->addIndex(['user_id'], ['name' => 'idx_wa_contact_user_id']);
            }
            $table->update();
        }

        $queue = $prefix . '_queue';
        if ($this->hasTable($queue)) {
            $table = $this->table($queue);
            if (!$table->hasColumn('contact_user_id')) {
                $table->addColumn('contact_user_id', 'string', ['limit' => 50, 'null' => true, 'after' => 'contact_phone']);
            }
            // Username-only queue rows keep the user_id and no phone.
            $table->changeColumn('contact_phone', 'string', ['limit' => 50, 'null' => true]);
            $table->update();
        }
    }

    public function down(): void
    {
        $prefix = $_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp';

        $conversations = $prefix . '_conversations';
        if ($this->hasTable($conversations)) {
            $table = $this->table($conversations);
            if ($table->hasIndex(['contact_user_id'])) {
                $table->removeIndex(['contact_user_id']);
            }
            foreach (['is_username_only', 'wa_username', 'contact_user_id'] as $column) {
                if ($table->hasColumn($column)) {
                    $table->removeColumn($column);
                }
            }
            $table->update();
        }

        $contacts = $prefix . '_contacts';
        if ($this->hasTable($contacts)) {
            $table = $this->table($contacts);
            if ($table->hasIndex(['user_id'])) {
                $table->removeIndex(['user_id']);
            }
            foreach (['wa_username', 'user_id'] as $column) {
                if ($table->hasColumn($column)) {
                    $table->removeColumn($column);
                }
            }
            $table->update();
        }

        $queue = $prefix . '_queue';
        if ($this->hasTable($queue)) {
            $table = $this->table($queue);
            if ($table->hasColumn('contact_user_id')) {
                $table->removeColumn('contact_user_id');
            }
            $table->update();
        }
    }
}
