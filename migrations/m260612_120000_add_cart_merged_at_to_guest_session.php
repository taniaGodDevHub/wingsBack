<?php

use yii\db\Migration;

class m260612_120000_add_cart_merged_at_to_guest_session extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%guest_session}}', 'cart_merged_at', $this->integer()->null()->after('updated_at'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%guest_session}}', 'cart_merged_at');
    }
}
