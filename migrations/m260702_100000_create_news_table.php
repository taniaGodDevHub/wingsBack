<?php

use yii\db\Migration;

class m260702_100000_create_news_table extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->getTableSchema('{{%news}}', true);
        if ($schema !== null) {
            return;
        }

        $this->createTable('{{%news}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'subtitle' => $this->string(255)->null(),
            'text' => $this->text()->null(),
            'image_url' => $this->string(512)->null(),
            'created_at' => $this->integer()->notNull(),
            'is_published' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->createIndex('idx-news-slug', '{{%news}}', 'slug', true);
        $this->createIndex('idx-news-is_published-created_at', '{{%news}}', ['is_published', 'created_at']);
    }

    public function safeDown(): void
    {
        $schema = $this->db->getTableSchema('{{%news}}', true);
        if ($schema === null) {
            return;
        }

        $this->dropTable('{{%news}}');
    }
}
