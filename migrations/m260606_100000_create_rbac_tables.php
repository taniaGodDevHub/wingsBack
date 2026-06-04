<?php

use yii\db\Migration;

/**
 * RBAC-таблицы уже могут существовать (m140506_102106_rbac_init). Миграция только для чистой установки без yii rbac.
 */
class m260606_100000_create_rbac_tables extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->schema->getTableSchema('{{%auth_rule}}', true) !== null) {
            echo "RBAC tables already exist, skipping.\n";

            return;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%auth_rule}}', [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_rule', '{{%auth_rule}}', 'name');

        $this->createTable('{{%auth_item}}', [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_item', '{{%auth_item}}', 'name');
        $this->createIndex('idx-auth_item-type', '{{%auth_item}}', 'type');
        $this->addForeignKey(
            'fk-auth_item-rule_name',
            '{{%auth_item}}',
            'rule_name',
            '{{%auth_rule}}',
            'name',
            'SET NULL',
            'CASCADE',
        );

        $this->createTable('{{%auth_item_child}}', [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_item_child', '{{%auth_item_child}}', ['parent', 'child']);
        $this->addForeignKey('fk-auth_item_child-parent', '{{%auth_item_child}}', 'parent', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-child', '{{%auth_item_child}}', 'child', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');

        $this->createTable('{{%auth_assignment}}', [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_assignment', '{{%auth_assignment}}', ['item_name', 'user_id']);
        $this->createIndex('idx-auth_assignment-user_id', '{{%auth_assignment}}', 'user_id');
        $this->addForeignKey(
            'fk-auth_assignment-item_name',
            '{{%auth_assignment}}',
            'item_name',
            '{{%auth_item}}',
            'name',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        if ($this->db->schema->getTableSchema('{{%auth_assignment}}', true) === null) {
            return;
        }

        $this->dropTable('{{%auth_assignment}}');
        $this->dropTable('{{%auth_item_child}}');
        $this->dropTable('{{%auth_item}}');
        $this->dropTable('{{%auth_rule}}');
    }
}
