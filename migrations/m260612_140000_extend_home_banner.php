<?php

use yii\db\Migration;

class m260612_140000_extend_home_banner extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%home_banner}}', 'title', $this->string(255)->null()->after('image_url'));
        $this->addColumn('{{%home_banner}}', 'text', $this->text()->null()->after('title'));
        $this->addColumn('{{%home_banner}}', 'button_text', $this->string(128)->notNull()->defaultValue('Перейти в каталог')->after('text'));
        $this->addColumn('{{%home_banner}}', 'button_url', $this->string(512)->null()->after('button_text'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%home_banner}}', 'button_url');
        $this->dropColumn('{{%home_banner}}', 'button_text');
        $this->dropColumn('{{%home_banner}}', 'text');
        $this->dropColumn('{{%home_banner}}', 'title');
    }
}
