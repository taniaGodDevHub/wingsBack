<?php

use yii\db\Migration;

class m260706_100000_create_contact_info extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%contact_info}}', [
            'id' => $this->primaryKey(),
            'phone' => $this->string(32)->notNull()->defaultValue(''),
            'email' => $this->string(255)->notNull()->defaultValue(''),
            'telegram' => $this->string(255)->notNull()->defaultValue(''),
            'work_hours_from' => $this->string(5)->notNull()->defaultValue('10:00'),
            'work_hours_to' => $this->string(5)->notNull()->defaultValue('22:00'),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->insert('{{%contact_info}}', [
            'id' => 1,
            'phone' => '',
            'email' => '',
            'telegram' => '',
            'work_hours_from' => '10:00',
            'work_hours_to' => '22:00',
            'updated_at' => time(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%contact_info}}');
    }
}
