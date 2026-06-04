<?php

use yii\db\Migration;

class m260605_100001_seed_orders_features extends Migration
{
    public function safeUp(): void
    {
        $this->update('{{%product}}', ['brand' => 'Wings', 'product_code' => 'SKU-101'], ['id' => 101]);
        $this->update('{{%product}}', ['brand' => 'Wings', 'product_code' => 'SKU-103'], ['id' => 103]);

        $this->insert('{{%catalog_feature}}', ['id' => 12, 'name_ru' => 'Материал']);
        $this->insert('{{%catalog_feature}}', ['id' => 13, 'name_ru' => 'Сезон']);
        $this->insert('{{%catalog_feature_value}}', ['id' => 1001, 'feature_id' => 12, 'name' => 'Хлопок']);
        $this->insert('{{%catalog_feature_value}}', ['id' => 1002, 'feature_id' => 12, 'name' => 'Лён']);
        $this->insert('{{%catalog_feature_value}}', ['id' => 2001, 'feature_id' => 13, 'name' => 'Лето']);
        $this->batchInsert('{{%product_feature_value}}', ['product_id', 'feature_value_id'], [
            [101, 1001],
            [103, 1001],
            [107, 1002],
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%product_feature_value}}');
        $this->delete('{{%catalog_feature_value}}');
        $this->delete('{{%catalog_feature}}');
    }
}
