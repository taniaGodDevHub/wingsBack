<?php

use yii\db\Migration;

class m260612_170000_create_home_bottom_banner extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%home_bottom_banner}}', [
            'id' => $this->primaryKey(),
            'image_url' => $this->string(512)->notNull()->defaultValue(''),
            'button_text' => $this->string(128)->notNull()->defaultValue('Перейти в каталог'),
            'button_url' => $this->string(512)->null(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->insert('{{%home_bottom_banner}}', [
            'id' => 1,
            'image_url' => '',
            'button_text' => 'Перейти в каталог',
            'button_url' => null,
            'updated_at' => time(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%home_bottom_banner}}');
    }
}
