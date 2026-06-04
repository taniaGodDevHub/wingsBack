<?php

use yii\db\Migration;

class m260603_120000_init_auth_and_shop extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string(255)->null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-user-username', '{{%user}}', 'username', true);
        $this->createIndex('idx-user-status', '{{%user}}', 'status');

        $this->createTable('{{%user_profile}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'phone_number' => $this->string(32)->null(),
            'email' => $this->string(255)->null(),
            'name' => $this->string(255)->null(),
        ]);
        $this->addForeignKey(
            'fk-user_profile-user_id',
            '{{%user_profile}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->createIndex('idx-user_profile-user_id', '{{%user_profile}}', 'user_id', true);
        $this->createIndex('idx-user_profile-phone', '{{%user_profile}}', 'phone_number', true);
        $this->createIndex('idx-user_profile-email', '{{%user_profile}}', 'email', true);

        $this->createTable('{{%auth_verification_challenge}}', [
            'id' => $this->primaryKey(),
            'record_id' => $this->char(36)->notNull(),
            'channel' => $this->string(16)->notNull(),
            'destination' => $this->string(255)->notNull(),
            'code_hash' => $this->string(255)->notNull(),
            'type' => $this->string(32)->notNull(),
            'expires_at' => $this->integer()->notNull(),
            'used_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-challenge-record_id', '{{%auth_verification_challenge}}', 'record_id', true);
        $this->createIndex('idx-challenge-destination', '{{%auth_verification_challenge}}', ['channel', 'destination']);

        $this->createTable('{{%refresh_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token_hash' => $this->string(64)->notNull(),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey(
            'fk-refresh_token-user_id',
            '{{%refresh_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->createIndex('idx-refresh_token-hash', '{{%refresh_token}}', 'token_hash', true);

        $this->createTable('{{%guest_session}}', [
            'session_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk-guest_session', '{{%guest_session}}', 'session_id');

        $this->createTable('{{%cart}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null(),
            'session_id' => $this->string(64)->null(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey(
            'fk-cart-user_id',
            '{{%cart}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->createIndex('idx-cart-user-active', '{{%cart}}', ['user_id', 'is_active']);
        $this->createIndex('idx-cart-session-active', '{{%cart}}', ['session_id', 'is_active']);

        $this->createTable('{{%cart_item}}', [
            'id' => $this->primaryKey(),
            'cart_id' => $this->integer()->notNull(),
            'product_id' => $this->string(64)->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
        ]);
        $this->addForeignKey(
            'fk-cart_item-cart_id',
            '{{%cart_item}}',
            'cart_id',
            '{{%cart}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->createIndex('idx-cart_item-cart-product', '{{%cart_item}}', ['cart_id', 'product_id'], true);

        $this->createTable('{{%favorite_item}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null(),
            'session_id' => $this->string(64)->null(),
            'product_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey(
            'fk-favorite_item-user_id',
            '{{%favorite_item}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->createIndex('idx-favorite-user-product', '{{%favorite_item}}', ['user_id', 'product_id'], true);
        $this->createIndex('idx-favorite-session-product', '{{%favorite_item}}', ['session_id', 'product_id'], true);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%favorite_item}}');
        $this->dropTable('{{%cart_item}}');
        $this->dropTable('{{%cart}}');
        $this->dropTable('{{%guest_session}}');
        $this->dropTable('{{%refresh_token}}');
        $this->dropTable('{{%auth_verification_challenge}}');
        $this->dropTable('{{%user_profile}}');
        $this->dropTable('{{%user}}');
    }
}
