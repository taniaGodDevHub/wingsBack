<?php

use yii\db\Migration;

class m260706_180000_add_delivery_fields_to_user_address extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user_address}}', 'is_pvz', $this->boolean()->notNull()->defaultValue(false)->after('user_id'));
        $this->addColumn('{{%user_address}}', 'pvz_code', $this->string(32)->null()->after('is_pvz'));
        $this->createIndex('idx-user_address-user-pvz', '{{%user_address}}', ['user_id', 'is_pvz', 'pvz_code']);
        $this->createIndex('idx-user_address-user-fias', '{{%user_address}}', ['user_id', 'fias_id']);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-user_address-user-fias', '{{%user_address}}');
        $this->dropIndex('idx-user_address-user-pvz', '{{%user_address}}');
        $this->dropColumn('{{%user_address}}', 'pvz_code');
        $this->dropColumn('{{%user_address}}', 'is_pvz');
    }
}
