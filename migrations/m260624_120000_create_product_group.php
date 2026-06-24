<?php

use yii\db\Migration;

class m260624_120000_create_product_group extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->getTableSchema('{{%product_group}}', true);
        if ($schema === null) {
            $this->createTable('{{%product_group}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'slug' => $this->string(255)->notNull(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex('idx-product_group-slug', '{{%product_group}}', 'slug', true);
        }

        $productSchema = $this->db->getTableSchema('{{%product}}', true);
        if ($productSchema !== null && !isset($productSchema->columns['product_group_id'])) {
            $this->addColumn('{{%product}}', 'product_group_id', $this->integer()->null()->after('description'));
            $this->createIndex('idx-product-product_group', '{{%product}}', 'product_group_id');
            $this->addForeignKey(
                'fk-product-product_group',
                '{{%product}}',
                'product_group_id',
                '{{%product_group}}',
                'id',
                'SET NULL',
                'CASCADE',
            );
        }
    }

    public function safeDown(): void
    {
        $productSchema = $this->db->getTableSchema('{{%product}}', true);
        if ($productSchema !== null && isset($productSchema->columns['product_group_id'])) {
            $this->dropForeignKey('fk-product-product_group', '{{%product}}');
            $this->dropIndex('idx-product-product_group', '{{%product}}');
            $this->dropColumn('{{%product}}', 'product_group_id');
        }

        $schema = $this->db->getTableSchema('{{%product_group}}', true);
        if ($schema !== null) {
            $this->dropTable('{{%product_group}}');
        }
    }
}
