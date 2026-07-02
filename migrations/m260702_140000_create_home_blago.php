<?php

use yii\db\Migration;

class m260702_140000_create_home_blago extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%home_blago}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull()->defaultValue(''),
            'collection_start_at' => $this->integer()->notNull()->defaultValue(0),
            'collection_end_at' => $this->integer()->notNull()->defaultValue(0),
            'amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'image_url' => $this->string(512)->notNull()->defaultValue(''),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->insert('{{%home_blago}}', [
            'id' => 1,
            'title' => '',
            'collection_start_at' => 0,
            'collection_end_at' => 0,
            'amount' => 0,
            'image_url' => '',
            'updated_at' => time(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%home_blago}}');
    }
}
