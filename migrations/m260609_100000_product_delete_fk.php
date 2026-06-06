<?php

use yii\db\Migration;

class m260609_100000_product_delete_fk extends Migration
{
    public function safeUp(): void
    {
        foreach (['order_item', 'cart_item', 'favorite_item'] as $table) {
            $this->dropProductForeignKeys($table);
        }
    }

    public function safeDown(): void
    {
        // FK to product intentionally not restored — order history keeps product_id as reference.
    }

    private function dropProductForeignKeys(string $table): void
    {
        $tableName = '{{%' . $table . '}}';
        $schema = $this->db->getTableSchema($tableName, true);
        if ($schema === null) {
            return;
        }

        foreach ($schema->foreignKeys as $fkName => $fk) {
            if (!is_array($fk) || $fk === []) {
                continue;
            }

            $refTable = preg_replace('/[{}%]/', '', (string) $fk[0]);
            if ($refTable !== 'product') {
                continue;
            }

            $this->dropForeignKey($fkName, $tableName);
        }
    }
}
