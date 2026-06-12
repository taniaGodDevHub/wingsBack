<?php

use yii\db\Migration;

class m260612_160000_create_home_gender_block extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%home_gender_block}}', [
            'id' => $this->primaryKey(),
            'gender_code' => $this->string(16)->notNull(),
            'image_url' => $this->string(512)->notNull()->defaultValue(''),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-home_gender_block-code', '{{%home_gender_block}}', 'gender_code', true);

        $now = time();
        $this->batchInsert('{{%home_gender_block}}', ['gender_code', 'image_url', 'updated_at'], [
            ['male', '', $now],
            ['female', '', $now],
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%home_gender_block}}');
    }
}
