<?php

use yii\db\Migration;

class m260708_100000_create_news_subscription_email_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%news_subscription_email}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(255)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-news_subscription_email-email', '{{%news_subscription_email}}', 'email', true);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%news_subscription_email}}');
    }
}
