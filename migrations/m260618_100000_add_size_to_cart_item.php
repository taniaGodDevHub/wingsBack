<?php

use yii\db\Migration;

class m260618_100000_add_size_to_cart_item extends Migration
{
    public function safeUp(): void
    {
        $table = '{{%cart_item}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema !== null && !isset($schema->columns['size_value'])) {
            $this->addColumn($table, 'size_value', $this->string(16)->notNull()->defaultValue(''));
        }

        if (!$this->indexExists($table, 'idx-cart_item-cart-product-size')) {
            $this->createIndex(
                'idx-cart_item-cart-product-size',
                $table,
                ['cart_id', 'product_id', 'size_value'],
                true,
            );
        }

        if ($this->indexExists($table, 'idx-cart_item-cart-product')) {
            $this->dropIndex('idx-cart_item-cart-product', $table);
        }
    }

    public function safeDown(): void
    {
        $table = '{{%cart_item}}';

        if (!$this->indexExists($table, 'idx-cart_item-cart-product')) {
            $this->createIndex('idx-cart_item-cart-product', $table, ['cart_id', 'product_id'], true);
        }

        if ($this->indexExists($table, 'idx-cart_item-cart-product-size')) {
            $this->dropIndex('idx-cart_item-cart-product-size', $table);
        }

        $schema = $this->db->getTableSchema($table, true);
        if ($schema !== null && isset($schema->columns['size_value'])) {
            $this->dropColumn($table, 'size_value');
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $rawTable = $this->db->schema->getRawTableName($table);

        return $this->db->createCommand(
            'SHOW INDEX FROM `' . $rawTable . '` WHERE Key_name = :name',
            [':name' => $name],
        )->queryOne() !== false;
    }
}
