<?php

use yii\db\Migration;

class m260607_100000_replace_product_currency_with_blago extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%product}}',
            'blago',
            $this->decimal(12, 2)->notNull()->defaultValue(0)->after('old_price'),
        );
        $this->dropColumn('{{%product}}', 'currency');
    }

    public function safeDown(): void
    {
        $this->addColumn(
            '{{%product}}',
            'currency',
            $this->string(3)->notNull()->defaultValue('RUB')->after('old_price'),
        );
        $this->dropColumn('{{%product}}', 'blago');
    }
}
