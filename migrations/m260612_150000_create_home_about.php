<?php

use yii\db\Migration;

class m260612_150000_create_home_about extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%home_about}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull()->defaultValue(''),
            'image_url' => $this->string(512)->notNull()->defaultValue(''),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->insert('{{%home_about}}', [
            'id' => 1,
            'title' => '',
            'image_url' => '',
            'updated_at' => time(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%home_about}}');
    }
}
