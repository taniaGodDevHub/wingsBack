<?php

use yii\db\Migration;

class m260606_100000_create_gender_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%gender}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(16)->notNull(),
            'name' => $this->string(100)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
        ]);
        $this->createIndex('idx-gender-code', '{{%gender}}', 'code', true);

        $this->batchInsert('{{%gender}}', ['code', 'name', 'sort_order', 'is_active'], [
            ['male', 'Мужской', 1, true],
            ['female', 'Женский', 2, true],
            ['unisex', 'Унисекс', 3, true],
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%gender}}');
    }
}
