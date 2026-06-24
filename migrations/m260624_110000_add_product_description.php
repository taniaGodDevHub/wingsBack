<?php

use yii\db\Migration;

class m260624_110000_add_product_description extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->getTableSchema('{{%product}}', true);
        if ($schema === null || isset($schema->columns['description'])) {
            return;
        }

        $this->addColumn('{{%product}}', 'description', $this->text()->null()->after('name'));
    }

    public function safeDown(): void
    {
        $schema = $this->db->getTableSchema('{{%product}}', true);
        if ($schema === null || !isset($schema->columns['description'])) {
            return;
        }

        $this->dropColumn('{{%product}}', 'description');
    }
}
