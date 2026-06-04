<?php

use yii\db\Migration;

class m260603_130000_add_f_i_to_user_profile extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user_profile}}', 'f', $this->string(100)->null()->after('name'));
        $this->addColumn('{{%user_profile}}', 'i', $this->string(100)->null()->after('f'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user_profile}}', 'i');
        $this->dropColumn('{{%user_profile}}', 'f');
    }
}
