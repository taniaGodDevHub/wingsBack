<?php

use yii\db\Migration;

class m260706_140000_add_order_code_and_blago_total extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%shop_order}}', 'code', $this->string(12)->null()->after('user_id'));
        $this->addColumn(
            '{{%shop_order}}',
            'blago_total',
            $this->decimal(12, 2)->notNull()->defaultValue(0)->after('total_price'),
        );
        $this->createIndex('idx-shop_order-code', '{{%shop_order}}', 'code', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-shop_order-code', '{{%shop_order}}');
        $this->dropColumn('{{%shop_order}}', 'blago_total');
        $this->dropColumn('{{%shop_order}}', 'code');
    }
}
