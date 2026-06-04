<?php

use yii\db\Migration;

class m260604_100000_create_catalog_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->null(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
        ]);
        $this->createIndex('idx-category-slug', '{{%category}}', 'slug', true);
        $this->createIndex('idx-category-parent', '{{%category}}', 'parent_id');
        $this->addForeignKey(
            'fk-category-parent',
            '{{%category}}',
            'parent_id',
            '{{%category}}',
            'id',
            'SET NULL',
            'CASCADE',
        );

        $this->createTable('{{%color}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'hex' => $this->string(7)->notNull(),
        ]);

        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'slug' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'price' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'old_price' => $this->decimal(12, 2)->null(),
            'currency' => $this->string(3)->notNull()->defaultValue('RUB'),
            'is_available' => $this->boolean()->notNull()->defaultValue(true),
            'is_bestseller' => $this->boolean()->notNull()->defaultValue(false),
            'is_featured_home' => $this->boolean()->notNull()->defaultValue(false),
            'featured_sort' => $this->integer()->notNull()->defaultValue(0),
            'bestseller_rank' => $this->integer()->notNull()->defaultValue(0),
            'gender' => $this->string(16)->null(),
            'search_text' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-product-slug', '{{%product}}', 'slug', true);
        $this->createIndex('idx-product-showcase', '{{%product}}', ['is_featured_home', 'featured_sort', 'is_bestseller']);

        $this->createTable('{{%product_image}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'image_url' => $this->string(512)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addForeignKey(
            'fk-product_image-product',
            '{{%product_image}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->createTable('{{%product_category}}', [
            'product_id' => $this->integer()->notNull(),
            'category_id' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk-product_category', '{{%product_category}}', ['product_id', 'category_id']);
        $this->addForeignKey('fk-pc-product', '{{%product_category}}', 'product_id', '{{%product}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-pc-category', '{{%product_category}}', 'category_id', '{{%category}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%product_color}}', [
            'product_id' => $this->integer()->notNull(),
            'color_id' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk-product_color', '{{%product_color}}', ['product_id', 'color_id']);
        $this->addForeignKey('fk-pcolor-product', '{{%product_color}}', 'product_id', '{{%product}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-pcolor-color', '{{%product_color}}', 'color_id', '{{%color}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%product_size}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'size_value' => $this->string(16)->notNull(),
        ]);
        $this->createIndex('idx-product_size-product', '{{%product_size}}', 'product_id');
        $this->addForeignKey('fk-psize-product', '{{%product_size}}', 'product_id', '{{%product}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%home_banner}}', [
            'id' => $this->primaryKey(),
            'image_url' => $this->string(512)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
        ]);

        $this->alterColumn('{{%cart_item}}', 'product_id', $this->integer()->notNull());
        $this->alterColumn('{{%favorite_item}}', 'product_id', $this->integer()->notNull());

        $this->addColumn('{{%guest_session}}', 'favorites_merged_at', $this->integer()->null());
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%guest_session}}', 'favorites_merged_at');
        $this->alterColumn('{{%favorite_item}}', 'product_id', $this->string(64)->notNull());
        $this->alterColumn('{{%cart_item}}', 'product_id', $this->string(64)->notNull());

        $this->dropTable('{{%home_banner}}');
        $this->dropTable('{{%product_size}}');
        $this->dropTable('{{%product_color}}');
        $this->dropTable('{{%product_category}}');
        $this->dropTable('{{%product_image}}');
        $this->dropTable('{{%product}}');
        $this->dropTable('{{%color}}');
        $this->dropTable('{{%category}}');
    }
}
