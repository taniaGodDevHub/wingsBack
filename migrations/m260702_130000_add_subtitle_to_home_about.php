<?php

use yii\db\Migration;

class m260702_130000_add_subtitle_to_home_about extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%home_about}}', 'subtitle', $this->string(255)->null()->after('title'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%home_about}}', 'subtitle');
    }
}
