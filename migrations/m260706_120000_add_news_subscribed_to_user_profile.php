<?php

use yii\db\Migration;

class m260706_120000_add_news_subscribed_to_user_profile extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%user_profile}}',
            'news_subscribed',
            $this->boolean()->notNull()->defaultValue(false)->after('email_confirmed'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user_profile}}', 'news_subscribed');
    }
}
