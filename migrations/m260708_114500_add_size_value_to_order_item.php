<?php

use yii\db\Migration;

class m260708_114500_add_size_value_to_order_item extends Migration
{
    public function safeUp(): void
    {
        $table = '{{%order_item}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema === null || isset($schema->columns['size_value'])) {
            return;
        }

        $this->addColumn($table, 'size_value', $this->string(16)->null()->after('product_id'));
    }

    public function safeDown(): void
    {
        $table = '{{%order_item}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema === null || !isset($schema->columns['size_value'])) {
            return;
        }

        $this->dropColumn($table, 'size_value');
    }
}

