<?php

use yii\db\Migration;

class m260702_120000_add_cdek_fields_to_shop_order extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%shop_order}}', 'delivery_cost', $this->decimal(10, 2)->null()->after('total_price'));
        $this->addColumn('{{%shop_order}}', 'cdek_tariff_code', $this->integer()->null()->after('delivery_method_code'));
        $this->addColumn('{{%shop_order}}', 'pvz_code', $this->string(32)->null()->after('destination_address'));
        $this->addColumn('{{%shop_order}}', 'delivery_period_min', $this->smallInteger()->null()->after('pvz_code'));
        $this->addColumn('{{%shop_order}}', 'delivery_period_max', $this->smallInteger()->null()->after('delivery_period_min'));
        $this->addColumn('{{%shop_order}}', 'cdek_order_uuid', $this->string(36)->null()->after('payment_url'));
        $this->addColumn('{{%shop_order}}', 'cdek_track_number', $this->string(32)->null()->after('cdek_order_uuid'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%shop_order}}', 'cdek_track_number');
        $this->dropColumn('{{%shop_order}}', 'cdek_order_uuid');
        $this->dropColumn('{{%shop_order}}', 'delivery_period_max');
        $this->dropColumn('{{%shop_order}}', 'delivery_period_min');
        $this->dropColumn('{{%shop_order}}', 'pvz_code');
        $this->dropColumn('{{%shop_order}}', 'cdek_tariff_code');
        $this->dropColumn('{{%shop_order}}', 'delivery_cost');
    }
}
