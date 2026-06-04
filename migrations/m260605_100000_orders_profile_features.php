<?php

use yii\db\Migration;

class m260605_100000_orders_profile_features extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user_profile}}', 'surname', $this->string(100)->null()->after('name'));
        $this->addColumn('{{%user_profile}}', 'gender', $this->string(16)->null()->after('surname'));
        $this->addColumn('{{%user_profile}}', 'birth_date', $this->date()->null()->after('gender'));
        $this->addColumn('{{%user_profile}}', 'phone_number_confirmed', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('{{%user_profile}}', 'email_confirmed', $this->boolean()->notNull()->defaultValue(false));

        $this->addColumn('{{%product}}', 'brand', $this->string(255)->null()->after('name'));
        $this->addColumn('{{%product}}', 'product_code', $this->string(64)->null()->after('brand'));

        $this->createTable('{{%catalog_feature}}', [
            'id' => $this->primaryKey(),
            'name_ru' => $this->string(255)->notNull(),
        ]);

        $this->createTable('{{%catalog_feature_value}}', [
            'id' => $this->primaryKey(),
            'feature_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
        ]);
        $this->addForeignKey(
            'fk-cfv-feature',
            '{{%catalog_feature_value}}',
            'feature_id',
            '{{%catalog_feature}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->createTable('{{%product_feature_value}}', [
            'product_id' => $this->integer()->notNull(),
            'feature_value_id' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk-product_feature_value', '{{%product_feature_value}}', ['product_id', 'feature_value_id']);
        $this->addForeignKey('fk-pfv-product', '{{%product_feature_value}}', 'product_id', '{{%product}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-pfv-value', '{{%product_feature_value}}', 'feature_value_id', '{{%catalog_feature_value}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%user_address}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'city_id' => $this->integer()->null(),
            'city_fias_id' => $this->string(64)->null(),
            'fias_id' => $this->string(64)->null(),
            'kladr_id' => $this->string(32)->null(),
            'city_name' => $this->string(255)->null(),
            'region' => $this->string(255)->null(),
            'postal_code' => $this->string(16)->null(),
            'latitude' => $this->string(32)->null(),
            'longitude' => $this->string(32)->null(),
            'full_address' => $this->string(512)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey('fk-user_address-user', '{{%user_address}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx-user_address-user', '{{%user_address}}', 'user_id');

        $this->createTable('{{%shop_order}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('draft'),
            'payment_status' => $this->string(32)->notNull()->defaultValue('pending'),
            'expires_at' => $this->integer()->null(),
            'total_price' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'delivery_provider' => $this->string(32)->null(),
            'delivery_method_code' => $this->string(64)->null(),
            'delivery_method_id' => $this->integer()->null(),
            'city_fias_id' => $this->string(64)->null(),
            'destination_id' => $this->string(64)->null(),
            'destination_address' => $this->text()->null(),
            'delivery_address' => $this->text()->null(),
            'payment_method' => $this->string(32)->null(),
            'payment_url' => $this->string(512)->null(),
            'comment' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'completed_at' => $this->integer()->null(),
        ]);
        $this->addForeignKey('fk-shop_order-user', '{{%shop_order}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx-shop_order-user-status', '{{%shop_order}}', ['user_id', 'status']);

        $this->createTable('{{%order_item}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
            'unit_price' => $this->decimal(12, 2)->notNull(),
            'total_price' => $this->decimal(12, 2)->notNull(),
            'delivery_label' => $this->string(255)->null(),
        ]);
        $this->addForeignKey('fk-order_item-order', '{{%order_item}}', 'order_id', '{{%shop_order}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx-order_item-order', '{{%order_item}}', 'order_id');

        $this->createTable('{{%order_tracking}}', [
            'order_id' => $this->integer()->notNull(),
            'provider' => $this->string(32)->notNull()->defaultValue('cdek'),
            'track_number' => $this->string(64)->null(),
            'current_status' => $this->string(32)->null(),
            'description' => $this->string(255)->null(),
            'current_city' => $this->string(128)->null(),
            'updated_at' => $this->integer()->null(),
            'expected_delivery' => $this->string(32)->null(),
        ]);
        $this->addPrimaryKey('pk-order_tracking', '{{%order_tracking}}', 'order_id');
        $this->addForeignKey('fk-order_tracking-order', '{{%order_tracking}}', 'order_id', '{{%shop_order}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%order_tracking}}');
        $this->dropTable('{{%order_item}}');
        $this->dropTable('{{%shop_order}}');
        $this->dropTable('{{%user_address}}');
        $this->dropTable('{{%product_feature_value}}');
        $this->dropTable('{{%catalog_feature_value}}');
        $this->dropTable('{{%catalog_feature}}');
        $this->dropColumn('{{%product}}', 'product_code');
        $this->dropColumn('{{%product}}', 'brand');
        $this->dropColumn('{{%user_profile}}', 'email_confirmed');
        $this->dropColumn('{{%user_profile}}', 'phone_number_confirmed');
        $this->dropColumn('{{%user_profile}}', 'birth_date');
        $this->dropColumn('{{%user_profile}}', 'gender');
        $this->dropColumn('{{%user_profile}}', 'surname');
    }
}
